<?php
/**
 * Schema Frontend Handler
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

namespace MeowSEO\Modules\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_Frontend class.
 */
class Schema_Frontend {

	/**
	 * JSON-LD instance.
	 *
	 * @var Schema_JsonLD
	 */
	private $jsonld;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->jsonld = new Schema_JsonLD();
		$this->init();
	}

	/**
	 * Initialize frontend hooks.
	 */
	private function init(): void {
		// Setup JSON-LD output.
		$this->jsonld->setup();

		// Register shortcodes.
		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	/**
	 * Register shortcodes.
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'meowseo_schema', array( $this, 'schema_shortcode' ) );
	}

	/**
	 * Schema shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function schema_shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'   => '',
				'type' => '',
			),
			$atts,
			'meowseo_schema'
		);

		if ( empty( $atts['id'] ) ) {
			return '';
		}

		// Get schema by shortcode ID.
		$result = Schema_DB::get_schema_by_shortcode( $atts['id'] );

		if ( ! $result ) {
			return '';
		}

		$schema = $result['schema'];

		if ( empty( $schema['@type'] ) ) {
			return '';
		}

		// Get schema type.
		$type = is_array( $schema['@type'] ) ? $schema['@type'][0] : $schema['@type'];
		$schema_type = Schema_Registry::get( $type );

		if ( ! $schema_type ) {
			return '';
		}

		// Generate JSON-LD.
		$post   = get_post( $result['post_id'] );
		$jsonld = $schema_type->generate( $schema, $post );

		// Output as JSON-LD script.
		$json = array(
			'@context' => 'https://schema.org',
		);
		$json = array_merge( $json, $jsonld );

		$output = wp_json_encode( $json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $output ) {
			return '';
		}

		return '<script type="application/ld+json" class="meowseo-schema-shortcode">' . $output . '</script>';
	}

	/**
	 * Get JSON-LD instance.
	 *
	 * @return Schema_JsonLD
	 */
	public function get_jsonld(): Schema_JsonLD {
		return $this->jsonld;
	}
}
