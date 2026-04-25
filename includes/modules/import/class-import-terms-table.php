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

		$per_page = 20;
		if ( isset( $_REQUEST['per_page'] ) && is_numeric( $_REQUEST['per_page'] ) ) {
			$per_page = intval( $_REQUEST['per_page'] );
		}
		
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

		$this->items = \get_terms( $args );

		// Get total count (using lightweight count array)
		$count_args = $args;
		unset( $count_args['number'] );
		unset( $count_args['offset'] );
		$count_args['count'] = true;
		
		$total_items = \get_terms( $count_args );
		if ( \is_wp_error( $total_items ) ) {
			$total_items = 0;
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}
}
