<?php
/**
 * Job Posting Schema Type
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
 * Job_Posting_Schema class.
 */
class Job_Posting_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'JobPosting';
		$this->label       = __( 'Job Posting', 'meowseo' );
		$this->description = __( 'A job posting or job listing. Perfect for career pages and job boards.', 'meowseo' );
		$this->icon        = 'businessman';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'title'              => array(
				'type'        => 'text',
				'label'       => __( 'Job Title', 'meowseo' ),
				'description' => __( 'The title of the job position', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'        => array(
				'type'        => 'textarea',
				'label'       => __( 'Job Description', 'meowseo' ),
				'description' => __( 'A description of the job', 'meowseo' ),
				'default'     => '%excerpt%',
				'required'    => true,
			),
			'datePosted'         => array(
				'type'        => 'date',
				'label'       => __( 'Date Posted', 'meowseo' ),
				'description' => __( 'The date the job was posted', 'meowseo' ),
				'default'     => '%date(Y-m-d)%',
				'required'    => true,
			),
			'validThrough'       => array(
				'type'        => 'date',
				'label'       => __( 'Valid Through', 'meowseo' ),
				'description' => __( 'The date when the job posting will expire', 'meowseo' ),
			),
			'employmentType'     => array(
				'type'        => 'select',
				'label'       => __( 'Employment Type', 'meowseo' ),
				'description' => __( 'Type of employment', 'meowseo' ),
				'options'     => array(
					''           => __( 'Select Type', 'meowseo' ),
					'FULL_TIME'  => __( 'Full Time', 'meowseo' ),
					'PART_TIME'  => __( 'Part Time', 'meowseo' ),
					'CONTRACTOR' => __( 'Contractor', 'meowseo' ),
					'TEMPORARY'  => __( 'Temporary', 'meowseo' ),
					'INTERN'     => __( 'Intern', 'meowseo' ),
					'VOLUNTEER'  => __( 'Volunteer', 'meowseo' ),
					'PER_DIEM'   => __( 'Per Diem', 'meowseo' ),
					'OTHER'      => __( 'Other', 'meowseo' ),
				),
			),
			'hiringOrganization' => array(
				'type'        => 'group',
				'label'       => __( 'Hiring Organization', 'meowseo' ),
				'description' => __( 'The organization offering the job', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'Organization',
					),
					'name'  => array(
						'type'     => 'text',
						'label'    => __( 'Company Name', 'meowseo' ),
						'required' => true,
					),
					'sameAs' => array(
						'type'  => 'url',
						'label' => __( 'Company Website', 'meowseo' ),
					),
					'logo'  => array(
						'type'  => 'image',
						'label' => __( 'Company Logo', 'meowseo' ),
					),
				),
			),
			'jobLocation'        => array(
				'type'        => 'group',
				'label'       => __( 'Job Location', 'meowseo' ),
				'description' => __( 'The location of the job', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
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
			'baseSalary'         => array(
				'type'        => 'group',
				'label'       => __( 'Base Salary', 'meowseo' ),
				'description' => __( 'The base salary of the job', 'meowseo' ),
				'fields'      => array(
					'@type'    => array(
						'type'    => 'hidden',
						'default' => 'MonetaryAmount',
					),
					'currency' => array(
						'type'    => 'text',
						'label'   => __( 'Currency', 'meowseo' ),
						'default' => 'USD',
					),
					'value'    => array(
						'type'   => 'group',
						'label'  => __( 'Value', 'meowseo' ),
						'fields' => array(
							'@type'    => array(
								'type'    => 'hidden',
								'default' => 'QuantitativeValue',
							),
							'value'    => array(
								'type'  => 'number',
								'label' => __( 'Amount', 'meowseo' ),
							),
							'unitText' => array(
								'type'    => 'select',
								'label'   => __( 'Unit', 'meowseo' ),
								'options' => array(
									'HOUR'  => __( 'Per Hour', 'meowseo' ),
									'DAY'   => __( 'Per Day', 'meowseo' ),
									'WEEK'  => __( 'Per Week', 'meowseo' ),
									'MONTH' => __( 'Per Month', 'meowseo' ),
									'YEAR'  => __( 'Per Year', 'meowseo' ),
								),
							),
						),
					),
				),
			),
			'jobBenefits'        => array(
				'type'        => 'textarea',
				'label'       => __( 'Job Benefits', 'meowseo' ),
				'description' => __( 'Benefits offered with the job', 'meowseo' ),
			),
			'experienceRequirements' => array(
				'type'        => 'textarea',
				'label'       => __( 'Experience Requirements', 'meowseo' ),
				'description' => __( 'Required experience for the job', 'meowseo' ),
			),
			'educationRequirements' => array(
				'type'        => 'textarea',
				'label'       => __( 'Education Requirements', 'meowseo' ),
				'description' => __( 'Required education for the job', 'meowseo' ),
			),
			'skills'             => array(
				'type'        => 'textarea',
				'label'       => __( 'Skills', 'meowseo' ),
				'description' => __( 'Skills required for the job', 'meowseo' ),
			),
			'qualifications'     => array(
				'type'        => 'textarea',
				'label'       => __( 'Qualifications', 'meowseo' ),
				'description' => __( 'Qualifications required for the job', 'meowseo' ),
			),
			'responsibilities'   => array(
				'type'        => 'textarea',
				'label'       => __( 'Responsibilities', 'meowseo' ),
				'description' => __( 'Job responsibilities', 'meowseo' ),
			),
			'industry'           => array(
				'type'        => 'text',
				'label'       => __( 'Industry', 'meowseo' ),
				'description' => __( 'The industry associated with the job', 'meowseo' ),
			),
			'workHours'          => array(
				'type'        => 'text',
				'label'       => __( 'Work Hours', 'meowseo' ),
				'description' => __( 'Typical work hours (e.g., 9am-5pm)', 'meowseo' ),
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

		$defaults['hiringOrganization'] = array(
			'@type'  => 'Organization',
			'name'   => '',
			'sameAs' => '',
			'logo'   => '',
		);

		$defaults['jobLocation'] = array(
			'@type'   => 'Place',
			'address' => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => '',
				'addressLocality' => '',
				'addressRegion'   => '',
				'postalCode'      => '',
				'addressCountry'  => '',
			),
		);

		$defaults['baseSalary'] = array(
			'@type'    => 'MonetaryAmount',
			'currency' => 'USD',
			'value'    => array(
				'@type'    => 'QuantitativeValue',
				'value'    => '',
				'unitText' => 'YEAR',
			),
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$job = new Job_Posting_Schema();
	$job->register();
} );
