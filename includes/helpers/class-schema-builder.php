<?php
/**
 * Schema Builder helper class.
 *
 * Core schema engine that assembles JSON-LD @graph arrays from individual node builders.
 * Uses node-based architecture where each schema type is a separate class extending Abstract_Schema_Node.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Helpers;

use MeowSEO\Options;
use WP_Post;
use MeowSEO\Helpers\Breadcrumbs;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema Builder class.
 *
 * Assembles complete @graph arrays by collecting nodes from individual node builders.
 * Implements Requirements 1.1, 1.2, 1.3.
 *
 * @since 1.0.0
 */
class Schema_Builder {

	/**
	 * Options instance.
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Post ID
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private int $post_id;

	/**
	 * Post object
	 *
	 * @since 1.0.0
	 * @var WP_Post|null
	 */
	private ?WP_Post $post = null;

	/**
	 * Context data
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $context = array();

	/**
	 * Breadcrumbs instance
	 *
	 * @since 1.0.0
	 * @var Breadcrumbs
	 */
	private Breadcrumbs $breadcrumbs;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->breadcrumbs = new Breadcrumbs( $options );
	}

	/**
	 * Build complete schema graph for a post.
	 *
	 * Returns a complete script tag with JSON-LD @graph array.
	 * Implements Requirement 1.1.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Schema graph array with @context and @graph.
	 */
	public function build( int $post_id ): array {
		$this->post_id = $post_id;
		$this->post    = get_post( $post_id );

		if ( ! $this->post ) {
			return array();
		}

		// Initialize context.
		$this->context = $this->get_context();

		// Collect nodes from builders.
		$nodes = $this->collect_nodes();

		// Assemble @graph array.
		$graph = $this->assemble_graph( $nodes );

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
	}

	/**
	 * Collect nodes from individual node builders.
	 *
	 * Gathers schema nodes from all node builder classes.
	 * Implements Requirement 1.2.
	 *
	 * @since 1.0.0
	 * @return array Array of schema node arrays.
	 */
	private function collect_nodes(): array {
		$nodes = array();

		// Note: Node builder classes will be implemented in subsequent tasks.
		// For now, we use the legacy methods to maintain backward compatibility.
		
		// Always include base nodes (Requirement 1.3).
		$nodes[] = $this->build_website();
		$nodes[] = $this->build_organization();
		$nodes[] = $this->build_webpage( $this->post );
		$nodes[] = $this->build_breadcrumb( $this->post );

		// Conditional nodes based on post type and schema type.
		$content_type = get_post_meta( $this->post_id, '_meowseo_content_type', true );
		if ( $this->should_include_article() ) {
			$article_type = 'Article';
			if ( 'news' === $content_type ) {
				$article_type = 'NewsArticle';
			} elseif ( 'journal' === $content_type ) {
				$article_type = 'ScholarlyArticle';
			} elseif ( 'education' === $content_type ) {
				$article_type = 'EducationalResource';
			}
			$nodes[] = $this->build_article( $this->post, $article_type );
		}

		if ( $this->should_include_product() ) {
			$nodes[] = $this->build_product( $this->post );
		}

		if ( 'review' === $content_type ) {
			$nodes[] = $this->build_manual_review_schema( $this->post );
		}

		if ( $this->should_include_faq() ) {
			$schema_type = get_post_meta( $this->post_id, 'meowseo_schema_type', true );
			if ( 'FAQPage' === $schema_type ) {
				$faq_items = get_post_meta( $this->post_id, 'meowseo_faq_items', true );
				if ( ! empty( $faq_items ) ) {
					$items = json_decode( $faq_items, true );
					if ( is_array( $items ) && ! empty( $items ) ) {
						$nodes[] = $this->build_faq( $items );
					}
				}
			}
		}

		// Add Recipe schema if configured (Requirement 1.1).
		if ( $this->should_include_recipe() ) {
			$recipe_node = $this->build_recipe_schema( $this->post_id );
			if ( ! empty( $recipe_node ) ) {
				$nodes[] = $recipe_node;
			}
		}

		// Add Event schema if configured (Requirement 1.2).
		if ( $this->should_include_event() ) {
			$event_node = $this->build_event_schema( $this->post_id );
			if ( ! empty( $event_node ) ) {
				$nodes[] = $event_node;
			}
		}

		// Add VideoObject schema if configured (Requirement 1.3).
		if ( $this->should_include_video() ) {
			$video_node = $this->build_video_schema( $this->post_id );
			if ( ! empty( $video_node ) ) {
				$nodes[] = $video_node;
			}
		}

		// Add Course schema if configured (Requirement 1.4).
		if ( $this->should_include_course() ) {
			$course_node = $this->build_course_schema( $this->post_id );
			if ( ! empty( $course_node ) ) {
				$nodes[] = $course_node;
			}
		}

		// Add JobPosting schema if configured (Requirement 1.5).
		if ( $this->should_include_job() ) {
			$job_node = $this->build_job_schema( $this->post_id );
			if ( ! empty( $job_node ) ) {
				$nodes[] = $job_node;
			}
		}

		// Add Book schema if configured (Requirement 1.6).
		if ( $this->should_include_book() ) {
			$book_node = $this->build_book_schema( $this->post_id );
			if ( ! empty( $book_node ) ) {
				$nodes[] = $book_node;
			}
		}

		// Add Person schema if configured (Requirement 1.7).
		if ( $this->should_include_person() ) {
			$person_node = $this->build_person_schema( $this->post_id );
			if ( ! empty( $person_node ) ) {
				$nodes[] = $person_node;
			}
		}

		// Auto-detect and add VideoObject schema for embedded videos (Requirement 2.4).
		if ( $this->is_auto_video_schema_enabled() ) {
			$video_detector = new \MeowSEO\Modules\Schema\Video_Detector();
			$videos = $video_detector->detect_videos( $this->post->post_content );

			if ( ! empty( $videos ) ) {
				foreach ( $videos as $video ) {
					$video_schema = $this->build_video_schema_from_detection( $video );
					if ( ! empty( $video_schema ) ) {
						$nodes[] = $video_schema;
					}
				}
			}
		}

		// Validate and filter nodes (Requirement 17.1, 17.2).
		$validated_nodes = array();
		foreach ( $nodes as $node ) {
			if ( $this->validate_node( $node ) ) {
				$validated_nodes[] = $node;
			}
		}

		return $validated_nodes;
	}

	/**
	 * Assemble @graph array from collected nodes.
	 *
	 * Takes an array of schema nodes and assembles them into a @graph array.
	 * Implements Requirement 1.3.
	 *
	 * @since 1.0.0
	 * @param array $nodes Array of schema node arrays.
	 * @return array Assembled @graph array.
	 */
	private function assemble_graph( array $nodes ): array {
		// Filter out empty nodes.
		$nodes = array_filter( $nodes, function( $node ) {
			return ! empty( $node ) && is_array( $node );
		});

		// Apply individual node filters for each node type.
		$filtered_nodes = array();
		foreach ( $nodes as $node ) {
			if ( isset( $node['@type'] ) ) {
				$node_type = strtolower( $node['@type'] );
				/**
				 * Filter individual schema node before adding to @graph
				 *
				 * Allows customization of specific node types.
				 *
				 * @since 1.0.0
				 * @param array $node    Schema node array.
				 * @param int   $post_id Post ID.
				 */
				$node = apply_filters( "meowseo_schema_node_{$node_type}", $node, $this->post_id );
			}
			$filtered_nodes[] = $node;
		}

		/**
		 * Filter complete @graph array before output
		 *
		 * Allows customization of the entire schema graph.
		 *
		 * @since 1.0.0
		 * @param array $nodes   Array of schema nodes.
		 * @param int   $post_id Post ID.
		 */
		$filtered_nodes = apply_filters( 'meowseo_schema_graph', $filtered_nodes, $this->post_id );

		return array_values( $filtered_nodes );
	}

	/**
	 * Get context data.
	 *
	 * Builds context data array for node builders.
	 *
	 * @since 1.0.0
	 * @return array Context data.
	 */
	private function get_context(): array {
		return array(
			'post_id'     => $this->post_id,
			'post_type'   => $this->post->post_type,
			'schema_type' => get_post_meta( $this->post_id, '_meowseo_schema_type', true ),
			'is_front_page' => is_front_page(),
			'is_archive'    => is_archive(),
			'is_search'     => is_search(),
			'breadcrumbs'   => $this->breadcrumbs,
		);
	}

	/**
	 * Check if Article node should be included.
	 *
	 * Implements conditional logic for Article node (Requirement 1.4).
	 *
	 * @since 1.0.0
	 * @return bool True if Article node should be included.
	 */
	private function should_include_article(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		/**
		 * Filter schema type detection
		 *
		 * Allows customization of schema type for a post.
		 *
		 * @since 1.0.0
		 * @param string $schema_type Current schema type.
		 * @param int    $post_id     Post ID.
		 */
		$schema_type = apply_filters( 'meowseo_schema_type', $schema_type, $this->post_id );
		
		// Include if post type is 'post' OR schema type is 'Article'.
		return 'post' === $this->post->post_type || 'Article' === $schema_type;
	}

	/**
	 * Check if Product node should be included.
	 *
	 * Implements conditional logic for Product node (Requirement 1.5).
	 *
	 * @since 1.0.0
	 * @return bool True if Product node should be included.
	 */
	private function should_include_product(): bool {
		// Include if post type is 'product' AND WooCommerce is active.
		return 'product' === $this->post->post_type && class_exists( 'WooCommerce' );
	}

	/**
	 * Check if FAQ node should be included.
	 *
	 * Implements conditional logic for FAQ node (Requirement 1.6).
	 *
	 * @since 1.0.0
	 * @return bool True if FAQ node should be included.
	 */
	private function should_include_faq(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		// Include if schema type is 'FAQPage'.
		return 'FAQPage' === $schema_type;
	}

	/**
	 * Check if Recipe node should be included.
	 *
	 * Implements conditional logic for Recipe node (Requirement 1.1).
	 *
	 * @since 1.0.0
	 * @return bool True if Recipe node should be included.
	 */
	private function should_include_recipe(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		// Include if schema type is 'Recipe'.
		return 'Recipe' === $schema_type;
	}

	/**
	 * Check if Event node should be included.
	 *
	 * Implements conditional logic for Event node (Requirement 1.2).
	 *
	 * @since 1.0.0
	 * @return bool True if Event node should be included.
	 */
	private function should_include_event(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		// Include if schema type is 'Event'.
		return 'Event' === $schema_type;
	}

	/**
	 * Check if VideoObject node should be included.
	 *
	 * Implements conditional logic for VideoObject node (Requirement 1.3).
	 *
	 * @since 1.0.0
	 * @return bool True if VideoObject node should be included.
	 */
	private function should_include_video(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		// Include if schema type is 'VideoObject'.
		return 'VideoObject' === $schema_type;
	}

	/**
	 * Check if Course node should be included.
	 *
	 * Implements conditional logic for Course node (Requirement 1.4).
	 *
	 * @since 1.0.0
	 * @return bool True if Course node should be included.
	 */
	private function should_include_course(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		// Include if schema type is 'Course'.
		return 'Course' === $schema_type;
	}

	/**
	 * Check if JobPosting node should be included.
	 *
	 * Implements conditional logic for JobPosting node (Requirement 1.5).
	 *
	 * @since 1.0.0
	 * @return bool True if JobPosting node should be included.
	 */
	private function should_include_job(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		// Include if schema type is 'JobPosting'.
		return 'JobPosting' === $schema_type;
	}

	/**
	 * Check if Book node should be included.
	 *
	 * Implements conditional logic for Book node (Requirement 1.6).
	 *
	 * @since 1.0.0
	 * @return bool True if Book node should be included.
	 */
	private function should_include_book(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		// Include if schema type is 'Book'.
		return 'Book' === $schema_type;
	}

	/**
	 * Check if Person node should be included.
	 *
	 * Implements conditional logic for Person node (Requirement 1.7).
	 *
	 * @since 1.0.0
	 * @return bool True if Person node should be included.
	 */
	private function should_include_person(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		// Include if schema type is 'Person'.
		return 'Person' === $schema_type;
	}

	/**
	 * Check if a specific node type should be included.
	 *
	 * Generic method for checking if a node type should be included.
	 * Implements Requirement 1.3.
	 *
	 * @since 1.0.0
	 * @param string $node_type Node type identifier.
	 * @return bool True if node should be included.
	 */
	private function should_include_node( string $node_type ): bool {
		switch ( $node_type ) {
			case 'article':
				return $this->should_include_article();
			case 'product':
				return $this->should_include_product();
			case 'faq':
				return $this->should_include_faq();
			default:
				return false;
		}
	}

	/**
	 * Build WebSite schema.
	 *
	 * @return array WebSite schema array.
	 */
	public function build_website(): array {
		$site_url = get_site_url();
		$site_name = get_bloginfo( 'name' );
		$site_description = get_bloginfo( 'description' );

		$schema = array(
			'@type'       => 'WebSite',
			'@id'         => $site_url . '/#website',
			'url'         => $site_url,
			'name'        => $site_name,
			'description' => $site_description,
			'publisher'   => array(
				'@id' => $site_url . '/#organization',
			),
		);

		// Add potential action for search.
		$schema['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $site_url . '/?s={search_term_string}',
			),
			'query-input' => 'required name=search_term_string',
		);

		return $schema;
	}

	/**
	 * Build WebPage schema.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array WebPage schema array.
	 */
	public function build_webpage( \WP_Post $post ): array {
		$permalink = get_permalink( $post );
		$title = get_the_title( $post );
		$site_url = get_site_url();

		$schema = array(
			'@type'            => 'WebPage',
			'@id'              => $permalink . '#webpage',
			'url'              => $permalink,
			'name'             => $title,
			'isPartOf'         => array(
				'@id' => $site_url . '/#website',
			),
			'datePublished'    => $this->format_date_safe( get_the_date( 'c', $post ) ),
			'dateModified'     => $this->format_date_safe( get_the_modified_date( 'c', $post ) ),
			'breadcrumb'       => array(
				'@id' => $permalink . '#breadcrumb',
			),
			'inLanguage'       => get_bloginfo( 'language' ),
		);

		// Add description if available.
		$description = get_post_meta( $post->ID, 'meowseo_description', true );
		if ( empty( $description ) && ! empty( $post->post_excerpt ) ) {
			$description = $post->post_excerpt;
		}
		if ( ! empty( $description ) ) {
			$schema['description'] = $description;
		}

		// Add featured image if available (with error handling).
		if ( has_post_thumbnail( $post ) ) {
			$image_id = get_post_thumbnail_id( $post );
			$image_url = $this->get_image_url_safe( $image_id );
			if ( $image_url ) {
				$schema['primaryImageOfPage'] = array(
					'@id' => $permalink . '#primaryimage',
				);
			}
		}

		return $schema;
	}

	/**
	 * Build Article schema.
	 *
	 * @param \WP_Post $post Post object.
	 * @param string   $type Article type (e.g. Article, NewsArticle, ScholarlyArticle).
	 * @return array Article schema array.
	 */
	public function build_article( \WP_Post $post, string $type = 'Article' ): array {
		$permalink = get_permalink( $post );
		$title = get_the_title( $post );
		$site_url = get_site_url();

		$schema = array(
			'@type'            => $type,
			'@id'              => $permalink . '#article',
			'isPartOf'         => array(
				'@id' => $permalink . '#webpage',
			),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $post->post_author ),
				'url'   => get_author_posts_url( $post->post_author ),
			),
			'headline'         => $title,
			'datePublished'    => $this->format_date_safe( get_the_date( 'c', $post ) ),
			'dateModified'     => $this->format_date_safe( get_the_modified_date( 'c', $post ) ),
			'mainEntityOfPage' => array(
				'@id' => $permalink . '#webpage',
			),
			'publisher'        => array(
				'@id' => $site_url . '/#organization',
			),
			'inLanguage'       => get_bloginfo( 'language' ),
		);

		// Add description if available.
		$description = get_post_meta( $post->ID, 'meowseo_description', true );
		if ( empty( $description ) && ! empty( $post->post_excerpt ) ) {
			$description = $post->post_excerpt;
		}
		if ( ! empty( $description ) ) {
			$schema['description'] = $description;
		}

		// Add featured image if available (with error handling).
		if ( has_post_thumbnail( $post ) ) {
			$image_id = get_post_thumbnail_id( $post );
			$image_url = $this->get_image_url_safe( $image_id );
			if ( $image_url ) {
				$schema['image'] = array(
					'@id' => $permalink . '#primaryimage',
				);
			}
		}

		return $schema;
	}

	/**
	 * Build BreadcrumbList schema.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array BreadcrumbList schema array.
	 */
	public function build_breadcrumb( \WP_Post $post ): array {
		$permalink = get_permalink( $post );
		$site_url = get_site_url();
		$site_name = get_bloginfo( 'name' );

		$items = array();

		// Home breadcrumb.
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => $site_name,
			'item'     => $site_url,
		);

		$position = 2;

		// Add post type archive if applicable.
		$post_type_object = get_post_type_object( $post->post_type );
		if ( $post_type_object && $post_type_object->has_archive ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $post_type_object->labels->name,
				'item'     => get_post_type_archive_link( $post->post_type ),
			);
		}

		// Add categories for posts.
		if ( 'post' === $post->post_type ) {
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) ) {
				$category = $categories[0];
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => $category->name,
					'item'     => get_category_link( $category->term_id ),
				);
			}
		}

		// Current page.
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => get_the_title( $post ),
			'item'     => $permalink,
		);

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $permalink . '#breadcrumb',
			'itemListElement' => $items,
		);
	}

	/**
	 * Build Organization schema.
	 *
	 * @return array Organization schema array.
	 */
	public function build_organization(): array {
		$site_url = get_site_url();
		$site_name = get_bloginfo( 'name' );

		$schema = array(
			'@type' => 'Organization',
			'@id'   => $site_url . '/#organization',
			'name'  => $site_name,
			'url'   => $site_url,
		);

		// Add logo if available (with error handling).
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$logo_url = $this->get_image_url_safe( $custom_logo_id );
			if ( $logo_url ) {
				$schema['logo'] = array(
					'@type' => 'ImageObject',
					'url'   => $logo_url,
				);
			}
		}

		// Add social profiles with filter hook.
		$social_profiles = $this->options->get( 'meowseo_schema_social_profiles', array() );
		
		/**
		 * Filter social profiles for Organization schema
		 *
		 * Allows customization of social media profiles.
		 *
		 * @since 1.0.0
		 * @param array $profiles Array of social profile URLs.
		 */
		$social_profiles = apply_filters( 'meowseo_schema_social_profiles', $social_profiles );
		
		if ( ! empty( $social_profiles ) && is_array( $social_profiles ) ) {
			$schema['sameAs'] = array_values( $social_profiles );
		}

		return $schema;
	}

	/**
	 * Build Product schema (WooCommerce).
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Product schema array.
	 */
	public function build_product( \WP_Post $post ): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return array();
		}

		$permalink = get_permalink( $post );
		$site_url = get_site_url();

		$schema = array(
			'@type'       => 'Product',
			'@id'         => $permalink . '#product',
			'name'        => $product->get_name(),
			'url'         => $permalink,
			'description' => wp_strip_all_tags( $product->get_short_description() ),
		);

		// Add SKU if available.
		if ( $product->get_sku() ) {
			$schema['sku'] = $product->get_sku();
		}

		// Add image if available (with error handling).
		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$image_url = $this->get_image_url_safe( $image_id );
			if ( $image_url ) {
				$schema['image'] = $image_url;
			}
		}

		// Add offers.
		$schema['offers'] = array(
			'@type'         => 'Offer',
			'url'           => $permalink,
			'priceCurrency' => get_woocommerce_currency(),
			'price'         => $product->get_price(),
			'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
		);

		// Add aggregate rating if reviews exist.
		$rating_count = $product->get_rating_count();
		if ( $rating_count > 0 ) {
			$schema['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'reviewCount' => $product->get_review_count(),
			);
		}

		return $schema;
	}

	/**
	 * Build manual Review schema for non-WooCommerce posts.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Review/Product schema array.
	 */
	public function build_manual_review_schema( \WP_Post $post ): array {
		$rating = get_post_meta( $post->ID, '_meowseo_review_rating', true );
		$price  = get_post_meta( $post->ID, '_meowseo_product_price', true );

		if ( ! $rating && ! $price ) {
			return array();
		}

		$permalink = get_permalink( $post );

		$schema = array(
			'@type'       => 'Product',
			'@id'         => $permalink . '#product',
			'name'        => get_the_title( $post ),
			'url'         => $permalink,
			'description' => wp_strip_all_tags( get_the_excerpt( $post ) ),
		);

		if ( $rating ) {
			$schema['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $rating,
				'bestRating'  => '5',
				'worstRating' => '1',
				'ratingCount' => '1', // Default to 1 for manual reviews
			);
		}

		if ( $price ) {
			$schema['offers'] = array(
				'@type'         => 'Offer',
				'url'           => $permalink,
				'price'         => $price,
				'priceCurrency' => $this->options->get( 'currency', 'USD' ),
				'availability'  => 'https://schema.org/InStock',
			);
		}

		return $schema;
	}

	/**
	 * Build FAQPage schema.
	 *
	 * @param array $items Array of FAQ items with 'question' and 'answer' keys.
	 * @return array FAQPage schema array.
	 */
	public function build_faq( array $items ): array {
		if ( empty( $items ) ) {
			return array();
		}

		$main_entity = array();

		foreach ( $items as $item ) {
			if ( empty( $item['question'] ) || empty( $item['answer'] ) ) {
				continue;
			}

			$main_entity[] = array(
				'@type'          => 'Question',
				'name'           => $item['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $item['answer'],
				),
			);
		}

		if ( empty( $main_entity ) ) {
			return array();
		}

		return array(
			'@type'      => 'FAQPage',
			'mainEntity' => $main_entity,
		);
	}

	/**
	 * Build Recipe schema.
	 *
	 * Generates Recipe schema using Recipe_Schema_Generator.
	 * Implements Requirement 1.1, 1.9.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Recipe schema array.
	 */
	public function build_recipe_schema( int $post_id ): array {
		// Get schema configuration from postmeta.
		$config_json = get_post_meta( $post_id, '_meowseo_schema_config', true );
		
		if ( empty( $config_json ) ) {
			return array();
		}

		$config = json_decode( $config_json, true );
		if ( ! is_array( $config ) ) {
			return array();
		}

		// Use Recipe_Schema_Generator to generate schema.
		$generator = new \MeowSEO\Modules\Schema\Generators\Recipe_Schema_Generator();
		$schema = $generator->generate( $post_id, $config );

		return $schema;
	}

	/**
	 * Build Event schema.
	 *
	 * Generates Event schema using Event_Schema_Generator.
	 * Implements Requirement 1.2, 1.9.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Event schema array.
	 */
	public function build_event_schema( int $post_id ): array {
		// Get schema configuration from postmeta.
		$config_json = get_post_meta( $post_id, '_meowseo_schema_config', true );
		
		if ( empty( $config_json ) ) {
			return array();
		}

		$config = json_decode( $config_json, true );
		if ( ! is_array( $config ) ) {
			return array();
		}

		// Use Event_Schema_Generator to generate schema.
		$generator = new \MeowSEO\Modules\Schema\Generators\Event_Schema_Generator();
		$schema = $generator->generate( $post_id, $config );

		return $schema;
	}

	/**
	 * Build VideoObject schema.
	 *
	 * Generates VideoObject schema using Video_Schema_Generator.
	 * Implements Requirement 1.3, 1.9.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array VideoObject schema array.
	 */
	public function build_video_schema( int $post_id ): array {
		// Get schema configuration from postmeta.
		$config_json = get_post_meta( $post_id, '_meowseo_schema_config', true );
		
		if ( empty( $config_json ) ) {
			return array();
		}

		$config = json_decode( $config_json, true );
		if ( ! is_array( $config ) ) {
			return array();
		}

		// Use Video_Schema_Generator to generate schema.
		$generator = new \MeowSEO\Modules\Schema\Generators\Video_Schema_Generator();
		$schema = $generator->generate( $post_id, $config );

		return $schema;
	}

	/**
	 * Build Course schema.
	 *
	 * Generates Course schema using Course_Schema_Generator.
	 * Implements Requirement 1.4, 1.9.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Course schema array.
	 */
	public function build_course_schema( int $post_id ): array {
		// Get schema configuration from postmeta.
		$config_json = get_post_meta( $post_id, '_meowseo_schema_config', true );
		
		if ( empty( $config_json ) ) {
			return array();
		}

		$config = json_decode( $config_json, true );
		if ( ! is_array( $config ) ) {
			return array();
		}

		// Use Course_Schema_Generator to generate schema.
		$generator = new \MeowSEO\Modules\Schema\Generators\Course_Schema_Generator();
		$schema = $generator->generate( $post_id, $config );

		return $schema;
	}

	/**
	 * Build JobPosting schema.
	 *
	 * Generates JobPosting schema using Job_Schema_Generator.
	 * Implements Requirement 1.5, 1.9.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array JobPosting schema array.
	 */
	public function build_job_schema( int $post_id ): array {
		// Get schema configuration from postmeta.
		$config_json = get_post_meta( $post_id, '_meowseo_schema_config', true );
		
		if ( empty( $config_json ) ) {
			return array();
		}

		$config = json_decode( $config_json, true );
		if ( ! is_array( $config ) ) {
			return array();
		}

		// Use Job_Schema_Generator to generate schema.
		$generator = new \MeowSEO\Modules\Schema\Generators\Job_Schema_Generator();
		$schema = $generator->generate( $post_id, $config );

		return $schema;
	}

	/**
	 * Build Book schema.
	 *
	 * Generates Book schema using Book_Schema_Generator.
	 * Implements Requirement 1.6, 1.9.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Book schema array.
	 */
	public function build_book_schema( int $post_id ): array {
		// Get schema configuration from postmeta.
		$config_json = get_post_meta( $post_id, '_meowseo_schema_config', true );
		
		if ( empty( $config_json ) ) {
			return array();
		}

		$config = json_decode( $config_json, true );
		if ( ! is_array( $config ) ) {
			return array();
		}

		// Use Book_Schema_Generator to generate schema.
		$generator = new \MeowSEO\Modules\Schema\Generators\Book_Schema_Generator();
		$schema = $generator->generate( $post_id, $config );

		return $schema;
	}

	/**
	 * Build Person schema.
	 *
	 * Generates Person schema using Person_Schema_Generator.
	 * Implements Requirement 1.7, 1.9.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Person schema array.
	 */
	public function build_person_schema( int $post_id ): array {
		// Get schema configuration from postmeta.
		$config_json = get_post_meta( $post_id, '_meowseo_schema_config', true );
		
		if ( empty( $config_json ) ) {
			return array();
		}

		$config = json_decode( $config_json, true );
		if ( ! is_array( $config ) ) {
			return array();
		}

		// Use Person_Schema_Generator to generate schema.
		$generator = new \MeowSEO\Modules\Schema\Generators\Person_Schema_Generator();
		$schema = $generator->generate( $post_id, $config );

		return $schema;
	}

	/**
	 * Validate schema node
	 *
	 * Checks that required properties (@type and @id) are present and valid.
	 * Logs warnings for missing properties and skips invalid nodes.
	 * Implements Requirements 17.1, 17.2, 17.3.
	 *
	 * @since 1.0.0
	 * @param array $node Schema node array.
	 * @return bool True if node is valid, false otherwise.
	 */
	private function validate_node( array $node ): bool {
		// Empty nodes are invalid.
		if ( empty( $node ) ) {
			return false;
		}

		// Check for required @type property (Requirement 17.1).
		if ( ! isset( $node['@type'] ) || empty( $node['@type'] ) ) {
			Logger::warning(
				'Schema node missing required @type property',
				array(
					'post_id' => $this->post_id,
					'node'    => wp_json_encode( $node ),
				)
			);
			return false;
		}

		// Check for required @id property (Requirement 17.1).
		if ( ! isset( $node['@id'] ) || empty( $node['@id'] ) ) {
			Logger::warning(
				'Schema node missing required @id property',
				array(
					'post_id' => $this->post_id,
					'type'    => $node['@type'],
				)
			);
			return false;
		}

		// Validate @id is a valid URL (Requirement 17.3).
		if ( ! filter_var( $node['@id'], FILTER_VALIDATE_URL ) ) {
			Logger::warning(
				'Schema node @id is not a valid URL',
				array(
					'post_id' => $this->post_id,
					'type'    => $node['@type'],
					'id'      => $node['@id'],
				)
			);
			return false;
		}

		// Validate schema type-specific requirements (Requirement 1.8).
		$type_validation = $this->validate_schema_type( $node );
		if ( ! $type_validation ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate schema type-specific requirements
	 *
	 * Validates required properties for each schema type.
	 * Implements Requirement 1.8.
	 *
	 * @since 1.0.0
	 * @param array $node Schema node.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_schema_type( array $node ): bool {
		$type = $node['@type'] ?? '';

		switch ( $type ) {
			case 'Recipe':
				return $this->validate_recipe_schema( $node );
			case 'Event':
				return $this->validate_event_schema( $node );
			case 'VideoObject':
				return $this->validate_video_schema( $node );
			case 'Course':
				return $this->validate_course_schema( $node );
			case 'JobPosting':
				return $this->validate_job_schema( $node );
			case 'Book':
				return $this->validate_book_schema( $node );
			case 'Person':
				return $this->validate_person_schema( $node );
			default:
				// No specific validation for other types.
				return true;
		}
	}

	/**
	 * Validate Recipe schema
	 *
	 * Checks required properties: name, description, recipeIngredient, recipeInstructions.
	 * Implements Requirement 1.8.
	 *
	 * @since 1.0.0
	 * @param array $node Recipe schema node.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_recipe_schema( array $node ): bool {
		$required_fields = array( 'name', 'description', 'recipeIngredient', 'recipeInstructions' );

		foreach ( $required_fields as $field ) {
			if ( empty( $node[ $field ] ) ) {
				Logger::warning(
					'Recipe schema missing required property',
					array(
						'post_id' => $this->post_id,
						'field'   => $field,
					)
				);
				return false;
			}
		}

		// Validate recipeIngredient is an array.
		if ( ! is_array( $node['recipeIngredient'] ) ) {
			Logger::warning(
				'Recipe schema recipeIngredient must be an array',
				array(
					'post_id' => $this->post_id,
					'type'    => gettype( $node['recipeIngredient'] ),
				)
			);
			return false;
		}

		// Validate recipeInstructions is an array.
		if ( ! is_array( $node['recipeInstructions'] ) ) {
			Logger::warning(
				'Recipe schema recipeInstructions must be an array',
				array(
					'post_id' => $this->post_id,
					'type'    => gettype( $node['recipeInstructions'] ),
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Validate Event schema
	 *
	 * Checks required properties: name, startDate, location.
	 * Implements Requirement 1.8.
	 *
	 * @since 1.0.0
	 * @param array $node Event schema node.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_event_schema( array $node ): bool {
		$required_fields = array( 'name', 'startDate', 'location' );

		foreach ( $required_fields as $field ) {
			if ( empty( $node[ $field ] ) ) {
				Logger::warning(
					'Event schema missing required property',
					array(
						'post_id' => $this->post_id,
						'field'   => $field,
					)
				);
				return false;
			}
		}

		// Validate location is an object with @type Place.
		if ( ! is_array( $node['location'] ) || empty( $node['location']['@type'] ) || 'Place' !== $node['location']['@type'] ) {
			Logger::warning(
				'Event schema location must be a Place object',
				array(
					'post_id' => $this->post_id,
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Validate VideoObject schema
	 *
	 * Checks required properties: name, description, thumbnailUrl, uploadDate.
	 * Implements Requirement 1.8.
	 *
	 * @since 1.0.0
	 * @param array $node VideoObject schema node.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_video_schema( array $node ): bool {
		$required_fields = array( 'name', 'description', 'thumbnailUrl', 'uploadDate' );

		foreach ( $required_fields as $field ) {
			if ( empty( $node[ $field ] ) ) {
				Logger::warning(
					'VideoObject schema missing required property',
					array(
						'post_id' => $this->post_id,
						'field'   => $field,
					)
				);
				return false;
			}
		}

		// Validate thumbnailUrl is a valid URL.
		if ( ! filter_var( $node['thumbnailUrl'], FILTER_VALIDATE_URL ) ) {
			Logger::warning(
				'VideoObject schema thumbnailUrl is not a valid URL',
				array(
					'post_id'      => $this->post_id,
					'thumbnailUrl' => $node['thumbnailUrl'],
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Validate Course schema
	 *
	 * Checks required properties: name, description, provider.
	 * Implements Requirement 1.8.
	 *
	 * @since 1.0.0
	 * @param array $node Course schema node.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_course_schema( array $node ): bool {
		$required_fields = array( 'name', 'description', 'provider' );

		foreach ( $required_fields as $field ) {
			if ( empty( $node[ $field ] ) ) {
				Logger::warning(
					'Course schema missing required property',
					array(
						'post_id' => $this->post_id,
						'field'   => $field,
					)
				);
				return false;
			}
		}

		// Validate provider is an object with @type Organization.
		if ( ! is_array( $node['provider'] ) || empty( $node['provider']['@type'] ) || 'Organization' !== $node['provider']['@type'] ) {
			Logger::warning(
				'Course schema provider must be an Organization object',
				array(
					'post_id' => $this->post_id,
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Validate JobPosting schema
	 *
	 * Checks required properties: title, description, datePosted, hiringOrganization.
	 * Implements Requirement 1.8.
	 *
	 * @since 1.0.0
	 * @param array $node JobPosting schema node.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_job_schema( array $node ): bool {
		$required_fields = array( 'title', 'description', 'datePosted', 'hiringOrganization' );

		foreach ( $required_fields as $field ) {
			if ( empty( $node[ $field ] ) ) {
				Logger::warning(
					'JobPosting schema missing required property',
					array(
						'post_id' => $this->post_id,
						'field'   => $field,
					)
				);
				return false;
			}
		}

		// Validate hiringOrganization is an object with @type Organization.
		if ( ! is_array( $node['hiringOrganization'] ) || empty( $node['hiringOrganization']['@type'] ) || 'Organization' !== $node['hiringOrganization']['@type'] ) {
			Logger::warning(
				'JobPosting schema hiringOrganization must be an Organization object',
				array(
					'post_id' => $this->post_id,
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Validate Book schema
	 *
	 * Checks required properties: name, author.
	 * Implements Requirement 1.8.
	 *
	 * @since 1.0.0
	 * @param array $node Book schema node.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_book_schema( array $node ): bool {
		$required_fields = array( 'name', 'author' );

		foreach ( $required_fields as $field ) {
			if ( empty( $node[ $field ] ) ) {
				Logger::warning(
					'Book schema missing required property',
					array(
						'post_id' => $this->post_id,
						'field'   => $field,
					)
				);
				return false;
			}
		}

		// Validate author is an object with @type Person or Organization.
		if ( ! is_array( $node['author'] ) || empty( $node['author']['@type'] ) ) {
			Logger::warning(
				'Book schema author must be a Person or Organization object',
				array(
					'post_id' => $this->post_id,
				)
			);
			return false;
		}

		$valid_author_types = array( 'Person', 'Organization' );
		if ( ! in_array( $node['author']['@type'], $valid_author_types, true ) ) {
			Logger::warning(
				'Book schema author @type must be Person or Organization',
				array(
					'post_id' => $this->post_id,
					'type'    => $node['author']['@type'],
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Validate Person schema
	 *
	 * Checks required properties: name.
	 * Implements Requirement 1.8.
	 *
	 * @since 1.0.0
	 * @param array $node Person schema node.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_person_schema( array $node ): bool {
		if ( empty( $node['name'] ) ) {
			Logger::warning(
				'Person schema missing required property: name',
				array(
					'post_id' => $this->post_id,
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Format date with error handling
	 *
	 * Handles invalid date formats with fallback to current time.
	 * Implements Requirement 17.4.
	 *
	 * @since 1.0.0
	 * @param string $date Date string.
	 * @return string ISO 8601 formatted date.
	 */
	private function format_date_safe( string $date ): string {
		// Try to parse the date.
		$timestamp = strtotime( $date );

		// If parsing fails, use current time as fallback.
		if ( false === $timestamp ) {
			Logger::warning(
				'Invalid date format in schema generation, using current time as fallback',
				array(
					'post_id'      => $this->post_id,
					'invalid_date' => $date,
				)
			);
			$timestamp = time();
		}

		// Return ISO 8601 format.
		return gmdate( 'c', $timestamp );
	}

	/**
	 * Get image URL with error handling
	 *
	 * Handles missing images gracefully by returning null instead of failing.
	 *
	 * @since 1.0.0
	 * @param int $image_id Attachment ID.
	 * @return string|null Image URL or null if not found.
	 */
	private function get_image_url_safe( int $image_id ): ?string {
		if ( ! $image_id ) {
			return null;
		}

		$image_url = wp_get_attachment_image_url( $image_id, 'full' );

		if ( false === $image_url || empty( $image_url ) ) {
			Logger::warning(
				'Failed to retrieve image URL for schema',
				array(
					'post_id'  => $this->post_id,
					'image_id' => $image_id,
				)
			);
			return null;
		}

		return $image_url;
	}

	/**
	 * Convert schema graph to JSON string.
	 *
	 * Security: Escapes all output in schema JSON-LD (Requirement 19.2).
	 *
	 * @param array $graph Schema graph array.
	 * @return string JSON-encoded schema.
	 */
	public function to_json( array $graph ): string {
		// Security: Recursively escape all string values in the graph (Requirement 19.2).
		$escaped_graph = $this->escape_schema_values( $graph );
		
		return wp_json_encode( $escaped_graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Recursively escape all string values in schema data
	 *
	 * Security: Ensures all output is properly escaped (Requirement 19.2).
	 *
	 * @since 1.0.0
	 * @param mixed $data Data to escape.
	 * @return mixed Escaped data.
	 */
	private function escape_schema_values( $data ) {
		if ( is_array( $data ) ) {
			$escaped = array();
			foreach ( $data as $key => $value ) {
				$escaped[ $key ] = $this->escape_schema_values( $value );
			}
			return $escaped;
		}

		if ( is_string( $data ) ) {
			// Use esc_html for string values to prevent XSS.
			// JSON encoding will handle the rest.
			return esc_html( $data );
		}

		// Return other types as-is (numbers, booleans, null).
		return $data;
	}

	/**
	 * Get validation errors for debug mode
	 *
	 * Returns array of validation errors when WP_DEBUG is enabled.
	 * Implements Requirements 17.5, 17.6.
	 *
	 * @since 1.0.0
	 * @return array Array of validation error messages.
	 */
	public function get_validation_errors(): array {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return array();
		}

		$errors = array();

		if ( ! $this->post ) {
			return $errors;
		}

		// Collect nodes
		$nodes = $this->collect_nodes();

		// Validate each node
		foreach ( $nodes as $index => $node ) {
			if ( empty( $node ) ) {
				$errors[] = "Node at index {$index} is empty";
				continue;
			}

			if ( ! isset( $node['@type'] ) || empty( $node['@type'] ) ) {
				$errors[] = "Node at index {$index} missing required @type property";
			}

			if ( ! isset( $node['@id'] ) || empty( $node['@id'] ) ) {
				$errors[] = "Node at index {$index} ({$node['@type']}) missing required @id property";
			}

			if ( isset( $node['@id'] ) && ! filter_var( $node['@id'], FILTER_VALIDATE_URL ) ) {
				$errors[] = "Node {$node['@type']} has invalid @id format: {$node['@id']}";
			}

			if ( isset( $node['datePublished'] ) ) {
				$timestamp = strtotime( $node['datePublished'] );
				if ( false === $timestamp ) {
					$errors[] = "Node {$node['@type']} has invalid datePublished format: {$node['datePublished']}";
				}
			}

			if ( isset( $node['dateModified'] ) ) {
				$timestamp = strtotime( $node['dateModified'] );
				if ( false === $timestamp ) {
					$errors[] = "Node {$node['@type']} has invalid dateModified format: {$node['dateModified']}";
				}
			}
		}

		return $errors;
	}

	/**
	 * Output validation errors as HTML comments
	 *
	 * Outputs validation errors as HTML comments when WP_DEBUG is enabled.
	 * Implements Requirements 17.5, 17.6.
	 *
	 * @since 1.0.0
	 * @return string HTML comments with validation errors.
	 */
	public function get_debug_output(): string {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return '';
		}

		$errors = $this->get_validation_errors();

		if ( empty( $errors ) ) {
			return "<!-- MeowSEO Schema Debug: No validation errors -->\n";
		}

		$output = "<!-- MeowSEO Schema Debug: Validation Errors -->\n";
		foreach ( $errors as $error ) {
			$output .= "<!-- Schema Error: " . esc_html( $error ) . " -->\n";
		}

		return $output;
	}

	/**
	 * Check if automatic video schema generation is enabled
	 *
	 * Implements Requirement 2.10.
	 *
	 * @since 1.0.0
	 * @return bool True if automatic video schema generation is enabled.
	 */
	private function is_auto_video_schema_enabled(): bool {
		$enabled = $this->options->get( 'auto_video_schema_enabled', true );
		
		/**
		 * Filter whether automatic video schema generation is enabled
		 *
		 * Allows customization of automatic video schema generation.
		 *
		 * @since 1.0.0
		 * @param bool $enabled Whether automatic video schema generation is enabled.
		 * @param int  $post_id Post ID.
		 */
		return apply_filters( 'meowseo_auto_video_schema_enabled', $enabled, $this->post_id );
	}

	/**
	 * Build VideoObject schema from detected video
	 *
	 * Generates VideoObject schema from video detection data.
	 * Fetches metadata from oEmbed APIs and falls back to URL-only schema.
	 * Implements Requirement 2.4, 2.5, 2.6.
	 *
	 * @since 1.0.0
	 * @param array $video Video detection data with 'platform' and 'id' keys.
	 * @return array VideoObject schema array.
	 */
	private function build_video_schema_from_detection( array $video ): array {
		if ( empty( $video['platform'] ) || empty( $video['id'] ) ) {
			return array();
		}

		$platform = $video['platform'];
		$video_id = $video['id'];

		// Build video URL based on platform.
		$video_url = '';
		if ( 'youtube' === $platform ) {
			$video_url = 'https://www.youtube.com/watch?v=' . $video_id;
		} elseif ( 'vimeo' === $platform ) {
			$video_url = 'https://vimeo.com/' . $video_id;
		}

		if ( empty( $video_url ) ) {
			return array();
		}

		// Fetch video metadata using Video_Detector.
		$video_detector = new \MeowSEO\Modules\Schema\Video_Detector();
		$metadata = $video_detector->fetch_video_metadata( $platform, $video_id );

		$permalink = get_permalink( $this->post );

		// Build base schema with required properties.
		$schema = array(
			'@type'       => 'VideoObject',
			'@id'         => $permalink . '#video-' . $video_id,
			'url'         => $video_url,
			'embedUrl'    => $video_url,
		);

		// Add metadata if available (Requirement 2.5).
		if ( false !== $metadata && is_array( $metadata ) ) {
			if ( ! empty( $metadata['title'] ) ) {
				$schema['name'] = $metadata['title'];
			}

			if ( ! empty( $metadata['description'] ) ) {
				$schema['description'] = $metadata['description'];
			}

			if ( ! empty( $metadata['thumbnail_url'] ) ) {
				$schema['thumbnailUrl'] = $metadata['thumbnail_url'];
			}

			if ( ! empty( $metadata['duration'] ) ) {
				$schema['duration'] = $metadata['duration'];
			}

			// Add uploadDate from post date as fallback.
			$schema['uploadDate'] = $this->format_date_safe( get_the_date( 'c', $this->post ) );
		} else {
			// Fallback to minimal schema with URL only (Requirement 2.6).
			$schema['name'] = get_the_title( $this->post );
			$schema['description'] = get_the_excerpt( $this->post );
			$schema['uploadDate'] = $this->format_date_safe( get_the_date( 'c', $this->post ) );

			// Use post thumbnail as fallback.
			if ( has_post_thumbnail( $this->post ) ) {
				$thumbnail_id = get_post_thumbnail_id( $this->post );
				$thumbnail_url = $this->get_image_url_safe( $thumbnail_id );
				if ( $thumbnail_url ) {
					$schema['thumbnailUrl'] = $thumbnail_url;
				}
			}
		}

		/**
		 * Filter auto-generated video schema
		 *
		 * Allows customization of automatically generated video schema.
		 *
		 * @since 1.0.0
		 * @param array $schema   VideoObject schema array.
		 * @param array $video    Video detection data.
		 * @param int   $post_id  Post ID.
		 */
		return apply_filters( 'meowseo_auto_video_schema', $schema, $video, $this->post_id );
	}
}
