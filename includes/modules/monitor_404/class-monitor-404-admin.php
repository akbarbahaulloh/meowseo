<?php
/**
 * Monitor 404 Admin Interface
 *
 * Provides admin UI for managing 404 log entries.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\Monitor_404
 */

namespace MeowSEO\Modules\Monitor_404;

use MeowSEO\Options;
use MeowSEO\Helpers\DB;
use MeowSEO\Helpers\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Monitor 404 Admin class
 *
 * Handles admin interface for 404 log management.
 * Requirements: 13.1, 13.2, 13.3, 13.4, 13.5
 */
class Monitor_404_Admin {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Log entries per page
	 *
	 * @var int
	 */
	private const LOG_ENTRIES_PER_PAGE = 50;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot admin functionality
	 *
	 * Registers admin hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_meowseo_create_redirect_from_404', array( $this, 'handle_create_redirect' ) );
		add_action( 'wp_ajax_meowseo_ignore_404_url', array( $this, 'handle_ignore_url' ) );
		add_action( 'wp_ajax_meowseo_clear_all_404', array( $this, 'handle_clear_all' ) );
	}

	/**
	 * Register admin menu
	 *
	 * Adds 404 Monitor submenu under MeowSEO menu.
	 * Requirement: 13.1
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'meowseo-settings',
			__( '404 Monitor', 'meowseo' ),
			__( '404 Monitor', 'meowseo' ),
			'manage_options',
			'meowseo-404-monitor',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( string $hook ): void {
		// Only load on our admin page.
		if ( 'meowseo_page_meowseo-404-monitor' !== $hook ) {
			return;
		}

		// Enqueue WordPress core scripts.
		wp_enqueue_script( 'jquery' );

		// Add inline script for AJAX actions.
		$inline_script = "
		jQuery(document).ready(function($) {
			// Handle Create Redirect inline form toggle
			$('.meowseo-create-redirect-btn').on('click', function(e) {
				e.preventDefault();
				var row = $(this).closest('tr');
				var formRow = row.next('.meowseo-redirect-form-row');
				
				if (formRow.length) {
					formRow.toggle();
				} else {
					var url = $(this).data('url');
					var entryId = $(this).data('id');
					var formHtml = '<tr class=\"meowseo-redirect-form-row\"><td colspan=\"5\" style=\"background: #f9f9f9; padding: 15px;\">' +
						'<form class=\"meowseo-redirect-form\" data-id=\"' + entryId + '\">' +
						'<h4 style=\"margin-top: 0;\">' + '" . esc_js( __( 'Create Redirect', 'meowseo' ) ) . "'</h4>' +
						'<p><strong>' + '" . esc_js( __( 'Source URL:', 'meowseo' ) ) . "' </strong>' + url + '</p>' +
						'<table class=\"form-table\"><tbody>' +
						'<tr><th scope=\"row\"><label>' + '" . esc_js( __( 'Target URL', 'meowseo' ) ) . "'</label></th>' +
						'<td><input type=\"text\" name=\"target_url\" class=\"regular-text\" required placeholder=\"/new-page/\"></td></tr>' +
						'<tr><th scope=\"row\"><label>' + '" . esc_js( __( 'Redirect Type', 'meowseo' ) ) . "'</label></th>' +
						'<td><select name=\"redirect_type\">' +
						'<option value=\"301\">' + '" . esc_js( __( '301 - Permanent', 'meowseo' ) ) . "'</option>' +
						'<option value=\"302\">' + '" . esc_js( __( '302 - Temporary', 'meowseo' ) ) . "'</option>' +
						'<option value=\"307\">' + '" . esc_js( __( '307 - Temporary (Preserve Method)', 'meowseo' ) ) . "'</option>' +
						'</select></td></tr>' +
						'</tbody></table>' +
						'<p><button type=\"submit\" class=\"button button-primary\">' + '" . esc_js( __( 'Create Redirect', 'meowseo' ) ) . "'</button> ' +
						'<button type=\"button\" class=\"button meowseo-cancel-redirect\">' + '" . esc_js( __( 'Cancel', 'meowseo' ) ) . "'</button></p>' +
						'</form></td></tr>';
					
					row.after(formHtml);
				}
			});

			// Handle form submission
			$(document).on('submit', '.meowseo-redirect-form', function(e) {
				e.preventDefault();
				var form = $(this);
				var entryId = form.data('id');
				var targetUrl = form.find('input[name=\"target_url\"]').val();
				var redirectType = form.find('select[name=\"redirect_type\"]').val();
				var sourceUrl = form.closest('tr').prev('tr').find('.meowseo-create-redirect-btn').data('url');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'meowseo_create_redirect_from_404',
						nonce: '" . wp_create_nonce( 'meowseo_404_action' ) . "',
						entry_id: entryId,
						source_url: sourceUrl,
						target_url: targetUrl,
						redirect_type: redirectType
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							location.reload();
						} else {
							alert(response.data.message || '" . esc_js( __( 'Failed to create redirect.', 'meowseo' ) ) . "');
						}
					},
					error: function() {
						alert('" . esc_js( __( 'An error occurred. Please try again.', 'meowseo' ) ) . "');
					}
				});
			});

			// Handle cancel button
			$(document).on('click', '.meowseo-cancel-redirect', function() {
				$(this).closest('.meowseo-redirect-form-row').remove();
			});

			// Handle Ignore action
			$('.meowseo-ignore-btn').on('click', function(e) {
				e.preventDefault();
				if (!confirm('" . esc_js( __( 'Are you sure you want to ignore this URL?', 'meowseo' ) ) . "')) {
					return;
				}

				var btn = $(this);
				var entryId = btn.data('id');
				var url = btn.data('url');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'meowseo_ignore_404_url',
						nonce: '" . wp_create_nonce( 'meowseo_404_action' ) . "',
						entry_id: entryId,
						url: url
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							location.reload();
						} else {
							alert(response.data.message || '" . esc_js( __( 'Failed to ignore URL.', 'meowseo' ) ) . "');
						}
					},
					error: function() {
						alert('" . esc_js( __( 'An error occurred. Please try again.', 'meowseo' ) ) . "');
					}
				});
			});

			// Handle Clear All button
			$('#meowseo-clear-all-404').on('click', function(e) {
				e.preventDefault();
				if (!confirm('" . esc_js( __( 'Are you sure you want to delete all 404 log entries? This action cannot be undone.', 'meowseo' ) ) . "')) {
					return;
				}

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'meowseo_clear_all_404',
						nonce: '" . wp_create_nonce( 'meowseo_404_action' ) . "'
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							location.reload();
						} else {
							alert(response.data.message || '" . esc_js( __( 'Failed to clear log.', 'meowseo' ) ) . "');
						}
					},
					error: function() {
						alert('" . esc_js( __( 'An error occurred. Please try again.', 'meowseo' ) ) . "');
					}
				});
			});
		});
		";

		wp_add_inline_script( 'jquery', $inline_script );
	}

	/**
	 * Render admin page
	 *
	 * Outputs the main 404 monitor page.
	 * Requirements: 13.1, 13.2, 13.3, 13.4, 13.5
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Verify user has manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Monitor and manage 404 errors on your site. Create redirects or ignore URLs directly from this page.', 'meowseo' ); ?></p>

			<?php $this->render_clear_all_button(); ?>
			<?php $this->render_table(); ?>
		</div>
		<?php
	}

	/**
	 * Render Clear All button
	 *
	 * Requirement: 13.5 - Provide Clear All button with JavaScript confirmation dialog
	 *
	 * @return void
	 */
	private function render_clear_all_button(): void {
		?>
		<div class="card" style="margin-bottom: 20px;">
			<h2><?php esc_html_e( 'Bulk Actions', 'meowseo' ); ?></h2>
			<p>
				<button type="button" id="meowseo-clear-all-404" class="button button-secondary">
					<?php esc_html_e( 'Clear All 404 Entries', 'meowseo' ); ?>
				</button>
				<span class="description" style="margin-left: 10px;">
					<?php esc_html_e( 'This will delete all logged 404 entries. This action cannot be undone.', 'meowseo' ); ?>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Render 404 log table
	 *
	 * Requirements: 13.1, 13.2, 13.3
	 *
	 * @return void
	 */
	private function render_table(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_404_log';

		// Get current page.
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset = ( $paged - 1 ) * self::LOG_ENTRIES_PER_PAGE;

		// Get sorting parameters.
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'last_seen';
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';

		// Validate orderby.
		$allowed_orderby = array( 'id', 'url', 'hit_count', 'first_seen', 'last_seen' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'last_seen';
		}

		// Validate order.
		$order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

		// Get total count.
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		// Get log entries.
		$entries = DB::get_404_log(
			array(
				'limit'   => self::LOG_ENTRIES_PER_PAGE,
				'offset'  => $offset,
				'orderby' => $orderby,
				'order'   => $order,
			)
		);

		// Calculate total pages.
		$total_pages = ceil( $total / self::LOG_ENTRIES_PER_PAGE );

		// Build sort URLs.
		$sort_url_base = add_query_arg( 'page', 'meowseo-404-monitor', admin_url( 'admin.php' ) );

		?>
		<div class="card">
			<h2><?php esc_html_e( '404 Log Entries', 'meowseo' ); ?></h2>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php $this->render_sortable_header( 'url', __( 'URL', 'meowseo' ), $orderby, $order, $sort_url_base ); ?></th>
						<th><?php $this->render_sortable_header( 'hit_count', __( 'Hits', 'meowseo' ), $orderby, $order, $sort_url_base ); ?></th>
						<th><?php $this->render_sortable_header( 'first_seen', __( 'First Seen', 'meowseo' ), $orderby, $order, $sort_url_base ); ?></th>
						<th><?php $this->render_sortable_header( 'last_seen', __( 'Last Seen', 'meowseo' ), $orderby, $order, $sort_url_base ); ?></th>
						<th><?php esc_html_e( 'Actions', 'meowseo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $entries ) ) : ?>
						<tr>
							<td colspan="5"><?php esc_html_e( 'No 404 entries found.', 'meowseo' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $entries as $entry ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $entry['url'] ); ?></strong>
									<?php if ( ! empty( $entry['referrer'] ) ) : ?>
										<br><small><?php echo esc_html( sprintf( __( 'Referrer: %s', 'meowseo' ), $entry['referrer'] ) ); ?></small>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $entry['hit_count'] ); ?></td>
								<td><?php echo esc_html( $entry['first_seen'] ); ?></td>
								<td><?php echo esc_html( $entry['last_seen'] ); ?></td>
								<td>
									<button type="button" 
										class="button button-small meowseo-create-redirect-btn" 
										data-id="<?php echo esc_attr( $entry['id'] ); ?>"
										data-url="<?php echo esc_attr( $entry['url'] ); ?>">
										<?php esc_html_e( 'Create Redirect', 'meowseo' ); ?>
									</button>
									<button type="button" 
										class="button button-small meowseo-ignore-btn" 
										data-id="<?php echo esc_attr( $entry['id'] ); ?>"
										data-url="<?php echo esc_attr( $entry['url'] ); ?>">
										<?php esc_html_e( 'Ignore', 'meowseo' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						echo paginate_links(
							array(
								'base'      => add_query_arg( array( 'paged' => '%#%', 'orderby' => $orderby, 'order' => $order ) ),
								'format'    => '',
								'prev_text' => __( '&laquo;', 'meowseo' ),
								'next_text' => __( '&raquo;', 'meowseo' ),
								'total'     => $total_pages,
								'current'   => $paged,
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render sortable column header
	 *
	 * @param string $column       Column name.
	 * @param string $label        Column label.
	 * @param string $current_orderby Current orderby value.
	 * @param string $current_order   Current order value.
	 * @param string $base_url        Base URL for sorting.
	 * @return void
	 */
	private function render_sortable_header( string $column, string $label, string $current_orderby, string $current_order, string $base_url ): void {
		$is_current = ( $current_orderby === $column );
		$new_order  = ( $is_current && $current_order === 'ASC' ) ? 'DESC' : 'ASC';
		$sort_url   = add_query_arg( array( 'orderby' => $column, 'order' => $new_order ), $base_url );

		echo '<a href="' . esc_url( $sort_url ) . '">';
		echo esc_html( $label );

		if ( $is_current ) {
			echo ' <span class="dashicons dashicons-arrow-' . ( $current_order === 'ASC' ? 'up' : 'down' ) . '"></span>';
		}

		echo '</a>';
	}

	/**
	 * Handle Create Redirect AJAX action
	 *
	 * Requirements: 13.1, 13.2, 13.3 - Create redirect and remove from 404 log
	 *
	 * @return void
	 */
	public function handle_create_redirect(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'meowseo_404_action' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'meowseo' ) ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to create redirects.', 'meowseo' ) ) );
		}

		// Get parameters.
		$entry_id      = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
		$source_url    = isset( $_POST['source_url'] ) ? sanitize_text_field( $_POST['source_url'] ) : '';
		$target_url    = isset( $_POST['target_url'] ) ? esc_url_raw( $_POST['target_url'] ) : '';
		$redirect_type = isset( $_POST['redirect_type'] ) ? absint( $_POST['redirect_type'] ) : 301;

		// Validate required fields.
		if ( empty( $source_url ) || empty( $target_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Source URL and Target URL are required.', 'meowseo' ) ) );
		}

		// Validate redirect type.
		if ( ! in_array( $redirect_type, array( 301, 302, 307 ), true ) ) {
			$redirect_type = 301;
		}

		global $wpdb;

		// Create redirect.
		$redirects_table = $wpdb->prefix . 'meowseo_redirects';
		$result          = $wpdb->insert(
			$redirects_table,
			array(
				'source_url'    => $source_url,
				'target_url'    => $target_url,
				'redirect_type' => $redirect_type,
				'is_regex'      => 0,
				'is_active'     => 1,
			),
			array( '%s', '%s', '%d', '%d', '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create redirect.', 'meowseo' ) ) );
		}

		// Requirement 13.3: Remove URL from 404 log when redirect is created.
		$log_table = $wpdb->prefix . 'meowseo_404_log';
		$wpdb->delete(
			$log_table,
			array( 'id' => $entry_id ),
			array( '%d' )
		);

		// Log the action.
		Logger::info(
			'Redirect created from 404 entry',
			array(
				'entry_id'      => $entry_id,
				'source_url'    => $source_url,
				'target_url'    => $target_url,
				'redirect_type' => $redirect_type,
			)
		);

		wp_send_json_success(
			array(
				'message' => __( 'Redirect created successfully and removed from 404 log.', 'meowseo' ),
			)
		);
	}

	/**
	 * Handle Ignore URL AJAX action
	 *
	 * Requirement: 13.4 - Add URL to ignore list in plugin options
	 *
	 * @return void
	 */
	public function handle_ignore_url(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'meowseo_404_action' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'meowseo' ) ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to ignore URLs.', 'meowseo' ) ) );
		}

		// Get parameters.
		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
		$url      = isset( $_POST['url'] ) ? sanitize_text_field( $_POST['url'] ) : '';

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => __( 'URL is required.', 'meowseo' ) ) );
		}

		// Get current ignore list.
		$ignore_list = $this->options->get( 'monitor_404_ignore_list', array() );
		if ( ! is_array( $ignore_list ) ) {
			$ignore_list = array();
		}

		// Add URL to ignore list if not already present.
		if ( ! in_array( $url, $ignore_list, true ) ) {
			$ignore_list[] = $url;
			$this->options->set( 'monitor_404_ignore_list', $ignore_list );
			$this->options->save();
		}

		// Remove from 404 log.
		global $wpdb;
		$log_table = $wpdb->prefix . 'meowseo_404_log';
		$wpdb->delete(
			$log_table,
			array( 'id' => $entry_id ),
			array( '%d' )
		);

		// Log the action.
		Logger::info(
			'URL added to 404 ignore list',
			array(
				'entry_id' => $entry_id,
				'url'      => $url,
			)
		);

		wp_send_json_success(
			array(
				'message' => __( 'URL added to ignore list and removed from 404 log.', 'meowseo' ),
			)
		);
	}

	/**
	 * Handle Clear All AJAX action
	 *
	 * Requirement: 13.5 - Clear all 404 log entries with confirmation
	 *
	 * @return void
	 */
	public function handle_clear_all(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'meowseo_404_action' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'meowseo' ) ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to clear the 404 log.', 'meowseo' ) ) );
		}

		global $wpdb;
		$log_table = $wpdb->prefix . 'meowseo_404_log';

		// Get count before deletion for logging.
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table}" );

		// Delete all entries.
		$result = $wpdb->query( "TRUNCATE TABLE {$log_table}" );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to clear 404 log.', 'meowseo' ) ) );
		}

		// Log the action.
		Logger::info(
			'404 log cleared',
			array(
				'entries_deleted' => $count,
			)
		);

		wp_send_json_success(
			array(
				'message' => sprintf( __( 'Successfully deleted %d 404 log entries.', 'meowseo' ), $count ),
			)
		);
	}
}
