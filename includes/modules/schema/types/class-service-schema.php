<?php
/**
 * Service Schema Type
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
 * Service_Schema class.
 */
class Service_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Service';
		$this->label       = __( 'Service', 'meowseo' );
		$this->description = __( 'A service offered by an organization or person. Perfect for service pages and business offerings.', 'meowseo' );
		$this->icon        = 'admin-tools';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'name'           => array(
				'type'        => 'text',
				'label'       => __( 'Service Name', 'meowseo' ),
				'description' => __( 'The name of the service', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'    => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the service', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'          => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Image representing the service', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'provider'       => array(
				'type'        => 'group',
				'label'       => __( 'Service Provider', 'meowseo' ),
				'description' => __( 'The organization or person providing the service', 'meowseo' ),
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
						'type'  => 'text',
						'label' => __( 'Name', 'meowseo' ),
					),
					'url'   => array(
						'type'  => 'url',
						'label' => __( 'Website URL', 'meowseo' ),
					),
					'telephone' => array(
						'type'  => 'text',
						'label' => __( 'Phone Number', 'meowseo' ),
					),
				),
			),
			'serviceType'    => array(
				'type'        => 'text',
				'label'       => __( 'Service Type', 'meowseo' ),
				'description' => __( 'The type of service being offered', 'meowseo' ),
			),
			'category'       => array(
				'type'        => 'text',
				'label'       => __( 'Category', 'meowseo' ),
				'description' => __( 'Category of the service', 'meowseo' ),
			),
			'areaServed'     => array(
				'type'        => 'repeater',
				'label'       => __( 'Area Served', 'meowseo' ),
				'description' => __( 'Geographic areas where the service is provided', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'select',
						'label'   => __( 'Type', 'meowseo' ),
						'default' => 'City',
						'options' => array(
							'City'    => __( 'City', 'meowseo' ),
							'State'   => __( 'State', 'meowseo' ),
							'Country' => __( 'Country', 'meowseo' ),
						),
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Location Name', 'meowseo' ),
					),
				),
			),
			'availableChannel' => array(
				'type'        => 'group',
				'label'       => __( 'Available Channel', 'meowseo' ),
				'description' => __( 'How the service can be accessed', 'meowseo' ),
				'fields'      => array(
					'@type'            => array(
						'type'    => 'hidden',
						'default' => 'ServiceChannel',
					),
					'serviceUrl'       => array(
						'type'  => 'url',
						'label' => __( 'Service URL', 'meowseo' ),
					),
					'servicePhone'     => array(
						'type'  => 'text',
						'label' => __( 'Service Phone', 'meowseo' ),
					),
					'availableLanguage' => array(
						'type'  => 'text',
						'label' => __( 'Available Language', 'meowseo' ),
					),
				),
			),
			'offers'         => array(
				'type'        => 'group',
				'label'       => __( 'Offers', 'meowseo' ),
				'description' => __( 'Pricing information for the service', 'meowseo' ),
				'fields'      => array(
					'@type'         => array(
						'type'    => 'hidden',
						'default' => 'Offer',
					),
					'price'         => array(
						'type'  => 'text',
						'label' => __( 'Price', 'meowseo' ),
					),
					'priceCurrency' => array(
						'type'    => 'text',
						'label'   => __( 'Currency', 'meowseo' ),
						'default' => 'USD',
					),
					'priceSpecification' => array(
						'type'   => 'group',
						'label'  => __( 'Price Specification', 'meowseo' ),
						'fields' => array(
							'@type'       => array(
								'type'    => 'hidden',
								'default' => 'UnitPriceSpecification',
							),
							'price'       => array(
								'type'  => 'text',
								'label' => __( 'Price', 'meowseo' ),
							),
							'priceCurrency' => array(
								'type'    => 'text',
								'label'   => __( 'Currency', 'meowseo' ),
								'default' => 'USD',
							),
							'referenceQuantity' => array(
								'type'   => 'group',
								'label'  => __( 'Reference Quantity', 'meowseo' ),
								'fields' => array(
									'@type'    => array(
										'type'    => 'hidden',
										'default' => 'QuantitativeValue',
									),
									'value'    => array(
										'type'  => 'number',
										'label' => __( 'Value', 'meowseo' ),
									),
									'unitCode' => array(
										'type'    => 'select',
										'label'   => __( 'Unit', 'meowseo' ),
										'options' => array(
											'HUR' => __( 'Hour', 'meowseo' ),
											'DAY' => __( 'Day', 'meowseo' ),
											'MON' => __( 'Month', 'meowseo' ),
											'ANN' => __( 'Year', 'meowseo' ),
										),
									),
								),
							),
						),
					),
				),
			),
			'hoursAvailable' => array(
				'type'        => 'repeater',
				'label'       => __( 'Hours Available', 'meowseo' ),
				'description' => __( 'Hours when the service is available', 'meowseo' ),
				'fields'      => array(
					'@type'     => array(
						'type'    => 'hidden',
						'default' => 'OpeningHoursSpecification',
					),
					'dayOfWeek' => array(
						'type'    => 'select',
						'label'   => __( 'Day of Week', 'meowseo' ),
						'options' => array(
							'Monday'    => __( 'Monday', 'meowseo' ),
							'Tuesday'   => __( 'Tuesday', 'meowseo' ),
							'Wednesday' => __( 'Wednesday', 'meowseo' ),
							'Thursday'  => __( 'Thursday', 'meowseo' ),
							'Friday'    => __( 'Friday', 'meowseo' ),
							'Saturday'  => __( 'Saturday', 'meowseo' ),
							'Sunday'    => __( 'Sunday', 'meowseo' ),
						),
					),
					'opens'     => array(
						'type'  => 'text',
						'label' => __( 'Opens', 'meowseo' ),
					),
					'closes'    => array(
						'type'  => 'text',
						'label' => __( 'Closes', 'meowseo' ),
					),
				),
			),
			'aggregateRating' => array(
				'type'        => 'group',
				'label'       => __( 'Aggregate Rating', 'meowseo' ),
				'description' => __( 'The overall rating of the service', 'meowseo' ),
				'fields'      => array(
					'@type'       => array(
						'type'    => 'hidden',
						'default' => 'AggregateRating',
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
					'ratingCount' => array(
						'type'  => 'number',
						'label' => __( 'Rating Count', 'meowseo' ),
					),
				),
			),
			'termsOfService' => array(
				'type'        => 'url',
				'label'       => __( 'Terms of Service', 'meowseo' ),
				'description' => __( 'URL to the terms of service', 'meowseo' ),
			),
			'slogan'         => array(
				'type'        => 'text',
				'label'       => __( 'Slogan', 'meowseo' ),
				'description' => __( 'A slogan or tagline for the service', 'meowseo' ),
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

		$defaults['provider'] = array(
			'@type'     => 'Organization',
			'name'      => '',
			'url'       => '',
			'telephone' => '',
		);

		$defaults['offers'] = array(
			'@type'         => 'Offer',
			'price'         => '',
			'priceCurrency' => 'USD',
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$service = new Service_Schema();
	$service->register();
} );
