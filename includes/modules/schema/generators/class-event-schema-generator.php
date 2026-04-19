<?php
/**
 * Event Schema Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema\Generators;

/**
 * Event_Schema_Generator class
 *
 * Generates Event schema markup for concerts, webinars, meetups, and other events.
 */
class Event_Schema_Generator {
	/**
	 * Generate Event schema
	 *
	 * @param int   $post_id Post ID.
	 * @param array $config  Schema configuration.
	 * @return array Schema data.
	 */
	public function generate( int $post_id, array $config ): array {
		// Implementation will be added in task 3.1
		return array();
	}

	/**
	 * Get required fields
	 *
	 * @return array Required field names.
	 */
	public function get_required_fields(): array {
		return array( 'name', 'startDate', 'location' );
	}

	/**
	 * Get optional fields
	 *
	 * @return array Optional field names.
	 */
	public function get_optional_fields(): array {
		return array(
			'endDate',
			'description',
			'eventStatus',
			'eventAttendanceMode',
			'organizer',
			'offers',
			'image',
		);
	}

	/**
	 * Validate configuration
	 *
	 * @param array $config Schema configuration.
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_config( array $config ) {
		// Implementation will be added in task 3.1
		return true;
	}
}
