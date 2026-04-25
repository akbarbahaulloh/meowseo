<?php
/**
 * Import Terms List Table.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Import;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Import_Terms_List_Table extends \WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'term',
			'plural'   => 'terms',
			'ajax'     => false,
		) );
	}

	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'name'       => \__( 'Name', 'meowseo' ),
			'taxonomy'   => \__( 'Taxonomy', 'meowseo' ),
			'seo_status' => \__( 'SEO Status', 'meowseo' ),
		);
	}

	protected function get_sortable_columns() {
		return array(
			'name' => array( 'name', false ),
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
		$taxonomies = array_values( \get_taxonomies( array( 'public' => true ) ) );
		$base_url   = \admin_url( 'admin.php' );

		$counts = $this->get_term_counts_by_seo_status( $taxonomies );

		$make_url = function( $status ) use ( $base_url ) {
			return \add_query_arg( array( 'page' => 'meowseo-import', 'tab' => 'terms', 'status' => $status ), $base_url );
		};

		return array(
			'all'      => sprintf( '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', esc_url( $make_url( 'all' ) ), 'all' === $current ? 'current' : '', \__( 'All', 'meowseo' ), $counts['all'] ),
			'pending'  => sprintf( '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', esc_url( $make_url( 'pending' ) ), 'pending' === $current ? 'current' : '', \__( 'Pending', 'meowseo' ), $counts['pending'] ),
			'imported' => sprintf( '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', esc_url( $make_url( 'imported' ) ), 'imported' === $current ? 'current' : '', \__( 'Imported', 'meowseo' ), $counts['imported'] ),
		);
	}

	private function get_term_counts_by_seo_status( array $taxonomies ): array {
		global $wpdb;

		if ( empty( $taxonomies ) ) {
			return array( 'all' => 0, 'pending' => 0, 'imported' => 0 );
		}

		$in_tax = "'" . implode( "','", array_map( 'esc_sql', $taxonomies ) ) . "'";

		// Total count via SQL — very fast.
		$all = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ($in_tax)"
		);

		// Imported count: terms that have _meowseo_title termmeta.
		$imported = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT tt.term_id)
			FROM {$wpdb->term_taxonomy} tt
			INNER JOIN {$wpdb->termmeta} tm ON tt.term_id = tm.term_id AND tm.meta_key = '_meowseo_title'
			WHERE tt.taxonomy IN ($in_tax)"
		);

		return array(
			'all'      => $all,
			'pending'  => $all - $imported,
			'imported' => $imported,
		);
	}

	private function count_terms_by_seo_status( $status, $taxonomies ) {
		$counts = $this->get_term_counts_by_seo_status( (array) $taxonomies );
		return $counts[ $status ] ?? 0;
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'taxonomy':
				$tax_obj = \get_taxonomy( $item->taxonomy );
				return $tax_obj ? $tax_obj->labels->singular_name : $item->taxonomy;
			case 'seo_status':
				$is_imported = \get_term_meta( $item->term_id, '_meowseo_title', true ) || \get_term_meta( $item->term_id, '_meowseo_description', true );
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
			'<input type="checkbox" name="term[]" value="%s" />',
			$item->term_id
		);
	}

	public function column_name( $item ) {
		$edit_link = \get_edit_term_link( $item->term_id, $item->taxonomy );
		
		if ( $edit_link ) {
			return sprintf( '<strong><a class="row-title" href="%s">%s</a></strong>', esc_url( $edit_link ), esc_html( $item->name ) );
		}
		return sprintf( '<strong>%s</strong>', esc_html( $item->name ) );
	}

	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = $this->get_items_per_page( 'meowseo_import_per_page', 20 );
		$current_page = $this->get_pagenum();

		// Get all public taxonomies.
		$taxonomies = \get_taxonomies( array( 'public' => true ) );

		$args = array(
			'taxonomy'   => $taxonomies,
			'hide_empty' => false,
			'number'     => $per_page,
			'offset'     => ( $current_page - 1 ) * $per_page,
			'orderby'    => ! empty( $_REQUEST['orderby'] ) ? \sanitize_text_field( $_REQUEST['orderby'] ) : 'name',
			'order'      => ! empty( $_REQUEST['order'] ) ? \sanitize_text_field( $_REQUEST['order'] ) : 'ASC',
		);

		$status = isset( $_GET['status'] ) ? \sanitize_text_field( $_GET['status'] ) : 'all';
		if ( 'pending' === $status ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_meowseo_title',
					'compare' => 'NOT EXISTS',
				),
			);
		} elseif ( 'imported' === $status ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_meowseo_title',
					'compare' => 'EXISTS',
				),
			);
		}

		$this->items = \get_terms( $args );

		// Get total count (using lightweight count array)
		if ( 'all' === $status ) {
			global $wpdb;
			$in_tax      = "'" . implode( "','", array_map( 'esc_sql', $taxonomies ) ) . "'";
			$total_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ($in_tax)" );
		} else {
			$count_args = $args;
			$count_args['count'] = true;
			unset( $count_args['number'] );
			unset( $count_args['offset'] );
			$total_items = (int) \get_terms( $count_args );
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}
}
