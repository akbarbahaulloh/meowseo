<?php
/**
 * WebSite Schema Generator
 *
 * Generates WebSite schema markup with search action for sitelinks search box.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Schema\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebSite Schema Generator class
 *
 * Generates WebSite schema with name, url, and potentialAction properties.
 * Requirement 1.1: Generate WebSite schema for sitelinks search box.
 *
 * @since 1.0.0
 */
class WebSite_Schema {

	/**
	 * Generate WebSite schema
	 *
	 * Generates WebSite schema with search action for sitelinks search box.
	 * Uses home_url() for URL and get_bloginfo('name') for site name.
	 * Requirement 1.1: Generate WebSite schema with @type, @id, url, name, and potentialAction.
	 *
	 * @since 1.0.0
	 * @return array WebSite schema object.
	 */
	public function generate(): array {
		$home_url  = home_url( '/' );
		$site_name = get_bloginfo( 'name' );

		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'@id'             => $home_url . '#website',
			'url'             => $home_url,
			'name'            => $site_name,
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
}
