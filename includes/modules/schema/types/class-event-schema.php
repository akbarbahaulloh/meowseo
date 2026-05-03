<?php
/**
 * Event Schema Type
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
 * Event_Schema class.
 */
class Event_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Event';
		$this->label       = __( 'Event', 'meowseo' );
		$this->description = __( 'An event happening at a certain time and location.', 'meowseo' );
		$this->icon        = 'calendar-alt';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'name'            => array(
				'type'        => 'text',
				'label'       => __( 'Event Name', 'meowseo' ),
				'description' => __( 'The name of the event', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'     => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the event', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'           => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Image of the event', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'startDate'       => array(
				'type'        => 'datetime',
				'label'       => __( 'Start Date', 'meowseo' ),
				'description' => __( 'The start date and time of the event (ISO 8601 format)', 'meowseo' ),
				'placeholder' => '2026-05-15T19:00:00+07:00',
				'required'    => true,
			),
			'endDate'         => array(
				'type'        => 'datetime',
				'label'       => __( 'End Date', 'meowseo' ),
				'description' => __( 'The end date and time of the event (ISO 8601 format)', 'meowseo' ),
				'placeholder' => '2026-05-15T22:00:00+07:00',
			),
			'eventStatus'     => array(
				'type'        => 'select',
				'label'       => __( 'Event Status', 'meowseo' ),
				'description' => __( 'The status of the event', 'meowseo' ),
				'default'     => 'https://schema.org/EventScheduled',
				'options'     => array(
					'https://schema.org/EventScheduled'   => __( 'Scheduled', 'meowseo' ),
					'https://schema.org/EventCancelled'   => __( 'Cancelled', 'meowseo' ),
					'https://schema.org/EventMovedOnline' => __( 'Moved Online', 'meowseo' ),
					'https://schema.org/EventPostponed'   => __( 'Postponed', 'meowseo' ),
					'https://schema.org/EventRescheduled' => __( 'Rescheduled', 'meowseo' ),
				),
			),
			'eventAttendanceMode' => array(
				'type'        => 'select',
				'label'       => __( 'Attendance Mode', 'meowseo' ),
				'description' => __( 'How the event will be attended', 'meowseo' ),
				'default'     => 'https://schema.org/OfflineEventAttendanceMode',
				'options'     => array(
					'https://schema.org/OfflineEventAttendanceMode' => __( 'Offline (Physical Location)', 'meowseo' ),
					'https://schema.org/OnlineEventAttendanceMode'  => __( 'Online', 'meowseo' ),
					'https://schema.org/MixedEventAttendanceMode'   => __( 'Mixed (Online and Offline)', 'meowseo' ),
				),
			),
			'location'        => array(
				'type'        => 'group',
				'label'       => __( 'Location', 'meowseo' ),
				'description' => __( 'The location of the event', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
					'@type'   => array(
						'type'    => 'select',
						'label'   => __( 'Location Type', 'meowseo' ),
						'default' => 'Place',
						'options' => array(
							'Place'          => __( 'Physical Place', 'meowseo' ),
							'VirtualLocation' => __( 'Virtual Location', 'meowseo' ),
						),
					),
					'name'    => array(
						'type'  => 'text',
						'label' => __( 'Location Name', 'meowseo' ),
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
					'url'     => array(
						'type'        => 'url',
						'label'       => __( 'URL', 'meowseo' ),
						'description' => __( 'For virtual events, the URL to join', 'meowseo' ),
					),
				),
			),
			'organizer'       => array(
				'type'        => 'group',
				'label'       => __( 'Organizer', 'meowseo' ),
				'description' => __( 'The organizer of the event', 'meowseo' ),
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
			'performer'       => array(
				'type'        => 'repeater',
				'label'       => __( 'Performers', 'meowseo' ),
				'description' => __( 'Performers at the event', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'select',
						'label'   => __( 'Type', 'meowseo' ),
						'default' => 'Person',
						'options' => array(
							'Person'           => __( 'Person', 'meowseo' ),
							'PerformingGroup'  => __( 'Performing Group', 'meowseo' ),
							'MusicGroup'       => __( 'Music Group', 'meowseo' ),
						),
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Name', 'meowseo' ),
					),
				),
			),
			'offers'          => array(
				'type'        => 'repeater',
				'label'       => __( 'Offers', 'meowseo' ),
				'description' => __( 'Ticket offers for the event', 'meowseo' ),
				'fields'      => array(
					'@type'         => array(
						'type'    => 'hidden',
						'default' => 'Offer',
					),
					'name'          => array(
						'type'  => 'text',
						'label' => __( 'Offer Name', 'meowseo' ),
					),
					'price'         => array(
						'type'  => 'number',
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
						'default' => 'https://schema.org/InStock',
						'options' => array(
							'https://schema.org/InStock'     => __( 'In Stock', 'meowseo' ),
							'https://schema.org/SoldOut'     => __( 'Sold Out', 'meowseo' ),
							'https://schema.org/PreOrder'    => __( 'Pre Order', 'meowseo' ),
						),
					),
					'url'           => array(
						'type'  => 'url',
						'label' => __( 'Ticket URL', 'meowseo' ),
					),
					'validFrom'     => array(
						'type'  => 'datetime',
						'label' => __( 'Valid From', 'meowseo' ),
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

		// Set default organizer.
		$defaults['organizer'] = array(
			'@type' => 'Organization',
			'name'  => '%sitename%',
			'url'   => '%siteurl%',
		);

		// Set default location.
		$defaults['location'] = array(
			'@type' => 'Place',
			'name'  => '',
			'address' => array(
				'@type' => 'PostalAddress',
			),
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$event = new Event_Schema();
	$event->register();
} );
