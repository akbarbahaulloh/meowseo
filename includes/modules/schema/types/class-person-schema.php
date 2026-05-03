<?php
/**
 * Person Schema Type
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
 * Person_Schema class.
 */
class Person_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Person';
		$this->label       = __( 'Person', 'meowseo' );
		$this->description = __( 'A person (alive, dead, undead, or fictional). Perfect for author pages, team members, and biographies.', 'meowseo' );
		$this->icon        = 'admin-users';
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
				'label'       => __( 'Name', 'meowseo' ),
				'description' => __( 'The name of the person', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'    => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the person', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'          => array(
				'type'        => 'image',
				'label'       => __( 'Photo', 'meowseo' ),
				'description' => __( 'Photo of the person', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'jobTitle'       => array(
				'type'        => 'text',
				'label'       => __( 'Job Title', 'meowseo' ),
				'description' => __( 'The job title of the person', 'meowseo' ),
			),
			'worksFor'       => array(
				'type'        => 'group',
				'label'       => __( 'Works For', 'meowseo' ),
				'description' => __( 'The organization the person works for', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'Organization',
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Organization Name', 'meowseo' ),
					),
					'url'   => array(
						'type'  => 'url',
						'label' => __( 'Organization URL', 'meowseo' ),
					),
				),
			),
			'email'          => array(
				'type'        => 'email',
				'label'       => __( 'Email', 'meowseo' ),
				'description' => __( 'Email address', 'meowseo' ),
			),
			'telephone'      => array(
				'type'        => 'text',
				'label'       => __( 'Telephone', 'meowseo' ),
				'description' => __( 'Phone number', 'meowseo' ),
			),
			'url'            => array(
				'type'        => 'url',
				'label'       => __( 'Website', 'meowseo' ),
				'description' => __( 'Personal website URL', 'meowseo' ),
				'default'     => '%permalink%',
			),
			'sameAs'         => array(
				'type'        => 'repeater',
				'label'       => __( 'Social Profiles', 'meowseo' ),
				'description' => __( 'Social media profile URLs', 'meowseo' ),
				'fields'      => array(
					'url' => array(
						'type'        => 'url',
						'label'       => __( 'Profile URL', 'meowseo' ),
						'placeholder' => 'https://twitter.com/username',
					),
				),
			),
			'address'        => array(
				'type'        => 'group',
				'label'       => __( 'Address', 'meowseo' ),
				'description' => __( 'Physical address', 'meowseo' ),
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
			'birthDate'      => array(
				'type'        => 'date',
				'label'       => __( 'Birth Date', 'meowseo' ),
				'description' => __( 'Date of birth', 'meowseo' ),
			),
			'nationality'    => array(
				'type'        => 'text',
				'label'       => __( 'Nationality', 'meowseo' ),
				'description' => __( 'Nationality of the person', 'meowseo' ),
			),
		);
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
	$person = new Person_Schema();
	$person->register();
} );
