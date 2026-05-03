<?php
/**
 * Review Schema Type
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
 * Review_Schema class.
 */
class Review_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Review';
		$this->label       = __( 'Review', 'meowseo' );
		$this->description = __( 'A review of an item, such as a product, service, or creative work.', 'meowseo' );
		$this->icon        = 'star-filled';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'itemReviewed' => array(
				'type'        => 'group',
				'label'       => __( 'Item Reviewed', 'meowseo' ),
				'description' => __( 'The item that is being reviewed', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
					'@type' => array(
						'type'        => 'select',
						'label'       => __( 'Item Type', 'meowseo' ),
						'description' => __( 'The type of item being reviewed', 'meowseo' ),
						'default'     => 'Product',
						'options'     => array(
							'Product'           => __( 'Product', 'meowseo' ),
							'Service'           => __( 'Service', 'meowseo' ),
							'Book'              => __( 'Book', 'meowseo' ),
							'Movie'             => __( 'Movie', 'meowseo' ),
							'Restaurant'        => __( 'Restaurant', 'meowseo' ),
							'LocalBusiness'     => __( 'Local Business', 'meowseo' ),
							'Organization'      => __( 'Organization', 'meowseo' ),
							'CreativeWork'      => __( 'Creative Work', 'meowseo' ),
							'Event'             => __( 'Event', 'meowseo' ),
							'SoftwareApplication' => __( 'Software Application', 'meowseo' ),
						),
					),
					'name'  => array(
						'type'        => 'text',
						'label'       => __( 'Item Name', 'meowseo' ),
						'description' => __( 'The name of the item being reviewed', 'meowseo' ),
						'required'    => true,
					),
					'image' => array(
						'type'        => 'image',
						'label'       => __( 'Item Image', 'meowseo' ),
						'description' => __( 'Image of the item being reviewed', 'meowseo' ),
					),
				),
			),
			'reviewRating' => array(
				'type'        => 'group',
				'label'       => __( 'Rating', 'meowseo' ),
				'description' => __( 'The rating given in this review', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
					'@type'       => array(
						'type'    => 'hidden',
						'default' => 'Rating',
					),
					'ratingValue' => array(
						'type'        => 'number',
						'label'       => __( 'Rating Value', 'meowseo' ),
						'description' => __( 'The rating value (e.g., 4.5)', 'meowseo' ),
						'required'    => true,
						'step'        => 0.1,
						'min'         => 0,
						'max'         => 5,
					),
					'bestRating'  => array(
						'type'        => 'number',
						'label'       => __( 'Best Rating', 'meowseo' ),
						'description' => __( 'The highest rating value', 'meowseo' ),
						'default'     => 5,
					),
					'worstRating' => array(
						'type'        => 'number',
						'label'       => __( 'Worst Rating', 'meowseo' ),
						'description' => __( 'The lowest rating value', 'meowseo' ),
						'default'     => 1,
					),
				),
			),
			'author'       => array(
				'type'        => 'group',
				'label'       => __( 'Author', 'meowseo' ),
				'description' => __( 'The author of the review', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
					'@type' => array(
						'type'    => 'select',
						'label'   => __( 'Type', 'meowseo' ),
						'default' => 'Person',
						'options' => array(
							'Person'       => __( 'Person', 'meowseo' ),
							'Organization' => __( 'Organization', 'meowseo' ),
						),
					),
					'name'  => array(
						'type'     => 'text',
						'label'    => __( 'Name', 'meowseo' ),
						'default'  => '%author%',
						'required' => true,
					),
				),
			),
			'reviewBody'   => array(
				'type'        => 'textarea',
				'label'       => __( 'Review Body', 'meowseo' ),
				'description' => __( 'The actual body of the review', 'meowseo' ),
				'default'     => '%content%',
				'rows'        => 5,
			),
			'datePublished' => array(
				'type'        => 'text',
				'label'       => __( 'Date Published', 'meowseo' ),
				'description' => __( 'The date the review was published', 'meowseo' ),
				'default'     => '%date(Y-m-d)%',
			),
			'publisher'    => array(
				'type'        => 'group',
				'label'       => __( 'Publisher', 'meowseo' ),
				'description' => __( 'The publisher of the review', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'select',
						'label'   => __( 'Type', 'meowseo' ),
						'default' => 'Organization',
						'options' => array(
							'Organization' => __( 'Organization', 'meowseo' ),
							'Person'       => __( 'Person', 'meowseo' ),
						),
					),
					'name'  => array(
						'type'    => 'text',
						'label'   => __( 'Name', 'meowseo' ),
						'default' => '%sitename%',
					),
				),
			),
			'positiveNotes' => array(
				'type'        => 'group',
				'label'       => __( 'Positive Notes', 'meowseo' ),
				'description' => __( 'Positive aspects of the review', 'meowseo' ),
				'fields'      => array(
					'@type'           => array(
						'type'    => 'hidden',
						'default' => 'ItemList',
					),
					'itemListElement' => array(
						'type'        => 'repeater',
						'label'       => __( 'Positive Points', 'meowseo' ),
						'item_type'   => 'text',
						'placeholder' => 'Great quality',
					),
				),
			),
			'negativeNotes' => array(
				'type'        => 'group',
				'label'       => __( 'Negative Notes', 'meowseo' ),
				'description' => __( 'Negative aspects of the review', 'meowseo' ),
				'fields'      => array(
					'@type'           => array(
						'type'    => 'hidden',
						'default' => 'ItemList',
					),
					'itemListElement' => array(
						'type'        => 'repeater',
						'label'       => __( 'Negative Points', 'meowseo' ),
						'item_type'   => 'text',
						'placeholder' => 'Expensive',
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

		// Set default item reviewed.
		$defaults['itemReviewed'] = array(
			'@type' => 'Product',
			'name'  => '',
		);

		// Set default rating.
		$defaults['reviewRating'] = array(
			'@type'       => 'Rating',
			'ratingValue' => 5,
			'bestRating'  => 5,
			'worstRating' => 1,
		);

		// Set default author.
		$defaults['author'] = array(
			'@type' => 'Person',
			'name'  => '%author%',
		);

		// Set default publisher.
		$defaults['publisher'] = array(
			'@type' => 'Organization',
			'name'  => '%sitename%',
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$review = new Review_Schema();
	$review->register();
} );
