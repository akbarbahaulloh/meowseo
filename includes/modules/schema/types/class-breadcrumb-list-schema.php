<?php
/**
 * BreadcrumbList Schema Type
 *
 * @package MeowSEO
 * @subpackage Modules\Schema\Types
 */

namespace MeowSEO\Modules\Schema\Types;

use MeowSEO\Modules\Schema\Schema_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Breadcrumb_List_Schema class.
 */
class Breadcrumb_List_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'BreadcrumbList';
		$this->label       = __( 'Breadcrumb List', 'meowseo' );
		$this->description = __( 'A breadcrumb trail showing the page hierarchy. Helps users understand site structure in search results.', 'meowseo' );
		$this->icon        = 'arrow-right-alt';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'itemListElement' => array(
				'type'        => 'repeater',
				'label'       => __( 'Breadcrumb Items', 'meowseo' ),
				'description' => __( 'The breadcrumb trail items in order', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
					'@type'    => array(
						'type'    => 'hidden',
						'default' => 'ListItem',
					),
					'position' => array(
						'type'        => 'number',
						'label'       => __( 'Position', 'meowseo' ),
						'description' => __( 'Position in the breadcrumb trail (1, 2, 3...)', 'meowseo' ),
						'required'    => true,
					),
					'name'     => array(
						'type'        => 'text',
						'label'       => __( 'Name', 'meowseo' ),
						'description' => __( 'The name of the breadcrumb item', 'meowseo' ),
						'required'    => true,
					),
					'item'     => array(
						'type'        => 'url',
						'label'       => __( 'URL', 'meowseo' ),
						'description' => __( 'The URL of the breadcrumb item', 'meowseo' ),
						'required'    => true,
					),
				),
			),
		);
	}

	/**
	 * Get default schema data.
	 *
	 * @param object|null $object Post or term object.
	 * @return array
	 */
	public function get_defaults( $object = null ): array {
		$defaults = parent::get_defaults( $object );

		// Auto-generate breadcrumb trail if object is provided.
		if ( $object && isset( $object->ID ) ) {
			$breadcrumbs = $this->generate_breadcrumb_trail( $object );
			if ( ! empty( $breadcrumbs ) ) {
				$defaults['itemListElement'] = $breadcrumbs;
			}
		} else {
			// Default structure with home page.
			$defaults['itemListElement'] = array(
				array(
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => get_bloginfo( 'name' ),
					'item'     => home_url( '/' ),
				),
			);
		}

		return $defaults;
	}

	/**
	 * Generate breadcrumb trail for a post.
	 *
	 * @param object $post Post object.
	 * @return array
	 */
	private function generate_breadcrumb_trail( $post ): array {
		$breadcrumbs = array();
		$position    = 1;

		// Home page.
		$breadcrumbs[] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => get_bloginfo( 'name' ),
			'item'     => home_url( '/' ),
		);

		// Post type archive (if not 'post').
		if ( 'post' !== $post->post_type ) {
			$post_type_object = get_post_type_object( $post->post_type );
			if ( $post_type_object && $post_type_object->has_archive ) {
				$breadcrumbs[] = array(
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => $post_type_object->labels->name,
					'item'     => get_post_type_archive_link( $post->post_type ),
				);
			}
		}

		// Categories (for posts).
		if ( 'post' === $post->post_type ) {
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) ) {
				$category = $categories[0];
				
				// Get parent categories.
				$parent_cats = array();
				$parent_id   = $category->parent;
				while ( $parent_id ) {
					$parent_cat    = get_category( $parent_id );
					$parent_cats[] = $parent_cat;
					$parent_id     = $parent_cat->parent;
				}
				
				// Add parent categories in reverse order.
				$parent_cats = array_reverse( $parent_cats );
				foreach ( $parent_cats as $parent_cat ) {
					$breadcrumbs[] = array(
						'@type'    => 'ListItem',
						'position' => $position++,
						'name'     => $parent_cat->name,
						'item'     => get_category_link( $parent_cat->term_id ),
					);
				}
				
				// Add current category.
				$breadcrumbs[] = array(
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => $category->name,
					'item'     => get_category_link( $category->term_id ),
				);
			}
		}

		// Parent pages (for hierarchical post types).
		if ( $post->post_parent ) {
			$parent_ids = array();
			$parent_id  = $post->post_parent;
			
			while ( $parent_id ) {
				$parent_ids[] = $parent_id;
				$parent       = get_post( $parent_id );
				$parent_id    = $parent->post_parent;
			}
			
			// Add parent pages in reverse order.
			$parent_ids = array_reverse( $parent_ids );
			foreach ( $parent_ids as $parent_id ) {
				$parent        = get_post( $parent_id );
				$breadcrumbs[] = array(
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => get_the_title( $parent ),
					'item'     => get_permalink( $parent ),
				);
			}
		}

		// Current page.
		$breadcrumbs[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => get_the_title( $post ),
			'item'     => get_permalink( $post ),
		);

		return $breadcrumbs;
	}

	/**
	 * Generate JSON-LD output.
	 *
	 * @param array       $data   Schema data.
	 * @param object|null $object Post or term object.
	 * @return array
	 */
	public function generate( array $data, $object = null ): array {
		$schema = parent::generate( $data, $object );

		// Ensure itemListElement is properly formatted.
		if ( ! empty( $schema['itemListElement'] ) && is_array( $schema['itemListElement'] ) ) {
			// Sort by position.
			usort( $schema['itemListElement'], function( $a, $b ) {
				return ( $a['position'] ?? 0 ) - ( $b['position'] ?? 0 );
			});
		}

		return $schema;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$breadcrumb = new Breadcrumb_List_Schema();
	$breadcrumb->register();
} );
