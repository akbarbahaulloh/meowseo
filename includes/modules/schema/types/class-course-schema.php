<?php
/**
 * Course Schema Type
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
 * Course_Schema class.
 */
class Course_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Course';
		$this->label       = __( 'Course', 'meowseo' );
		$this->description = __( 'An educational course. Helps your courses appear in Google Course Search results.', 'meowseo' );
		$this->icon        = 'welcome-learn-more';
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
				'description' => __( 'The title of the course', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'    => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the course', 'meowseo' ),
				'default'     => '%excerpt%',
				'required'    => true,
			),
			'provider'       => array(
				'type'        => 'group',
				'label'       => __( 'Provider', 'meowseo' ),
				'description' => __( 'The organization that provides the course', 'meowseo' ),
				'required'    => true,
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
					'url'   => array(
						'type'    => 'url',
						'label'   => __( 'URL', 'meowseo' ),
						'default' => '%siteurl%',
					),
				),
			),
			'image'          => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Course image or thumbnail', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'courseCode'     => array(
				'type'        => 'text',
				'label'       => __( 'Course Code', 'meowseo' ),
				'description' => __( 'The identifier for the course (e.g., CS101)', 'meowseo' ),
			),
			'educationalLevel' => array(
				'type'        => 'select',
				'label'       => __( 'Educational Level', 'meowseo' ),
				'description' => __( 'The level of the course', 'meowseo' ),
				'options'     => array(
					''             => __( 'Select Level', 'meowseo' ),
					'Beginner'     => __( 'Beginner', 'meowseo' ),
					'Intermediate' => __( 'Intermediate', 'meowseo' ),
					'Advanced'     => __( 'Advanced', 'meowseo' ),
					'Expert'       => __( 'Expert', 'meowseo' ),
				),
			),
			'hasCourseInstance' => array(
				'type'        => 'repeater',
				'label'       => __( 'Course Instances', 'meowseo' ),
				'description' => __( 'Specific offerings of the course', 'meowseo' ),
				'fields'      => array(
					'@type'         => array(
						'type'    => 'hidden',
						'default' => 'CourseInstance',
					),
					'courseMode'    => array(
						'type'        => 'select',
						'label'       => __( 'Course Mode', 'meowseo' ),
						'description' => __( 'How the course is delivered', 'meowseo' ),
						'options'     => array(
							'online'  => __( 'Online', 'meowseo' ),
							'onsite'  => __( 'On-site', 'meowseo' ),
							'blended' => __( 'Blended', 'meowseo' ),
						),
					),
					'courseSchedule' => array(
						'type'   => 'group',
						'label'  => __( 'Schedule', 'meowseo' ),
						'fields' => array(
							'@type'     => array(
								'type'    => 'hidden',
								'default' => 'Schedule',
							),
							'startDate' => array(
								'type'        => 'date',
								'label'       => __( 'Start Date', 'meowseo' ),
								'description' => __( 'When the course starts', 'meowseo' ),
							),
							'endDate'   => array(
								'type'        => 'date',
								'label'       => __( 'End Date', 'meowseo' ),
								'description' => __( 'When the course ends', 'meowseo' ),
							),
							'duration'  => array(
								'type'        => 'text',
								'label'       => __( 'Duration', 'meowseo' ),
								'description' => __( 'Course duration in ISO 8601 format (e.g., P6W for 6 weeks)', 'meowseo' ),
								'placeholder' => 'P6W',
							),
						),
					),
					'instructor'    => array(
						'type'   => 'group',
						'label'  => __( 'Instructor', 'meowseo' ),
						'fields' => array(
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
							'url'   => array(
								'type'  => 'url',
								'label' => __( 'URL', 'meowseo' ),
							),
						),
					),
					'location'      => array(
						'type'   => 'group',
						'label'  => __( 'Location', 'meowseo' ),
						'fields' => array(
							'@type'   => array(
								'type'    => 'select',
								'label'   => __( 'Type', 'meowseo' ),
								'default' => 'VirtualLocation',
								'options' => array(
									'VirtualLocation' => __( 'Virtual Location', 'meowseo' ),
									'Place'           => __( 'Physical Place', 'meowseo' ),
								),
							),
							'url'     => array(
								'type'        => 'url',
								'label'       => __( 'URL', 'meowseo' ),
								'description' => __( 'For virtual locations', 'meowseo' ),
							),
							'name'    => array(
								'type'        => 'text',
								'label'       => __( 'Name', 'meowseo' ),
								'description' => __( 'For physical places', 'meowseo' ),
							),
							'address' => array(
								'type'        => 'text',
								'label'       => __( 'Address', 'meowseo' ),
								'description' => __( 'For physical places', 'meowseo' ),
							),
						),
					),
				),
			),
			'offers'         => array(
				'type'        => 'group',
				'label'       => __( 'Offers', 'meowseo' ),
				'description' => __( 'Pricing information for the course', 'meowseo' ),
				'fields'      => array(
					'@type'         => array(
						'type'    => 'hidden',
						'default' => 'Offer',
					),
					'price'         => array(
						'type'        => 'number',
						'label'       => __( 'Price', 'meowseo' ),
						'description' => __( 'The course price (use 0 for free courses)', 'meowseo' ),
					),
					'priceCurrency' => array(
						'type'        => 'text',
						'label'       => __( 'Currency', 'meowseo' ),
						'description' => __( 'The currency of the price (e.g., USD, EUR)', 'meowseo' ),
						'default'     => 'USD',
					),
					'category'      => array(
						'type'        => 'select',
						'label'       => __( 'Category', 'meowseo' ),
						'description' => __( 'The pricing category', 'meowseo' ),
						'options'     => array(
							'Paid'         => __( 'Paid', 'meowseo' ),
							'Free'         => __( 'Free', 'meowseo' ),
							'Subscription' => __( 'Subscription', 'meowseo' ),
						),
					),
					'availability'  => array(
						'type'    => 'select',
						'label'   => __( 'Availability', 'meowseo' ),
						'default' => 'https://schema.org/InStock',
						'options' => array(
							'https://schema.org/InStock'     => __( 'Available', 'meowseo' ),
							'https://schema.org/OutOfStock'  => __( 'Not Available', 'meowseo' ),
							'https://schema.org/PreOrder'    => __( 'Coming Soon', 'meowseo' ),
						),
					),
					'url'           => array(
						'type'        => 'url',
						'label'       => __( 'URL', 'meowseo' ),
						'description' => __( 'The URL where the course can be purchased', 'meowseo' ),
						'default'     => '%permalink%',
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
			'timeRequired'   => array(
				'type'        => 'text',
				'label'       => __( 'Time Required', 'meowseo' ),
				'description' => __( 'Approximate time to complete the course in ISO 8601 format (e.g., P6W for 6 weeks)', 'meowseo' ),
				'placeholder' => 'P6W',
			),
			'inLanguage'     => array(
				'type'        => 'text',
				'label'       => __( 'Language', 'meowseo' ),
				'description' => __( 'The language of the course (e.g., en, id)', 'meowseo' ),
				'default'     => 'en',
			),
			'teaches'        => array(
				'type'        => 'textarea',
				'label'       => __( 'What You Will Learn', 'meowseo' ),
				'description' => __( 'What the course teaches (one item per line)', 'meowseo' ),
			),
			'coursePrerequisites' => array(
				'type'        => 'textarea',
				'label'       => __( 'Prerequisites', 'meowseo' ),
				'description' => __( 'Prerequisites for taking the course (one item per line)', 'meowseo' ),
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

		// Set default provider.
		$defaults['provider'] = array(
			'@type' => 'Organization',
			'name'  => '%sitename%',
			'url'   => '%siteurl%',
		);

		// Set default offers.
		$defaults['offers'] = array(
			'@type'         => 'Offer',
			'price'         => 0,
			'priceCurrency' => 'USD',
			'category'      => 'Free',
			'availability'  => 'https://schema.org/InStock',
			'url'           => '%permalink%',
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

		// Convert teaches to array if it's a string with line breaks.
		if ( ! empty( $schema['teaches'] ) && is_string( $schema['teaches'] ) ) {
			$teaches = array_filter( array_map( 'trim', explode( "\n", $schema['teaches'] ) ) );
			if ( ! empty( $teaches ) ) {
				$schema['teaches'] = $teaches;
			} else {
				unset( $schema['teaches'] );
			}
		}

		// Convert coursePrerequisites to array if it's a string with line breaks.
		if ( ! empty( $schema['coursePrerequisites'] ) && is_string( $schema['coursePrerequisites'] ) ) {
			$prerequisites = array_filter( array_map( 'trim', explode( "\n", $schema['coursePrerequisites'] ) ) );
			if ( ! empty( $prerequisites ) ) {
				$schema['coursePrerequisites'] = $prerequisites;
			} else {
				unset( $schema['coursePrerequisites'] );
			}
		}

		return $schema;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$course = new Course_Schema();
	$course->register();
} );
