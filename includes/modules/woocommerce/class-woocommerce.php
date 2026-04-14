<?php
/**
 * WooCommerce Module
 *
 * Provides SEO enhancements specific to WooCommerce product pages.
 * Only loaded when WooCommerce is active.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\WooCommerce;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce module class
 *
 * Extends Meta and Schema modules for product post type.
 * Adds SEO score columns to WooCommerce product list table.
 *
 * @since 1.0.0
 */
class WooCommerce implements Module {

	/**
	 * Module ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const MODULE_ID = 'woocommerce';

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module
	 *
	 * Register hooks and initialize module functionality.
	 * (Requirement 12.1, 12.4)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		// Ensure WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Add SEO score columns to product list table (Requirement 12.4)
		add_filter( 'manage_product_posts_columns', array( $this, 'add_seo_score_column' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_seo_score_column' ), 10, 2 );
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'make_seo_score_sortable' ) );

		// Filter sitemap posts to exclude out-of-stock products (Requirement 12.3)
		add_filter( 'meowseo_sitemap_posts', array( $this, 'filter_sitemap_products' ), 10, 2 );
	}

	/**
	 * Get module ID
	 *
	 * @since 1.0.0
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return self::MODULE_ID;
	}

	/**
	 * Add SEO score column to product list table
	 *
	 * (Requirement 12.4)
	 *
	 * @since 1.0.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_seo_score_column( array $columns ): array {
		// Insert SEO score column after the product name
		$new_columns = array();
		
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			
			if ( 'name' === $key ) {
				$new_columns['meowseo_score'] = __( 'SEO Score', 'meowseo' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render SEO score column content
	 *
	 * (Requirement 12.4)
	 *
	 * @since 1.0.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_seo_score_column( string $column, int $post_id ): void {
		if ( 'meowseo_score' !== $column ) {
			return;
		}

		// Get Meta module to compute SEO score
		$plugin = \MeowSEO\Plugin::instance();
		$module_manager = $plugin->get_module_manager();
		$meta_module = $module_manager->get_module( 'meta' );

		if ( ! $meta_module ) {
			echo '<span style="color: #999;">—</span>';
			return;
		}

		// Get post content for analysis
		$post = get_post( $post_id );
		if ( ! $post ) {
			echo '<span style="color: #999;">—</span>';
			return;
		}

		// Get SEO analysis
		$analysis = $meta_module->get_seo_analysis( $post_id, $post->post_content );

		if ( empty( $analysis ) || ! isset( $analysis['score'], $analysis['color'] ) ) {
			echo '<span style="color: #999;">—</span>';
			return;
		}

		$score = $analysis['score'];
		$color = $analysis['color'];

		// Map color to hex
		$color_map = array(
			'red'    => '#dc3232',
			'orange' => '#f56e28',
			'green'  => '#46b450',
		);

		$hex_color = $color_map[ $color ] ?? '#999';

		// Render score indicator
		echo '<span style="display: inline-flex; align-items: center; gap: 6px;">';
		echo '<span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: ' . esc_attr( $hex_color ) . ';"></span>';
		echo '<span style="font-weight: 500;">' . esc_html( $score ) . '/100</span>';
		echo '</span>';
	}

	/**
	 * Make SEO score column sortable
	 *
	 * @since 1.0.0
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function make_seo_score_sortable( array $columns ): array {
		$columns['meowseo_score'] = 'meowseo_score';
		return $columns;
	}

	/**
	 * Filter sitemap products to exclude out-of-stock items
	 *
	 * (Requirement 12.3)
	 *
	 * @since 1.0.0
	 * @param array  $posts     Array of WP_Post objects.
	 * @param string $post_type Post type name.
	 * @return array Filtered posts.
	 */
	public function filter_sitemap_products( array $posts, string $post_type ): array {
		// Only filter product post type
		if ( 'product' !== $post_type ) {
			return $posts;
		}

		// Check if out-of-stock exclusion is enabled
		$exclude_out_of_stock = $this->options->get( 'woocommerce_exclude_out_of_stock', false );
		
		if ( ! $exclude_out_of_stock ) {
			return $posts;
		}

		// Filter out out-of-stock products
		return array_filter(
			$posts,
			function ( $post ) {
				$product = wc_get_product( $post->ID );
				
				if ( ! $product ) {
					return true; // Keep if product object can't be loaded
				}

				return $product->is_in_stock();
			}
		);
	}
}

