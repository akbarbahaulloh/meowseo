<?php
/**
 * Meta Resolver Class
 *
 * @package MeowSEO
 * @subpackage Modules\Meta
 */

namespace MeowSEO\Modules\Meta;

use MeowSEO\Options;

/**
 * Meta_Resolver class
 *
 * Responsible for resolving all meta field values through fallback chains.
 */
class Meta_Resolver {
	/**
	 * Archive type constants
	 */
	const ARCHIVE_TYPE_AUTHOR = 'author_archive';
	const ARCHIVE_TYPE_DATE = 'date_archive';
	const ARCHIVE_TYPE_CATEGORY = 'category_archive';
	const ARCHIVE_TYPE_TAG = 'tag_archive';
	const ARCHIVE_TYPE_SEARCH = 'search_results';
	const ARCHIVE_TYPE_ATTACHMENT = 'attachment';
	const ARCHIVE_TYPE_POST_TYPE = 'post_type_archive';

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
	 * Constructor
	 *
	 * @param Options        $options  Options instance.
	 * @param Title_Patterns $patterns Title patterns instance.
	 */
	public function __construct( Options $options, Title_Patterns $patterns ) {
		$this->options  = $options;
		$this->patterns = $patterns;
	}

	/**
	 * Resolve SEO title
	 *
	 * Fallback chain: postmeta → pattern → raw title + site name
	 *
	 * @param int|null $post_id Post ID.
	 * @return string Resolved title.
	 */
	public function resolve_title( ?int $post_id = null ): string {
		// Check if this is an archive page.
		if ( is_archive() || is_search() || is_404() || ( is_home() && ! is_front_page() ) ) {
			return $this->resolve_archive_title();
		}
		
		// Check if this is the homepage.
		if ( is_front_page() ) {
			return $this->resolve_homepage_title();
		}
		
		// If no post ID, use current post.
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		// If still no post ID, return site name.
		if ( ! $post_id ) {
			return get_bloginfo( 'name' );
		}

		// Check postmeta first.
		$custom_title = $this->get_postmeta( $post_id, '_meowseo_title' );
		if ( ! empty( $custom_title ) ) {
			return $custom_title;
		}

		// Get post object.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return get_bloginfo( 'name' );
		}

		// Apply title pattern for post type.
		$pattern = $this->patterns->get_pattern_for_post_type( $post->post_type );
		$context = array(
			'title'       => $post->post_title,
			'page_number' => get_query_var( 'page', 0 ),
		);

		$resolved = $this->patterns->resolve( $pattern, $context );

		// If pattern resolution failed or empty, use fallback.
		if ( empty( $resolved ) ) {
			$separator = $this->options->get_separator();
			$site_name = get_bloginfo( 'name' );
			return $post->post_title . ' ' . $separator . ' ' . $site_name;
		}

		return $resolved;
	}

	/**
	 * Resolve archive title
	 *
	 * Resolves title for archive pages using archive-specific patterns.
	 *
	 * @return string Resolved archive title.
	 */
	private function resolve_archive_title(): string {
		// Detect archive type.
		$archive_type = $this->detect_archive_type();
		
		if ( ! $archive_type ) {
			return get_bloginfo( 'name' );
		}
		
		// Get pattern for this archive type.
		$pattern = $this->patterns->get_pattern_for_archive_type( $archive_type );
		
		// Resolve archive variables.
		$context = $this->patterns->resolve_archive_variables();
		
		// Resolve pattern with context.
		$resolved = $this->patterns->resolve( $pattern, $context );
		
		// If pattern resolution failed or empty, use fallback.
		if ( empty( $resolved ) ) {
			$separator = $this->options->get_separator();
			$site_name = get_bloginfo( 'name' );
			return $site_name;
		}
		
		return $resolved;
	}

	/**
	 * Resolve homepage title
	 *
	 * Resolves title for the homepage using homepage pattern.
	 *
	 * @return string Resolved homepage title.
	 */
	private function resolve_homepage_title(): string {
		// Get homepage pattern.
		$pattern = $this->patterns->get_pattern_for_page_type( 'homepage' );
		
		// Build context.
		$context = array();
		
		// Resolve pattern with context.
		$resolved = $this->patterns->resolve( $pattern, $context );
		
		// If pattern resolution failed or empty, use fallback.
		if ( empty( $resolved ) ) {
			$separator = $this->options->get_separator();
			$site_name = get_bloginfo( 'name' );
			$tagline = get_bloginfo( 'description' );
			return $site_name . ' ' . $separator . ' ' . $tagline;
		}
		
		return $resolved;
	}

	/**
	 * Detect archive type
	 *
	 * Detects the current archive type using WordPress conditionals.
	 *
	 * @return string|null Archive type or null if not an archive.
	 */
	private function detect_archive_type(): ?string {
		if ( is_category() ) {
			return 'category_archive';
		}
		
		if ( is_tag() ) {
			return 'tag_archive';
		}
		
		if ( is_tax() ) {
			return 'custom_taxonomy_archive';
		}
		
		if ( is_author() ) {
			return 'author_page';
		}
		
		if ( is_search() ) {
			return 'search_results';
		}
		
		if ( is_date() ) {
			return 'date_archive';
		}
		
		if ( is_404() ) {
			return '404_page';
		}
		
		if ( is_post_type_archive() ) {
			return 'custom_taxonomy_archive';
		}
		
		return null;
	}

	/**
	 * Resolve meta description
	 *
	 * Fallback chain: postmeta → excerpt → content → empty
	 *
	 * @param int|null $post_id Post ID.
	 * @return string Resolved description.
	 */
	public function resolve_description( ?int $post_id = null ): string {
		// Check if this is an archive page.
		if ( is_archive() || is_search() || is_404() ) {
			return $this->resolve_archive_description();
		}
		
		// If no post ID, use current post.
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		// If still no post ID, return empty.
		if ( ! $post_id ) {
			return '';
		}

		// Check postmeta first.
		$custom_description = $this->get_postmeta( $post_id, '_meowseo_description' );
		if ( ! empty( $custom_description ) ) {
			return $custom_description;
		}

		// Get post object.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		// Try excerpt.
		if ( ! empty( $post->post_excerpt ) ) {
			return $this->truncate_text( $post->post_excerpt, 160 );
		}

		// Try content.
		if ( ! empty( $post->post_content ) ) {
			return $this->truncate_text( $post->post_content, 160 );
		}

		// Return empty string.
		return '';
	}

	/**
	 * Resolve archive description
	 *
	 * Resolves description for archive pages.
	 *
	 * @return string Resolved archive description.
	 */
	private function resolve_archive_description(): string {
		// For category/tag archives, try to get term description.
		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term && isset( $term->description ) && ! empty( $term->description ) ) {
				return $this->truncate_text( $term->description, 160 );
			}
		}
		
		// For author archives, try to get author bio.
		if ( is_author() ) {
			$author = get_queried_object();
			if ( $author && isset( $author->ID ) ) {
				$bio = get_the_author_meta( 'description', $author->ID );
				if ( ! empty( $bio ) ) {
					return $this->truncate_text( $bio, 160 );
				}
			}
		}
		
		// Return empty for other archive types.
		return '';
	}

	/**
	 * Resolve Open Graph image
	 *
	 * Fallback chain: postmeta → featured image → content image → global default → empty
	 *
	 * @param int|null $post_id Post ID.
	 * @return array Image data with URL and dimensions (url, width, height).
	 */
	public function resolve_og_image( ?int $post_id = null ): array {
		// If no post ID, use current post.
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		// If still no post ID, try global default.
		if ( ! $post_id ) {
			$global_url = $this->options->get_default_social_image_url();
			if ( ! empty( $global_url ) ) {
				return array( 'url' => $global_url );
			}
			return array();
		}

		// Check postmeta first.
		$custom_image_id = $this->get_postmeta( $post_id, '_meowseo_og_image' );
		if ( ! empty( $custom_image_id ) && wp_attachment_is_image( $custom_image_id ) ) {
			$dimensions = $this->get_image_dimensions( $custom_image_id );
			$url        = wp_get_attachment_image_url( $custom_image_id, 'full' );
			if ( $url ) {
				return array_merge( array( 'url' => $url ), $dimensions );
			}
		}

		// Try featured image.
		$featured_id = get_post_thumbnail_id( $post_id );
		if ( $featured_id ) {
			$dimensions = $this->get_image_dimensions( $featured_id );
			if ( isset( $dimensions['width'] ) && $dimensions['width'] >= 1200 ) {
				$url = wp_get_attachment_image_url( $featured_id, 'full' );
				if ( $url ) {
					return array_merge( array( 'url' => $url ), $dimensions );
				}
			}
		}

		// Try first content image.
		$content_image = $this->get_first_content_image( $post_id, 1200 );
		if ( $content_image ) {
			return $content_image;
		}

		// Try global default.
		$global_url = $this->options->get_default_social_image_url();
		if ( ! empty( $global_url ) ) {
			return array( 'url' => $global_url );
		}

		// Return empty array.
		return array();
	}

	/**
	 * Resolve canonical URL
	 *
	 * Fallback chain: postmeta → get_permalink() → get_term_link() → home_url()
	 * Always strips pagination parameters.
	 *
	 * @param int|null $post_id Post ID.
	 * @return string Canonical URL.
	 */
	public function resolve_canonical( ?int $post_id = null ): string {
		// Check postmeta first if post ID provided.
		if ( $post_id ) {
			$custom_canonical = $this->get_postmeta( $post_id, '_meowseo_canonical' );
			if ( ! empty( $custom_canonical ) ) {
				return $this->strip_pagination_params( $custom_canonical );
			}
		}

		// Determine page type and get appropriate URL.
		$url = '';

		if ( is_singular() ) {
			if ( null === $post_id ) {
				$post_id = get_the_ID();
			}
			if ( $post_id ) {
				$url = get_permalink( $post_id );
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term && isset( $term->term_id ) ) {
				$url = get_term_link( $term );
			}
		} elseif ( is_front_page() || is_home() ) {
			$url = home_url( '/' );
		} elseif ( is_author() ) {
			$author = get_queried_object();
			if ( $author && isset( $author->ID ) ) {
				$url = get_author_posts_url( $author->ID );
			}
		} elseif ( is_post_type_archive() ) {
			$post_type = get_query_var( 'post_type' );
			if ( $post_type ) {
				$url = get_post_type_archive_link( $post_type );
			}
		} elseif ( is_search() ) {
			$url = home_url( '/' ) . '?s=' . urlencode( get_search_query() );
		}

		// Fallback to home URL if no URL determined.
		if ( empty( $url ) || is_wp_error( $url ) ) {
			$url = home_url( '/' );
		}

		// Strip pagination parameters.
		return $this->strip_pagination_params( $url );
	}

	/**
	 * Resolve robots directives
	 *
	 * Merges: base directives + post overrides + automatic rules
	 *
	 * @param int|null $post_id Post ID.
	 * @return string Robots directives.
	 */
	public function resolve_robots( ?int $post_id = null ): string {
		// Start with base directives.
		$directives = array(
			'index'                => true,
			'follow'               => true,
			'max-image-preview'    => 'large',
			'max-snippet'          => '-1',
			'max-video-preview'    => '-1',
		);

		// Check post-specific overrides.
		if ( $post_id ) {
			$noindex = $this->get_postmeta( $post_id, '_meowseo_robots_noindex' );
			if ( $noindex ) {
				$directives['index'] = false;
			}

			$nofollow = $this->get_postmeta( $post_id, '_meowseo_robots_nofollow' );
			if ( $nofollow ) {
				$directives['follow'] = false;
			}
		}

		// Apply automatic rules.
		if ( is_search() ) {
			$directives['index'] = false;
		}

		if ( is_attachment() ) {
			$directives['index'] = false;
		}

		if ( is_date() ) {
			$noindex_dates = $this->options->get( 'noindex_date_archives', false );
			if ( $noindex_dates ) {
				$directives['index'] = false;
			}
		}

		// Merge directives into string.
		return $this->merge_robots_directives( $directives );
	}

	/**
	 * Resolve Twitter Card title
	 *
	 * Independent from Open Graph title.
	 *
	 * @param int|null $post_id Post ID.
	 * @return string Twitter title.
	 */
	public function resolve_twitter_title( ?int $post_id = null ): string {
		// If no post ID, use current post.
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		// If still no post ID, return empty.
		if ( ! $post_id ) {
			return '';
		}

		// Check Twitter-specific postmeta first.
		$custom_title = $this->get_postmeta( $post_id, '_meowseo_twitter_title' );
		if ( ! empty( $custom_title ) ) {
			return $custom_title;
		}

		// Fall back to regular SEO title.
		return $this->resolve_title( $post_id );
	}

	/**
	 * Resolve Twitter Card description
	 *
	 * Independent from Open Graph description.
	 *
	 * @param int|null $post_id Post ID.
	 * @return string Twitter description.
	 */
	public function resolve_twitter_description( ?int $post_id = null ): string {
		// If no post ID, use current post.
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		// If still no post ID, return empty.
		if ( ! $post_id ) {
			return '';
		}

		// Check Twitter-specific postmeta first.
		$custom_description = $this->get_postmeta( $post_id, '_meowseo_twitter_description' );
		if ( ! empty( $custom_description ) ) {
			return $custom_description;
		}

		// Fall back to regular meta description.
		return $this->resolve_description( $post_id );
	}

	/**
	 * Resolve Twitter Card image
	 *
	 * Independent from Open Graph image.
	 *
	 * @param int|null $post_id Post ID.
	 * @return string Twitter image URL.
	 */
	public function resolve_twitter_image( ?int $post_id = null ): string {
		// If no post ID, use current post.
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		// If still no post ID, return empty.
		if ( ! $post_id ) {
			return '';
		}

		// Check Twitter-specific postmeta first.
		$custom_image_id = $this->get_postmeta( $post_id, '_meowseo_twitter_image' );
		if ( ! empty( $custom_image_id ) && wp_attachment_is_image( $custom_image_id ) ) {
			$url = wp_get_attachment_image_url( $custom_image_id, 'full' );
			if ( $url ) {
				return $url;
			}
		}

		// Fall back to OG image.
		$og_image = $this->resolve_og_image( $post_id );
		if ( isset( $og_image['url'] ) ) {
			return $og_image['url'];
		}

		return '';
	}

	/**
	 * Get hreflang alternates
	 *
	 * Only returns alternates when WPML or Polylang is active.
	 *
	 * @return array Language => URL mappings.
	 */
	public function get_hreflang_alternates(): array {
		$alternates = array();

		// Check WPML.
		if ( $this->is_wpml_active() ) {
			if ( function_exists( 'icl_get_languages' ) ) {
				$languages = icl_get_languages( 'skip_missing=0' );
				if ( is_array( $languages ) ) {
					foreach ( $languages as $lang ) {
						if ( isset( $lang['language_code'], $lang['url'] ) ) {
							$alternates[ $lang['language_code'] ] = $lang['url'];
						}
					}
				}
			}
			return $alternates;
		}

		// Check Polylang.
		if ( $this->is_polylang_active() ) {
			if ( function_exists( 'pll_the_languages' ) ) {
				$languages = pll_the_languages( array( 'raw' => 1 ) );
				if ( is_array( $languages ) ) {
					foreach ( $languages as $lang ) {
						if ( isset( $lang['slug'], $lang['url'] ) ) {
							$alternates[ $lang['slug'] ] = $lang['url'];
						}
					}
				}
			}
			return $alternates;
		}

		return $alternates;
	}

	/**
	 * Get postmeta value
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @return mixed Meta value.
	 */
	private function get_postmeta( int $post_id, string $key ) {
		$value = get_post_meta( $post_id, $key, true );
		// Treat false and null as empty string.
		return $value === false || $value === null ? '' : $value;
	}

	/**
	 * Truncate text to specified length
	 *
	 * Strips HTML tags and shortcodes before truncating.
	 *
	 * @param string $text   Text to truncate.
	 * @param int    $length Maximum length.
	 * @return string Truncated text.
	 */
	private function truncate_text( string $text, int $length ): string {
		// Strip shortcodes.
		$text = strip_shortcodes( $text );

		// Strip HTML tags.
		$text = wp_strip_all_tags( $text );

		// Normalize whitespace.
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		// Truncate to length.
		if ( mb_strlen( $text ) > $length ) {
			$text = mb_substr( $text, 0, $length );
			// Try to break at word boundary.
			$last_space = mb_strrpos( $text, ' ' );
			if ( $last_space !== false && $last_space > $length * 0.8 ) {
				$text = mb_substr( $text, 0, $last_space );
			}
			$text .= '...';
		}

		return $text;
	}

	/**
	 * Strip pagination parameters from URL
	 *
	 * Removes /page/N/, ?paged=N, and ?page=N from URLs.
	 *
	 * @param string $url URL to process.
	 * @return string URL without pagination parameters.
	 */
	private function strip_pagination_params( string $url ): string {
		// Remove /page/N/ from path.
		$url = preg_replace( '#/page/\d+/?#', '/', $url );

		// Parse URL to handle query parameters.
		$parsed = wp_parse_url( $url );
		if ( ! isset( $parsed['query'] ) ) {
			return $url;
		}

		// Parse query string.
		parse_str( $parsed['query'], $query_params );

		// Remove pagination parameters.
		unset( $query_params['paged'], $query_params['page'] );

		// Rebuild URL.
		$base_url = $parsed['scheme'] . '://' . $parsed['host'];
		if ( isset( $parsed['port'] ) ) {
			$base_url .= ':' . $parsed['port'];
		}
		if ( isset( $parsed['path'] ) ) {
			$base_url .= $parsed['path'];
		}

		// Add query string if any params remain.
		if ( ! empty( $query_params ) ) {
			$base_url .= '?' . http_build_query( $query_params );
		}

		// Add fragment if present.
		if ( isset( $parsed['fragment'] ) ) {
			$base_url .= '#' . $parsed['fragment'];
		}

		return $base_url;
	}

	/**
	 * Get first content image
	 *
	 * Scans post content for images meeting minimum width requirement.
	 *
	 * @param int $post_id   Post ID.
	 * @param int $min_width Minimum width in pixels.
	 * @return array|null Image data or null.
	 */
	private function get_first_content_image( int $post_id, int $min_width ): ?array {
		$post = get_post( $post_id );
		if ( ! $post || empty( $post->post_content ) ) {
			return null;
		}

		// Find all img tags in content.
		preg_match_all( '/<img[^>]+>/i', $post->post_content, $matches );
		if ( empty( $matches[0] ) ) {
			return null;
		}

		foreach ( $matches[0] as $img_tag ) {
			// Try to extract attachment ID from class.
			if ( preg_match( '/wp-image-(\d+)/i', $img_tag, $class_id ) ) {
				$attachment_id = (int) $class_id[1];
				if ( wp_attachment_is_image( $attachment_id ) ) {
					$dimensions = $this->get_image_dimensions( $attachment_id );
					if ( isset( $dimensions['width'] ) && $dimensions['width'] >= $min_width ) {
						$url = wp_get_attachment_image_url( $attachment_id, 'full' );
						if ( $url ) {
							return array_merge( array( 'url' => $url ), $dimensions );
						}
					}
				}
			}

			// Try to extract src and check dimensions.
			if ( preg_match( '/src=["\']([^"\']+)["\']/i', $img_tag, $src_match ) ) {
				$src           = $src_match[1];
				$attachment_id = attachment_url_to_postid( $src );
				if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
					$dimensions = $this->get_image_dimensions( $attachment_id );
					if ( isset( $dimensions['width'] ) && $dimensions['width'] >= $min_width ) {
						return array_merge( array( 'url' => $src ), $dimensions );
					}
				}
			}
		}

		return null;
	}

	/**
	 * Get image dimensions
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array Image dimensions (width, height).
	 */
	private function get_image_dimensions( int $attachment_id ): array {
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! $metadata || ! isset( $metadata['width'], $metadata['height'] ) ) {
			return array();
		}

		return array(
			'width'  => (int) $metadata['width'],
			'height' => (int) $metadata['height'],
		);
	}

	/**
	 * Merge robots directives
	 *
	 * Converts directive array to comma-separated string.
	 *
	 * @param array $directives Directives to merge.
	 * @return string Merged directives string.
	 */
	private function merge_robots_directives( array $directives ): string {
		$parts = array();

		// Handle index/noindex.
		if ( isset( $directives['index'] ) ) {
			$parts[] = $directives['index'] ? 'index' : 'noindex';
		}

		// Handle follow/nofollow.
		if ( isset( $directives['follow'] ) ) {
			$parts[] = $directives['follow'] ? 'follow' : 'nofollow';
		}

		// Handle Google Discover directives (always present).
		if ( isset( $directives['max-image-preview'] ) ) {
			$parts[] = 'max-image-preview:' . $directives['max-image-preview'];
		}
		if ( isset( $directives['max-snippet'] ) ) {
			$parts[] = 'max-snippet:' . $directives['max-snippet'];
		}
		if ( isset( $directives['max-video-preview'] ) ) {
			$parts[] = 'max-video-preview:' . $directives['max-video-preview'];
		}

		return implode( ', ', $parts );
	}

	/**
	 * Check if WPML is active
	 *
	 * @return bool True if WPML is active.
	 */
	private function is_wpml_active(): bool {
		return defined( 'ICL_SITEPRESS_VERSION' ) && function_exists( 'icl_get_languages' );
	}

	/**
	 * Check if Polylang is active
	 *
	 * @return bool True if Polylang is active.
	 */
	private function is_polylang_active(): bool {
		return function_exists( 'pll_the_languages' ) && function_exists( 'pll_current_language' );
	}

	/**
	 * Get archive robots meta tags
	 *
	 * Resolves robots directives for archive pages based on archive type.
	 * Checks for term-specific overrides first, then falls back to global settings.
	 *
	 * @param string $archive_type Archive type constant.
	 * @return string Robots directives (e.g., "noindex, follow").
	 */
	public function get_archive_robots( string $archive_type ): string {
		// Get global setting for this archive type.
		$global_setting = $this->get_global_robots_setting( $archive_type );

		// Check for term-specific override if this is a taxonomy archive.
		if ( in_array( $archive_type, array( self::ARCHIVE_TYPE_CATEGORY, self::ARCHIVE_TYPE_TAG ), true ) ) {
			$term = get_queried_object();
			if ( $term && isset( $term->term_id ) ) {
				// Check for term-specific robots meta.
				$term_noindex = get_term_meta( $term->term_id, '_meowseo_robots_noindex', true );
				$term_nofollow = get_term_meta( $term->term_id, '_meowseo_robots_nofollow', true );

				// If term has specific settings, use those instead of global.
				if ( $term_noindex !== '' || $term_nofollow !== '' ) {
					$directives = array();
					if ( $term_noindex ) {
						$directives[] = 'noindex';
					} else {
						$directives[] = 'index';
					}
					if ( $term_nofollow ) {
						$directives[] = 'nofollow';
					} else {
						$directives[] = 'follow';
					}
					return implode( ', ', $directives );
				}
			}
		}

		// Use global setting.
		$directives = array();
		if ( isset( $global_setting['noindex'] ) && $global_setting['noindex'] ) {
			$directives[] = 'noindex';
		} else {
			$directives[] = 'index';
		}
		if ( isset( $global_setting['nofollow'] ) && $global_setting['nofollow'] ) {
			$directives[] = 'nofollow';
		} else {
			$directives[] = 'follow';
		}

		return implode( ', ', $directives );
	}

	/**
	 * Resolve robots directives for current archive page
	 *
	 * Detects the current archive type and returns appropriate robots directives.
	 *
	 * @return string Robots directives or empty string if not an archive.
	 */
	public function resolve_robots_for_archive(): string {
		// Detect archive type using WordPress conditionals.
		$archive_type = null;

		if ( is_author() ) {
			$archive_type = self::ARCHIVE_TYPE_AUTHOR;
		} elseif ( is_date() ) {
			$archive_type = self::ARCHIVE_TYPE_DATE;
		} elseif ( is_category() ) {
			$archive_type = self::ARCHIVE_TYPE_CATEGORY;
		} elseif ( is_tag() ) {
			$archive_type = self::ARCHIVE_TYPE_TAG;
		} elseif ( is_search() ) {
			$archive_type = self::ARCHIVE_TYPE_SEARCH;
		} elseif ( is_attachment() ) {
			$archive_type = self::ARCHIVE_TYPE_ATTACHMENT;
		} elseif ( is_post_type_archive() ) {
			$archive_type = self::ARCHIVE_TYPE_POST_TYPE;
		}

		// If not an archive, return empty string.
		if ( null === $archive_type ) {
			return '';
		}

		// Get robots directives for this archive type.
		return $this->get_archive_robots( $archive_type );
	}

	/**
	 * Get global robots setting for archive type
	 *
	 * Retrieves the global robots configuration from plugin options.
	 *
	 * @param string $archive_type Archive type constant.
	 * @return array Robots setting with 'noindex' and 'nofollow' keys.
	 */
	private function get_global_robots_setting( string $archive_type ): array {
		// Build option key from archive type.
		$option_key = 'robots_' . $archive_type;

		// Get setting from options.
		$setting = $this->options->get( $option_key, array() );

		// Ensure array structure.
		if ( ! is_array( $setting ) ) {
			$setting = array();
		}

		// Provide defaults.
		$defaults = array(
			'noindex'  => false,
			'nofollow' => false,
		);

		return array_merge( $defaults, $setting );
	}
}
