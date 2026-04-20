<?php
/**
 * Sitemap Generator
 *
 * Generates XML sitemap files and stores them in the filesystem.
 * Supports index sitemaps and child sitemaps per post type.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Sitemap;

use MeowSEO\Helpers\Logger;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sitemap Generator class
 *
 * Generates sitemap XML files and writes them to the filesystem.
 *
 * @since 1.0.0
 */
class Sitemap_Generator {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Sitemap directory path
	 *
	 * @var string
	 */
	private string $sitemap_dir;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->sitemap_dir = $this->get_sitemap_directory();
	}

	/**
	 * Get sitemap directory path
	 *
	 * @return string Sitemap directory path.
	 */
	private function get_sitemap_directory(): string {
		// Handle case where wp_upload_dir() might not be available (e.g., in tests)
		if ( function_exists( 'wp_upload_dir' ) ) {
			$upload_dir = wp_upload_dir();
			return trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';
		} else {
			// Fallback for test environments
			return sys_get_temp_dir() . '/meowseo-sitemaps';
		}
	}

	/**
	 * Ensure sitemap directory exists
	 *
	 * @return bool True on success, false on failure.
	 */
	private function ensure_directory_exists(): bool {
		if ( ! file_exists( $this->sitemap_dir ) ) {
			return wp_mkdir_p( $this->sitemap_dir );
		}
		return true;
	}

	/**
	 * Generate index sitemap
	 *
	 * @return string|false File path on success, false on failure.
	 */
	public function generate_index(): string|false {
		if ( ! $this->ensure_directory_exists() ) {
			// Log directory creation failure (Requirement 12.4)
			Logger::error(
				'Sitemap directory creation failed',
				array(
					'file_path' => $this->sitemap_dir,
					'error' => 'Failed to create sitemap directory',
				)
			);
			return false;
		}

		$post_types = $this->get_public_post_types();
		$xml = $this->build_index_xml( $post_types );

		$file_path = $this->sitemap_dir . '/sitemap-index.xml';
		
		if ( false === file_put_contents( $file_path, $xml ) ) {
			// Log file write failure (Requirement 12.4)
			Logger::error(
				'Sitemap file write failed',
				array(
					'file_path' => $file_path,
					'error' => 'Failed to write sitemap index file',
				)
			);
			return false;
		}

		return $file_path;
	}

	/**
	 * Generate child sitemap for a post type
	 *
	 * @param string $post_type Post type name.
	 * @return string|false File path on success, false on failure.
	 */
	public function generate_child( string $post_type ): string|false {
		if ( ! $this->ensure_directory_exists() ) {
			// Log directory creation failure (Requirement 12.4)
			Logger::error(
				'Sitemap directory creation failed',
				array(
					'file_path' => $this->sitemap_dir,
					'error' => 'Failed to create sitemap directory',
					'post_type' => $post_type,
				)
			);
			return false;
		}

		$posts = $this->get_posts_for_sitemap( $post_type );
		$xml = $this->build_child_xml( $posts );

		$file_path = $this->sitemap_dir . '/sitemap-' . $post_type . '.xml';
		
		if ( false === file_put_contents( $file_path, $xml ) ) {
			// Log file write failure (Requirement 12.4)
			Logger::error(
				'Sitemap file write failed',
				array(
					'file_path' => $file_path,
					'error' => 'Failed to write sitemap file',
					'post_type' => $post_type,
				)
			);
			return false;
		}

		return $file_path;
	}

	/**
	 * Build index sitemap XML
	 *
	 * @param array $post_types Array of post type names.
	 * @return string XML content.
	 */
	private function build_index_xml( array $post_types ): string {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		$site_url = trailingslashit( get_site_url() );

		foreach ( $post_types as $post_type ) {
			$xml .= "\t<sitemap>\n";
			$xml .= "\t\t<loc>" . esc_url( $site_url . 'meowseo-sitemap-' . $post_type . '.xml' ) . "</loc>\n";
			$xml .= "\t\t<lastmod>" . gmdate( 'Y-m-d\TH:i:s\+00:00' ) . "</lastmod>\n";
			$xml .= "\t</sitemap>\n";
		}

		$xml .= '</sitemapindex>';

		return $xml;
	}

	/**
	 * Build child sitemap XML
	 *
	 * @param array $posts Array of WP_Post objects.
	 * @return string XML content.
	 */
	private function build_child_xml( array $posts ): string {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

		foreach ( $posts as $post ) {
			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url( get_permalink( $post ) ) . "</loc>\n";
			$xml .= "\t\t<lastmod>" . gmdate( 'Y-m-d\TH:i:s\+00:00', strtotime( $post->post_modified_gmt ) ) . "</lastmod>\n";
			
			// Add priority based on post type
			$priority = 'page' === $post->post_type ? '0.8' : '0.6';
			$xml .= "\t\t<priority>" . $priority . "</priority>\n";

			// Add image entry if post has featured image (Requirement 6.7)
			$thumbnail_id = get_post_thumbnail_id( $post->ID );
			if ( $thumbnail_id ) {
				$image_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );
				if ( $image_url ) {
					$xml .= "\t\t<image:image>\n";
					$xml .= "\t\t\t<image:loc>" . esc_url( $image_url ) . "</image:loc>\n";
					$xml .= "\t\t</image:image>\n";
				}
			}

			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>';

		return $xml;
	}

	/**
	 * Get public post types for sitemap
	 *
	 * @return array Array of post type names.
	 */
	private function get_public_post_types(): array {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);

		// Exclude attachment post type
		unset( $post_types['attachment'] );

		return array_values( $post_types );
	}

	/**
	 * Get posts for sitemap
	 *
	 * Excludes noindex posts (Requirement 6.8).
	 * Applies WooCommerce product filtering (Requirement 12.3).
	 *
	 * @param string $post_type Post type name.
	 * @return array Array of WP_Post objects.
	 */
	private function get_posts_for_sitemap( string $post_type ): array {
		global $wpdb;

		// Query posts excluding noindex posts
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_type, p.post_modified_gmt
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'meowseo_noindex'
				WHERE p.post_type = %s
				AND p.post_status = 'publish'
				AND (pm.meta_value IS NULL OR pm.meta_value = '0' OR pm.meta_value = '')
				ORDER BY p.post_modified_gmt DESC
				LIMIT 50000",
				$post_type
			)
		);

		$posts = $posts ? $posts : array();

		// Apply WooCommerce product filtering if applicable (Requirement 12.3)
		$posts = apply_filters( 'meowseo_sitemap_posts', $posts, $post_type );

		return $posts;
	}

	/**
	 * Delete sitemap file
	 *
	 * @param string $type Sitemap type ('index' or post type name).
	 * @return bool True on success, false on failure.
	 */
	public function delete_sitemap( string $type ): bool {
		$file_name = 'index' === $type ? 'sitemap-index.xml' : 'sitemap-' . $type . '.xml';
		$file_path = $this->sitemap_dir . '/' . $file_name;

		if ( file_exists( $file_path ) ) {
			return unlink( $file_path );
		}

		return true;
	}
}
