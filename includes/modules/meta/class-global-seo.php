<?php
/**
 * Global SEO Class
 *
 * @package MeowSEO
 * @subpackage Modules\Meta
 */

namespace MeowSEO\Modules\Meta;

use MeowSEO\Options;

/**
 * Global_SEO class
 *
 * Responsible for handling SEO for non-singular pages (archives, homepage,
 * search, 404, etc.).
 */
class Global_SEO {
	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Title_Patterns instance
	 *
	 * @var Title_Patterns
	 */
	private Title_Patterns $patterns;

	/**
	 * Meta_Resolver instance
	 *
	 * @var Meta_Resolver
	 */
	private Meta_Resolver $resolver;

	/**
	 * Constructor
	 *
	 * @param Options        $options  Options instance.
	 * @param Title_Patterns $patterns Title patterns instance.
	 * @param Meta_Resolver  $resolver Meta resolver instance.
	 */
	public function __construct(
		Options $options,
		Title_Patterns $patterns,
		Meta_Resolver $resolver
	) {
		$this->options  = $options;
		$this->patterns = $patterns;
		$this->resolver = $resolver;
	}

	/**
	 * Get current page type
	 *
	 * @return string Page type.
	 */
	public function get_current_page_type(): string {
		if ( \is_front_page() ) {
			return 'homepage';
		}
		if ( \is_home() ) {
			return 'blog_index';
		}
		if ( \is_category() ) {
			return 'category';
		}
		if ( \is_tag() ) {
			return 'tag';
		}
		if ( \is_tax() ) {
			return 'custom_taxonomy';
		}
		if ( \is_author() ) {
			return 'author';
		}
		if ( \is_date() ) {
			return 'date_archive';
		}
		if ( \is_search() ) {
			return 'search';
		}
		if ( \is_404() ) {
			return '404';
		}
		if ( \is_post_type_archive() ) {
			return 'post_type_archive';
		}
		return 'unknown';
	}

	/**
	 * Get title for non-singular pages
	 *
	 * @return string Title.
	 */
	public function get_title(): string {
		$page_type = $this->get_current_page_type();
		$data      = array();

		switch ( $page_type ) {
			case 'homepage':
				$data = $this->handle_homepage();
				break;
			case 'blog_index':
				$data = $this->handle_blog_index();
				break;
			case 'category':
				$data = $this->handle_category();
				break;
			case 'tag':
				$data = $this->handle_tag();
				break;
			case 'custom_taxonomy':
				$data = $this->handle_custom_taxonomy();
				break;
			case 'author':
				$data = $this->handle_author();
				break;
			case 'date_archive':
				$data = $this->handle_date_archive();
				break;
			case 'search':
				$data = $this->handle_search();
				break;
			case '404':
				$data = $this->handle_404();
				break;
			case 'post_type_archive':
				$data = $this->handle_post_type_archive();
				break;
		}

		return $data['title'] ?? '';
	}

	/**
	 * Get description for non-singular pages
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		$page_type = $this->get_current_page_type();
		$data      = array();

		switch ( $page_type ) {
			case 'homepage':
				$data = $this->handle_homepage();
				break;
			case 'blog_index':
				$data = $this->handle_blog_index();
				break;
			case 'category':
				$data = $this->handle_category();
				break;
			case 'tag':
				$data = $this->handle_tag();
				break;
			case 'custom_taxonomy':
				$data = $this->handle_custom_taxonomy();
				break;
			case 'author':
				$data = $this->handle_author();
				break;
			case 'date_archive':
				$data = $this->handle_date_archive();
				break;
			case 'search':
				$data = $this->handle_search();
				break;
			case '404':
				$data = $this->handle_404();
				break;
			case 'post_type_archive':
				$data = $this->handle_post_type_archive();
				break;
		}

		return $data['description'] ?? '';
	}

	/**
	 * Get robots directives for non-singular pages
	 *
	 * @return string Robots directives.
	 */
	public function get_robots(): string {
		$page_type = $this->get_current_page_type();
		$data      = array();

		switch ( $page_type ) {
			case 'homepage':
				$data = $this->handle_homepage();
				break;
			case 'blog_index':
				$data = $this->handle_blog_index();
				break;
			case 'category':
				$data = $this->handle_category();
				break;
			case 'tag':
				$data = $this->handle_tag();
				break;
			case 'custom_taxonomy':
				$data = $this->handle_custom_taxonomy();
				break;
			case 'author':
				$data = $this->handle_author();
				break;
			case 'date_archive':
				$data = $this->handle_date_archive();
				break;
			case 'search':
				$data = $this->handle_search();
				break;
			case '404':
				$data = $this->handle_404();
				break;
			case 'post_type_archive':
				$data = $this->handle_post_type_archive();
				break;
		}

		return $data['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
	}

	/**
	 * Get canonical URL for non-singular pages
	 *
	 * @return string Canonical URL.
	 */
	public function get_canonical(): string {
		$page_type = $this->get_current_page_type();
		$data      = array();

		switch ( $page_type ) {
			case 'homepage':
				$data = $this->handle_homepage();
				break;
			case 'blog_index':
				$data = $this->handle_blog_index();
				break;
			case 'category':
				$data = $this->handle_category();
				break;
			case 'tag':
				$data = $this->handle_tag();
				break;
			case 'custom_taxonomy':
				$data = $this->handle_custom_taxonomy();
				break;
			case 'author':
				$data = $this->handle_author();
				break;
			case 'date_archive':
				$data = $this->handle_date_archive();
				break;
			case 'search':
				$data = $this->handle_search();
				break;
			case '404':
				$data = $this->handle_404();
				break;
			case 'post_type_archive':
				$data = $this->handle_post_type_archive();
				break;
		}

		return $data['canonical'] ?? \home_url();
	}

	/**
	 * Check if author page should be noindexed
	 *
	 * @param int $author_id Author ID.
	 * @return bool True if should noindex.
	 */
	private function should_noindex_author( int $author_id ): bool {
		// Count published posts by author.
		$post_count = \count_user_posts( $author_id, 'post', true );

		// Noindex if author has fewer than 2 published posts.
		return $post_count < 2;
	}

	/**
	 * Check if date archive should be noindexed
	 *
	 * @return bool True if should noindex.
	 */
	private function should_noindex_date_archive(): bool {
		// Check if noindex date archives option is enabled.
		$noindex_date_archives = $this->options->get( 'noindex_date_archives', false );

		return (bool) $noindex_date_archives;
	}

	/**
	 * Handle homepage
	 *
	 * @return array SEO data.
	 */
	private function handle_homepage(): array {
		// Get homepage pattern.
		$pattern = $this->patterns->get_pattern_for_page_type( 'homepage' );

		// Build context for pattern resolution.
		$context = array(
			'site_name' => \get_bloginfo( 'name' ),
			'tagline'   => \get_bloginfo( 'description' ),
			'sep'       => $this->options->get( 'separator', '|' ),
		);

		// Resolve title using pattern.
		$title = $this->patterns->resolve( $pattern, $context );

		// Use tagline as description fallback.
		$description = \get_bloginfo( 'description' );

		// Homepage canonical.
		$canonical = \home_url( '/' );

		// Default robots directives.
		$robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
		);
	}

	/**
	 * Handle blog index
	 *
	 * @return array SEO data.
	 */
	private function handle_blog_index(): array {
		// Get blog index pattern (same as homepage for most sites).
		$pattern = $this->patterns->get_pattern_for_page_type( 'homepage' );

		// Build context.
		$context = array(
			'site_name' => \get_bloginfo( 'name' ),
			'tagline'   => \get_bloginfo( 'description' ),
			'sep'       => $this->options->get( 'separator', '|' ),
		);

		// Resolve title.
		$title = $this->patterns->resolve( $pattern, $context );

		// Use tagline as description.
		$description = \get_bloginfo( 'description' );

		// Blog index canonical.
		$canonical = \get_permalink( \get_option( 'page_for_posts' ) );
		if ( ! $canonical ) {
			$canonical = \home_url( '/' );
		}

		// Default robots directives.
		$robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
		);
	}

	/**
	 * Handle category archive
	 *
	 * @return array SEO data.
	 */
	private function handle_category(): array {
		$category = \get_queried_object();

		if ( ! $category ) {
			return array();
		}

		// Get category pattern.
		$pattern = $this->patterns->get_pattern_for_page_type( 'category' );

		// Build context.
		$context = array(
			'term_name'        => $category->name,
			'term_description' => $category->description,
			'site_name'        => \get_bloginfo( 'name' ),
			'sep'              => $this->options->get( 'separator', '|' ),
		);

		// Resolve title.
		$title = $this->patterns->resolve( $pattern, $context );

		// Use category description as meta description.
		$description = $category->description ? \wp_strip_all_tags( $category->description ) : '';

		// Category canonical.
		$canonical = \get_term_link( $category );

		// Default robots directives.
		$robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
		);
	}

	/**
	 * Handle tag archive
	 *
	 * @return array SEO data.
	 */
	private function handle_tag(): array {
		$tag = \get_queried_object();

		if ( ! $tag ) {
			return array();
		}

		// Get tag pattern.
		$pattern = $this->patterns->get_pattern_for_page_type( 'tag' );

		// Build context.
		$context = array(
			'term_name'        => $tag->name,
			'term_description' => $tag->description,
			'site_name'        => \get_bloginfo( 'name' ),
			'sep'              => $this->options->get( 'separator', '|' ),
		);

		// Resolve title.
		$title = $this->patterns->resolve( $pattern, $context );

		// Use tag description as meta description.
		$description = $tag->description ? \wp_strip_all_tags( $tag->description ) : '';

		// Tag canonical.
		$canonical = \get_term_link( $tag );

		// Default robots directives.
		$robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
		);
	}

	/**
	 * Handle custom taxonomy archive
	 *
	 * @return array SEO data.
	 */
	private function handle_custom_taxonomy(): array {
		$term = \get_queried_object();

		if ( ! $term ) {
			return array();
		}

		// Use category pattern for custom taxonomies.
		$pattern = $this->patterns->get_pattern_for_page_type( 'category' );

		// Build context.
		$context = array(
			'term_name'        => $term->name,
			'term_description' => $term->description,
			'site_name'        => \get_bloginfo( 'name' ),
			'sep'              => $this->options->get( 'separator', '|' ),
		);

		// Resolve title.
		$title = $this->patterns->resolve( $pattern, $context );

		// Use term description as meta description.
		$description = $term->description ? \wp_strip_all_tags( $term->description ) : '';

		// Term canonical.
		$canonical = \get_term_link( $term );

		// Default robots directives.
		$robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
		);
	}

	/**
	 * Handle author page
	 *
	 * @return array SEO data.
	 */
	private function handle_author(): array {
		$author = \get_queried_object();

		if ( ! $author ) {
			return array();
		}

		// Get author pattern.
		$pattern = $this->patterns->get_pattern_for_page_type( 'author' );

		// Build context.
		$context = array(
			'author_name' => $author->display_name,
			'site_name'   => \get_bloginfo( 'name' ),
			'sep'         => $this->options->get( 'separator', '|' ),
		);

		// Resolve title.
		$title = $this->patterns->resolve( $pattern, $context );

		// Use author bio as description.
		$description = \get_the_author_meta( 'description', $author->ID );
		$description = $description ? \wp_strip_all_tags( $description ) : '';

		// Author canonical.
		$canonical = \get_author_posts_url( $author->ID );

		// Check if should noindex.
		$should_noindex = $this->should_noindex_author( $author->ID );

		// Robots directives.
		if ( $should_noindex ) {
			$robots = 'noindex, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
		} else {
			$robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
		}

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
		);
	}

	/**
	 * Handle date archive
	 *
	 * @return array SEO data.
	 */
	private function handle_date_archive(): array {
		// Get date pattern.
		$pattern = $this->patterns->get_pattern_for_page_type( 'date' );

		// Build context.
		$context = array(
			'current_year'  => \get_query_var( 'year' ) ? \get_query_var( 'year' ) : \gmdate( 'Y' ),
			'current_month' => \get_query_var( 'monthnum' ) ? \date_i18n( 'F', \mktime( 0, 0, 0, \get_query_var( 'monthnum' ), 1 ) ) : \gmdate( 'F' ),
			'site_name'     => \get_bloginfo( 'name' ),
			'sep'           => $this->options->get( 'separator', '|' ),
		);

		// Resolve title.
		$title = $this->patterns->resolve( $pattern, $context );

		// No description for date archives.
		$description = '';

		// Date archive canonical.
		$year  = \get_query_var( 'year' );
		$month = \get_query_var( 'monthnum' );
		$day   = \get_query_var( 'day' );

		if ( $day ) {
			$canonical = \get_day_link( $year, $month, $day );
		} elseif ( $month ) {
			$canonical = \get_month_link( $year, $month );
		} else {
			$canonical = \get_year_link( $year );
		}

		// Check if should noindex.
		$should_noindex = $this->should_noindex_date_archive();

		// Robots directives.
		if ( $should_noindex ) {
			$robots = 'noindex, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
		} else {
			$robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
		}

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
		);
	}

	/**
	 * Handle search results
	 *
	 * @return array SEO data.
	 */
	private function handle_search(): array {
		// Get search pattern.
		$pattern = $this->patterns->get_pattern_for_page_type( 'search' );

		// Build context.
		$context = array(
			'site_name' => \get_bloginfo( 'name' ),
			'sep'       => $this->options->get( 'separator', '|' ),
		);

		// Resolve title.
		$title = $this->patterns->resolve( $pattern, $context );

		// No description for search pages.
		$description = '';

		// Search canonical.
		$canonical = \home_url( '/' );

		// Always noindex search pages.
		$robots = 'noindex, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
		);
	}

	/**
	 * Handle 404 page
	 *
	 * @return array SEO data.
	 */
	private function handle_404(): array {
		// Get 404 pattern.
		$pattern = $this->patterns->get_pattern_for_page_type( '404' );

		// Build context.
		$context = array(
			'site_name' => \get_bloginfo( 'name' ),
			'sep'       => $this->options->get( 'separator', '|' ),
		);

		// Resolve title.
		$title = $this->patterns->resolve( $pattern, $context );

		// No description for 404 pages.
		$description = '';

		// 404 canonical.
		$canonical = \home_url( '/' );

		// Always noindex 404 pages.
		$robots = 'noindex, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
		);
	}

	/**
	 * Handle post type archive
	 *
	 * @return array SEO data.
	 */
	private function handle_post_type_archive(): array {
		$post_type = \get_query_var( 'post_type' );

		if ( \is_array( $post_type ) ) {
			$post_type = \reset( $post_type );
		}

		$post_type_obj = \get_post_type_object( $post_type );

		if ( ! $post_type_obj ) {
			return array();
		}

		// Use category pattern for post type archives.
		$pattern = $this->patterns->get_pattern_for_page_type( 'category' );

		// Build context.
		$context = array(
			'term_name' => $post_type_obj->labels->name,
			'site_name' => \get_bloginfo( 'name' ),
			'sep'       => $this->options->get( 'separator', '|' ),
		);

		// Resolve title.
		$title = $this->patterns->resolve( $pattern, $context );

		// No description for post type archives.
		$description = '';

		// Post type archive canonical.
		$canonical = \get_post_type_archive_link( $post_type );

		// Default robots directives.
		$robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
		);
	}
}

