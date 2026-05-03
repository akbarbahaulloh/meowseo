<?php
/**
 * Module Autoloader
 *
 * Registers PSR-4 autoloader paths for all MeowSEO modules.
 *
 * @package MeowSEO
 * @subpackage Modules
 */

namespace MeowSEO\Modules;

/**
 * Register module autoloader paths
 *
 * Adds PSR-4 namespace mappings for all Sprint 4 modules to the
 * WordPress autoloader.
 *
 * @return void
 */
function register_autoloader_paths() {
	$modules_dir = __DIR__;

	// Base module classes
	if ( function_exists( 'meowseo_autoloader' ) ) {
		meowseo_autoloader( 'MeowSEO\\Modules\\', $modules_dir . '/base/' );

		// Sprint 4 modules
		meowseo_autoloader( 'MeowSEO\\Modules\\Roles\\', $modules_dir . '/roles/' );
		meowseo_autoloader( 'MeowSEO\\Modules\\Multilingual\\', $modules_dir . '/multilingual/' );
		meowseo_autoloader( 'MeowSEO\\Modules\\Multisite\\', $modules_dir . '/multisite/' );
		meowseo_autoloader( 'MeowSEO\\Modules\\Locations\\', $modules_dir . '/locations/' );
		meowseo_autoloader( 'MeowSEO\\Modules\\Bulk\\', $modules_dir . '/bulk/' );
		meowseo_autoloader( 'MeowSEO\\Modules\\Analytics\\', $modules_dir . '/analytics/' );
		meowseo_autoloader( 'MeowSEO\\Modules\\AdminBar\\', $modules_dir . '/admin-bar/' );
		meowseo_autoloader( 'MeowSEO\\Modules\\Orphaned\\', $modules_dir . '/orphaned/' );
		meowseo_autoloader( 'MeowSEO\\Modules\\Synonyms\\', $modules_dir . '/synonyms/' );
		meowseo_autoloader( 'MeowSEO\\Modules\\Schema\\', $modules_dir . '/schema/' );
	}
}

// Register autoloader paths when this file is included
register_autoloader_paths();
