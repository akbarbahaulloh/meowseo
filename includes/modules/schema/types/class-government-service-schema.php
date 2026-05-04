<?php
/**
 * GovernmentService Schema Type
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
 * Government_Service_Schema class.
 */
class Government_Service_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'GovernmentService';
		$this->label       = __( 'Government Service', 'meowseo' );
		$this->description = __( 'A service provided by a government organization. Perfect for government websites and public services.', 'meowseo' );
		$this->icon        = 'shield';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'name'              => array(
				'type'        => 'text',
				'label'       => __( 'Service Name', 'meowseo' ),
				'description' => __( 'The name of the government service', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'       => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the service', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'url'               => array(
				'type'        => 'url',
				'label'       => __( 'Service URL', 'meowseo' ),
				'description' => __( 'URL to access the service', 'meowseo' ),
				'default'     => '%permalink%',
			),
			'serviceType'       => array(
				'type'        => 'text',
				'label'       => __( 'Service Type', 'meowseo' ),
				'description' => __( 'The type of government service', 'meowseo' ),
			),
			'provider'          => array(
				'type'        => 'group',
				'label'       => __( 'Service Provider', 'meowseo' ),
				'description' => __( 'The government organization providing the service', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'GovernmentOrganization',
					),
					'name'  => array(
						'type'     => 'text',
						'label'    => __( 'Organization Name', 'meowseo' ),
						'required' => true,
					),
					'url'   => array(
						'type'  => 'url',
						'label' => __( 'Organization URL', 'meowseo' ),
					),
				),
			),
			'serviceOperator'   => array(
				'type'        => 'group',
				'label'       => __( 'Service Operator', 'meowseo' ),
				'description' => __( 'The organization that operates the service', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'GovernmentOrganization',
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Operator Name', 'meowseo' ),
					),
				),
			),
			'areaServed'        => array(
				'type'        => 'repeater',
				'label'       => __( 'Area Served', 'meowseo' ),
				'description' => __( 'Geographic areas where the service is available', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'select',
						'label'   => __( 'Type', 'meowseo' ),
						'default' => 'AdministrativeArea',
						'options' => array(
							'AdministrativeArea' => __( 'Administrative Area', 'meowseo' ),
							'City'               => __( 'City', 'meowseo' ),
							'State'              => __( 'State', 'meowseo' ),
							'Country'            => __( 'Country', 'meowseo' ),
						),
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Location Name', 'meowseo' ),
					),
				),
			),
			'audience'          => array(
				'type'        => 'group',
				'label'       => __( 'Audience', 'meowseo' ),
				'description' => __( 'The intended audience for the service', 'meowseo' ),
				'fields'      => array(
					'@type'        => array(
						'type'    => 'hidden',
						'default' => 'Audience',
					),
					'audienceType' => array(
						'type'        => 'text',
						'label'       => __( 'Audience Type', 'meowseo' ),
						'description' => __( 'e.g., Citizens, Businesses, Tourists', 'meowseo' ),
					),
				),
			),
			'availableChannel'  => array(
				'type'        => 'group',
				'label'       => __( 'Available Channel', 'meowseo' ),
				'description' => __( 'How the service can be accessed', 'meowseo' ),
				'fields'      => array(
					'@type'         => array(
						'type'    => 'hidden',
						'default' => 'ServiceChannel',
					),
					'serviceUrl'    => array(
						'type'  => 'url',
						'label' => __( 'Service URL', 'meowseo' ),
					),
					'servicePhone'  => array(
						'type'  => 'text',
						'label' => __( 'Service Phone', 'meowseo' ),
					),
					'serviceLocation' => array(
						'type'   => 'group',
						'label'  => __( 'Service Location', 'meowseo' ),
						'fields' => array(
							'@type'   => array(
								'type'    => 'hidden',
								'default' => 'Place',
							),
							'address' => array(
								'type'   => 'group',
								'label'  => __( 'Address', 'meowseo' ),
								'fields' => array(
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
						),
					),
				),
			),
			'category'          => array(
				'type'        => 'text',
				'label'       => __( 'Category', 'meowseo' ),
				'description' => __( 'Category of the service', 'meowseo' ),
			),
			'termsOfService'    => array(
				'type'        => 'url',
				'label'       => __( 'Terms of Service', 'meowseo' ),
				'description' => __( 'URL to the terms of service', 'meowseo' ),
			),
			'hoursAvailable'    => array(
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
			'serviceOutput'     => array(
				'type'        => 'text',
				'label'       => __( 'Service Output', 'meowseo' ),
				'description' => __( 'The tangible output of the service', 'meowseo' ),
			),
			'logo'              => array(
				'type'        => 'image',
				'label'       => __( 'Logo', 'meowseo' ),
				'description' => __( 'Logo of the service or organization', 'meowseo' ),
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
			'@type' => 'GovernmentOrganization',
			'name'  => '',
			'url'   => '',
		);

		$defaults['serviceOperator'] = array(
			'@type' => 'GovernmentOrganization',
			'name'  => '',
		);

		$defaults['audience'] = array(
			'@type'        => 'Audience',
			'audienceType' => '',
		);

		$defaults['availableChannel'] = array(
			'@type'           => 'ServiceChannel',
			'serviceUrl'      => '',
			'servicePhone'    => '',
			'serviceLocation' => array(
				'@type'   => 'Place',
				'address' => array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => '',
					'addressLocality' => '',
					'addressRegion'   => '',
					'postalCode'      => '',
					'addressCountry'  => '',
				),
			),
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$government_service = new Government_Service_Schema();
	$government_service->register();
} );
