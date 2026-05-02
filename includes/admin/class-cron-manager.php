<?php
/**
 * Cron Manager class for MeowSEO.
 *
 * Handles monitoring and manual execution of plugin-specific background tasks.
 *
 * @package MeowSEO\Admin
 */

namespace MeowSEO\Admin;

use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cron_Manager class
 */
class Cron_Manager {

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
	 * Get list of MeowSEO-related cron jobs.
	 *
	 * @return array
	 */
	public function get_meowseo_crons(): array {
		$crons = _get_cron_array();
		$meowseo_crons = array();

		if ( empty( $crons ) ) {
			return array();
		}

		foreach ( $crons as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $data ) {
				// Filter for MeowSEO hooks.
				if ( strpos( $hook, 'meowseo_' ) === 0 ) {
					foreach ( $data as $id => $job ) {
						$meowseo_crons[] = array(
							'hook'     => $hook,
							'next_run' => $timestamp,
							'schedule' => $job['schedule'] ?? __( 'One-off', 'meowseo' ),
							'interval' => $job['interval'] ?? 0,
						);
					}
				}
			}
		}

		return $meowseo_crons;
	}

	/**
	 * Render the Cron Manager tab content.
	 */
	public function render_tab(): void {
		$crons = $this->get_meowseo_crons();
		?>
		<div class="meowseo-cron-manager">
			<h2><?php esc_html_e( 'Background Task Manager (Cron)', 'meowseo' ); ?></h2>
			<p><?php esc_html_e( 'Monitor and manually trigger MeowSEO background operations. These tasks run automatically to keep your site optimized.', 'meowseo' ); ?></p>

			<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Task Name', 'meowseo' ); ?></th>
						<th><?php esc_html_e( 'Frequency', 'meowseo' ); ?></th>
						<th><?php esc_html_e( 'Next Scheduled Run', 'meowseo' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'meowseo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $crons ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No active MeowSEO background tasks found.', 'meowseo' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $crons as $cron ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $this->get_hook_label( $cron['hook'] ) ); ?></strong><br>
									<code><?php echo esc_html( $cron['hook'] ); ?></code>
								</td>
								<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $cron['schedule'] ) ) ); ?></td>
								<td>
									<?php 
									$diff = $cron['next_run'] - time();
									if ( $diff > 0 ) {
										printf( __( 'In %s', 'meowseo' ), human_time_diff( time(), $cron['next_run'] ) );
									} else {
										esc_html_e( 'Pending...', 'meowseo' );
									}
									echo ' (' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $cron['next_run'] ) . ')';
									?>
								</td>
								<td>
									<button type="submit" name="meowseo_run_cron" value="<?php echo esc_attr( $cron['hook'] ); ?>" class="button button-small">
										<?php esc_html_e( 'Run Now', 'meowseo' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<div class="notice notice-info inline" style="margin-top: 20px;">
				<p><?php esc_html_e( 'Note: "Run Now" will execute the task immediately in your current session. This is useful for testing or forced updates.', 'meowseo' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Get a human-readable label for a hook.
	 *
	 * @param string $hook Hook name.
	 * @return string
	 */
	private function get_hook_label( string $hook ): string {
		$labels = array(
			'meowseo_broken_links_cron'          => __( 'Broken Link Checker', 'meowseo' ),
			'meowseo_content_refresh_heartbeat'  => __( 'Safe Content Refresh Pulse', 'meowseo' ),
			'meowseo_monitor_404_cleanup'        => __( '404 Log Cleanup', 'meowseo' ),
			'meowseo_search_console_sync'        => __( 'Search Console Data Sync', 'meowseo' ),
			'meowseo_analytics_sync'             => __( 'Analytics Data Sync', 'meowseo' ),
		);

		return $labels[ $hook ] ?? $hook;
	}

	/**
	 * Handle manual cron execution.
	 *
	 * @param string $hook Hook name.
	 * @return bool
	 */
	public function run_task( string $hook ): bool {
		if ( strpos( $hook, 'meowseo_' ) !== 0 ) {
			return false;
		}

		do_action( $hook );
		return true;
	}
}
