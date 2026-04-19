<?php
/**
 * Yoast SEO Importer class.
 *
 * Imports SEO data from Yoast SEO plugin to MeowSEO.
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
 * Yoast_Importer class.
 *
 * Imports SEO data from Yoast SEO plugin.
 */
class Yoast_Importer extends Base_Importer {

	/**
	 * Postmeta mappings from Yoast to MeowSEO.
	 *
	 * @var array
	 */
	private array $postmeta_mappings = array(
		'_yoast_wpseo_title'             => '_meowseo_title',
		'_yoast_wpseo_metadesc'          => '_meowseo_description',
		'_yoast_wpseo_focuskw'           => '_meowseo_focus_keyword',
		'_yoast_wpseo_canonical'         => '_meowseo_canonical_url',
		'_yoast_wpseo_meta-robots-noindex'  => '_meowseo_robots_noindex',
		'_yoast_wpseo_meta-robots-nofollow' => '_meowseo_robots_nofollow',
		'_yoast_wpseo_opengraph-title'      => '_meowseo_og_title',
		'_yoast_wpseo_opengraph-description' => '_meowseo_og_description',
		'_yoast_wpseo_twitter-title'        => '_meowseo_twitter_title',
		'_yoast_wpseo_twitter-description'  => '_meowseo_twitter_description',
	);

	/**
	 * Termmeta mappings from Yoast to MeowSEO.
	 *
	 * @var array
	 */
	private array $termmeta_mappings = array(
		'_wpseo_title' => '_meowseo_title',
		'_wpseo_desc'  => '_meowseo_description',
	);

	/**
	 * Options mappings from Yoast to MeowSEO.
	 *
	 * @var array
	 */
	private array $options_mappings = array(
		'wpseo' => array(
			'separator'            => 'separator',
			'title-home-wpseo'     => 'homepage_title',
			'metadesc-home-wpseo'  => 'homepage_description',
		),
		'wpseo_titles' => array(
			'title-post'      => 'title_pattern_post',
			'title-page'      => 'title_pattern_page',
			'title-category'  => 'title_pattern_category',
			'title-post_tag'  => 'title_pattern_tag',
			'title-author'    => 'title_pattern_author',
			'title-archive'   => 'title_pattern_archive',
			'title-search'    => 'title_pattern_search',
			'title-404'       => 'title_pattern_404',
		),
	);

	/**
	 * Constructor.
	 *
	 * @param Batch_Processor $processor Batch processor instance.
	 */
	public function __construct( Batch_Processor $processor ) {
		parent::__construct( $processor );
	}

	/**
	 * Get plugin name.
	 *
	 * @return string Plugin name.
	 */
	public function get_plugin_name(): string {
		return 'Yoast SEO';
	}

	/**
	 * Check if Yoast SEO is installed.
	 *
	 * Checks for Yoast option keys in the database.
	 *
	 * @return bool True if Yoast SEO is installed, false otherwise.
	 */
	public function is_plugin_installed(): bool {
		// Check for Yoast SEO option keys.
		$wpseo_options = \get_option( 'wpseo', false );
		$wpseo_titles  = \get_option( 'wpseo_titles', false );

		// If either option exists, Yoast is installed.
		return ( false !== $wpseo_options || false !== $wpseo_titles );
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

	/**
	 * Import redirects from Yoast SEO.
	 *
	 * Queries wpseo_redirect custom post type and transforms to MeowSEO format.
	 *
	 * @return array Import results.
	 */
	public function import_redirects(): array {
		$imported = 0;
		$errors   = 0;

		// Query wpseo_redirect custom post type.
		$args = array(
			'post_type'      => 'wpseo_redirect',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return array(
				'imported' => 0,
				'errors'   => 0,
			);
		}

		foreach ( $query->posts as $post_id ) {
			$result = $this->import_single_redirect( $post_id );

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
	 * @param int $post_id Redirect post ID.
	 * @return bool True on success, false on failure.
	 */
	private function import_single_redirect( int $post_id ): bool {
		// Get redirect data from Yoast.
		$source_url = \get_post_meta( $post_id, '_yoast_wpseo_redirect_source', true );
		$target_url = \get_post_meta( $post_id, '_yoast_wpseo_redirect_target', true );
		$redirect_type = \get_post_meta( $post_id, '_yoast_wpseo_redirect_type', true );

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
	 * @param mixed $type Redirect type from Yoast.
	 * @return int Valid redirect type (default: 301).
	 */
	private function validate_redirect_type( mixed $type ): int {
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
	 * Extends parent validation with Yoast-specific transformations.
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

		// Handle robots noindex/nofollow boolean values.
		if ( in_array( $key, array( '_meowseo_robots_noindex', '_meowseo_robots_nofollow' ), true ) ) {
			// Yoast stores as '1' for true, '0' or empty for false.
			return ( '1' === (string) $value ) ? '1' : '0';
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

		// Transform Yoast title patterns to MeowSEO format.
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
	private function is_title_pattern_key( string $key ): bool {
		return ( 0 === strpos( $key, 'title_pattern_' ) );
	}

	/**
	 * Transform Yoast title pattern to MeowSEO format.
	 *
	 * Yoast uses %%variable%% syntax, MeowSEO uses {variable} syntax.
	 *
	 * @param string $pattern Yoast title pattern.
	 * @return string MeowSEO title pattern.
	 */
	private function transform_title_pattern( string $pattern ): string {
		// Map Yoast variables to MeowSEO variables.
		$variable_map = array(
			'%%title%%'        => '{title}',
			'%%sitename%%'     => '{site_name}',
			'%%sitedesc%%'     => '{tagline}',
			'%%sep%%'          => '{sep}',
			'%%page%%'         => '{page}',
			'%%category%%'     => '{category}',
			'%%tag%%'          => '{tag}',
			'%%term%%'         => '{term}',
			'%%name%%'         => '{author_name}',
			'%%date%%'         => '{date}',
			'%%searchphrase%%' => '{search_phrase}',
			'%%posttype%%'     => '{post_type}',
			'%%id%%'           => '{post_id}',
			'%%excerpt%%'      => '{excerpt}',
		);

		// Replace Yoast variables with MeowSEO variables.
		foreach ( $variable_map as $yoast_var => $meowseo_var ) {
			$pattern = str_replace( $yoast_var, $meowseo_var, $pattern );
		}

		return $pattern;
	}
}
