<?php
/**
 * Clear MeowSEO update cache and force re-check.
 */

require_once( __DIR__ . '/wp-load.php' );

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied.' );
}

delete_site_transient( 'meowseo_gh_commit_data' );
delete_site_transient( 'update_plugins' );
delete_option( 'meowseo_installed_sha' ); // Force it to think it's a new version

echo "Cache and SHA cleared. Go to <a href='" . admin_url( 'plugins.php' ) . "'>Plugins</a> to see the update notification.";
