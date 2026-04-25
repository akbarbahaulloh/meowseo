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

use MeowSEO\Options;
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
	 * Batch processor instance.
	 *
	 * @var Batch_Processor
	 */
	private Batch_Processor $processor;

	/**
	 * Registered importers.
	 *
	 * @var array<string, Base_Importer>
	 */
	private array $importers = array();

	/**
	 * Constructor.
	 *
	 * @param Options         $options   Options instance.
	 * @param Batch_Processor $processor Batch processor instance.
	 */
	public function __construct( Options $options, Batch_Processor $processor ) {
		$this->options   = $options;
		$this->processor = $processor;
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
	 * Start an import process.
	 *
	 * Validates plugin detection, creates import job, and returns import_id.
	 *
	 * @param string $plugin_slug Plugin slug to import from (e.g., 'yoast', 'rankmath').
	 * @return array Import job data with structure:
	 *               [
	 *                   'import_id' => 'yoast_20240115_123456',
	 *                   'status' => 'pending',
	 *                   'plugin' => 'yoast',
	 *               ]
	 *               Or WP_Error on failure.
	 */
	public function start_import( string $plugin_slug ): array {
		// Validate plugin slug.
		if ( ! isset( $this->importers[ $plugin_slug ] ) ) {
			return array(
				'error' => true,
				'message' => sprintf(
					/* translators: %s: plugin slug */
					__( 'Unknown plugin: %s', 'meowseo' ),
					$plugin_slug
				),
			);
		}

		$importer = $this->importers[ $plugin_slug ];

		// Verify plugin is installed.
		if ( ! $importer->is_plugin_installed() ) {
			return array(
				'error' => true,
				'message' => sprintf(
					/* translators: %s: plugin name */
					__( '%s is not installed or detected.', 'meowseo' ),
					$importer->get_plugin_name()
				),
			);
		}

		// Generate import ID.
		$import_id = $plugin_slug . '_' . gmdate( 'Ymd_His' );

		// Calculate initial totals for accurate UI progress.
		$total_options   = $importer->get_total_options();
		$total_redirects = $importer->get_total_redirects();
		$total_terms     = $importer->get_total_terms();
		$total_posts     = $importer->get_total_posts();

		// Create import job.
		$job = array(
			'import_id'    => $import_id,
			'plugin'       => $plugin_slug,
			'status'       => 'pending',
			'started_at'   => time(),
			'completed_at' => null,
			'progress'     => array(
				'options'   => array( 'processed' => 0, 'total' => $total_options, 'is_done' => false ),
				'redirects' => array( 'processed' => 0, 'total' => $total_redirects, 'is_done' => false ),
				'terms'     => array( 'processed' => 0, 'total' => $total_terms, 'is_done' => false, 'offset' => 0 ),
				'posts'     => array( 'processed' => 0, 'total' => $total_posts, 'is_done' => false, 'page' => 1 ),
			),
			'summary'      => array(
				'posts_imported'     => 0,
				'terms_imported'     => 0,
				'options_imported'   => 0,
				'redirects_imported' => 0,
				'errors'             => 0,
			),
			'errors'       => array(),
		);

		// Store import job in transient (expires in 24 hours).
		set_transient( 'meowseo_import_' . $import_id, $job, DAY_IN_SECONDS );

		return $job;
	}

	/**
	 * Get import status.
	 *
	 * Returns progress (processed/total counts, current phase).
	 *
	 * @param string $import_id Import ID.
	 * @return array Import job data or error array.
	 */
	public function get_import_status( string $import_id ): array {
		$job = get_transient( 'meowseo_import_' . $import_id );

		if ( false === $job ) {
			return array(
				'error' => true,
				'message' => __( 'Import job not found or expired.', 'meowseo' ),
			);
		}

		return $job;
	}

	/**
	 * Cancel an import process.
	 *
	 * Marks the import as cancelled and cleans up transients.
	 *
	 * @param string $import_id Import ID.
	 * @return bool True on success, false on failure.
	 */
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
