<?php
/**
 * Schema JSON-LD Generator
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

namespace MeowSEO\Modules\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_JsonLD class.
 */
class Schema_JsonLD {

	/**
	 * Current post object.
	 *
	 * @var \WP_Post|null
	 */
	private $post = null;

	/**
	 * Current post ID.
	 *
	 * @var int
	 */
	private $post_id = 0;

	/**
	 * Schema data array.
	 *
	 * @var array
	 */
	/**
	 * Options instance.
	 *
	 * @var \MeowSEO\Options
	 */
	private $options;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->options = new \MeowSEO\Options();
	}

	/**
	 * Setup hooks.
	 */
	public function setup(): void {
		add_action( 'wp_head', array( $this, 'output_json_ld' ), 90 );
		add_action( 'meowseo_schema_json_ld', array( $this, 'add_global_entities' ) );
	}

	/**
	 * Output JSON-LD to head.
	 */
	public function output_json_ld(): void {
		if ( is_admin() || is_feed() || is_robots() ) {
			return;
		}

		// Get current post.
		if ( is_singular() ) {
			global $post;
			$this->post    = $post;
			$this->post_id = $post->ID;
		}

		// Collect schema data.
		$data = $this->collect_schema_data();

		if ( empty( $data ) ) {
			return;
		}

		// Validate and clean data.
		$data = $this->validate_data( $data );

		if ( empty( $data ) ) {
			return;
		}

		// Output JSON-LD.
		$this->output( $data );
	}

	/**
	 * Collect all schema data.
	 *
	 * @return array Schema data array.
	 */
	private function collect_schema_data(): array {
		$data = array();

		/**
		 * Filter to collect schema data.
		 *
		 * @param array         $data   Schema data array.
		 * @param Schema_JsonLD $jsonld JsonLD instance.
		 */
		$data = apply_filters( 'meowseo_schema_json_ld', $data, $this );

		// Add post-specific schemas.
		if ( is_singular() && $this->post_id ) {
			$data = $this->add_post_schemas( $data );
		}

		return $data;
	}

	/**
	 * Add global entities (Website, Organization, Breadcrumbs, Person).
	 *
	 * @param array $data Schema data array.
	 * @return array Modified schema data.
	 */
	public function add_global_entities( array $data ): array {
		// Add Website.
		if ( $this->should_add_website() ) {
			$data['Website'] = $this->get_website_schema();
		}

		// Add Organization/Person.
		if ( $this->should_add_organization() ) {
			$data['Organization'] = $this->get_organization_schema();
		}

		// Add Author Person schema (for singular posts).
		if ( $this->should_add_author() ) {
			$data['Author'] = $this->get_author_schema();
		}

		// Add Breadcrumbs.
		if ( $this->should_add_breadcrumbs() ) {
			$data['BreadcrumbList'] = $this->get_breadcrumb_schema();
		}

		// Add WebPage.
		if ( $this->should_add_webpage() ) {
			$data['WebPage'] = $this->get_webpage_schema();
		}

		return $data;
	}

	/**
	 * Add post-specific schemas.
	 *
	 * @param array $data Schema data array.
	 * @return array Modified schema data.
	 */
	private function add_post_schemas( array $data ): array {
		$schemas = Schema_DB::get_schemas( $this->post_id );

		if ( empty( $schemas ) ) {
			return $data;
		}

		foreach ( $schemas as $schema_id => $schema ) {
			if ( empty( $schema['@type'] ) ) {
				continue;
			}

			$type = is_array( $schema['@type'] ) ? $schema['@type'][0] : $schema['@type'];

			// Get schema type instance.
			$schema_type = Schema_Registry::get( $type );

			if ( ! $schema_type ) {
				continue;
			}

			// Generate JSON-LD.
			$jsonld = $schema_type->generate( $schema, $this->post );

			// Add @id.
			if ( ! isset( $jsonld['@id'] ) ) {
				$jsonld['@id'] = $this->get_schema_id( $type );
			}

			// Add to data.
			$key          = $schema_id;
			$data[ $key ] = $jsonld;
		}

		return $data;
	}

	/**
	 * Get Website schema.
	 *
	 * @return array Website schema.
	 */
	private function get_website_schema(): array {
		$schema = array(
			'@type' => 'WebSite',
			'@id'   => home_url( '/#website' ),
			'url'   => home_url( '/' ),
			'name'  => get_bloginfo( 'name' ),
		);

		$description = get_bloginfo( 'description' );
		if ( $description ) {
			$schema['description'] = $description;
		}

		// Add search action.
		$search_url = home_url( '/?s={search_term_string}' );
		$schema['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $search_url,
			),
			'query-input' => 'required name=search_term_string',
		);

		// Add publisher reference.
		if ( $this->should_add_organization() ) {
			$schema['publisher'] = array(
				'@id' => home_url( '/#organization' ),
			);
		}

		return apply_filters( 'meowseo_schema_website', $schema );
	}

	/**
	 * Get Organization schema.
	 *
	 * @return array Organization schema.
	 */
	private function get_organization_schema(): array {
		$type = $this->options->get( 'schema_organization_type', 'Organization' );
		$name = $this->options->get( 'schema_business_name', get_bloginfo( 'name' ) );
		$logo = $this->options->get( 'schema_organization_logo', '' );

		// Use site name if organization name is empty.
		if ( empty( $name ) ) {
			$name = get_bloginfo( 'name' );
		}

		$schema = array(
			'@type' => $type,
			'@id'   => home_url( '/#organization' ),
			'name'  => $name,
			'url'   => home_url( '/' ),
		);

		// Add logo.
		if ( $logo ) {
			$logo_data = array(
				'@type' => 'ImageObject',
				'@id'   => home_url( '/#logo' ),
				'url'   => $logo,
			);

			// Add dimensions if available.
			$logo_width = $this->options->get( 'schema_organization_logo_width', '' );
			$logo_height = $this->options->get( 'schema_organization_logo_height', '' );
			if ( ! empty( $logo_width ) ) {
				$logo_data['width'] = absint( $logo_width );
			}
			if ( ! empty( $logo_height ) ) {
				$logo_data['height'] = absint( $logo_height );
			}

			$schema['logo'] = $logo_data;
		}

		// Add social profiles.
		$social_profiles = $this->get_social_profiles();
		if ( ! empty( $social_profiles ) ) {
			$schema['sameAs'] = $social_profiles;
		}

		return apply_filters( 'meowseo_schema_organization', $schema );
	}

	/**
	 * Get Breadcrumb schema.
	 *
	 * @return array Breadcrumb schema.
	 */
	private function get_breadcrumb_schema(): array {
		$items = $this->get_breadcrumb_items();

		if ( empty( $items ) ) {
			return array();
		}

		$schema = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $this->get_current_url() . '#breadcrumb',
			'itemListElement' => array(),
		);

		$position = 1;
		foreach ( $items as $item ) {
			$schema['itemListElement'][] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $item['name'],
				'item'     => $item['url'],
			);
		}

		return apply_filters( 'meowseo_schema_breadcrumb', $schema );
	}

	/**
	 * Get WebPage schema.
	 *
	 * @return array WebPage schema.
	 */
	private function get_webpage_schema(): array {
		$schema = array(
			'@type'            => 'WebPage',
			'@id'              => $this->get_current_url() . '#webpage',
			'url'              => $this->get_current_url(),
			'name'             => $this->get_page_title(),
			'isPartOf'         => array(
				'@id' => home_url( '/#website' ),
			),
			'datePublished'    => $this->get_date_published(),
			'dateModified'     => $this->get_date_modified(),
		);

		// Add description.
		$description = $this->get_page_description();
		if ( $description ) {
			$schema['description'] = $description;
		}

		// Add breadcrumb reference.
		if ( $this->should_add_breadcrumbs() ) {
			$schema['breadcrumb'] = array(
				'@id' => $this->get_current_url() . '#breadcrumb',
			);
		}

		// Add primary image.
		if ( is_singular() && has_post_thumbnail( $this->post_id ) ) {
			$schema['primaryImageOfPage'] = array(
				'@id' => $this->get_current_url() . '#primaryimage',
			);
		}

		return apply_filters( 'meowseo_schema_webpage', $schema );
	}

	/**
	 * Get breadcrumb items.
	 *
	 * @return array Breadcrumb items.
	 */
	private function get_breadcrumb_items(): array {
		$items = array();

		// Home.
		$items[] = array(
			'name' => get_bloginfo( 'name' ),
			'url'  => home_url( '/' ),
		);

		if ( is_singular() ) {
			// Post type archive.
			$post_type = get_post_type( $this->post_id );
			$post_type_obj = get_post_type_object( $post_type );

			if ( $post_type_obj && $post_type_obj->has_archive ) {
				$items[] = array(
					'name' => $post_type_obj->labels->name,
					'url'  => get_post_type_archive_link( $post_type ),
				);
			}

			// Categories (for posts).
			if ( 'post' === $post_type ) {
				$categories = get_the_category( $this->post_id );
				if ( ! empty( $categories ) ) {
					$category = $categories[0];
					$items[] = array(
						'name' => $category->name,
						'url'  => get_category_link( $category->term_id ),
					);
				}
			}

			// Current page.
			$items[] = array(
				'name' => get_the_title( $this->post_id ),
				'url'  => get_permalink( $this->post_id ),
			);
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			$items[] = array(
				'name' => $term->name,
				'url'  => get_term_link( $term ),
			);
		} elseif ( is_post_type_archive() ) {
			$post_type = get_query_var( 'post_type' );
			$post_type_obj = get_post_type_object( $post_type );
			$items[] = array(
				'name' => $post_type_obj->labels->name,
				'url'  => get_post_type_archive_link( $post_type ),
			);
		}

		return apply_filters( 'meowseo_breadcrumb_items', $items );
	}

	/**
	 * Get social profiles.
	 *
	 * @return array Social profile URLs.
	 */
	private function get_social_profiles(): array {
		$profiles = array();

		$social_options = array(
			'social_facebook_url',
			'social_twitter_url',
			'social_instagram_url',
			'social_linkedin_url',
			'social_youtube_url',
			'social_pinterest_url',
		);

		foreach ( $social_options as $option ) {
			$url = $this->options->get( $option, '' );
			if ( $url ) {
				$profiles[] = $url;
			}
		}

		return apply_filters( 'meowseo_social_profiles', $profiles );
	}

	/**
	 * Validate and clean schema data.
	 *
	 * @param array $data Schema data.
	 * @return array Cleaned data.
	 */
	private function validate_data( array $data ): array {
		foreach ( $data as $key => $schema ) {
			// Remove if only has @type.
			if ( isset( $schema['@type'] ) && 1 === count( $schema ) ) {
				unset( $data[ $key ] );
				continue;
			}

			// Recursive clean.
			$data[ $key ] = $this->remove_empty_values( $schema );

			// Remove if empty after cleaning.
			if ( empty( $data[ $key ] ) ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}

	/**
	 * Remove empty values from array.
	 *
	 * @param array $data Array to clean.
	 * @return array Cleaned array.
	 */
	private function remove_empty_values( array $data ): array {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->remove_empty_values( $value );
				
				if ( empty( $data[ $key ] ) ) {
					unset( $data[ $key ] );
				}
			} elseif ( '' === $value || null === $value ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}

	/**
	 * Output JSON-LD script tag.
	 *
	 * @param array $data Schema data.
	 */
	private function output( array $data ): void {
		$json = array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( $data ),
		);

		$options = defined( 'WP_DEBUG' ) && WP_DEBUG 
			? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

		$output = wp_json_encode( $json, $options );

		if ( false === $output ) {
			return;
		}

		echo '<script type="application/ld+json" class="meowseo-schema">' . $output . '</script>' . "\n";
	}

	/**
	 * Check if should add Website schema.
	 *
	 * @return bool
	 */
	private function should_add_website(): bool {
		$auto_website = $this->options->get( 'schema_auto_website', true );
		return apply_filters( 'meowseo_schema_add_website', $auto_website );
	}

	/**
	 * Check if should add Organization schema.
	 *
	 * @return bool
	 */
	private function should_add_organization(): bool {
		$auto_organization = $this->options->get( 'schema_auto_organization', true );
		return apply_filters( 'meowseo_schema_add_organization', $auto_organization );
	}

	/**
	 * Check if should add Breadcrumbs schema.
	 *
	 * @return bool
	 */
	private function should_add_breadcrumbs(): bool {
		if ( is_front_page() ) {
			return false;
		}

		$auto_breadcrumbs = $this->options->get( 'schema_auto_breadcrumbs', true );
		return apply_filters( 'meowseo_schema_add_breadcrumbs', $auto_breadcrumbs );
	}

	/**
	 * Check if should add WebPage schema.
	 *
	 * @return bool
	 */
	private function should_add_webpage(): bool {
		$auto_webpage = $this->options->get( 'schema_auto_webpage', true );
		return apply_filters( 'meowseo_schema_add_webpage', $auto_webpage );
	}

	/**
	 * Check if should add Author schema.
	 *
	 * @return bool
	 */
	private function should_add_author(): bool {
		// Only add author on singular posts.
		if ( ! is_singular() || ! $this->post ) {
			return false;
		}

		// Check if automatic author schema is enabled.
		$auto_author = $this->options->get( 'schema_auto_author', true );
		
		return apply_filters( 'meowseo_schema_add_author', $auto_author, $this->post );
	}

	/**
	 * Get Author Person schema.
	 *
	 * @return array Author schema.
	 */
	private function get_author_schema(): array {
		if ( ! $this->post ) {
			return array();
		}

		$author_id = $this->post->post_author;
		$author    = get_userdata( $author_id );

		if ( ! $author ) {
			return array();
		}

		$schema = array(
			'@type' => 'Person',
			'@id'   => get_author_posts_url( $author_id ) . '#author',
			'name'  => $author->display_name,
			'url'   => get_author_posts_url( $author_id ),
		);

		// Add description if available.
		$description = get_the_author_meta( 'description', $author_id );
		if ( $description ) {
			$schema['description'] = $description;
		}

		// Add image (avatar).
		$avatar_url = get_avatar_url( $author_id, array( 'size' => 96 ) );
		if ( $avatar_url ) {
			$schema['image'] = array(
				'@type' => 'ImageObject',
				'@id'   => get_author_posts_url( $author_id ) . '#avatar',
				'url'   => $avatar_url,
			);
		}

		// Add social profiles if available.
		$social_profiles = array();
		
		// Website.
		$website = get_the_author_meta( 'user_url', $author_id );
		if ( $website ) {
			$social_profiles[] = $website;
		}

		// Custom social fields (if they exist).
		$social_fields = array( 'twitter', 'facebook', 'linkedin', 'instagram', 'youtube' );
		foreach ( $social_fields as $field ) {
			$url = get_the_author_meta( $field, $author_id );
			if ( $url ) {
				$social_profiles[] = $url;
			}
		}

		if ( ! empty( $social_profiles ) ) {
			$schema['sameAs'] = $social_profiles;
		}

		return apply_filters( 'meowseo_schema_author', $schema, $author_id );
	}

	/**
	 * Get schema @id.
	 *
	 * @param string $type Schema type.
	 * @return string Schema @id.
	 */
	private function get_schema_id( string $type ): string {
		return $this->get_current_url() . '#' . strtolower( $type );
	}

	/**
	 * Get current URL.
	 *
	 * @return string Current URL.
	 */
	private function get_current_url(): string {
		global $wp;
		return home_url( add_query_arg( array(), $wp->request ) );
	}

	/**
	 * Get page title.
	 *
	 * @return string Page title.
	 */
	private function get_page_title(): string {
		if ( is_singular() ) {
			return get_the_title( $this->post_id );
		}

		return wp_get_document_title();
	}

	/**
	 * Get page description.
	 *
	 * @return string Page description.
	 */
	private function get_page_description(): string {
		if ( is_singular() && $this->post ) {
			return wp_trim_words( wp_strip_all_tags( $this->post->post_excerpt ?: $this->post->post_content ), 55 );
		}

		return get_bloginfo( 'description' );
	}

	/**
	 * Get date published.
	 *
	 * @return string ISO 8601 date.
	 */
	private function get_date_published(): string {
		if ( is_singular() && $this->post ) {
			return get_the_date( 'c', $this->post );
		}

		return current_time( 'c' );
	}

	/**
	 * Get date modified.
	 *
	 * @return string ISO 8601 date.
	 */
	private function get_date_modified(): string {
		if ( is_singular() && $this->post ) {
			return get_the_modified_date( 'c', $this->post );
		}

		return current_time( 'c' );
	}

	/**
	 * Get post object.
	 *
	 * @return \WP_Post|null
	 */
	public function get_post(): ?\WP_Post {
		return $this->post;
	}

	/**
	 * Get post ID.
	 *
	 * @return int
	 */
	public function get_post_id(): int {
		return $this->post_id;
	}
}
