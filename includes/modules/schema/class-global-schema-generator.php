<?php
/**
 * Global Schema Generator
 *
 * Generates WebSite and Organization schema markup for output on every page.
 * Provides global identity schema for sitelinks search box and knowledge panels.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Schema;

use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global Schema Generator class
 *
 * Orchestrates generation of WebSite and Organization schema on every page.
 *
 * @since 1.0.0
 */
class Global_Schema_Generator {

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
	 * Generate global schema
	 *
	 * Returns array of schema objects for WebSite and Organization.
	 * Requirements 1.1, 1.2: Generate WebSite and Organization schema.
	 *
	 * @since 1.0.0
	 * @return array Array of schema objects.
	 */
	public function generate_global_schema(): array {
		$schemas = array();

		// Always generate WebSite schema (Requirement 1.1).
		$schemas[] = $this->get_website_schema();

		// Generate Organization schema if configured (Requirement 1.2).
		if ( $this->should_output_schema() ) {
			$organization_schema = $this->get_organization_schema();
			if ( ! empty( $organization_schema ) ) {
				$schemas[] = $organization_schema;
			}
		}

		return $schemas;
	}

	/**
	 * Check if schema should be output
	 *
	 * Checks if organization settings are configured.
	 * Requirement 1.6: Check organization settings before output.
	 *
	 * @since 1.0.0
	 * @return bool True if organization schema should be output.
	 */
	public function should_output_schema(): bool {
		$organization = $this->options->get( 'organization', array() );

		// Organization schema requires at least a name.
		return ! empty( $organization['name'] );
	}

	/**
	 * Get WebSite schema
	 *
	 * Generates WebSite schema with search action for sitelinks search box.
	 * Requirement 1.1: Generate WebSite schema with name, url, and potentialAction.
	 *
	 * @since 1.0.0
	 * @return array WebSite schema object.
	 */
	private function get_website_schema(): array {
		$home_url = home_url( '/' );
		$site_name = get_bloginfo( 'name' );

		return array(
			'@context' => 'https://schema.org',
			'@type'    => 'WebSite',
			'@id'      => $home_url . '#website',
			'url'      => $home_url,
			'name'     => $site_name,
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);
	}

	/**
	 * Get Organization schema
	 *
	 * Generates Organization schema with brand identity information.
	 * Requirements 1.2, 1.3, 1.5: Generate Organization schema with logo, contactPoint, and sameAs.
	 *
	 * @since 1.0.0
	 * @return array Organization schema object.
	 */
	private function get_organization_schema(): array {
		$organization = $this->options->get( 'organization', array() );

		// Organization name is required.
		if ( empty( $organization['name'] ) ) {
			return array();
		}

		$home_url = home_url( '/' );

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'@id'      => $home_url . '#organization',
			'name'     => $organization['name'],
			'url'      => $home_url,
		);

		// Add logo if configured (Requirement 1.5: Omit logo when not configured).
		if ( ! empty( $organization['logo_url'] ) ) {
			$logo = array(
				'@type' => 'ImageObject',
				'url'   => esc_url( $organization['logo_url'] ),
			);

			// Add dimensions if provided.
			if ( ! empty( $organization['logo_width'] ) ) {
				$logo['width'] = absint( $organization['logo_width'] );
			}
			if ( ! empty( $organization['logo_height'] ) ) {
				$logo['height'] = absint( $organization['logo_height'] );
			}

			$schema['logo'] = $logo;
		}

		// Add contact point if email configured (Requirement 1.2).
		if ( ! empty( $organization['contact_email'] ) ) {
			$schema['contactPoint'] = array(
				'@type'       => 'ContactPoint',
				'contactType' => 'customer service',
				'email'       => sanitize_email( $organization['contact_email'] ),
			);
		}

		// Add social profiles if configured (Requirement 1.3).
		$social_profiles = $this->get_social_profiles();
		if ( ! empty( $social_profiles ) ) {
			$schema['sameAs'] = $social_profiles;
		}

		return $schema;
	}

	/**
	 * Get social profiles
	 *
	 * Returns array of configured social profile URLs.
	 * Requirement 1.3: Include sameAs properties for social media profiles.
	 *
	 * @since 1.0.0
	 * @return array Array of social profile URLs.
	 */
	private function get_social_profiles(): array {
		$organization = $this->options->get( 'organization', array() );
		$social_profiles = array();

		if ( empty( $organization['social_profiles'] ) || ! is_array( $organization['social_profiles'] ) ) {
			return $social_profiles;
		}

		$profiles = $organization['social_profiles'];

		// Add each configured social profile.
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
