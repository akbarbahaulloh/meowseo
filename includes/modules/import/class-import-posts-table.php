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

		$query = new \WP_Query( $args );

		$this->items = $query->posts;

		$this->set_pagination_args( array(
			'total_items' => $query->found_posts,
			'per_page'    => $per_page,
			'total_pages' => ceil( $query->found_posts / $per_page ),
		) );
	}
}
