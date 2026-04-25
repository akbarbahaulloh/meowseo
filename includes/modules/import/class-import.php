<?php
/**
 * Import Module
 *
 * Handles SEO data import from competitor plugins (Yoast SEO, RankMath).
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Import;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;
use MeowSEO\Modules\Import\Importers\Yoast_Importer;
use MeowSEO\Modules\Import\Importers\RankMath_Importer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import module class.
 *
 * Orchestrates SEO data import from competitor plugins.
 */
class Import implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Import Manager instance.
	 *
	 * @var Import_Manager
	 */
	private Import_Manager $import_manager;

	/**
	 * Import Admin instance.
	 *
	 * @var Import_Admin
	 */
	private Import_Admin $import_admin;

	/**
	 * Batch Processor instance.
	 *
	 * @var Batch_Processor
	 */
	private Batch_Processor $batch_processor;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;

		// Initialize batch processor.
		$this->batch_processor = new Batch_Processor();

		// Initialize import manager.
		$this->import_manager = new Import_Manager( $options, $this->batch_processor );

		// Register importers.
		$this->register_importers();

		// Initialize admin interface.
		$this->import_admin = new Import_Admin( $this->import_manager );
	}

	/**
	 * Boot the module.
	 *
	 * Registers hooks for import functionality.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Boot admin interface.
		if ( is_admin() ) {
			$this->import_admin->boot();
		}
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'import';
	}

	/**
	 * Register importers.
	 *
	 * Registers Yoast and RankMath importers with the import manager.
	 *
	 * @return void
	 */
	private function register_importers(): void {
		// Register Yoast importer.
		$yoast_importer = new Yoast_Importer( $this->batch_processor );
		$this->import_manager->register_importer( 'yoast', $yoast_importer );

		// Register RankMath importer.
		$rankmath_importer = new RankMath_Importer( $this->batch_processor );
		$this->import_manager->register_importer( 'rankmath', $rankmath_importer );
	}

	/**
	 * Get Import Manager instance.
	 *
	 * @return Import_Manager Import Manager instance.
	 */
	public function get_import_manager(): Import_Manager {
		return $this->import_manager;
	}

	/**
	 * Get Import Admin instance.
	 *
	 * @return Import_Admin Import Admin instance.
	 */
	public function get_import_admin(): Import_Admin {
		return $this->import_admin;
	}
}
