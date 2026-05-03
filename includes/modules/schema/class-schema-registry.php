<?php
/**
 * Schema Type Registry
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

namespace MeowSEO\Modules\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_Registry class.
 */
class Schema_Registry {

	/**
	 * Registered schema types.
	 *
	 * @var array
	 */
	private static $types = array();

	/**
	 * Register a schema type.
	 *
	 * @param Schema_Type $schema Schema type instance.
	 * @return bool True on success, false on failure.
	 */
	public static function register( Schema_Type $schema ): bool {
		$type = $schema->get_type();

		if ( isset( self::$types[ $type ] ) ) {
			return false;
		}

		self::$types[ $type ] = $schema;

		return true;
	}

	/**
	 * Unregister a schema type.
	 *
	 * @param string $type Schema type name.
	 * @return bool True on success, false if not found.
	 */
	public static function unregister( string $type ): bool {
		if ( ! isset( self::$types[ $type ] ) ) {
			return false;
		}

		unset( self::$types[ $type ] );

		return true;
	}

	/**
	 * Get a schema type instance.
	 *
	 * @param string $type Schema type name.
	 * @return Schema_Type|null Schema type instance or null if not found.
	 */
	public static function get( string $type ): ?Schema_Type {
		return self::$types[ $type ] ?? null;
	}

	/**
	 * Get all registered schema types.
	 *
	 * @return array Array of schema type instances.
	 */
	public static function get_all(): array {
		return self::$types;
	}

	/**
	 * Get all schema types info for JavaScript.
	 *
	 * @return array Array of schema type info.
	 */
	public static function get_all_info(): array {
		$info = array();

		foreach ( self::$types as $type => $schema ) {
			$info[ $type ] = $schema->get_info();
		}

		return $info;
	}

	/**
	 * Check if a schema type is registered.
	 *
	 * @param string $type Schema type name.
	 * @return bool True if registered, false otherwise.
	 */
	public static function is_registered( string $type ): bool {
		return isset( self::$types[ $type ] );
	}

	/**
	 * Get schema types as choices for select field.
	 *
	 * @return array Array of type => label pairs.
	 */
	public static function get_choices(): array {
		$choices = array();

		foreach ( self::$types as $type => $schema ) {
			$choices[ $type ] = $schema->get_label();
		}

		// Sort alphabetically.
		asort( $choices );

		return $choices;
	}
}
