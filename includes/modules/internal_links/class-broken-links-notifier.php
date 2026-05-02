<?php
/**
 * Broken Links Notifier class.
 *
 * Handles sending email notifications for broken links.
 *
 * @package MeowSEO\Modules\Internal_Links
 */

namespace MeowSEO\Modules\Internal_Links;

use MeowSEO\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Broken_Links_Notifier class.
 */
class Broken_Links_Notifier {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Send an immediate notification for a single broken link.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     Broken URL.
	 * @return void
	 */
	public function send_immediate_notification( int $post_id, string $url ): void {
		if ( ! $this->options->get( 'broken_links_notifications', false ) ) {
			return;
		}

		if ( 'immediate' !== $this->options->get( 'broken_links_notification_type', 'daily' ) ) {
			return;
		}

		$to      = $this->options->get( 'broken_links_notification_email', get_option( 'admin_email' ) );
		$subject = sprintf( '[%s] Broken Link Detected', get_bloginfo( 'name' ) );
		$post    = get_post( $post_id );
		$post_title = $post ? $post->post_title : '#' . $post_id;

		$message  = "A new broken link was detected on your site.\n\n";
		$message .= "Post: " . $post_title . "\n";
		$message .= "URL: " . $url . "\n\n";
		$message .= "Manage broken links: " . admin_url( 'admin.php?page=meowseo-broken-links' ) . "\n";

		wp_mail( $to, $subject, $message );
	}

	/**
	 * Send a daily summary of all broken links.
	 *
	 * @return void
	 */
	public function send_daily_summary(): void {
		if ( ! $this->options->get( 'broken_links_notifications', false ) ) {
			return;
		}

		if ( 'daily' !== $this->options->get( 'broken_links_notification_type', 'daily' ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_link_checks';
		$broken_links = $wpdb->get_results( "SELECT * FROM {$table} WHERE is_broken = 1 ORDER BY source_post_id ASC" );

		if ( empty( $broken_links ) ) {
			return;
		}

		$to      = $this->options->get( 'broken_links_notification_email', get_option( 'admin_email' ) );
		$subject = sprintf( '[%s] Daily Broken Links Summary', get_bloginfo( 'name' ) );

		$message  = "Here is your daily summary of broken links on " . get_bloginfo( 'name' ) . ".\n\n";
		$message .= "Total Broken Links: " . count( $broken_links ) . "\n\n";
		
		$current_post_id = 0;
		foreach ( $broken_links as $link ) {
			if ( $link->source_post_id !== $current_post_id ) {
				$post = get_post( $link->source_post_id );
				$message .= "\n--- " . ( $post ? $post->post_title : '#' . $link->source_post_id ) . " ---\n";
				$current_post_id = $link->source_post_id;
			}
			$message .= "- " . $link->target_url . " (" . $link->http_status . ")\n";
		}

		$message .= "\nManage all broken links: " . admin_url( 'admin.php?page=meowseo-broken-links' ) . "\n";

		wp_mail( $to, $subject, $message );
	}
}
