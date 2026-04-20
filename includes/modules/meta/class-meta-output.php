<?php
/**
 * Meta Output Class
 *
 * @package MeowSEO
 * @subpackage Modules\Meta
 */

namespace MeowSEO\Modules\Meta;

/**
 * Meta_Output class
 *
 * Responsible for outputting all meta tags in correct order in wp_head.
 */
class Meta_Output {
	/**
	 * Meta_Resolver instance
	 *
	 * @var Meta_Resolver
	 */
	private Meta_Resolver $resolver;

	/**
	 * Constructor
	 *
	 * @param Meta_Resolver $resolver Meta resolver instance.
	 */
	public function __construct( Meta_Resolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Output all head tags
	 *
	 * Main output method hooked to wp_head. Outputs all 7 tag groups in order:
	 * A (Title), B (Description), C (Robots), D (Canonical), E (Open Graph),
	 * F (Twitter Card), G (Hreflang).
	 *
	 * @return void
	 */
	public function output_head_tags(): void {
		// Group A: Title tag.
		$this->output_title();

		// Group B: Meta description.
		$this->output_description();

		// Group C: Robots meta tag.
		$this->output_robots();

		// Group D: Canonical link.
		$this->output_canonical();

		// Group E: Open Graph tags.
		$this->output_open_graph();

		// Group F: Twitter Card tags.
		$this->output_twitter_card();

		// Group G: Hreflang alternates.
		$this->output_hreflang();
	}

	/**
	 * Output title tag (Group A)
	 *
	 * @return void
	 */
	private function output_title(): void {
		$title = $this->resolver->resolve_title();
		if ( ! empty( $title ) ) {
			echo '<title>' . esc_html( $title ) . '</title>' . "\n";
		}
	}

	/**
	 * Output meta description (Group B)
	 *
	 * Only outputs tag if description is non-empty.
	 *
	 * @return void
	 */
	private function output_description(): void {
		$description = $this->resolver->resolve_description();
		if ( ! empty( $description ) ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		}
	}

	/**
	 * Output robots meta tag (Group C)
	 *
	 * Ensures Google Discover directives are always present.
	 * For archive pages, uses archive-specific robots settings.
	 *
	 * @return void
	 */
	private function output_robots(): void {
		// Check if this is an archive page.
		if ( is_archive() || is_search() || is_attachment() ) {
			$robots = $this->resolver->resolve_robots_for_archive();
		} else {
			$robots = $this->resolver->resolve_robots();
		}
		
		if ( ! empty( $robots ) ) {
			echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
		}
	}

	/**
	 * Output canonical link (Group D)
	 *
	 * @return void
	 */
	private function output_canonical(): void {
		$canonical = $this->resolver->resolve_canonical();
		if ( ! empty( $canonical ) ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
		}
	}

	/**
	 * Output Open Graph tags (Group E)
	 *
	 * Outputs tags in exact order: og:type, og:title, og:description, og:url,
	 * og:image (with dimensions), og:site_name, article:published_time,
	 * article:modified_time.
	 *
	 * @return void
	 */
	private function output_open_graph(): void {
		// Get current post ID.
		$post_id = get_the_ID();

		// og:type.
		$og_type = is_singular() ? 'article' : 'website';
		echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '">' . "\n";

		// og:title.
		$og_title = $this->resolver->resolve_title( $post_id );
		if ( ! empty( $og_title ) ) {
			echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
		}

		// og:description.
		$og_description = $this->resolver->resolve_description( $post_id );
		if ( ! empty( $og_description ) ) {
			echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '">' . "\n";
		}

		// og:url.
		$og_url = $this->resolver->resolve_canonical( $post_id );
		if ( ! empty( $og_url ) ) {
			echo '<meta property="og:url" content="' . esc_url( $og_url ) . '">' . "\n";
		}

		// og:image (with dimensions).
		$og_image = $this->resolver->resolve_og_image( $post_id );
		if ( ! empty( $og_image['url'] ) ) {
			echo '<meta property="og:image" content="' . esc_url( $og_image['url'] ) . '">' . "\n";

			// og:image:width.
			if ( isset( $og_image['width'] ) ) {
				echo '<meta property="og:image:width" content="' . esc_attr( $og_image['width'] ) . '">' . "\n";
			}

			// og:image:height.
			if ( isset( $og_image['height'] ) ) {
				echo '<meta property="og:image:height" content="' . esc_attr( $og_image['height'] ) . '">' . "\n";
			}
		}

		// og:site_name.
		$site_name = \get_bloginfo( 'name' );
		if ( ! empty( $site_name ) ) {
			echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
		}

		// article:published_time and article:modified_time (only for articles).
		if ( is_singular() && $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				// article:published_time.
				if ( ! empty( $post->post_date_gmt ) && $post->post_date_gmt !== '0000-00-00 00:00:00' ) {
					$published_time = $this->format_iso8601( $post->post_date_gmt );
					echo '<meta property="article:published_time" content="' . esc_attr( $published_time ) . '">' . "\n";
				}

				// article:modified_time.
				if ( ! empty( $post->post_modified_gmt ) && $post->post_modified_gmt !== '0000-00-00 00:00:00' ) {
					$modified_time = $this->format_iso8601( $post->post_modified_gmt );
					echo '<meta property="article:modified_time" content="' . esc_attr( $modified_time ) . '">' . "\n";
				}
			}
		}
	}

	/**
	 * Output Twitter Card tags (Group F)
	 *
	 * Uses Twitter-specific values from Meta_Resolver.
	 *
	 * @return void
	 */
	private function output_twitter_card(): void {
		// Get current post ID.
		$post_id = get_the_ID();

		// twitter:card.
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";

		// twitter:title.
		$twitter_title = $this->resolver->resolve_twitter_title( $post_id );
		if ( ! empty( $twitter_title ) ) {
			echo '<meta name="twitter:title" content="' . esc_attr( $twitter_title ) . '">' . "\n";
		}

		// twitter:description.
		$twitter_description = $this->resolver->resolve_twitter_description( $post_id );
		if ( ! empty( $twitter_description ) ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $twitter_description ) . '">' . "\n";
		}

		// twitter:image.
		$twitter_image = $this->resolver->resolve_twitter_image( $post_id );
		if ( ! empty( $twitter_image ) ) {
			echo '<meta name="twitter:image" content="' . esc_url( $twitter_image ) . '">' . "\n";
		}
	}

	/**
	 * Output hreflang alternates (Group G)
	 *
	 * Only outputs when WPML or Polylang is detected.
	 *
	 * @return void
	 */
	private function output_hreflang(): void {
		$alternates = $this->resolver->get_hreflang_alternates();
		if ( ! empty( $alternates ) ) {
			foreach ( $alternates as $lang => $url ) {
				echo '<link rel="alternate" hreflang="' . esc_attr( $lang ) . '" href="' . esc_url( $url ) . '">' . "\n";
			}
		}
	}

	/**
	 * Escape meta content
	 *
	 * Helper for escaping meta tag content attributes.
	 *
	 * @param string $content Content to escape.
	 * @return string Escaped content.
	 */
	private function esc_meta_content( string $content ): string {
		return esc_attr( $content );
	}

	/**
	 * Format date in ISO 8601 format
	 *
	 * Converts MySQL datetime to ISO 8601 format (YYYY-MM-DDTHH:MM:SS+00:00).
	 *
	 * @param string $date MySQL datetime string (GMT).
	 * @return string ISO 8601 formatted date.
	 */
	private function format_iso8601( string $date ): string {
		// Parse the date.
		$timestamp = strtotime( $date . ' UTC' );
		if ( false === $timestamp ) {
			return '';
		}

		// Format as ISO 8601.
		return gmdate( 'Y-m-d\TH:i:s\+00:00', $timestamp );
	}
}
