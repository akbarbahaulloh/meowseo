<?php
/**
 * Installer class.
 *
 * Handles plugin activation, deactivation, and uninstall.
 * Creates custom database tables using dbDelta().
 *
 * @package MeowSEO
 */

namespace MeowSEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer class.
 */
class Installer {

	/**
	 * Plugin activation hook.
	 *
	 * Creates custom tables and sets up initial options.
	 *
	 * @return void
	 */
	public static function activate(): void {
		global $wpdb;

		// Require dbDelta function.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Get table schema.
		$schema = self::get_schema();

		// Execute dbDelta to create/update tables.
		dbDelta( $schema );

		// Store plugin version.
		update_option( 'meowseo_version', MEOWSEO_VERSION );

		// Initialize options if not already set.
		$options = new Options();
		$options->save();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Flush rewrite rules.
		flush_rewrite_rules();

		// Clear scheduled cron events.
		wp_clear_scheduled_hook( 'meowseo_flush_404_cron' );
		wp_clear_scheduled_hook( 'meowseo_process_gsc_queue' );
		wp_clear_scheduled_hook( 'meowseo_scan_links_cron' );
	}

	/**
	 * Plugin uninstall.
	 *
	 * Removes all plugin data if configured to do so.
	 * This is called from uninstall.php.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		global $wpdb;

		// Check if we should delete data on uninstall.
		$options = new Options();
		if ( ! $options->is_delete_on_uninstall() ) {
			return;
		}

		// Drop custom tables.
		$tables = array(
			$wpdb->prefix . 'meowseo_redirects',
			$wpdb->prefix . 'meowseo_404_log',
			$wpdb->prefix . 'meowseo_gsc_queue',
			$wpdb->prefix . 'meowseo_gsc_data',
			$wpdb->prefix . 'meowseo_link_checks',
			$wpdb->prefix . 'meowseo_logs',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		// Delete options.
		delete_option( 'meowseo_options' );
		delete_option( 'meowseo_version' );
		delete_option( 'meowseo_gsc_credentials' );

		// Delete all postmeta with meowseo_ prefix.
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'meowseo_%'" );

		// Clear scheduled cron events.
		wp_clear_scheduled_hook( 'meowseo_flush_404_cron' );
		wp_clear_scheduled_hook( 'meowseo_process_gsc_queue' );
		wp_clear_scheduled_hook( 'meowseo_scan_links_cron' );
	}

	/**
	 * Get database schema for all custom tables.
	 *
	 * @return string SQL schema for dbDelta.
	 */
	private static function get_schema(): string {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$prefix = $wpdb->prefix;

		$schema = "
CREATE TABLE {$prefix}meowseo_redirects (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	source_url VARCHAR(2048) NOT NULL,
	target_url VARCHAR(2048) NOT NULL,
	redirect_type SMALLINT NOT NULL DEFAULT 301,
	is_regex TINYINT(1) NOT NULL DEFAULT 0,
	status VARCHAR(10) NOT NULL DEFAULT 'active',
	hit_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
	last_accessed DATETIME NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_source_url (source_url(191)),
	KEY idx_is_regex_status (is_regex, status)
) $charset_collate;

CREATE TABLE {$prefix}meowseo_404_log (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	url VARCHAR(2048) NOT NULL,
	url_hash CHAR(64) NOT NULL,
	referrer VARCHAR(2048) NULL,
	user_agent VARCHAR(512) NULL,
	hit_count BIGINT UNSIGNED NOT NULL DEFAULT 1,
	first_seen DATE NOT NULL,
	last_seen DATE NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY idx_url_hash_date (url_hash(64), first_seen),
	KEY idx_last_seen (last_seen)
) $charset_collate;

CREATE TABLE {$prefix}meowseo_gsc_queue (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	job_type VARCHAR(50) NOT NULL,
	payload JSON NOT NULL,
	status VARCHAR(20) NOT NULL DEFAULT 'pending',
	attempts TINYINT NOT NULL DEFAULT 0,
	retry_after DATETIME NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	processed_at DATETIME NULL,
	PRIMARY KEY (id),
	KEY idx_status_retry (status, retry_after)
) $charset_collate;

CREATE TABLE {$prefix}meowseo_gsc_data (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	url VARCHAR(2048) NOT NULL,
	url_hash CHAR(64) NOT NULL,
	date DATE NOT NULL,
	clicks INT UNSIGNED NOT NULL DEFAULT 0,
	impressions INT UNSIGNED NOT NULL DEFAULT 0,
	ctr DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
	position DECIMAL(6,2) NOT NULL DEFAULT 0.00,
	PRIMARY KEY (id),
	UNIQUE KEY idx_url_hash_date (url_hash(64), date),
	KEY idx_date (date)
) $charset_collate;

CREATE TABLE {$prefix}meowseo_link_checks (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	source_post_id BIGINT UNSIGNED NOT NULL,
	target_url VARCHAR(2048) NOT NULL,
	target_url_hash CHAR(64) NOT NULL,
	anchor_text VARCHAR(512) NULL,
	http_status SMALLINT NULL,
	last_checked DATETIME NULL,
	PRIMARY KEY (id),
	KEY idx_source_post (source_post_id),
	KEY idx_http_status (http_status),
	UNIQUE KEY idx_source_target (source_post_id, target_url_hash(64))
) $charset_collate;

CREATE TABLE {$prefix}meowseo_logs (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	level VARCHAR(20) NOT NULL,
	module VARCHAR(50) NOT NULL,
	message TEXT NOT NULL,
	message_hash CHAR(64) NOT NULL,
	context JSON NULL,
	stack_trace TEXT NULL,
	hit_count BIGINT UNSIGNED NOT NULL DEFAULT 1,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_level (level),
	KEY idx_module (module),
	KEY idx_created_at (created_at),
	KEY idx_dedup (level, module, message_hash)
) $charset_collate;
";

		return $schema;
	}
}
