<?php
/**
 * Organization Schema Type
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
 * Organization_Schema class.
 */
class Organization_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Organization';
		$this->label       = __( 'Organization', 'meowseo' );
		$this->description = __( 'An organization such as a company, NGO, school, club, etc. Perfect for about pages and company profiles.', 'meowseo' );
		$this->icon        = 'building';
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
				'label'       => __( 'Organization Name', 'meowseo' ),
				'description' => __( 'The name of the organization', 'meowseo' ),
				'default'     => '%sitename%',
				'required'    => true,
			),
			'description'    => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the organization', 'meowseo' ),
				'default'     => '%sitedesc%',
			),
			'url'            => array(
				'type'        => 'url',
				'label'       => __( 'Website URL', 'meowseo' ),
				'description' => __( 'The organization website URL', 'meowseo' ),
				'default'     => '%siteurl%',
				'required'    => true,
			),
			'logo'           => array(
				'type'        => 'group',
				'label'       => __( 'Logo', 'meowseo' ),
				'description' => __( 'The organization logo', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'ImageObject',
					),
					'url'   => array(
						'type'  => 'image',
						'label' => __( 'Logo URL', 'meowseo' ),
					),
					'width' => array(
						'type'  => 'number',
						'label' => __( 'Width', 'meowseo' ),
					),
					'height' => array(
						'type'  => 'number',
						'label' => __( 'Height', 'meowseo' ),
					),
				),
			),
			'image'          => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Image representing the organization', 'meowseo' ),
			),
			'@type'          => array(
				'type'        => 'select',
				'label'       => __( 'Organization Type', 'meowseo' ),
				'description' => __( 'Specific type of organization', 'meowseo' ),
				'default'     => 'Organization',
				'options'     => array(
					'Organization'         => __( 'Organization', 'meowseo' ),
					'Corporation'          => __( 'Corporation', 'meowseo' ),
					'EducationalOrganization' => __( 'Educational Organization', 'meowseo' ),
					'GovernmentOrganization' => __( 'Government Organization', 'meowseo' ),
					'LocalBusiness'        => __( 'Local Business', 'meowseo' ),
					'NGO'                  => __( 'NGO', 'meowseo' ),
					'PerformingGroup'      => __( 'Performing Group', 'meowseo' ),
					'SportsOrganization'   => __( 'Sports Organization', 'meowseo' ),
					'MedicalOrganization'  => __( 'Medical Organization', 'meowseo' ),
					'NewsMediaOrganization' => __( 'News Media Organization', 'meowseo' ),
				),
			),
			'address'        => array(
				'type'        => 'group',
				'label'       => __( 'Address', 'meowseo' ),
				'description' => __( 'Physical address of the organization', 'meowseo' ),
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
			'contactPoint'   => array(
				'type'        => 'group',
				'label'       => __( 'Contact Point', 'meowseo' ),
				'description' => __( 'Contact information for the organization', 'meowseo' ),
				'fields'      => array(
					'@type'       => array(
						'type'    => 'hidden',
						'default' => 'ContactPoint',
					),
					'telephone'   => array(
						'type'  => 'text',
						'label' => __( 'Telephone', 'meowseo' ),
					),
					'contactType' => array(
						'type'    => 'select',
						'label'   => __( 'Contact Type', 'meowseo' ),
						'options' => array(
							'customer service' => __( 'Customer Service', 'meowseo' ),
							'technical support' => __( 'Technical Support', 'meowseo' ),
							'billing support' => __( 'Billing Support', 'meowseo' ),
							'bill payment'    => __( 'Bill Payment', 'meowseo' ),
							'sales'           => __( 'Sales', 'meowseo' ),
							'reservations'    => __( 'Reservations', 'meowseo' ),
							'credit card support' => __( 'Credit Card Support', 'meowseo' ),
							'emergency'       => __( 'Emergency', 'meowseo' ),
							'baggage tracking' => __( 'Baggage Tracking', 'meowseo' ),
							'roadside assistance' => __( 'Roadside Assistance', 'meowseo' ),
							'package tracking' => __( 'Package Tracking', 'meowseo' ),
						),
					),
					'email'       => array(
						'type'  => 'email',
						'label' => __( 'Email', 'meowseo' ),
					),
					'areaServed'  => array(
						'type'  => 'text',
						'label' => __( 'Area Served', 'meowseo' ),
					),
					'availableLanguage' => array(
						'type'  => 'text',
						'label' => __( 'Available Language', 'meowseo' ),
					),
				),
			),
			'sameAs'         => array(
				'type'        => 'repeater',
				'label'       => __( 'Social Profiles', 'meowseo' ),
				'description' => __( 'Social media profile URLs', 'meowseo' ),
				'fields'      => array(
					'url' => array(
						'type'        => 'url',
						'label'       => __( 'Profile URL', 'meowseo' ),
						'placeholder' => 'https://facebook.com/company',
					),
				),
			),
			'founder'        => array(
				'type'        => 'group',
				'label'       => __( 'Founder', 'meowseo' ),
				'description' => __( 'The founder of the organization', 'meowseo' ),
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
						'type'  => 'text',
						'label' => __( 'Name', 'meowseo' ),
					),
				),
			),
			'foundingDate'   => array(
				'type'        => 'date',
				'label'       => __( 'Founding Date', 'meowseo' ),
				'description' => __( 'The date the organization was founded', 'meowseo' ),
			),
			'numberOfEmployees' => array(
				'type'        => 'number',
				'label'       => __( 'Number of Employees', 'meowseo' ),
				'description' => __( 'The number of employees', 'meowseo' ),
			),
			'slogan'         => array(
				'type'        => 'text',
				'label'       => __( 'Slogan', 'meowseo' ),
				'description' => __( 'The organization slogan or tagline', 'meowseo' ),
			),
			'taxID'          => array(
				'type'        => 'text',
				'label'       => __( 'Tax ID', 'meowseo' ),
				'description' => __( 'The Tax / Fiscal ID of the organization', 'meowseo' ),
			),
			'vatID'          => array(
				'type'        => 'text',
				'label'       => __( 'VAT ID', 'meowseo' ),
				'description' => __( 'The Value-added Tax ID', 'meowseo' ),
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

		$defaults['logo'] = array(
			'@type'  => 'ImageObject',
			'url'    => '',
			'width'  => '',
			'height' => '',
		);

		$defaults['address'] = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => '',
			'addressLocality' => '',
			'addressRegion'   => '',
			'postalCode'      => '',
			'addressCountry'  => '',
		);

		$defaults['contactPoint'] = array(
			'@type'       => 'ContactPoint',
			'telephone'   => '',
			'contactType' => 'customer service',
			'email'       => '',
		);

		return $defaults;
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

		// Convert sameAs repeater to array of URLs.
		if ( ! empty( $schema['sameAs'] ) && is_array( $schema['sameAs'] ) ) {
			$urls = array();
			foreach ( $schema['sameAs'] as $item ) {
				if ( ! empty( $item['url'] ) ) {
					$urls[] = $item['url'];
				}
			}
			$schema['sameAs'] = ! empty( $urls ) ? $urls : null;
		}

		return $schema;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$organization = new Organization_Schema();
	$organization->register();
} );
