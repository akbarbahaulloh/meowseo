<?php
/**
 * Restaurant Schema Type
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
 * Restaurant_Schema class.
 */
class Restaurant_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Restaurant';
		$this->label       = __( 'Restaurant', 'meowseo' );
		$this->description = __( 'A restaurant or food establishment. Perfect for restaurant websites and food business pages.', 'meowseo' );
		$this->icon        = 'food';
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
				'label'       => __( 'Restaurant Name', 'meowseo' ),
				'description' => __( 'The name of the restaurant', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'    => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the restaurant', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'          => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Image of the restaurant', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'address'        => array(
				'type'        => 'group',
				'label'       => __( 'Address', 'meowseo' ),
				'description' => __( 'Physical address of the restaurant', 'meowseo' ),
				'fields'      => array(
					'@type'           => array(
						'type'    => 'hidden',
						'default' => 'PostalAddress',
					),
					'streetAddress'   => array(
						'type'  => 'text',
						'label' => __( 'Street Address', 'meowseo' ),
					),
					'addressLocality' => array(
						'type'  => 'text',
						'label' => __( 'City', 'meowseo' ),
					),
					'addressRegion'   => array(
						'type'  => 'text',
						'label' => __( 'State/Region', 'meowseo' ),
					),
					'postalCode'      => array(
						'type'  => 'text',
						'label' => __( 'Postal Code', 'meowseo' ),
					),
					'addressCountry'  => array(
						'type'  => 'text',
						'label' => __( 'Country', 'meowseo' ),
					),
				),
			),
			'telephone'      => array(
				'type'        => 'text',
				'label'       => __( 'Telephone', 'meowseo' ),
				'description' => __( 'Phone number of the restaurant', 'meowseo' ),
			),
			'url'            => array(
				'type'        => 'url',
				'label'       => __( 'Website URL', 'meowseo' ),
				'description' => __( 'Website URL of the restaurant', 'meowseo' ),
				'default'     => '%url%',
			),
			'servesCuisine'  => array(
				'type'        => 'text',
				'label'       => __( 'Cuisine Type', 'meowseo' ),
				'description' => __( 'Type of cuisine served (e.g., Italian, Chinese, Mexican)', 'meowseo' ),
			),
			'priceRange'     => array(
				'type'        => 'text',
				'label'       => __( 'Price Range', 'meowseo' ),
				'description' => __( 'Price range (e.g., $, $$, $$$, $$$$)', 'meowseo' ),
			),
			'menu'           => array(
				'type'        => 'url',
				'label'       => __( 'Menu URL', 'meowseo' ),
				'description' => __( 'URL to the restaurant menu', 'meowseo' ),
			),
			'acceptsReservations' => array(
				'type'        => 'select',
				'label'       => __( 'Accepts Reservations', 'meowseo' ),
				'description' => __( 'Whether the restaurant accepts reservations', 'meowseo' ),
				'options'     => array(
					''      => __( 'Select', 'meowseo' ),
					'true'  => __( 'Yes', 'meowseo' ),
					'false' => __( 'No', 'meowseo' ),
				),
			),
			'openingHours'   => array(
				'type'        => 'repeater',
				'label'       => __( 'Opening Hours', 'meowseo' ),
				'description' => __( 'Opening hours of the restaurant', 'meowseo' ),
				'fields'      => array(
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
				'description' => __( 'The overall rating of the restaurant', 'meowseo' ),
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
			'geo'            => array(
				'type'        => 'group',
				'label'       => __( 'Geographic Coordinates', 'meowseo' ),
				'description' => __( 'Geographic coordinates of the restaurant', 'meowseo' ),
				'fields'      => array(
					'@type'     => array(
						'type'    => 'hidden',
						'default' => 'GeoCoordinates',
					),
					'latitude'  => array(
						'type'  => 'text',
						'label' => __( 'Latitude', 'meowseo' ),
					),
					'longitude' => array(
						'type'  => 'text',
						'label' => __( 'Longitude', 'meowseo' ),
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

		$defaults['address'] = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => '',
			'addressLocality' => '',
			'addressRegion'   => '',
			'postalCode'      => '',
			'addressCountry'  => '',
		);

		$defaults['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => '',
			'longitude' => '',
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$restaurant = new Restaurant_Schema();
	$restaurant->register();
} );
