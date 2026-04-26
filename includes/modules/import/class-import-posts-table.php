<?php
/**
 * Import Posts List Table.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Import;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Import_Posts_List_Table extends \WP_List_Table {

	private array $post_types;

	public function __construct( array $post_types = array() ) {
		$this->post_types = $post_types;
		parent::__construct( array(
			'singular' => 'post',
			'plural'   => 'posts',
			'ajax'     => false,
		) );
	}

	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'title'      => \__( 'Title', 'meowseo' ),
			'type'       => \__( 'Type', 'meowseo' ),
			'status'     => \__( 'Status', 'meowseo' ),
			'seo_status' => \__( 'SEO Status', 'meowseo' ),
		);
	}

	protected function get_sortable_columns() {
		return array(
			'title' => array( 'title', false ),
			'type'  => array( 'post_type', false ),
		);
	}

	protected function get_bulk_actions() {
		return array(
			'import_yoast'    => \__( 'Import from Yoast SEO', 'meowseo' ),
			'import_rankmath' => \__( 'Import from RankMath', 'meowseo' ),
		);
	}

	protected function get_views() {
		$current    = isset( $_GET['status'] ) ? \sanitize_text_field( $_GET['status'] ) : 'all';
		$post_types = $this->post_types ?: array_values( array_diff( \get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
		$base_url   = \admin_url( 'admin.php' );
		$tab        = isset( $_GET['tab'] ) ? \sanitize_text_field( $_GET['tab'] ) : 'posts';

		$counts = $this->get_counts_by_seo_status( $post_types );

		$make_url = function( $status ) use ( $base_url, $tab ) {
			return \add_query_arg( array( 'page' => 'meowseo-import', 'tab' => $tab, 'status' => $status ), $base_url );
		};

		return array(
			'all'      => sprintf( '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', esc_url( $make_url( 'all' ) ), 'all' === $current ? 'current' : '', \__( 'All', 'meowseo' ), $counts['all'] ),
			'pending'  => sprintf( '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', esc_url( $make_url( 'pending' ) ), 'pending' === $current ? 'current' : '', \__( 'Pending', 'meowseo' ), $counts['pending'] ),
			'imported' => sprintf( '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', esc_url( $make_url( 'imported' ) ), 'imported' === $current ? 'current' : '', \__( 'Imported', 'meowseo' ), $counts['imported'] ),
		);
	}

	private function get_counts_by_seo_status( array $post_types ): array {
		global $wpdb;

		if ( empty( $post_types ) ) {
			return array( 'all' => 0, 'pending' => 0, 'imported' => 0 );
		}

		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// Total posts count.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$all = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ($placeholders) AND post_status != 'auto-draft'",
			...$post_types
		) );

		// External SEO meta keys to look for.
		$external_keys = array(
			'_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw',
			'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword'
		);
		$in_external = "'" . implode( "','", array_map( 'esc_sql', $external_keys ) ) . "'";

		// Imported count: posts that have _meowseo_title OR _meowseo_description meta.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$imported = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
			WHERE p.post_type IN ($placeholders) 
			AND p.post_status != 'auto-draft'
			AND (pm.meta_key = '_meowseo_title' OR pm.meta_key = '_meowseo_description')",
			...$post_types
		) );

		// Pending count: posts that have Yoast/RankMath meta BUT NO MeowSEO meta.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pending = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_ext ON p.ID = pm_ext.post_id
			LEFT JOIN {$wpdb->postmeta} pm_meow ON p.ID = pm_meow.post_id AND (pm_meow.meta_key = '_meowseo_title' OR pm_meow.meta_key = '_meowseo_description')
			WHERE p.post_type IN ($placeholders)
			AND p.post_status != 'auto-draft'
			AND pm_ext.meta_key IN ($in_external)
			AND pm_meow.meta_id IS NULL",
			...$post_types
		) );

		return array(
			'all'      => $imported + $pending,
			'pending'  => $pending,
			'imported' => $imported,
		);
	}

	private function count_posts_by_seo_status( $status, $post_types ) {
		$counts = $this->get_counts_by_seo_status( (array) $post_types );
		return $counts[ $status ] ?? 0;
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'type':
				$post_type_obj = \get_post_type_object( $item->post_type );
				return $post_type_obj ? $post_type_obj->labels->singular_name : $item->post_type;
			case 'status':
				return $item->post_status;
			case 'seo_status':
				$is_imported = \get_post_meta( $item->ID, '_meowseo_title', true ) || \get_post_meta( $item->ID, '_meowseo_description', true );
				if ( $is_imported ) {
					return '<span style="color: green;">' . \__( 'Imported', 'meowseo' ) . '</span>';
				} else {
					return '<span style="color: gray;">' . \__( 'Pending', 'meowseo' ) . '</span>';
				}
			default:
				return print_r( $item, true );
		}
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="post[]" value="%s" />',
			$item->ID
		);
	}

	public function column_title( $item ) {
		$edit_link = \get_edit_post_link( $item->ID );
		$title = _draft_or_post_title( $item );
		if ( ! $title ) {
			$title = \__( '(no title)', 'meowseo' );
		}
		
		if ( $edit_link ) {
			return sprintf( '<strong><a class="row-title" href="%s" aria-label="%s">%s</a></strong>', esc_url( $edit_link ), esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ), esc_html( $title ) );
		}
		return sprintf( '<strong>%s</strong>', esc_html( $title ) );
	}

	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = $this->get_items_per_page( 'meowseo_import_per_page', 20 );
		$current_page = $this->get_pagenum();

		// Get post types to display.
		if ( empty( $this->post_types ) ) {
			// All public post types EXCEPT attachment.
			$post_types = \get_post_types( array( 'public' => true ) );
			unset( $post_types['attachment'] );
		} else {
			$post_types = $this->post_types;
		}

		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'any',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'orderby'        => ! empty( $_REQUEST['orderby'] ) ? \sanitize_text_field( $_REQUEST['orderby'] ) : 'ID',
			'order'          => ! empty( $_REQUEST['order'] ) ? \sanitize_text_field( $_REQUEST['order'] ) : 'DESC',
		);

		$status = isset( $_GET['status'] ) ? \sanitize_text_field( $_GET['status'] ) : 'all';
		
		$external_keys = array(
			'_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw',
			'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword'
		);

		if ( 'pending' === $status ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'relation' => 'OR', // Must have at least one external key.
					array( 'key' => '_yoast_wpseo_title', 'compare' => 'EXISTS' ),
					array( 'key' => '_yoast_wpseo_metadesc', 'compare' => 'EXISTS' ),
					array( 'key' => '_yoast_wpseo_focuskw', 'compare' => 'EXISTS' ),
					array( 'key' => 'rank_math_title', 'compare' => 'EXISTS' ),
					array( 'key' => 'rank_math_description', 'compare' => 'EXISTS' ),
					array( 'key' => 'rank_math_focus_keyword', 'compare' => 'EXISTS' ),
				),
				array(
					'key'     => '_meowseo_title',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_meowseo_description',
					'compare' => 'NOT EXISTS',
				),
			);
		} elseif ( 'imported' === $status ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array( 'key' => '_meowseo_title', 'compare' => 'EXISTS' ),
				array( 'key' => '_meowseo_description', 'compare' => 'EXISTS' ),
			);
		} else {
			// 'all' tab: Show either already imported OR has external data to import.
			$args['meta_query'] = array(
				'relation' => 'OR',
				array( 'key' => '_meowseo_title', 'compare' => 'EXISTS' ),
				array( 'key' => '_meowseo_description', 'compare' => 'EXISTS' ),
				array( 'key' => '_yoast_wpseo_title', 'compare' => 'EXISTS' ),
				array( 'key' => '_yoast_wpseo_metadesc', 'compare' => 'EXISTS' ),
				array( 'key' => '_yoast_wpseo_focuskw', 'compare' => 'EXISTS' ),
				array( 'key' => 'rank_math_title', 'compare' => 'EXISTS' ),
				array( 'key' => 'rank_math_description', 'compare' => 'EXISTS' ),
				array( 'key' => 'rank_math_focus_keyword', 'compare' => 'EXISTS' ),
			);
		}

		$query = new \WP_Query( $args );

		$this->items = $query->posts;

		$this->set_pagination_args( array(
			'total_items' => $query->found_posts,
			'per_page'    => $per_page,
			'total_pages' => ceil( $query->found_posts / $per_page ),
		) );
	}
}
