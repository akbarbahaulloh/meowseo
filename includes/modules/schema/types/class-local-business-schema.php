<?php
/**
 * Local Business Schema Type
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
 * Local_Business_Schema class.
 */
class Local_Business_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'LocalBusiness';
		$this->label       = __( 'Local Business', 'meowseo' );
		$this->description = __( 'A particular physical business or branch of an organization.', 'meowseo' );
		$this->icon        = 'store';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'@type'           => array(
				'type'        => 'select',
				'label'       => __( 'Business Type', 'meowseo' ),
				'description' => __( 'The specific type of local business', 'meowseo' ),
				'default'     => 'LocalBusiness',
				'options'     => $this->get_business_types(),
			),
			'name'            => array(
				'type'        => 'text',
				'label'       => __( 'Business Name', 'meowseo' ),
				'description' => __( 'The name of the business', 'meowseo' ),
				'default'     => '%sitename%',
				'required'    => true,
			),
			'description'     => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the business', 'meowseo' ),
				'default'     => '%sitedesc%',
			),
			'image'           => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Image of the business', 'meowseo' ),
			),
			'logo'            => array(
				'type'        => 'image',
				'label'       => __( 'Logo', 'meowseo' ),
				'description' => __( 'Logo of the business', 'meowseo' ),
			),
			'url'             => array(
				'type'        => 'url',
				'label'       => __( 'Website URL', 'meowseo' ),
				'description' => __( 'The website URL of the business', 'meowseo' ),
				'default'     => '%siteurl%',
			),
			'telephone'       => array(
				'type'        => 'text',
				'label'       => __( 'Telephone', 'meowseo' ),
				'description' => __( 'The telephone number', 'meowseo' ),
				'placeholder' => '+1-555-555-5555',
			),
			'email'           => array(
				'type'        => 'email',
				'label'       => __( 'Email', 'meowseo' ),
				'description' => __( 'The email address', 'meowseo' ),
			),
			'address'         => array(
				'type'        => 'group',
				'label'       => __( 'Address', 'meowseo' ),
				'description' => __( 'The physical address of the business', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
					'@type'           => array(
						'type'    => 'hidden',
						'default' => 'PostalAddress',
					),
					'streetAddress'   => array(
						'type'     => 'text',
						'label'    => __( 'Street Address', 'meowseo' ),
						'required' => true,
					),
					'addressLocality' => array(
						'type'     => 'text',
						'label'    => __( 'City', 'meowseo' ),
						'required' => true,
					),
					'addressRegion'   => array(
						'type'  => 'text',
						'label' => __( 'State/Region', 'meowseo' ),
					),
					'postalCode'      => array(
						'type'     => 'text',
						'label'    => __( 'Postal Code', 'meowseo' ),
						'required' => true,
					),
					'addressCountry'  => array(
						'type'     => 'text',
						'label'    => __( 'Country', 'meowseo' ),
						'required' => true,
					),
				),
			),
			'geo'             => array(
				'type'        => 'group',
				'label'       => __( 'Geographic Coordinates', 'meowseo' ),
				'description' => __( 'The geographic coordinates of the business', 'meowseo' ),
				'fields'      => array(
					'@type'     => array(
						'type'    => 'hidden',
						'default' => 'GeoCoordinates',
					),
					'latitude'  => array(
						'type'        => 'number',
						'label'       => __( 'Latitude', 'meowseo' ),
						'step'        => 0.000001,
						'placeholder' => '40.7128',
						'width'       => 'calc(50% - 5px)',
					),
					'longitude' => array(
						'type'        => 'number',
						'label'       => __( 'Longitude', 'meowseo' ),
						'step'        => 0.000001,
						'placeholder' => '-74.0060',
						'width'       => 'calc(50% - 5px)',
					),
				),
			),
			'openingHoursSpecification' => array(
				'type'        => 'repeater',
				'label'       => __( 'Opening Hours', 'meowseo' ),
				'description' => __( 'The opening hours of the business', 'meowseo' ),
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
						'type'        => 'time',
						'label'       => __( 'Opens', 'meowseo' ),
						'placeholder' => '09:00',
					),
					'closes'    => array(
						'type'        => 'time',
						'label'       => __( 'Closes', 'meowseo' ),
						'placeholder' => '17:00',
					),
				),
			),
			'priceRange'      => array(
				'type'        => 'text',
				'label'       => __( 'Price Range', 'meowseo' ),
				'description' => __( 'The price range of the business (e.g., $, $$, $$$, $$$$)', 'meowseo' ),
				'placeholder' => '$$',
			),
			'servesCuisine'   => array(
				'type'        => 'text',
				'label'       => __( 'Serves Cuisine', 'meowseo' ),
				'description' => __( 'For restaurants, the type of cuisine served', 'meowseo' ),
			),
			'paymentAccepted' => array(
				'type'        => 'text',
				'label'       => __( 'Payment Accepted', 'meowseo' ),
				'description' => __( 'Payment methods accepted (e.g., Cash, Credit Card)', 'meowseo' ),
			),
			'currenciesAccepted' => array(
				'type'        => 'text',
				'label'       => __( 'Currencies Accepted', 'meowseo' ),
				'description' => __( 'Currencies accepted (e.g., USD, EUR)', 'meowseo' ),
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
			'sameAs'          => array(
				'type'        => 'repeater',
				'label'       => __( 'Social Profiles', 'meowseo' ),
				'description' => __( 'Social media profile URLs', 'meowseo' ),
				'item_type'   => 'url',
				'placeholder' => 'https://facebook.com/yourbusiness',
			),
		);
	}

	/**
	 * Get business types.
	 *
	 * @return array Business types.
	 */
	private function get_business_types(): array {
		return array(
			'LocalBusiness'         => __( 'Local Business (Generic)', 'meowseo' ),
			'AnimalShelter'         => __( 'Animal Shelter', 'meowseo' ),
			'AutomotiveBusiness'    => __( 'Automotive Business', 'meowseo' ),
			'ChildCare'             => __( 'Child Care', 'meowseo' ),
			'Dentist'               => __( 'Dentist', 'meowseo' ),
			'DryCleaningOrLaundry'  => __( 'Dry Cleaning or Laundry', 'meowseo' ),
			'EmergencyService'      => __( 'Emergency Service', 'meowseo' ),
			'EmploymentAgency'      => __( 'Employment Agency', 'meowseo' ),
			'EntertainmentBusiness' => __( 'Entertainment Business', 'meowseo' ),
			'FinancialService'      => __( 'Financial Service', 'meowseo' ),
			'FoodEstablishment'     => __( 'Food Establishment', 'meowseo' ),
			'GovernmentOffice'      => __( 'Government Office', 'meowseo' ),
			'HealthAndBeautyBusiness' => __( 'Health and Beauty Business', 'meowseo' ),
			'HomeAndConstructionBusiness' => __( 'Home and Construction Business', 'meowseo' ),
			'InternetCafe'          => __( 'Internet Cafe', 'meowseo' ),
			'LegalService'          => __( 'Legal Service', 'meowseo' ),
			'Library'               => __( 'Library', 'meowseo' ),
			'LodgingBusiness'       => __( 'Lodging Business', 'meowseo' ),
			'MedicalBusiness'       => __( 'Medical Business', 'meowseo' ),
			'ProfessionalService'   => __( 'Professional Service', 'meowseo' ),
			'RadioStation'          => __( 'Radio Station', 'meowseo' ),
			'RealEstateAgent'       => __( 'Real Estate Agent', 'meowseo' ),
			'RecyclingCenter'       => __( 'Recycling Center', 'meowseo' ),
			'SelfStorage'           => __( 'Self Storage', 'meowseo' ),
			'ShoppingCenter'        => __( 'Shopping Center', 'meowseo' ),
			'SportsActivityLocation' => __( 'Sports Activity Location', 'meowseo' ),
			'Store'                 => __( 'Store', 'meowseo' ),
			'TelevisionStation'     => __( 'Television Station', 'meowseo' ),
			'TouristInformationCenter' => __( 'Tourist Information Center', 'meowseo' ),
			'TravelAgency'          => __( 'Travel Agency', 'meowseo' ),
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

		// Set default address.
		$defaults['address'] = array(
			'@type' => 'PostalAddress',
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$business = new Local_Business_Schema();
	$business->register();
} );
