<?php
/**
 * Base Importer abstract class.
 *
 * Defines the import contract and shared logic for all importers.
 * Concrete importers (Yoast, RankMath) extend this class.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Import\Importers;

use MeowSEO\Modules\Import\Batch_Processor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base_Importer abstract class.
 *
 * Defines the import contract and shared logic for all importers.
 */
abstract class Base_Importer {

	/**
	 * Batch processor instance.
	 *
	 * @var Batch_Processor
	 */
	protected Batch_Processor $processor;

	/**
	 * Import ID for tracking.
	 *
	 * @var string
	 */
	protected string $import_id = '';

	/**
	 * Constructor.
	 *
	 * @param Batch_Processor $processor Batch processor instance.
	 */
	public function __construct( Batch_Processor $processor ) {
		$this->processor = $processor;
	}

	/**
	 * Get plugin name.
	 *
	 * @return string Plugin name (e.g., "Yoast SEO", "RankMath").
	 */
	abstract public function get_plugin_name(): string;

	/**
	 * Check if plugin is installed.
	 *
	 * Checks for plugin-specific option keys or files.
	 *
	 * @return bool True if plugin is installed, false otherwise.
	 */
	abstract public function is_plugin_installed(): bool;

	/**
	 * Get postmeta mappings.
	 *
	 * Returns array mapping source postmeta keys to MeowSEO keys.
	 *
	 * @return array Postmeta mappings with structure:
	 *               [
	 *                   'source_key' => 'meowseo_key',
	 *               ]
	 */
	abstract public function get_postmeta_mappings(): array;

	/**
	 * Get termmeta mappings.
	 *
	 * Returns array mapping source termmeta keys to MeowSEO keys.
	 *
	 * @return array Termmeta mappings with structure:
	 *               [
	 *                   'source_key' => 'meowseo_key',
	 *               ]
	 */
	abstract public function get_termmeta_mappings(): array;

	/**
	 * Get options mappings.
	 *
	 * Returns array mapping source option keys to MeowSEO option keys.
	 *
	 * @return array Options mappings with structure:
	 *               [
	 *                   'source_option' => [
	 *                       'source_key' => 'meowseo_key',
	 *                   ],
	 *               ]
	 */
	abstract public function get_options_mappings(): array;

	/**
	 * Import redirects.
	 *
	 * Imports redirects from plugin-specific storage.
	 *
	 * @return array Import results with structure:
	 *               [
	 *                   'imported' => 10,
	 *                   'errors' => 1,
	 *               ]
	 */
	abstract public function import_redirects(): array;

	/**
	 * Import postmeta.
	 *
	 * Processes all posts and imports mapped postmeta fields.
	 *
	 * @param array $post_ids Optional array of specific post IDs to import.
	 *                        If empty, imports all posts.
	 * @return array Import results with structure:
	 *               [
	 *                   'imported' => 150,
	 *                   'total' => 500,
	 *                   'errors' => 3,
	 *               ]
	 */
	public function import_postmeta( array $post_ids = array() ): array {
		$mappings = $this->get_postmeta_mappings();

		if ( empty( $mappings ) ) {
			return array(
				'imported' => 0,
				'total'     => 0,
				'errors'    => 0,
			);
		}

		// Build query args.
		$args = array(
			'post_type'   => 'any',
			'post_status' => 'any',
		);

		if ( ! empty( $post_ids ) ) {
			$args['post__in'] = $post_ids;
		}

		// Process posts in batches.
		$callback = function ( $post_id ) use ( $mappings ) {
			return $this->import_post_meta_fields( $post_id, $mappings );
		};

		$result = $this->processor->process_posts( $callback, $args );

		// Transform 'processed' to 'imported' for consistency with test expectations
		return array(
			'imported' => $result['processed'] ?? 0,
			'total'    => $result['total'] ?? 0,
			'errors'   => $result['errors'] ?? 0,
		);
	}

	/**
	 * Import termmeta.
	 *
	 * Processes all terms and imports mapped termmeta fields.
	 *
	 * @param array $term_ids Optional array of specific term IDs to import.
	 *                        If empty, imports all terms.
	 * @return array Import results with structure:
	 *               [
	 *                   'imported' => 50,
	 *                   'total' => 100,
	 *                   'errors' => 1,
	 *               ]
	 */
	public function import_termmeta( array $term_ids = array() ): array {
		$mappings = $this->get_termmeta_mappings();

		if ( empty( $mappings ) ) {
			return array(
				'imported' => 0,
				'total'     => 0,
				'errors'    => 0,
			);
		}

		// Build query args.
		$args = array(
			'taxonomy'   => \get_taxonomies( array( 'public' => true ) ),
			'hide_empty' => false,
		);

		if ( ! empty( $term_ids ) ) {
			$args['include'] = $term_ids;
		}

		// Process terms in batches.
		$callback = function ( $term_id ) use ( $mappings ) {
			return $this->import_term_meta_fields( $term_id, $mappings );
		};

		$result = $this->processor->process_terms( $callback, $args );

		// Transform 'processed' to 'imported' for consistency with test expectations
		return array(
			'imported' => $result['processed'] ?? 0,
			'total'    => $result['total'] ?? 0,
			'errors'   => $result['errors'] ?? 0,
		);
	}

	/**
	 * Import options.
	 *
	 * Imports plugin settings from options table.
	 *
	 * @return array Import results with structure:
	 *               [
	 *                   'imported' => 5,
	 *                   'errors' => 0,
	 *               ]
	 */
	public function import_options(): array {
		$mappings = $this->get_options_mappings();
		$imported = 0;
		$errors   = 0;

		foreach ( $mappings as $source_option => $field_mappings ) {
			$source_data = \get_option( $source_option, array() );

			if ( empty( $source_data ) || ! is_array( $source_data ) ) {
				continue;
			}

			foreach ( $field_mappings as $source_key => $meowseo_key ) {
				if ( ! isset( $source_data[ $source_key ] ) ) {
					continue;
				}

				$value = $this->validate_and_transform( $meowseo_key, $source_data[ $source_key ] );

				if ( false === $value ) {
					$errors++;
					continue;
				}

				// Store in MeowSEO options.
				$meowseo_options = \get_option( 'meowseo_options', array() );
				$meowseo_options[ $meowseo_key ] = $value;
				\update_option( 'meowseo_options', $meowseo_options );

				$imported++;
			}
		}

		return array(
			'imported' => $imported,
			'errors'   => $errors,
		);
	}

	/**
	 * Import post meta fields.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $mappings Postmeta mappings.
	 * @return bool True on success, false on failure.
	 */
	protected function import_post_meta_fields( int $post_id, array $mappings ): bool {
		$success = true;

		foreach ( $mappings as $source_key => $meowseo_key ) {
			$value = \get_post_meta( $post_id, $source_key, true );

			if ( empty( $value ) && '0' !== $value ) {
				continue;
			}

			$transformed = $this->validate_and_transform( $meowseo_key, $value );

			if ( false === $transformed ) {
				$success = false;
				continue;
			}

			\update_post_meta( $post_id, $meowseo_key, $transformed );
		}

		return $success;
	}

	/**
	 * Import term meta fields.
	 *
	 * @param int   $term_id  Term ID.
	 * @param array $mappings Termmeta mappings.
	 * @return bool True on success, false on failure.
	 */
	protected function import_term_meta_fields( int $term_id, array $mappings ): bool {
		$success = true;

		foreach ( $mappings as $source_key => $meowseo_key ) {
			$value = \get_term_meta( $term_id, $source_key, true );

			if ( empty( $value ) && '0' !== $value ) {
				continue;
			}

			$transformed = $this->validate_and_transform( $meowseo_key, $value );

			if ( false === $transformed ) {
				$success = false;
				continue;
			}

			\update_term_meta( $term_id, $meowseo_key, $transformed );
		}

		return $success;
	}

	/**
	 * Validate and transform a value.
	 *
	 * Validates the value against MeowSEO field constraints and transforms
	 * if necessary (e.g., array to string, encoding fixes).
	 *
	 * @param string $key   MeowSEO meta key.
	 * @param mixed  $value Value to validate and transform.
	 * @return mixed Transformed value or false on validation failure.
	 */
	protected function validate_and_transform( string $key, mixed $value ): mixed {
		// Handle empty values.
		if ( empty( $value ) && '0' !== $value ) {
			return false;
		}

		// Validate UTF-8 encoding for string values.
		if ( is_string( $value ) && ! mb_check_encoding( $value, 'UTF-8' ) ) {
			// Attempt to fix encoding.
			$value = mb_convert_encoding( $value, 'UTF-8', 'UTF-8' );

			// If still invalid, reject.
			if ( ! mb_check_encoding( $value, 'UTF-8' ) ) {
				return false;
			}
		}

		// Sanitize string values.
		if ( is_string( $value ) ) {
			$value = \sanitize_text_field( $value );
		}

		return $value;
	}

	/**
	 * Set import ID for tracking.
	 *
	 * @param string $import_id Import ID.
	 * @return void
	 */
	public function set_import_id( string $import_id ): void {
		$this->import_id = $import_id;
	}

	/**
	 * Get import ID.
	 *
	 * @return string Import ID.
	 */
	public function get_import_id(): string {
		return $this->import_id;
	}
}
