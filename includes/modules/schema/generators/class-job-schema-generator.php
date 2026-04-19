<?php
/**
 * Job Schema Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema\Generators;

/**
 * Job_Schema_Generator class
 *
 * Generates JobPosting schema markup for job listings.
 */
class Job_Schema_Generator {
	/**
	 * Generate JobPosting schema
	 *
	 * @param int   $post_id Post ID.
	 * @param array $config  Schema configuration.
	 * @return array Schema data.
	 */
	public function generate( int $post_id, array $config ): array {
		// Implementation will be added in task 5.2
		return array();
	}

	/**
	 * Get required fields
	 *
	 * @return array Required field names.
	 */
	public function get_required_fields(): array {
		return array( 'title', 'description', 'datePosted', 'hiringOrganization' );
	}

	/**
	 * Get optional fields
	 *
	 * @return array Optional field names.
	 */
	public function get_optional_fields(): array {
		return array(
			'validThrough',
			'employmentType',
			'jobLocation',
			'baseSalary',
		);
	}

	/**
	 * Validate configuration
	 *
	 * @param array $config Schema configuration.
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_config( array $config ) {
		// Implementation will be added in task 5.2
		return true;
	}
}
