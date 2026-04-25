<?php
/**
 * Import Manager class for orchestrating SEO data imports.
 *
 * Detects installed competitor plugins (Yoast SEO, RankMath) and manages
 * the import process with progress tracking and error handling.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Import;

use MeowSEO\Modules\Settings\Options;
use MeowSEO\Modules\Import\Importers\Base_Importer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import_Manager class.
 *
 * Orchestrates the import process, detects installed plugins, and manages import state.
 */
class Import_Manager {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Registered importers.
	 *
	 * @var array<string, Base_Importer>
	 */
	private array $importers = array();

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Register an importer.
	 *
	 * @param string        $slug     Plugin slug (e.g., 'yoast', 'rankmath').
	 * @param Base_Importer $importer Importer instance.
	 * @return void
	 */
	public function register_importer( string $slug, Base_Importer $importer ): void {
		$this->importers[ $slug ] = $importer;
	}

	/**
	 * Detect installed competitor plugins.
	 *
	 * Scans for Yoast SEO and RankMath by checking for option keys and plugin files.
	 *
	 * @return array Array of detected plugins with structure:
	 *               [
	 *                   'slug' => 'yoast',
	 *                   'name' => 'Yoast SEO',
	 *                   'installed' => true,
	 *               ]
	 */
	public function detect_installed_plugins(): array {
		$detected = array();

		foreach ( $this->importers as $slug => $importer ) {
			if ( $importer->is_plugin_installed() ) {
				$detected[] = array(
					'slug'      => $slug,
					'name'      => $importer->get_plugin_name(),
					'installed' => true,
				);
			}
		}

		return $detected;
	}

	/**
	public function cancel_import( string $import_id ): bool {
		$job = get_transient( 'meowseo_import_' . $import_id );

		if ( false === $job ) {
			return false;
		}

		// Update status to cancelled.
		$job['status']       = 'cancelled';
		$job['completed_at'] = time();

		// Update transient.
		set_transient( 'meowseo_import_' . $import_id, $job, DAY_IN_SECONDS );

		return true;
	}

	/**
	 * Update import job progress.
	 *
	 * @param string $import_id Import ID.
	 * @param array  $progress  Progress data to merge.
	 * @return bool True on success, false on failure.
	 */
	public function update_progress( string $import_id, array $progress ): bool {
		$job = get_transient( 'meowseo_import_' . $import_id );

		if ( false === $job ) {
			return false;
		}

		// Merge progress data.
		$job['progress'] = array_merge( $job['progress'], $progress );

		// Update transient.
		set_transient( 'meowseo_import_' . $import_id, $job, DAY_IN_SECONDS );

		return true;
	}

	/**
	 * Complete import job.
	 *
	 * Marks the import as completed and updates summary.
	 *
	 * @param string $import_id Import ID.
	 * @param array  $summary   Summary data.
	 * @return bool True on success, false on failure.
	 */
	public function complete_import( string $import_id, array $summary ): bool {
		$job = get_transient( 'meowseo_import_' . $import_id );

		if ( false === $job ) {
			return false;
		}

		// Update status and summary.
		$job['status']       = 'completed';
		$job['completed_at'] = time();
		$job['summary']      = array_merge( $job['summary'], $summary );

		// Update transient.
		set_transient( 'meowseo_import_' . $import_id, $job, DAY_IN_SECONDS );

		return true;
	}

	/**
	 * Log import error.
	 *
	 * @param string $import_id Import ID.
	 * @param array  $error     Error data with keys: post_id, field, error.
	 * @return bool True on success, false on failure.
	 */
	public function log_error( string $import_id, array $error ): bool {
		$job = get_transient( 'meowseo_import_' . $import_id );

		if ( false === $job ) {
			return false;
		}

		// Add error to log.
		$job['errors'][] = $error;
		$job['summary']['errors']++;

		// Update transient.
		set_transient( 'meowseo_import_' . $import_id, $job, DAY_IN_SECONDS );

		return true;
	}

	/**
	 * Get importer instance.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return Base_Importer|null Importer instance or null if not found.
	 */
	public function get_importer( string $plugin_slug ): ?Base_Importer {
		return $this->importers[ $plugin_slug ] ?? null;
	}
}
