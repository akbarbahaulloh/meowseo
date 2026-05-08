<?php
/**
 * Test script for MeowSEO GitHub Updater.
 */

// Load WordPress environment.
require_once( __DIR__ . '/wp-load.php' );

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied.' );
}

echo "<h1>MeowSEO GitHub Updater Test</h1>";

// Check if class exists.
if ( class_exists( 'MeowSEO\Updater\GitHub_Updater' ) ) {
	echo "<p style='color:green;'>✓ Class MeowSEO\Updater\GitHub_Updater exists.</p>";
} else {
	echo "<p style='color:red;'>✗ Class MeowSEO\Updater\GitHub_Updater does not exist.</p>";
}

// Check if initialized.
if ( isset( $GLOBALS['meowseo_updater'] ) ) {
	echo "<p style='color:green;'>✓ Updater initialized in GLOBALS.</p>";
	
	$updater = $GLOBALS['meowseo_updater'];
	echo "<h2>GitHub Connection Test</h2>";
	
	$latest = $updater->get_latest_commit( true );
	if ( $latest ) {
		echo "<p style='color:green;'>✓ Successfully connected to GitHub.</p>";
		echo "<ul>";
		echo "<li><strong>SHA:</strong> " . esc_html( $latest->sha ) . "</li>";
		echo "<li><strong>Message:</strong> " . esc_html( $latest->commit->message ) . "</li>";
		echo "<li><strong>Date:</strong> " . esc_html( $latest->commit->committer->date ) . "</li>";
		echo "</ul>";
		
		$remote_sha = $latest->sha;
		$local_sha = get_option( 'meowseo_installed_sha', '' );
		echo "<p><strong>Local SHA:</strong> " . ( $local_sha ?: 'None' ) . "</p>";
		
		if ( $remote_sha !== $local_sha ) {
			echo "<p style='color:blue;'>ℹ Update available (SHAs differ).</p>";
		} else {
			echo "<p style='color:blue;'>ℹ No update needed (SHAs match).</p>";
		}
	} else {
		echo "<p style='color:red;'>✗ Failed to connect to GitHub or fetch commit data.</p>";
	}
} else {
	echo "<p style='color:red;'>✗ Updater not initialized.</p>";
}

echo "<h2>Hook Verification</h2>";
$hooks = array(
	'pre_set_site_transient_update_plugins',
	'plugins_api',
	'upgrader_source_selection',
	'upgrader_process_complete'
);

foreach ( $hooks as $hook ) {
	$priority = has_filter( $hook, array( $GLOBALS['meowseo_updater'] ?? null, str_replace( 'upgrader_process_complete', 'update_installed_sha', str_replace( 'upgrader_source_selection', 'rename_github_folder', str_replace( 'plugins_api', 'get_plugin_info', str_replace( 'pre_set_site_transient_update_plugins', 'check_update', $hook ) ) ) ) ) );
	if ( false !== $priority ) {
		echo "<p style='color:green;'>✓ Hook <code>{$hook}</code> is registered at priority {$priority}.</p>";
	} else {
		echo "<p style='color:red;'>✗ Hook <code>{$hook}</code> is NOT registered.</p>";
	}
}
