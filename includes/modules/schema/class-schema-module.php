<?php
/**
 * Schema Generator Module
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

namespace MeowSEO\Modules\Schema;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_Module class.
 */
class Schema_Module implements Module {

	/**
	 * Module ID.
	 *
	 * @var string
	 */
	protected $id = 'schema';

	/**
	 * Module name.
	 *
	 * @var string
	 */
	protected $name = 'Schema Generator';

	/**
	 * Module description.
	 *
	 * @var string
	 */
	protected $description = 'Advanced Schema.org markup generator with visual builder';

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->init();
	}

	/**
	 * Initialize the module.
	 */
	public function init(): void {
		// Load dependencies.
		$this->load_dependencies();

		// Initialize components.
		if ( is_admin() ) {
			new Schema_Admin();
		}

		new Schema_Frontend();
		new Schema_REST();
	}

	/**
	 * Load module dependencies.
	 */
	private function load_dependencies(): void {
		require_once __DIR__ . '/class-schema-db.php';
		require_once __DIR__ . '/class-schema-variables.php';
		require_once __DIR__ . '/class-schema-type.php';
		require_once __DIR__ . '/class-schema-registry.php';
		require_once __DIR__ . '/class-schema-jsonld.php';
		require_once __DIR__ . '/class-schema-frontend.php';
		require_once __DIR__ . '/class-schema-rest.php';
		
		if ( is_admin() ) {
			require_once __DIR__ . '/class-schema-admin.php';
		}

		// Load schema types.
		$this->load_schema_types();

		// Trigger action after types are loaded.
		do_action( 'meowseo_schema_types_loaded' );
	}

	/**
	 * Load all schema types.
	 */
	private function load_schema_types(): void {
		$types_dir = __DIR__ . '/types';
		
		if ( ! is_dir( $types_dir ) ) {
			return;
		}

		$files = glob( $types_dir . '/class-*-schema.php' );
		
		foreach ( $files as $file ) {
			require_once $file;
		}
	}

	/**
	 * Get module settings.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		return array(
			'enabled'              => true,
			'default_schema_type'  => 'article',
			'enable_breadcrumbs'   => true,
			'enable_organization'  => true,
			'enable_website'       => true,
			'organization_type'    => 'Organization',
			'organization_name'    => get_bloginfo( 'name' ),
			'organization_logo'    => '',
		);
	}

	/**
	 * Get module ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}
}
