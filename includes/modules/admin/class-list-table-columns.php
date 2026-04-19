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
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_seo_score_column' ), 10, 2 );

			// Register sortable column.
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'register_sortable_column' ) );
		}

		// Handle sorting query modification.
		add_action( 'pre_get_posts', array( $this, 'handle_seo_score_sorting' ) );

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

			// Insert SEO Score column after Title.
			if ( 'title' === $key ) {
				$new_columns['seo_score'] = __( 'SEO Score', 'meowseo' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render SEO Score column content.
	 *
	 * Displays a colored indicator based on score range.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 * @return void
	 */
	public function render_seo_score_column( string $column_name, int $post_id ): void {
		if ( 'seo_score' !== $column_name ) {
			return;
		}

		// Retrieve SEO score from postmeta.
		$score = get_post_meta( $post_id, '_meowseo_seo_score', true );

		// Handle null/empty scores.
		if ( '' === $score || null === $score ) {
			echo '<span class="meowseo-score-indicator meowseo-score-none" '
				. 'title="' . esc_attr__( 'No SEO Score', 'meowseo' ) . '" '
				. 'aria-label="' . esc_attr__( 'No SEO Score', 'meowseo' ) . '">'
				. '<span class="meowseo-score-dash">—</span>'
				. '</span>';
			return;
		}

		// Convert to integer.
		$score = (int) $score;

		// Determine color class based on score range.
		$color_class = $this->get_score_color_class( $score );

		// Render score indicator.
		echo '<span class="meowseo-score-indicator ' . esc_attr( $color_class ) . '" '
			. 'title="' . esc_attr( sprintf( __( 'SEO Score: %d/100', 'meowseo' ), $score ) ) . '" '
			. 'aria-label="' . esc_attr( sprintf( __( 'SEO Score: %d out of 100', 'meowseo' ), $score ) ) . '">'
			. '<span class="meowseo-score-circle"></span>'
			. '<span class="meowseo-score-text">' . esc_html( $score ) . '</span>'
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
		$columns['seo_score'] = 'seo_score';
		return $columns;
	}

	/**
	 * Handle SEO Score sorting query modification.
	 *
	 * Modifies the query to sort by _meowseo_seo_score postmeta.
	 *
	 * @param \WP_Query $query WordPress query object.
	 * @return void
	 */
	public function handle_seo_score_sorting( \WP_Query $query ): void {
		// Only modify admin queries.
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Check if sorting by SEO score.
		$orderby = $query->get( 'orderby' );
		if ( 'seo_score' !== $orderby ) {
			return;
		}

		// Set meta query for sorting.
		$query->set( 'meta_key', '_meowseo_seo_score' );
		$query->set( 'orderby', 'meta_value_num' );
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
}
