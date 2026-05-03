<?php
/**
 * Schema Database Operations
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

namespace MeowSEO\Modules\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_DB class.
 */
class Schema_DB {

	/**
	 * Meta key prefix for schemas.
	 */
	const META_PREFIX = '_meowseo_schema_';

	/**
	 * Shortcode meta key prefix.
	 */
	const SHORTCODE_PREFIX = '_meowseo_shortcode_schema_';

	/**
	 * Get all schemas for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $table   Meta table name (postmeta or termmeta).
	 * @param bool   $from_db Force fetch from database.
	 * @return array Array of schemas.
	 */
	public static function get_schemas( int $post_id, string $table = 'postmeta', bool $from_db = false ): array {
		static $cache = array();

		// Check cache.
		$cache_key = $table . '_' . $post_id;
		if ( ! $from_db && isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		global $wpdb;

		$key    = 'termmeta' === $table ? 'term_id' : 'post_id';
		$table  = 'termmeta' === $table ? $wpdb->termmeta : $wpdb->postmeta;
		$prefix = self::META_PREFIX;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, meta_key, meta_value 
				FROM {$table} 
				WHERE {$key} = %d 
				AND meta_key LIKE %s
				ORDER BY meta_id ASC",
				$post_id,
				$wpdb->esc_like( $prefix ) . '%'
			)
		);

		$schemas = array();

		foreach ( $results as $row ) {
			$value = maybe_unserialize( $row->meta_value );
			
			if ( empty( $value ) || ! is_array( $value ) ) {
				continue;
			}

			$schema_id            = 'schema-' . $row->meta_id;
			$schemas[ $schema_id ] = $value;
		}

		// Cache the result.
		$cache[ $cache_key ] = $schemas;

		return $schemas;
	}

	/**
	 * Get a single schema by ID.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $schema_id Schema ID (e.g., 'schema-123').
	 * @param string $table     Meta table name.
	 * @return array|null Schema data or null if not found.
	 */
	public static function get_schema( int $post_id, string $schema_id, string $table = 'postmeta' ): ?array {
		$schemas = self::get_schemas( $post_id, $table );
		
		return $schemas[ $schema_id ] ?? null;
	}

	/**
	 * Save a schema.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $schema  Schema data.
	 * @param string $table   Meta table name.
	 * @return int|false Meta ID on success, false on failure.
	 */
	public static function save_schema( int $post_id, array $schema, string $table = 'postmeta' ) {
		if ( empty( $schema['@type'] ) ) {
			return false;
		}

		$type     = is_array( $schema['@type'] ) ? $schema['@type'][0] : $schema['@type'];
		$meta_key = self::META_PREFIX . $type;

		// Add shortcode if not exists.
		if ( empty( $schema['metadata']['shortcode'] ) ) {
			$schema['metadata']['shortcode'] = self::generate_shortcode_id();
		}

		// Save the schema.
		if ( 'termmeta' === $table ) {
			$meta_id = update_term_meta( $post_id, $meta_key, $schema );
		} else {
			$meta_id = update_post_meta( $post_id, $meta_key, $schema );
		}

		// Save shortcode reference for quick lookup.
		if ( $meta_id ) {
			$shortcode_key = self::SHORTCODE_PREFIX . $schema['metadata']['shortcode'];
			
			if ( 'termmeta' === $table ) {
				update_term_meta( $post_id, $shortcode_key, $meta_id );
			} else {
				update_post_meta( $post_id, $shortcode_key, $meta_id );
			}
		}

		// Clear cache.
		self::clear_cache( $post_id, $table );

		return $meta_id;
	}

	/**
	 * Delete a schema.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $schema_id Schema ID (e.g., 'schema-123').
	 * @param string $table     Meta table name.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_schema( int $post_id, string $schema_id, string $table = 'postmeta' ): bool {
		$meta_id = (int) str_replace( 'schema-', '', $schema_id );

		if ( ! $meta_id ) {
			return false;
		}

		// Get schema to find shortcode.
		$schema = self::get_schema( $post_id, $schema_id, $table );
		
		if ( $schema && ! empty( $schema['metadata']['shortcode'] ) ) {
			$shortcode_key = self::SHORTCODE_PREFIX . $schema['metadata']['shortcode'];
			
			if ( 'termmeta' === $table ) {
				delete_term_meta( $post_id, $shortcode_key );
			} else {
				delete_post_meta( $post_id, $shortcode_key );
			}
		}

		// Delete the schema.
		global $wpdb;
		$table_name = 'termmeta' === $table ? $wpdb->termmeta : $wpdb->postmeta;
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'meta_id' => $meta_id ),
			array( '%d' )
		);

		// Clear cache.
		self::clear_cache( $post_id, $table );

		return false !== $result;
	}

	/**
	 * Get schema by shortcode ID.
	 *
	 * @param string $shortcode_id Shortcode ID.
	 * @param bool   $from_db      Force fetch from database.
	 * @return array|null Array with 'post_id' and 'schema' keys, or null if not found.
	 */
	public static function get_schema_by_shortcode( string $shortcode_id, bool $from_db = false ): ?array {
		static $cache = array();

		// Check cache.
		if ( ! $from_db && isset( $cache[ $shortcode_id ] ) ) {
			return $cache[ $shortcode_id ];
		}

		global $wpdb;

		$shortcode_key = self::SHORTCODE_PREFIX . $shortcode_id;

		// First, try to get the shortcut meta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$shortcut = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 1",
				$shortcode_key
			)
		);

		if ( $shortcut ) {
			// Get the schema using meta_id.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_id = %d",
					$shortcut
				)
			);

			if ( $result ) {
				$data = array(
					'post_id' => (int) $result->post_id,
					'schema'  => maybe_unserialize( $result->meta_value ),
				);

				$cache[ $shortcode_id ] = $data;
				return $data;
			}
		}

		// Fallback: Search in meta_value (slower).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT post_id, meta_value 
				FROM {$wpdb->postmeta} 
				WHERE meta_key LIKE %s 
				AND meta_value LIKE %s 
				LIMIT 1",
				$wpdb->esc_like( self::META_PREFIX ) . '%',
				'%' . $wpdb->esc_like( $shortcode_id ) . '%'
			)
		);

		if ( ! $result ) {
			return null;
		}

		$data = array(
			'post_id' => (int) $result->post_id,
			'schema'  => maybe_unserialize( $result->meta_value ),
		);

		$cache[ $shortcode_id ] = $data;
		return $data;
	}

	/**
	 * Get schema types for a post.
	 *
	 * @param int  $post_id  Post ID.
	 * @param bool $sanitize Sanitize schema type names.
	 * @return string|false Comma-separated schema types or false if none.
	 */
	public static function get_schema_types( int $post_id, bool $sanitize = false ) {
		$schemas = self::get_schemas( $post_id );

		if ( empty( $schemas ) ) {
			return false;
		}

		$types = array();

		foreach ( $schemas as $schema ) {
			if ( empty( $schema['@type'] ) ) {
				continue;
			}

			$type = $schema['@type'];

			if ( is_array( $type ) ) {
				$types = array_merge( $types, $type );
			} else {
				$types[] = $type;
			}
		}

		$types = array_unique( $types );

		if ( $sanitize ) {
			$types = array_map( array( __CLASS__, 'sanitize_schema_title' ), $types );
		}

		return implode( ', ', $types );
	}

	/**
	 * Generate a unique shortcode ID.
	 *
	 * @return string Shortcode ID.
	 */
	public static function generate_shortcode_id(): string {
		return 's-' . wp_generate_password( 8, false );
	}

	/**
	 * Sanitize schema title for display.
	 *
	 * @param string $type Schema type.
	 * @return string Sanitized title.
	 */
	public static function sanitize_schema_title( string $type ): string {
		// Add spaces before capital letters.
		$title = preg_replace( '/([a-z])([A-Z])/', '$1 $2', $type );
		
		// Handle special cases.
		$replacements = array(
			'Faq'  => 'FAQ',
			'Seo'  => 'SEO',
			'Api'  => 'API',
			'Url'  => 'URL',
			'Html' => 'HTML',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $title );
	}

	/**
	 * Clear schema cache for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $table   Meta table name.
	 */
	private static function clear_cache( int $post_id, string $table ): void {
		// This will be cleared automatically on next request due to static cache.
		// For persistent cache (if using object cache), implement here.
	}

	/**
	 * Delete all schemas for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $table   Meta table name.
	 * @return int Number of schemas deleted.
	 */
	public static function delete_all_schemas( int $post_id, string $table = 'postmeta' ): int {
		global $wpdb;

		$table_name = 'termmeta' === $table ? $wpdb->termmeta : $wpdb->postmeta;
		$key        = 'termmeta' === $table ? 'term_id' : 'post_id';
		$prefix     = self::META_PREFIX;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} 
				WHERE {$key} = %d 
				AND (meta_key LIKE %s OR meta_key LIKE %s)",
				$post_id,
				$wpdb->esc_like( $prefix ) . '%',
				$wpdb->esc_like( self::SHORTCODE_PREFIX ) . '%'
			)
		);

		// Clear cache.
		self::clear_cache( $post_id, $table );

		return (int) $count;
	}
}
