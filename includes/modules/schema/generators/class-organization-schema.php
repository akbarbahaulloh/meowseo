<?php
/**
 * Organization Schema Generator
 *
 * Generates Organization schema markup with brand identity information.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Schema\Generators;

use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Organization Schema Generator class
 *
 * Generates Organization schema with name, url, logo, contactPoint, and sameAs properties.
 * Requirements 1.2, 1.3, 1.5: Generate Organization schema with optional logo, contact, and social profiles.
 *
 * @since 1.0.0
 */
class Organization_Schema {

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Generate Organization schema
	 *
	 * Generates Organization schema with brand identity information.
	 * Requirements 1.2, 1.3, 1.5: Generate Organization schema with logo, contactPoint, and sameAs.
	 *
	 * @since 1.0.0
	 * @return array Organization schema object, or empty array if name not configured.
	 */
	public function generate(): array {
		$organization = $this->options->get( 'organization', array() );

		// Organization name is required.
		if ( empty( $organization['name'] ) ) {
			return array();
		}

		$home_url = home_url( '/' );

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'@id'      => esc_url( $home_url . '#organization' ),
			'name'     => $organization['name'],
			'url'      => esc_url( $home_url ),
		);

		// Add logo if configured (Requirement 1.5: Omit logo when not configured).
		$logo = $this->get_logo_schema( $organization );
		if ( ! empty( $logo ) ) {
			$schema['logo'] = $logo;
		}

		// Add contact point if email configured (Requirement 1.2).
		$contact_point = $this->get_contact_point_schema( $organization );
		if ( ! empty( $contact_point ) ) {
			$schema['contactPoint'] = $contact_point;
		}

		// Add social profiles if configured (Requirement 1.3).
		$social_profiles = $this->get_social_profiles( $organization );
		if ( ! empty( $social_profiles ) ) {
			$schema['sameAs'] = $social_profiles;
		}

		return $schema;
	}

	/**
	 * Get logo schema
	 *
	 * Generates ImageObject schema for organization logo.
	 * Requirement 1.5: Omit logo property when not configured.
	 *
	 * @since 1.0.0
	 * @param array $organization Organization settings.
	 * @return array Logo ImageObject schema, or empty array if not configured.
	 */
	private function get_logo_schema( array $organization ): array {
		if ( empty( $organization['logo_url'] ) ) {
			return array();
		}

		$logo_url = esc_url( $organization['logo_url'] );
		if ( empty( $logo_url ) ) {
			return array();
		}

		$logo = array(
			'@type' => 'ImageObject',
			'url'   => $logo_url,
		);

		// Add dimensions if provided.
		if ( ! empty( $organization['logo_width'] ) ) {
			$logo['width'] = absint( $organization['logo_width'] );
		}
		if ( ! empty( $organization['logo_height'] ) ) {
			$logo['height'] = absint( $organization['logo_height'] );
		}

		return $logo;
	}

	/**
	 * Get contact point schema
	 *
	 * Generates ContactPoint schema for organization contact information.
	 * Requirement 1.2: Add contactPoint property when email configured.
	 *
	 * @since 1.0.0
	 * @param array $organization Organization settings.
	 * @return array ContactPoint schema, or empty array if not configured.
	 */
	private function get_contact_point_schema( array $organization ): array {
		if ( empty( $organization['contact_email'] ) ) {
			return array();
		}

		$email = sanitize_email( $organization['contact_email'] );
		if ( empty( $email ) ) {
			return array();
		}

		return array(
			'@type'       => 'ContactPoint',
			'contactType' => 'customer service',
			'email'       => $email,
		);
	}

	/**
	 * Get social profiles
	 *
	 * Returns array of configured social profile URLs.
	 * Requirement 1.3: Include sameAs properties for social media profiles.
	 *
	 * @since 1.0.0
	 * @param array $organization Organization settings.
	 * @return array Array of social profile URLs.
	 */
	private function get_social_profiles( array $organization ): array {
		$social_profiles = array();

		if ( empty( $organization['social_profiles'] ) || ! is_array( $organization['social_profiles'] ) ) {
			return $social_profiles;
		}

		$profiles = $organization['social_profiles'];

		// Add each configured social profile with URL validation.
		$services = array( 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube' );
		foreach ( $services as $service ) {
			if ( ! empty( $profiles[ $service ] ) ) {
				$url = esc_url( $profiles[ $service ] );
				if ( ! empty( $url ) ) {
					$social_profiles[] = $url;
				}
			}
		}

		return $social_profiles;
	}
}
