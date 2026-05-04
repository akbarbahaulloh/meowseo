<?php
/**
 * LLMs.txt Generator
 *
 * Serves a /llms.txt virtual file that guides AI language models (ChatGPT,
 * Gemini, Claude, Perplexity) on how to best crawl and understand this site.
 * Modeled after the llms.txt standard (llmstxt.org).
 *
 * Why this matters for 2026 SEO:
 * - AI Overviews (Google, Bing) use LLM crawlers that respect llms.txt
 * - Gives site owners control over what AI models learn about their content
 * - Acts as a "robots.txt for AI" - a competitive advantage for early adopters
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Meta;

use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LLMS_Txt class.
 *
 * Generates and serves /llms.txt on the frontend.
 */
class LLMS_Txt {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_action( 'template_redirect', array( $this, 'serve_llms_txt' ) );
	}

	/**
	 * Add rewrite rule for /llms.txt.
	 *
	 * @return void
	 */
	public function add_rewrite_rule(): void {
		add_rewrite_rule( '^llms\.txt$', 'index.php?meowseo_llms_txt=1', 'top' );

		// Flush rewrite rules once after the rule is first registered.
		if ( get_transient( 'meowseo_flush_llms_rewrite' ) ) {
			delete_transient( 'meowseo_flush_llms_rewrite' );
			flush_rewrite_rules( false );
		}
	}

	/**
	 * Register query var.
	 *
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = 'meowseo_llms_txt';
		return $vars;
	}

	/**
	 * Serve llms.txt content when the virtual URL is requested.
	 *
	 * @return void
	 */
	public function serve_llms_txt(): void {
		if ( ! get_query_var( 'meowseo_llms_txt' ) ) {
			return;
		}

		// Check if llms.txt is enabled in settings.
		if ( ! $this->options->get( 'llms_txt_enabled', true ) ) {
			wp_die( 'llms.txt is disabled.', '', array( 'response' => 404 ) );
		}

		// Output the llms.txt content.
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo $this->generate(); // phpcs:ignore WordPress.Security.EscapeOutput -- plain text output
		exit;
	}

	/**
	 * Generate the llms.txt content.
	 *
	 * Format follows the llmstxt.org specification:
	 * - Title (H1)
	 * - Short description
	 * - Optional sections with markdown links
	 *
	 * @return string
	 */
	public function generate(): string {
		$site_name    = get_bloginfo( 'name' );
		$site_url     = get_site_url();
		$description  = get_bloginfo( 'description' );
		$custom_intro = $this->options->get( 'llms_txt_intro', '' );

		$lines = array();

		// Header.
		$lines[] = "# {$site_name}";
		$lines[] = '';

		// Description block.
		if ( ! empty( $custom_intro ) ) {
			$lines[] = $custom_intro;
		} elseif ( ! empty( $description ) ) {
			$lines[] = "> {$description}";
		} else {
			$lines[] = "> {$site_name} adalah sumber informasi online yang menyajikan konten berkualitas tinggi.";
		}

		$lines[] = '';

		// Site metadata.
		$lines[] = "**URL:** {$site_url}";
		$lines[] = "**Bahasa:** " . get_bloginfo( 'language' );
		$lines[] = "**Dibuat dengan:** MeowSEO";
		$lines[] = '';

		// Main content sections.
		$lines[] = '## Konten Utama';
		$lines[] = '';

		// Get published posts (max 200 for performance).
		$post_types = $this->options->get( 'llms_txt_post_types', array( 'post', 'page' ) );
		if ( ! is_array( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		$posts = get_posts( array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $this->options->get( 'llms_txt_max_posts', 200 ),
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		) );

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$excerpt = $this->get_post_excerpt( $post );
				$lines[] = "- [{$post->post_title}](" . get_permalink( $post ) . ")" . ( $excerpt ? ": {$excerpt}" : '' );
			}
		}

		$lines[] = '';

		// Pages section.
		$pages = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 30,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		) );

		if ( ! empty( $pages ) ) {
			$lines[] = '## Halaman Penting';
			$lines[] = '';
			foreach ( $pages as $page ) {
				$lines[] = "- [{$page->post_title}](" . get_permalink( $page ) . ")";
			}
			$lines[] = '';
		}

		// Category index.
		$categories = get_categories( array( 'hide_empty' => true, 'number' => 30 ) );
		if ( ! empty( $categories ) ) {
			$lines[] = '## Kategori';
			$lines[] = '';
			foreach ( $categories as $cat ) {
				$lines[] = "- [{$cat->name}](" . get_category_link( $cat->term_id ) . "): {$cat->count} artikel";
			}
			$lines[] = '';
		}

		// Optional: blocks (what AI should NOT use).
		$blocked_paths = $this->options->get( 'llms_txt_blocked', '' );
		if ( ! empty( $blocked_paths ) ) {
			$lines[] = '## Konten yang Tidak Perlu Diproses';
			$lines[] = '';
			$blocked_list = array_filter( array_map( 'trim', explode( "\n", $blocked_paths ) ) );
			foreach ( $blocked_list as $path ) {
				$lines[] = "- {$path}";
			}
			$lines[] = '';
		}

		// Allow other modules to inject content.
		$lines = apply_filters( 'meowseo/llms_txt/content', $lines, $this->options );

		// Footer.
		$lines[] = '---';
		$lines[] = "Dihasilkan oleh MeowSEO pada " . wp_date( 'Y-m-d H:i:s' ) . ' WIB';

		return implode( "\n", $lines );
	}

	/**
	 * Get a short excerpt for a post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Short excerpt (max 120 chars).
	 */
	private function get_post_excerpt( \WP_Post $post ): string {
		// Try SEO description first.
		$excerpt = get_post_meta( $post->ID, '_meowseo_description', true );

		if ( empty( $excerpt ) ) {
			$excerpt = $post->post_excerpt;
		}

		if ( empty( $excerpt ) ) {
			$excerpt = wp_strip_all_tags( $post->post_content );
		}

		$excerpt = wp_strip_all_tags( $excerpt );
		$excerpt = preg_replace( '/\s+/', ' ', $excerpt );
		$excerpt = trim( $excerpt );

		if ( mb_strlen( $excerpt ) > 120 ) {
			$excerpt = mb_substr( $excerpt, 0, 117 ) . '...';
		}

		return $excerpt;
	}

	/**
	 * Get the llms.txt URL.
	 *
	 * @return string
	 */
	public static function get_url(): string {
		return trailingslashit( get_site_url() ) . 'llms.txt';
	}
}
