<?php
/**
 * RankMath Importer class.
 *
 * Imports SEO data from RankMath plugin to MeowSEO.
 * Handles postmeta, termmeta, options, and redirects.
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
 * RankMath_Importer class.
 *
 * Imports SEO data from RankMath plugin.
 */
class RankMath_Importer extends Base_Importer {

	/**
	 * Postmeta mappings from RankMath to MeowSEO.
	 *
	 * @var array
	 */
	private array $postmeta_mappings = array(
		'rank_math_title'                => '_meowseo_title',
		'rank_math_description'          => '_meowseo_description',
		'rank_math_focus_keyword'        => '_meowseo_focus_keyword',
		'rank_math_canonical_url'        => '_meowseo_canonical_url',
		'rank_math_facebook_title'       => '_meowseo_og_title',
		'rank_math_facebook_description' => '_meowseo_og_description',
		'rank_math_twitter_title'        => '_meowseo_twitter_title',
		'rank_math_twitter_description'  => '_meowseo_twitter_description',
	);

	/**
	 * Termmeta mappings from RankMath to MeowSEO.
	 *
	 * @var array
	 */
	private array $termmeta_mappings = array(
		'rank_math_title'       => '_meowseo_title',
		'rank_math_description' => '_meowseo_description',
	);

	/**
	 * Options mappings from RankMath to MeowSEO.
	 *
	 * @var array
	 */
	private array $options_mappings = array(
		'rank-math-options-general' => array(
			'separator'         => 'separator',
			'homepage_title'    => 'homepage_title',
			'homepage_description' => 'homepage_description',
		),
		'rank-math-options-titles' => array(
			'title_post'      => 'title_pattern_post',
			'title_page'      => 'title_pattern_page',
			'title_category'  => 'title_pattern_category',
			'title_post_tag'  => 'title_pattern_tag',
			'title_author'    => 'title_pattern_author',
			'title_date'      => 'title_pattern_archive',
			'title_search'    => 'title_pattern_search',
			'title_404'       => 'title_pattern_404',
		),
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get plugin name.
	 *
	 * @return string Plugin name.
	 */
	public function get_plugin_name(): string {
		return 'RankMath';
	}

	/**
	 * Check if RankMath is installed.
	 *
	 * Checks for RankMath option keys in the database.
	 *
	 * @return bool True if RankMath is installed, false otherwise.
	 */
	public function is_plugin_installed(): bool {
		// Check for RankMath option keys.
		$general_options = \get_option( 'rank-math-options-general', false );
		$titles_options  = \get_option( 'rank-math-options-titles', false );

		// If either option exists, RankMath is installed.
		return ( false !== $general_options || false !== $titles_options );
	}

	/**
	 * Get postmeta mappings.
	 *
	 * @return array Postmeta mappings.
	 */
	public function get_postmeta_mappings(): array {
		return $this->postmeta_mappings;
	}

	/**
	 * Get termmeta mappings.
	 *
	 * @return array Termmeta mappings.
	 */
	public function get_termmeta_mappings(): array {
		return $this->termmeta_mappings;
	}

	/**
	 * Get options mappings.
	 *
	 * @return array Options mappings.
	 */
	public function get_options_mappings(): array {
		return $this->options_mappings;
	}

	 * Import postmeta with RankMath-specific handling.
	 *
	 * Extends parent method to handle special RankMath fields:
	 * - rank_math_robots: Array containing multiple directives
	 * - rank_math_focus_keyword: Comma-separated string
	 *
	 * @param array $post_ids Array of post IDs to import.
	 * @return array Import results.
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
			$result = $this->import_rankmath_post_meta_fields( $post_id, $mappings );
			if ( $result ) {
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
	 * Import RankMath post meta fields with special handling.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $mappings Postmeta mappings.
	 * @return bool True on success, false on failure.
	 */
	protected function import_rankmath_post_meta_fields( int $post_id, array $mappings ): bool {
		$success = true;

		// Import standard mappings.
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

		// Handle rank_math_robots special case (array splitting).
		$robots_result = $this->import_robots_meta( $post_id );
		if ( ! $robots_result ) {
			$success = false;
		}

		// Handle rank_math_focus_keyword special case (comma-separated).
		$keyword_result = $this->import_focus_keywords( $post_id );
		if ( ! $keyword_result ) {
			$success = false;
		}

		return $success;
	}

	/**
	 * Import robots meta from rank_math_robots array.
	 *
	 * RankMath stores robots directives as an array, e.g., ['noindex', 'nofollow'].
	 * MeowSEO stores them as separate boolean fields.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True on success, false on failure.
	 */
	protected function import_robots_meta( int $post_id ): bool {
		$robots_value = \get_post_meta( $post_id, 'rank_math_robots', true );

		// If no robots value, nothing to import.
		if ( empty( $robots_value ) ) {
			return true;
		}

		// Handle both array and string formats.
		if ( is_string( $robots_value ) ) {
			// Maybe it's a serialized string or JSON.
			$decoded = json_decode( $robots_value, true );
			if ( is_array( $decoded ) ) {
				$robots_value = $decoded;
			} else {
				// Single value as string.
				$robots_value = array( $robots_value );
			}
		}

		if ( ! is_array( $robots_value ) ) {
			return true;
		}

		// Check for noindex.
		$noindex = in_array( 'noindex', $robots_value, true ) ? '1' : '0';
		\update_post_meta( $post_id, '_meowseo_robots_noindex', $noindex );

		// Check for nofollow.
		$nofollow = in_array( 'nofollow', $robots_value, true ) ? '1' : '0';
		\update_post_meta( $post_id, '_meowseo_robots_nofollow', $nofollow );

		return true;
	}

	/**
	 * Import focus keywords from comma-separated string.
	 *
	 * RankMath stores multiple keywords as comma-separated string.
	 * First keyword becomes primary, rest become secondary.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True on success, false on failure.
	 */
	protected function import_focus_keywords( int $post_id ): bool {
		$keywords_value = \get_post_meta( $post_id, 'rank_math_focus_keyword', true );

		// If no keywords value, nothing to import.
		if ( empty( $keywords_value ) ) {
			return true;
		}

		// Split by comma.
		$keywords = explode( ',', $keywords_value );

		// Trim and filter empty values.
		$keywords = array_filter( array_map( 'trim', $keywords ) );

		if ( empty( $keywords ) ) {
			return true;
		}

		// First keyword is primary.
		$primary_keyword = array_shift( $keywords );
		$primary_keyword = \sanitize_text_field( $primary_keyword );

		if ( ! empty( $primary_keyword ) ) {
			\update_post_meta( $post_id, '_meowseo_focus_keyword', $primary_keyword );
		}

		// Remaining keywords are secondary (max 4 to stay within 5 total limit).
		if ( ! empty( $keywords ) ) {
			$secondary_keywords = array_slice( $keywords, 0, 4 );
			$secondary_keywords = array_map( '\sanitize_text_field', $secondary_keywords );
			$secondary_keywords = array_filter( $secondary_keywords );

			if ( ! empty( $secondary_keywords ) ) {
				\update_post_meta( $post_id, '_meowseo_secondary_keywords', \wp_json_encode( array_values( $secondary_keywords ) ) );
			}
		}

		return true;
	}

	/**
	 * Get total redirects to import.
	 *
	 * @return int Total redirects.
	 */
	public function get_total_redirects(): int {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rank_math_redirections';
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$table_name
		) );

		if ( $table_name !== $table_exists ) {
			return 0;
		}

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		return (int) $total;
	}

	/**
	 * Import redirects from RankMath.
	 *
	 * Queries rank_math_redirections database table and transforms to MeowSEO format.
	 *
	 * @return array Import results.
	 */
	public function import_redirects(): array {
		global $wpdb;

		$imported = 0;
		$errors   = 0;

		// Check if the table exists.
		$table_name = $wpdb->prefix . 'rank_math_redirections';
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$table_name
		) );

		if ( $table_name !== $table_exists ) {
			// Table doesn't exist, nothing to import.
			return array(
				'imported' => 0,
				'errors'   => 0,
			);
		}

		// Query all redirects.
		$redirects = $wpdb->get_results(
			"SELECT * FROM {$table_name}"
		);

		if ( empty( $redirects ) ) {
			return array(
				'imported' => 0,
				'errors'   => 0,
			);
		}

		foreach ( $redirects as $redirect ) {
			$result = $this->import_single_redirect( $redirect );

			if ( $result ) {
				$imported++;
			} else {
				$errors++;
			}
		}

		return array(
			'imported' => $imported,
			'errors'   => $errors,
		);
	}

	/**
	 * Import a single redirect.
	 *
	 * @param object $redirect Redirect object from database.
	 * @return bool True on success, false on failure.
	 */
	protected function import_single_redirect( object $redirect ): bool {
		// Map columns: url_to → source_url, url_from → target_url, header_code → redirect_type.
		$source_url   = isset( $redirect->url_to ) ? $redirect->url_to : '';
		$target_url   = isset( $redirect->url_from ) ? $redirect->url_from : '';
		$redirect_type = isset( $redirect->header_code ) ? $redirect->header_code : 301;

		// Validate required fields.
		if ( empty( $source_url ) || empty( $target_url ) ) {
			return false;
		}

		// Validate and transform redirect type.
		$redirect_type = $this->validate_redirect_type( $redirect_type );

		// Create MeowSEO redirect post.
		$redirect_data = array(
			'post_type'   => 'meowseo_redirect',
			'post_title'  => $source_url,
			'post_status' => 'publish',
		);

		$redirect_id = \wp_insert_post( $redirect_data );

		if ( \is_wp_error( $redirect_id ) ) {
			return false;
		}

		// Add redirect meta.
		\update_post_meta( $redirect_id, '_meowseo_redirect_source', \sanitize_text_field( $source_url ) );
		\update_post_meta( $redirect_id, '_meowseo_redirect_target', \sanitize_url( $target_url ) );
		\update_post_meta( $redirect_id, '_meowseo_redirect_type', $redirect_type );

		return true;
	}

	/**
	 * Validate redirect type.
	 *
	 * Ensures redirect type is one of the allowed values: 301, 302, 307, 410.
	 *
	 * @param mixed $type Redirect type from RankMath.
	 * @return int Valid redirect type (default: 301).
	 */
	protected function validate_redirect_type( mixed $type ): int {
		$allowed_types = array( 301, 302, 307, 410 );

		// Handle string types.
		if ( is_string( $type ) ) {
			$type = intval( $type );
		}

		// Validate.
		if ( in_array( $type, $allowed_types, true ) ) {
			return (int) $type;
		}

		// Default to 301.
		return 301;
	}

	/**
	 * Validate and transform a value.
	 *
	 * Extends parent validation with RankMath-specific transformations.
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

		// Transform RankMath title patterns to MeowSEO format.
		if ( $this->is_title_pattern_key( $key ) ) {
			$value = $this->transform_title_pattern( $value );
		}

		return $value;
	}

	/**
	 * Check if key is a title pattern key.
	 *
	 * @param string $key Meta key.
	 * @return bool True if title pattern key.
	 */
	protected function is_title_pattern_key( string $key ): bool {
		return ( 0 === strpos( $key, 'title_pattern_' ) );
	}

	/**
	 * Transform RankMath title pattern to MeowSEO format.
	 *
	 * RankMath uses %variable% syntax, MeowSEO uses {variable} syntax.
	 *
	 * @param string $pattern RankMath title pattern.
	 * @return string MeowSEO title pattern.
	 */
	protected function transform_title_pattern( string $pattern ): string {
		// Map RankMath variables to MeowSEO variables.
		$variable_map = array(
			'%title%'         => '{title}',
			'%sitename%'      => '{site_name}',
			'%sitedesc%'      => '{tagline}',
			'%sep%'           => '{sep}',
			'%page%'          => '{page}',
			'%category%'      => '{category}',
			'%tag%'           => '{tag}',
			'%term%'          => '{term}',
			'%name%'          => '{author_name}',
			'%date%'          => '{date}',
			'%searchphrase%'  => '{search_phrase}',
			'%posttype%'      => '{post_type}',
			'%id%'            => '{post_id}',
			'%excerpt%'       => '{excerpt}',
			'%currentdate%'   => '{current_date}',
			'%currentyear%'   => '{current_year}',
			'%currentmonth%'  => '{current_month}',
		);

		// Replace RankMath variables with MeowSEO variables.
		foreach ( $variable_map as $rankmath_var => $meowseo_var ) {
			$pattern = str_replace( $rankmath_var, $meowseo_var, $pattern );
		}

		return $pattern;
	}
}
