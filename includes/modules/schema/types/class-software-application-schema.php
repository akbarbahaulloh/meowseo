<?php
/**
 * Software Application Schema Type
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
 * Software_Application_Schema class.
 */
class Software_Application_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'SoftwareApplication';
		$this->label       = __( 'Software Application', 'meowseo' );
		$this->description = __( 'A software application. Perfect for app landing pages, software products, and mobile apps.', 'meowseo' );
		$this->icon        = 'desktop';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'name'               => array(
				'type'        => 'text',
				'label'       => __( 'Application Name', 'meowseo' ),
				'description' => __( 'The name of the software application', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'        => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the application', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'              => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Image or icon of the application', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'applicationCategory' => array(
				'type'        => 'select',
				'label'       => __( 'Application Category', 'meowseo' ),
				'description' => __( 'Type of software application', 'meowseo' ),
				'options'     => array(
					''                      => __( 'Select Category', 'meowseo' ),
					'GameApplication'       => __( 'Game Application', 'meowseo' ),
					'SocialNetworkingApplication' => __( 'Social Networking', 'meowseo' ),
					'TravelApplication'     => __( 'Travel Application', 'meowseo' ),
					'ShoppingApplication'   => __( 'Shopping Application', 'meowseo' ),
					'SportsApplication'     => __( 'Sports Application', 'meowseo' ),
					'LifestyleApplication'  => __( 'Lifestyle Application', 'meowseo' ),
					'BusinessApplication'   => __( 'Business Application', 'meowseo' ),
					'DesignApplication'     => __( 'Design Application', 'meowseo' ),
					'DeveloperApplication'  => __( 'Developer Application', 'meowseo' ),
					'DriverApplication'     => __( 'Driver Application', 'meowseo' ),
					'EducationalApplication' => __( 'Educational Application', 'meowseo' ),
					'HealthApplication'     => __( 'Health Application', 'meowseo' ),
					'FinanceApplication'    => __( 'Finance Application', 'meowseo' ),
					'SecurityApplication'   => __( 'Security Application', 'meowseo' ),
					'BrowserApplication'    => __( 'Browser Application', 'meowseo' ),
					'CommunicationApplication' => __( 'Communication Application', 'meowseo' ),
					'DesktopEnhancementApplication' => __( 'Desktop Enhancement', 'meowseo' ),
					'EntertainmentApplication' => __( 'Entertainment Application', 'meowseo' ),
					'MultimediaApplication' => __( 'Multimedia Application', 'meowseo' ),
					'HomeApplication'       => __( 'Home Application', 'meowseo' ),
					'UtilitiesApplication'  => __( 'Utilities Application', 'meowseo' ),
					'ReferenceApplication'  => __( 'Reference Application', 'meowseo' ),
				),
			),
			'operatingSystem'    => array(
				'type'        => 'text',
				'label'       => __( 'Operating System', 'meowseo' ),
				'description' => __( 'Operating systems supported (e.g., Windows, macOS, iOS, Android)', 'meowseo' ),
			),
			'applicationSubCategory' => array(
				'type'        => 'text',
				'label'       => __( 'Application Sub-Category', 'meowseo' ),
				'description' => __( 'Subcategory of the application', 'meowseo' ),
			),
			'downloadUrl'        => array(
				'type'        => 'url',
				'label'       => __( 'Download URL', 'meowseo' ),
				'description' => __( 'URL to download the application', 'meowseo' ),
			),
			'installUrl'         => array(
				'type'        => 'url',
				'label'       => __( 'Install URL', 'meowseo' ),
				'description' => __( 'URL to install the application', 'meowseo' ),
			),
			'softwareVersion'    => array(
				'type'        => 'text',
				'label'       => __( 'Software Version', 'meowseo' ),
				'description' => __( 'Current version of the software', 'meowseo' ),
			),
			'fileSize'           => array(
				'type'        => 'text',
				'label'       => __( 'File Size', 'meowseo' ),
				'description' => __( 'Size of the application file (e.g., 25MB)', 'meowseo' ),
			),
			'datePublished'      => array(
				'type'        => 'date',
				'label'       => __( 'Date Published', 'meowseo' ),
				'description' => __( 'Date when the application was published', 'meowseo' ),
			),
			'offers'             => array(
				'type'        => 'group',
				'label'       => __( 'Offers', 'meowseo' ),
				'description' => __( 'Pricing information for the application', 'meowseo' ),
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
					'availability'  => array(
						'type'    => 'select',
						'label'   => __( 'Availability', 'meowseo' ),
						'options' => array(
							'https://schema.org/InStock'     => __( 'In Stock', 'meowseo' ),
							'https://schema.org/OutOfStock'  => __( 'Out of Stock', 'meowseo' ),
							'https://schema.org/PreOrder'    => __( 'Pre-Order', 'meowseo' ),
							'https://schema.org/Discontinued' => __( 'Discontinued', 'meowseo' ),
						),
					),
				),
			),
			'aggregateRating'    => array(
				'type'        => 'group',
				'label'       => __( 'Aggregate Rating', 'meowseo' ),
				'description' => __( 'The overall rating of the application', 'meowseo' ),
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
			'author'             => array(
				'type'        => 'group',
				'label'       => __( 'Author/Developer', 'meowseo' ),
				'description' => __( 'The developer or company that created the application', 'meowseo' ),
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
				),
			),
			'screenshot'         => array(
				'type'        => 'repeater',
				'label'       => __( 'Screenshots', 'meowseo' ),
				'description' => __( 'Screenshots of the application', 'meowseo' ),
				'fields'      => array(
					'url' => array(
						'type'  => 'image',
						'label' => __( 'Screenshot URL', 'meowseo' ),
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

		$defaults['offers'] = array(
			'@type'         => 'Offer',
			'price'         => '0',
			'priceCurrency' => 'USD',
		);

		$defaults['author'] = array(
			'@type' => 'Organization',
			'name'  => '',
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$software = new Software_Application_Schema();
	$software->register();
} );
