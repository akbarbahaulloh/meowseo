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
	 * Import ID for tracking.
	 *
	 * @var string
	 */
	protected string $import_id = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
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
	 * Get total redirects to import.
	 *
	 * @return int Total redirects.
	 */
	abstract public function get_total_redirects(): int;

	/**
	 * Get total posts to import.
	 *
	 * @return int Total posts.
	 */
	public function get_total_posts(): int {
		$args = array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);
		$query = new \WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Get total terms to import.
	 *
	 * @return int Total terms.
	 */
	public function get_total_terms(): int {
		global $wpdb;
		$taxonomies = \get_taxonomies( array( 'public' => true ) );
		if ( empty( $taxonomies ) ) {
			return 0;
		}

		$in_tax = "'" . implode( "','", array_map( 'esc_sql', $taxonomies ) ) . "'";
		$total  = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ($in_tax)" );

		return (int) $total;
	}

	/**
	 * Get total options to import.
	 *
	 * @return int Total options.
	 */
	public function get_total_options(): int {
		$mappings = $this->get_options_mappings();
		$total    = 0;
		foreach ( $mappings as $group ) {
			$total += count( $group );
		}
		return $total;
	}

	/**
	 * Import postmeta.
	 *
	 * Processes specific posts and imports mapped postmeta fields.
	 *
	 * @param array $post_ids Array of post IDs to import.
	 * @return array Import results with structure:
	 *               [
	 *                   'imported' => 150,
	 *                   'total' => 500,
	 *                   'errors' => 3,
	 *               ]
	 */
	public function import_postmeta( array $post_ids ): array {
		$mappings = $this->get_postmeta_mappings();

		if ( empty( $mappings ) || empty( $post_ids ) ) {
			return array(
				'imported' => 0,
				'total'    => count( $post_ids ),
				'errors'   => 0,
			);
		}

		$imported = 0;
		$errors   = 0;

		foreach ( $post_ids as $post_id ) {
			$result = $this->import_post_meta_fields( $post_id, $mappings );
			// Count per post: success if at least 1 field imported, error if all fields failed.
			if ( $result['imported'] > 0 || $result['errors'] === 0 ) {
				$imported++;
			} else {
				$errors++;
			}
		}

		return array(
			'imported' => $imported,
			'total'    => count( $post_ids ),
			'errors'   => $errors,
		);
	}

	/**
	 * Import termmeta.
	 *
	 * Processes specific terms and imports mapped termmeta fields.
	 *
	 * @param array $term_ids Array of term IDs to import.
	 * @return array Import results with structure:
	 *               [
	 *                   'imported' => 50,
	 *                   'total' => 100,
	 *                   'errors' => 1,
	 *               ]
	 */
	public function import_termmeta( array $term_ids ): array {
		$mappings = $this->get_termmeta_mappings();

		if ( empty( $mappings ) || empty( $term_ids ) ) {
			return array(
				'imported' => 0,
				'total'    => count( $term_ids ),
				'errors'   => 0,
			);
		}

		$imported = 0;
		$errors   = 0;

		foreach ( $term_ids as $term_id ) {
			$result = $this->import_term_meta_fields( $term_id, $mappings );
			// Count per term: success if at least 1 field imported.
			if ( $result['imported'] > 0 || $result['errors'] === 0 ) {
				$imported++;
			} else {
				$errors++;
			}
		}

		return array(
			'imported' => $imported,
			'total'    => count( $term_ids ),
			'errors'   => $errors,
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
	 * @return array Result with 'imported' and 'errors' counts.
	 */
	protected function import_post_meta_fields( int $post_id, array $mappings ): array {
		$imported = 0;
		$errors   = 0;

		foreach ( $mappings as $source_key => $meowseo_key ) {
			$value = \get_post_meta( $post_id, $source_key, true );

			if ( empty( $value ) && '0' !== $value ) {
				continue;
			}

			$transformed = $this->validate_and_transform( $meowseo_key, $value );

			if ( false === $transformed ) {
				$errors++;
				continue;
			}

			\update_post_meta( $post_id, $meowseo_key, $transformed );
			$imported++;
		}

		return array(
			'imported' => $imported,
			'errors'   => $errors,
		);
	}

	/**
	 * Import term meta fields.
	 *
	 * @param int   $term_id  Term ID.
	 * @param array $mappings Termmeta mappings.
	 * @return array Result with 'imported' and 'errors' counts.
	 */
	protected function import_term_meta_fields( int $term_id, array $mappings ): array {
		$imported = 0;
		$errors   = 0;

		foreach ( $mappings as $source_key => $meowseo_key ) {
			$value = \get_term_meta( $term_id, $source_key, true );

			if ( empty( $value ) && '0' !== $value ) {
				continue;
			}

			$transformed = $this->validate_and_transform( $meowseo_key, $value );

			if ( false === $transformed ) {
				$errors++;
				continue;
			}

			\update_term_meta( $term_id, $meowseo_key, $transformed );
			$imported++;
		}

		return array(
			'imported' => $imported,
			'errors'   => $errors,
		);
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
