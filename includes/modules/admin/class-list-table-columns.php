<?php
/**
 * List Table Columns class for adding SEO Score column to post list tables.
 *
 * Adds an SEO Score column to WordPress admin list tables with sorting support.
 * Displays colored indicators based on score ranges and handles column sorting.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Admin;

use MeowSEO\Options;
use MeowSEO\Helpers\DB;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * List_Table_Columns class.
 *
 * Adds SEO Score column to WordPress admin list tables with sorting support.
 */
class List_Table_Columns {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Post types to exclude from SEO Score column.
	 *
	 * @var array
	 */
	private const EXCLUDED_POST_TYPES = array(
		'attachment',
		'revision',
		'nav_menu_item',
	);

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Get all public post types.
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		foreach ( $post_types as $post_type ) {
			// Skip excluded post types.
			if ( in_array( $post_type, self::EXCLUDED_POST_TYPES, true ) ) {
				continue;
			}

			// Add column to list table.
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_seo_score_column' ) );

			// Render column content.
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_custom_columns' ), 10, 2 );

			// Register sortable column.
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'register_sortable_column' ) );
		}

		// Handle sorting query modification.
		add_action( 'pre_get_posts', array( $this, 'handle_column_sorting' ) );

		// Enqueue admin styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Add SEO Score column to list table.
	 *
	 * Positions the column after the "Title" column.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_seo_score_column( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Insert SEO columns after Title.
			if ( 'title' === $key ) {
				$new_columns['seo_score']         = '<span class="dashicons dashicons-admin-site-alt3" title="' . esc_attr__( 'SEO Score', 'meowseo' ) . '"></span>';
				$new_columns['readability_score'] = '<span class="dashicons dashicons-media-text" title="' . esc_attr__( 'Readability Score', 'meowseo' ) . '"></span>';
				$new_columns['outbound_links']    = '<span class="dashicons dashicons-external" title="' . esc_attr__( 'Outbound Links', 'meowseo' ) . '"></span>';
				$new_columns['internal_links']    = '<span class="dashicons dashicons-admin-links" title="' . esc_attr__( 'Internal Links', 'meowseo' ) . '"></span>';
				$new_columns['inbound_links']     = '<span class="dashicons dashicons-share-alt" title="' . esc_attr__( 'Inbound Links', 'meowseo' ) . '"></span>';
				$new_columns['broken_links']      = '<span class="dashicons dashicons-warning" title="' . esc_attr__( 'Broken Links', 'meowseo' ) . '" style="color: #dc3232;"></span>';
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 * @return void
	 */
	public function render_custom_columns( string $column_name, int $post_id ): void {
		switch ( $column_name ) {
			case 'seo_score':
				$this->render_seo_score_indicator( $post_id );
				break;
			case 'readability_score':
				$this->render_readability_score_indicator( $post_id );
				break;
			case 'outbound_links':
				$count = get_post_meta( $post_id, '_meowseo_outbound_links', true );
				echo ( '' === $count ) ? '0' : esc_html( $count );
				break;
			case 'internal_links':
				$count = get_post_meta( $post_id, '_meowseo_internal_links', true );
				echo ( '' === $count ) ? '0' : esc_html( $count );
				break;
			case 'inbound_links':
				$count = DB::get_inbound_link_count( $post_id );
				echo esc_html( $count );
				break;
			case 'broken_links':
				$this->render_broken_links_indicator( $post_id );
				break;
		}
	}

	/**
	 * Render SEO Score indicator.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_seo_score_indicator( int $post_id ): void {
		$score = get_post_meta( $post_id, '_meowseo_seo_score', true );

		if ( '' === $score || null === $score ) {
			echo '<span class="meowseo-score-indicator meowseo-score-none" '
				. 'title="' . esc_attr__( 'No SEO Score', 'meowseo' ) . '" '
				. 'aria-label="' . esc_attr__( 'No SEO Score', 'meowseo' ) . '">'
				. '<span class="meowseo-score-dash">—</span>'
				. '</span>';
			return;
		}

		$score = (int) $score;
		$color_class = $this->get_score_color_class( $score );

		echo '<span class="meowseo-score-indicator ' . esc_attr( $color_class ) . '" '
			. 'title="' . esc_attr( sprintf( __( 'SEO Score: %d/100', 'meowseo' ), $score ) ) . '" '
			. 'aria-label="' . esc_attr( sprintf( __( 'SEO Score: %d out of 100', 'meowseo' ), $score ) ) . '">'
			. '<span class="meowseo-score-circle"></span>'
			. '<span class="meowseo-score-text">' . esc_html( $score ) . '</span>'
			. '</span>';
	}

	/**
	 * Render Readability Score indicator.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_readability_score_indicator( int $post_id ): void {
		$score = get_post_meta( $post_id, '_meowseo_readability_score', true );

		if ( '' === $score || null === $score ) {
			echo '—';
			return;
		}

		$score = (int) $score;
		$color_class = $this->get_score_color_class( $score );

		echo '<span class="meowseo-score-indicator ' . esc_attr( $color_class ) . '" '
			. 'title="' . esc_attr( sprintf( __( 'Readability Score: %d/100', 'meowseo' ), $score ) ) . '">'
			. '<span class="meowseo-score-circle"></span>'
			. '</span>';
	}

	/**
	 * Get color class based on score range.
	 *
	 * @param int $score SEO score (0-100).
	 * @return string Color class name.
	 */
	private function get_score_color_class( int $score ): string {
		if ( $score >= 71 ) {
			return 'meowseo-score-good';
		} elseif ( $score >= 41 ) {
			return 'meowseo-score-ok';
		} else {
			return 'meowseo-score-poor';
		}
	}

	/**
	 * Register SEO Score as sortable column.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function register_sortable_column( array $columns ): array {
		$columns['seo_score']         = 'seo_score';
		$columns['readability_score'] = 'readability_score';
		$columns['outbound_links']    = 'outbound_links';
		$columns['internal_links']    = 'internal_links';
		return $columns;
	}

	/**
	 * Handle column sorting query modification.
	 *
	 * @param \WP_Query $query WordPress query object.
	 * @return void
	 */
	public function handle_column_sorting( \WP_Query $query ): void {
		// Only modify admin queries.
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		switch ( $orderby ) {
			case 'seo_score':
				$query->set( 'meta_key', '_meowseo_seo_score' );
				$query->set( 'orderby', 'meta_value_num' );
				break;
			case 'readability_score':
				$query->set( 'meta_key', '_meowseo_readability_score' );
				$query->set( 'orderby', 'meta_value_num' );
				break;
			case 'outbound_links':
				$query->set( 'meta_key', '_meowseo_outbound_links' );
				$query->set( 'orderby', 'meta_value_num' );
				break;
			case 'internal_links':
				$query->set( 'meta_key', '_meowseo_internal_links' );
				$query->set( 'orderby', 'meta_value_num' );
				break;
		}
	}

	/**
	 * Enqueue admin styles for list table columns.
	 *
	 * Only enqueues on admin list table pages.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_styles( string $hook_suffix ): void {
		// Only enqueue on edit.php pages (list tables).
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}

		// Get the plugin directory URL.
		$plugin_url = defined( 'MEOWSEO_URL' ) ? MEOWSEO_URL : plugin_dir_url( dirname( __FILE__, 3 ) );

		// Enqueue the stylesheet.
		wp_enqueue_style(
			'meowseo-list-table-columns',
			$plugin_url . 'admin/css/list-table-columns.css',
			array(),
			defined( 'MEOWSEO_VERSION' ) ? MEOWSEO_VERSION : '1.0.0',
			'all'
		);
	}

	/**
	 * Render broken links indicator.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_broken_links_indicator( int $post_id ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_link_checks';
		
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE source_post_id = %d AND is_broken = 1",
			$post_id
		) );

		if ( $count > 0 ) {
			echo sprintf(
				'<a href="%s" class="meowseo-broken-count" style="color: #dc3232; font-weight: bold; text-decoration: none;" title="%s">%d</a>',
				esc_url( admin_url( 'admin.php?page=meowseo-broken-links&s=' . $post_id ) ),
				esc_attr__( 'View broken links', 'meowseo' ),
				absint( $count )
			);
		} else {
			echo '<span style="color: #46b450;">✓</span>';
		}
	}
}
