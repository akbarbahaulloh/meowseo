<?php
/**
 * Product Schema Type
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
 * Product_Schema class.
 */
class Product_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Product';
		$this->label       = __( 'Product', 'meowseo' );
		$this->description = __( 'Any offered product or service. For example: a pair of shoes, a concert ticket, or a car.', 'meowseo' );
		$this->icon        = 'products';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'name'        => array(
				'type'        => 'text',
				'label'       => __( 'Name', 'meowseo' ),
				'description' => __( 'The name of the product', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description' => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the product', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'       => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Product image', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'brand'       => array(
				'type'        => 'group',
				'label'       => __( 'Brand', 'meowseo' ),
				'description' => __( 'The brand of the product', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'select',
						'label'   => __( 'Type', 'meowseo' ),
						'default' => 'Brand',
						'options' => array(
							'Brand'        => __( 'Brand', 'meowseo' ),
							'Organization' => __( 'Organization', 'meowseo' ),
						),
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Brand Name', 'meowseo' ),
					),
				),
			),
			'sku'         => array(
				'type'        => 'text',
				'label'       => __( 'SKU', 'meowseo' ),
				'description' => __( 'The Stock Keeping Unit (SKU)', 'meowseo' ),
			),
			'mpn'         => array(
				'type'        => 'text',
				'label'       => __( 'MPN', 'meowseo' ),
				'description' => __( 'The Manufacturer Part Number (MPN)', 'meowseo' ),
			),
			'gtin'        => array(
				'type'        => 'text',
				'label'       => __( 'GTIN', 'meowseo' ),
				'description' => __( 'Global Trade Item Number (GTIN-8, GTIN-13, GTIN-14)', 'meowseo' ),
			),
			'offers'      => array(
				'type'        => 'group',
				'label'       => __( 'Offers', 'meowseo' ),
				'description' => __( 'An offer to sell the product', 'meowseo' ),
				'fields'      => array(
					'@type'         => array(
						'type'    => 'hidden',
						'default' => 'Offer',
					),
					'price'         => array(
						'type'        => 'number',
						'label'       => __( 'Price', 'meowseo' ),
						'description' => __( 'The offer price', 'meowseo' ),
						'required'    => true,
					),
					'priceCurrency' => array(
						'type'        => 'text',
						'label'       => __( 'Currency', 'meowseo' ),
						'description' => __( 'The currency of the price (e.g., USD, EUR)', 'meowseo' ),
						'default'     => 'USD',
						'required'    => true,
					),
					'priceValidUntil' => array(
						'type'        => 'date',
						'label'       => __( 'Price Valid Until', 'meowseo' ),
						'description' => __( 'The date after which the price is no longer valid', 'meowseo' ),
					),
					'availability'  => array(
						'type'        => 'select',
						'label'       => __( 'Availability', 'meowseo' ),
						'description' => __( 'The availability of the product', 'meowseo' ),
						'default'     => 'https://schema.org/InStock',
						'options'     => array(
							'https://schema.org/InStock'              => __( 'In Stock', 'meowseo' ),
							'https://schema.org/OutOfStock'           => __( 'Out of Stock', 'meowseo' ),
							'https://schema.org/PreOrder'             => __( 'Pre Order', 'meowseo' ),
							'https://schema.org/Discontinued'         => __( 'Discontinued', 'meowseo' ),
							'https://schema.org/LimitedAvailability'  => __( 'Limited Availability', 'meowseo' ),
							'https://schema.org/OnlineOnly'           => __( 'Online Only', 'meowseo' ),
							'https://schema.org/InStoreOnly'          => __( 'In Store Only', 'meowseo' ),
							'https://schema.org/SoldOut'              => __( 'Sold Out', 'meowseo' ),
						),
					),
					'url'           => array(
						'type'        => 'url',
						'label'       => __( 'URL', 'meowseo' ),
						'description' => __( 'The URL where the offer can be acquired', 'meowseo' ),
						'default'     => '%permalink%',
					),
					'seller'        => array(
						'type'        => 'group',
						'label'       => __( 'Seller', 'meowseo' ),
						'description' => __( 'The seller of the product', 'meowseo' ),
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
				),
			),
			'aggregateRating' => array(
				'type'        => 'group',
				'label'       => __( 'Aggregate Rating', 'meowseo' ),
				'description' => __( 'The overall rating based on multiple ratings', 'meowseo' ),
				'fields'      => array(
					'@type'       => array(
						'type'    => 'hidden',
						'default' => 'AggregateRating',
					),
					'ratingValue' => array(
						'type'        => 'number',
						'label'       => __( 'Rating Value', 'meowseo' ),
						'description' => __( 'The rating value (e.g., 4.5)', 'meowseo' ),
						'step'        => 0.1,
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
					'ratingCount' => array(
						'type'        => 'number',
						'label'       => __( 'Rating Count', 'meowseo' ),
						'description' => __( 'The total number of ratings', 'meowseo' ),
					),
					'reviewCount' => array(
						'type'        => 'number',
						'label'       => __( 'Review Count', 'meowseo' ),
						'description' => __( 'The total number of reviews', 'meowseo' ),
					),
				),
			),
			'review'      => array(
				'type'        => 'repeater',
				'label'       => __( 'Reviews', 'meowseo' ),
				'description' => __( 'Individual reviews of the product', 'meowseo' ),
				'fields'      => array(
					'@type'        => array(
						'type'    => 'hidden',
						'default' => 'Review',
					),
					'author'       => array(
						'type'   => 'group',
						'label'  => __( 'Author', 'meowseo' ),
						'fields' => array(
							'@type' => array(
								'type'    => 'hidden',
								'default' => 'Person',
							),
							'name'  => array(
								'type'  => 'text',
								'label' => __( 'Name', 'meowseo' ),
							),
						),
					),
					'reviewRating' => array(
						'type'   => 'group',
						'label'  => __( 'Rating', 'meowseo' ),
						'fields' => array(
							'@type'       => array(
								'type'    => 'hidden',
								'default' => 'Rating',
							),
							'ratingValue' => array(
								'type'  => 'number',
								'label' => __( 'Rating Value', 'meowseo' ),
								'step'  => 0.1,
							),
							'bestRating'  => array(
								'type'    => 'number',
								'label'   => __( 'Best Rating', 'meowseo' ),
								'default' => 5,
							),
						),
					),
					'reviewBody'   => array(
						'type'  => 'textarea',
						'label' => __( 'Review Body', 'meowseo' ),
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

		// Set default offers.
		$defaults['offers'] = array(
			'@type'         => 'Offer',
			'price'         => '',
			'priceCurrency' => 'USD',
			'availability'  => 'https://schema.org/InStock',
			'url'           => '%permalink%',
			'seller'        => array(
				'@type' => 'Organization',
				'name'  => '%sitename%',
			),
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$product = new Product_Schema();
	$product->register();
} );
