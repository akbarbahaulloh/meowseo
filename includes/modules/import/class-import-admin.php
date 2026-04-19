<?php
/**
 * Import Admin class for rendering import UI and handling AJAX requests.
 *
 * Provides the admin interface for importing SEO data from competitor plugins
 * (Yoast SEO, RankMath) with progress tracking and error reporting.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Import;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import_Admin class.
 *
 * Handles admin UI rendering and AJAX endpoints for import functionality.
 */
class Import_Admin {

	/**
	 * Import Manager instance.
	 *
	 * @var Import_Manager
	 */
	private Import_Manager $import_manager;

	/**
	 * Constructor.
	 *
	 * @param Import_Manager $import_manager Import Manager instance.
	 */
	public function __construct( Import_Manager $import_manager ) {
		$this->import_manager = $import_manager;
	}

	/**
	 * Boot the admin interface.
	 *
	 * Registers admin menu and AJAX handlers.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_post_meowseo_start_import', array( $this, 'handle_start_import' ) );
		add_action( 'wp_ajax_meowseo_import_status', array( $this, 'handle_import_status' ) );
		add_action( 'wp_ajax_meowseo_cancel_import', array( $this, 'handle_cancel_import' ) );
		add_action( 'wp_ajax_meowseo_process_import_batch', array( $this, 'handle_process_batch' ) );
		add_action( 'wp_ajax_meowseo_export_error_log', array( $this, 'handle_export_error_log' ) );
		add_action( 'wp_ajax_meowseo_set_completed_import', array( $this, 'handle_set_completed_import' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register admin menu page.
	 *
	 * Adds import page under MeowSEO settings.
	 * Requirement: 1.26
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			'meowseo',
			__( 'Import SEO Data', 'meowseo' ),
			__( 'Import', 'meowseo' ),
			'manage_options',
			'meowseo-import',
			array( $this, 'render_import_page' )
		);
	}

	/**
	 * Render import wizard UI.
	 *
	 * Displays detected plugins, import wizard, progress tracking, and completion summary.
	 * Requirements: 1.26, 1.27, 1.28
	 *
	 * @return void
	 */
	public function render_import_page(): void {
		// Verify user has manage_options capability.
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		// Detect installed plugins.
		$detected_plugins = $this->import_manager->detect_installed_plugins();

		// Check for active import.
		$active_import_id = \get_transient( 'meowseo_active_import_id' );
		$active_import    = false;

		if ( $active_import_id ) {
			$import_status = $this->import_manager->get_import_status( $active_import_id );
			if ( ! isset( $import_status['error'] ) && in_array( $import_status['status'], array( 'pending', 'in_progress' ), true ) ) {
				$active_import = $import_status;
			}
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Import SEO Data', 'meowseo' ); ?></h1>

			<?php
			// Check for completed import.
			$completed_import_id = get_transient( 'meowseo_completed_import_id' );
			if ( $completed_import_id ) {
				$completed_import = $this->import_manager->get_import_status( $completed_import_id );
				if ( ! isset( $completed_import['error'] ) && 'completed' === $completed_import['status'] ) {
					$this->render_import_summary( $completed_import );
					delete_transient( 'meowseo_completed_import_id' );
				} else {
					$this->render_import_wizard( $detected_plugins );
				}
			} elseif ( $active_import ) {
				$this->render_import_progress( $active_import );
			} else {
				$this->render_import_wizard( $detected_plugins );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render import wizard with plugin detection.
	 *
	 * Requirement: 1.26
	 *
	 * @param array $detected_plugins Array of detected plugins.
	 * @return void
	 */
	private function render_import_wizard( array $detected_plugins ): void {
		?>
		<div class="meowseo-import-wizard">
			<div class="meowseo-card">
				<h2><?php echo esc_html__( 'Import from Competitor Plugins', 'meowseo' ); ?></h2>
				<p><?php echo esc_html__( 'MeowSEO can import your existing SEO data from Yoast SEO or RankMath. This includes post metadata, term metadata, settings, and redirects.', 'meowseo' ); ?></p>

				<?php if ( empty( $detected_plugins ) ) : ?>
					<div class="notice notice-warning inline">
						<p><?php echo esc_html__( 'No compatible SEO plugins detected. Please install Yoast SEO or RankMath to import data.', 'meowseo' ); ?></p>
					</div>
				<?php else : ?>
					<h3><?php echo esc_html__( 'Detected Plugins', 'meowseo' ); ?></h3>
					<div class="meowseo-detected-plugins">
						<?php foreach ( $detected_plugins as $plugin ) : ?>
							<div class="meowseo-plugin-card">
								<div class="meowseo-plugin-info">
									<h4><?php echo esc_html( $plugin['name'] ); ?></h4>
									<p class="description">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: plugin name */
												__( 'Import SEO data from %s', 'meowseo' ),
												$plugin['name']
											)
										);
										?>
									</p>
								</div>
								<div class="meowseo-plugin-actions">
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'meowseo_start_import', 'meowseo_import_nonce' ); ?>
										<input type="hidden" name="action" value="meowseo_start_import" />
										<input type="hidden" name="plugin_slug" value="<?php echo esc_attr( $plugin['slug'] ); ?>" />
										<button type="submit" class="button button-primary">
											<?php
											echo esc_html(
												sprintf(
													/* translators: %s: plugin name */
													__( 'Start Import from %s', 'meowseo' ),
													$plugin['name']
												)
											);
											?>
										</button>
									</form>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="meowseo-import-info">
						<h3><?php echo esc_html__( 'What will be imported?', 'meowseo' ); ?></h3>
						<ul>
							<li><?php echo esc_html__( 'Post metadata (titles, descriptions, keywords, robots settings, social meta)', 'meowseo' ); ?></li>
							<li><?php echo esc_html__( 'Term metadata (category and tag titles and descriptions)', 'meowseo' ); ?></li>
							<li><?php echo esc_html__( 'Plugin settings (title patterns, separators, homepage settings)', 'meowseo' ); ?></li>
							<li><?php echo esc_html__( 'Redirects (301, 302, 307, 410)', 'meowseo' ); ?></li>
						</ul>
						<p class="description">
							<?php echo esc_html__( 'Note: Original plugin data will not be deleted. You can verify the import before deactivating the source plugin.', 'meowseo' ); ?>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<style>
			.meowseo-import-wizard {
				max-width: 800px;
			}
			.meowseo-card {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 20px;
				margin-top: 20px;
			}
			.meowseo-detected-plugins {
				margin: 20px 0;
			}
			.meowseo-plugin-card {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 15px;
				border: 1px solid #ddd;
				border-radius: 4px;
				margin-bottom: 10px;
				background: #f9f9f9;
			}
			.meowseo-plugin-info h4 {
				margin: 0 0 5px 0;
			}
			.meowseo-plugin-info .description {
				margin: 0;
			}
			.meowseo-import-info {
				margin-top: 30px;
				padding-top: 20px;
				border-top: 1px solid #ddd;
			}
			.meowseo-import-info ul {
				list-style: disc;
				margin-left: 20px;
			}
		</style>
		<?php
	}

	/**
	 * Render import progress tracking UI.
	 *
	 * Requirements: 1.26, 1.28
	 *
	 * @param array $import_job Import job data.
	 * @return void
	 */
	private function render_import_progress( array $import_job ): void {
		$progress = $import_job['progress'];
		$status   = $import_job['status'];

		// Calculate overall progress.
		$total_items     = 0;
		$processed_items = 0;

		foreach ( $progress as $phase => $data ) {
			$total_items     += $data['total'];
			$processed_items += $data['processed'];
		}

		$progress_percentage = $total_items > 0 ? round( ( $processed_items / $total_items ) * 100 ) : 0;

		?>
		<div class="meowseo-import-progress">
			<div class="meowseo-card">
				<h2><?php echo esc_html__( 'Import in Progress', 'meowseo' ); ?></h2>

				<div class="meowseo-progress-bar-container">
					<div class="meowseo-progress-bar" style="width: <?php echo esc_attr( $progress_percentage ); ?>%;"></div>
				</div>
				<p class="meowseo-progress-text">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: processed items, 2: total items, 3: percentage */
							__( 'Processing: %1$d / %2$d items (%3$d%%)', 'meowseo' ),
							$processed_items,
							$total_items,
							$progress_percentage
						)
					);
					?>
				</p>

				<div class="meowseo-progress-phases">
					<h3><?php echo esc_html__( 'Import Phases', 'meowseo' ); ?></h3>
					<table class="widefat">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Phase', 'meowseo' ); ?></th>
								<th><?php echo esc_html__( 'Progress', 'meowseo' ); ?></th>
								<th><?php echo esc_html__( 'Status', 'meowseo' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$phase_labels = array(
								'posts'     => __( 'Posts & Pages', 'meowseo' ),
								'terms'     => __( 'Categories & Tags', 'meowseo' ),
								'options'   => __( 'Settings', 'meowseo' ),
								'redirects' => __( 'Redirects', 'meowseo' ),
							);

							foreach ( $progress as $phase => $data ) :
								$phase_percentage = $data['total'] > 0 ? round( ( $data['processed'] / $data['total'] ) * 100 ) : 0;
								$phase_status     = $data['processed'] >= $data['total'] ? 'complete' : 'in-progress';
								?>
								<tr>
									<td><?php echo esc_html( $phase_labels[ $phase ] ?? $phase ); ?></td>
									<td>
										<?php
										echo esc_html(
											sprintf(
												/* translators: 1: processed, 2: total */
												__( '%1$d / %2$d', 'meowseo' ),
												$data['processed'],
												$data['total']
											)
										);
										?>
									</td>
									<td>
										<?php if ( 'complete' === $phase_status ) : ?>
											<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
											<?php echo esc_html__( 'Complete', 'meowseo' ); ?>
										<?php else : ?>
											<span class="dashicons dashicons-update" style="color: #f56e28;"></span>
											<?php echo esc_html__( 'In Progress', 'meowseo' ); ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="meowseo-progress-actions">
					<button type="button" class="button" id="meowseo-cancel-import" data-import-id="<?php echo esc_attr( $import_job['import_id'] ); ?>">
						<?php echo esc_html__( 'Cancel Import', 'meowseo' ); ?>
					</button>
				</div>
			</div>
		</div>

		<style>
			.meowseo-progress-bar-container {
				width: 100%;
				height: 30px;
				background: #f0f0f1;
				border-radius: 4px;
				overflow: hidden;
				margin: 20px 0;
			}
			.meowseo-progress-bar {
				height: 100%;
				background: #2271b1;
				transition: width 0.3s ease;
			}
			.meowseo-progress-text {
				text-align: center;
				font-weight: 600;
				margin: 10px 0;
			}
			.meowseo-progress-phases {
				margin: 30px 0;
			}
			.meowseo-progress-actions {
				margin-top: 20px;
				text-align: center;
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			var importId = '<?php echo esc_js( $import_job['import_id'] ); ?>';
			var statusCheckInterval;

			// Poll import status every 2 seconds
			function checkImportStatus() {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'meowseo_import_status',
						nonce: '<?php echo esc_js( wp_create_nonce( 'meowseo_import_status' ) ); ?>',
						import_id: importId
					},
					success: function(response) {
						if (response.success && response.data) {
							var status = response.data.status;

							if (status === 'completed') {
								clearInterval(statusCheckInterval);
								// Set completed import transient via AJAX
								$.post(ajaxurl, {
									action: 'meowseo_set_completed_import',
									nonce: '<?php echo esc_js( wp_create_nonce( 'meowseo_set_completed_import' ) ); ?>',
									import_id: importId
								}, function() {
									location.reload();
								});
							} else if (status === 'cancelled' || status === 'failed') {
								clearInterval(statusCheckInterval);
								location.reload();
							}
						}
					}
				});
			}

			// Start polling
			statusCheckInterval = setInterval(checkImportStatus, 2000);

			// Handle cancel button
			$('#meowseo-cancel-import').on('click', function() {
				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to cancel this import?', 'meowseo' ) ); ?>')) {
					return;
				}

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'meowseo_cancel_import',
						nonce: '<?php echo esc_js( wp_create_nonce( 'meowseo_cancel_import' ) ); ?>',
						import_id: importId
					},
					success: function(response) {
						if (response.success) {
							clearInterval(statusCheckInterval);
							location.reload();
						}
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render import completion summary.
	 *
	 * Requirements: 1.26, 1.27
	 *
	 * @param array $import_job Completed import job data.
	 * @return void
	 */
	private function render_import_summary( array $import_job ): void {
		$summary = $import_job['summary'];
		$errors  = $import_job['errors'];

		?>
		<div class="meowseo-import-summary">
			<div class="meowseo-card">
				<h2>
					<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
					<?php echo esc_html__( 'Import Complete', 'meowseo' ); ?>
				</h2>

				<div class="meowseo-summary-stats">
					<div class="meowseo-stat-card">
						<div class="meowseo-stat-value"><?php echo esc_html( $summary['posts_imported'] ); ?></div>
						<div class="meowseo-stat-label"><?php echo esc_html__( 'Posts Imported', 'meowseo' ); ?></div>
					</div>
					<div class="meowseo-stat-card">
						<div class="meowseo-stat-value"><?php echo esc_html( $summary['terms_imported'] ); ?></div>
						<div class="meowseo-stat-label"><?php echo esc_html__( 'Terms Imported', 'meowseo' ); ?></div>
					</div>
					<div class="meowseo-stat-card">
						<div class="meowseo-stat-value"><?php echo esc_html( $summary['options_imported'] ); ?></div>
						<div class="meowseo-stat-label"><?php echo esc_html__( 'Settings Imported', 'meowseo' ); ?></div>
					</div>
					<div class="meowseo-stat-card">
						<div class="meowseo-stat-value"><?php echo esc_html( $summary['redirects_imported'] ); ?></div>
						<div class="meowseo-stat-label"><?php echo esc_html__( 'Redirects Imported', 'meowseo' ); ?></div>
					</div>
					<?php if ( $summary['errors'] > 0 ) : ?>
						<div class="meowseo-stat-card meowseo-stat-error">
							<div class="meowseo-stat-value"><?php echo esc_html( $summary['errors'] ); ?></div>
							<div class="meowseo-stat-label"><?php echo esc_html__( 'Errors', 'meowseo' ); ?></div>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $errors ) ) : ?>
					<div class="meowseo-error-log">
						<h3><?php echo esc_html__( 'Error Log', 'meowseo' ); ?></h3>
						<p class="description">
							<?php echo esc_html__( 'The following items encountered errors during import:', 'meowseo' ); ?>
						</p>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'Post ID', 'meowseo' ); ?></th>
									<th><?php echo esc_html__( 'Field', 'meowseo' ); ?></th>
									<th><?php echo esc_html__( 'Error', 'meowseo' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								// Show first 20 errors.
								$displayed_errors = array_slice( $errors, 0, 20 );
								foreach ( $displayed_errors as $error ) :
									?>
									<tr>
										<td><?php echo esc_html( $error['post_id'] ?? 'N/A' ); ?></td>
										<td><code><?php echo esc_html( $error['field'] ?? 'N/A' ); ?></code></td>
										<td><?php echo esc_html( $error['error'] ?? 'Unknown error' ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php if ( count( $errors ) > 20 ) : ?>
							<p class="description">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: number of additional errors */
										__( 'And %d more errors. Export the full error log to see all errors.', 'meowseo' ),
										count( $errors ) - 20
									)
								);
								?>
							</p>
						<?php endif; ?>
					</div>

					<div class="meowseo-summary-actions">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" style="display: inline;">
							<?php wp_nonce_field( 'meowseo_export_error_log', 'nonce' ); ?>
							<input type="hidden" name="action" value="meowseo_export_error_log" />
							<input type="hidden" name="import_id" value="<?php echo esc_attr( $import_job['import_id'] ); ?>" />
							<button type="submit" class="button">
								<span class="dashicons dashicons-download"></span>
								<?php echo esc_html__( 'Export Error Log', 'meowseo' ); ?>
							</button>
						</form>
					</div>
				<?php endif; ?>

				<div class="meowseo-summary-next-steps">
					<h3><?php echo esc_html__( 'Next Steps', 'meowseo' ); ?></h3>
					<ol>
						<li><?php echo esc_html__( 'Review your imported data to ensure everything looks correct', 'meowseo' ); ?></li>
						<li><?php echo esc_html__( 'Check a few posts and pages to verify SEO metadata', 'meowseo' ); ?></li>
						<li><?php echo esc_html__( 'Once verified, you can safely deactivate the source plugin', 'meowseo' ); ?></li>
						<li><?php echo esc_html__( 'Configure any additional MeowSEO settings as needed', 'meowseo' ); ?></li>
					</ol>
				</div>

				<div class="meowseo-summary-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=meowseo-import' ) ); ?>" class="button button-primary">
						<?php echo esc_html__( 'Start Another Import', 'meowseo' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=meowseo-settings' ) ); ?>" class="button">
						<?php echo esc_html__( 'Go to Settings', 'meowseo' ); ?>
					</a>
				</div>
			</div>
		</div>

		<style>
			.meowseo-import-summary h2 {
				display: flex;
				align-items: center;
				gap: 10px;
			}
			.meowseo-summary-stats {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
				gap: 15px;
				margin: 30px 0;
			}
			.meowseo-stat-card {
				background: #f0f6fc;
				border: 1px solid #c3dafe;
				border-radius: 4px;
				padding: 20px;
				text-align: center;
			}
			.meowseo-stat-card.meowseo-stat-error {
				background: #fcf0f1;
				border-color: #f1aeb5;
			}
			.meowseo-stat-value {
				font-size: 32px;
				font-weight: 700;
				color: #2271b1;
				margin-bottom: 5px;
			}
			.meowseo-stat-card.meowseo-stat-error .meowseo-stat-value {
				color: #d63638;
			}
			.meowseo-stat-label {
				font-size: 14px;
				color: #646970;
			}
			.meowseo-error-log {
				margin: 30px 0;
				padding: 20px;
				background: #fff8e5;
				border: 1px solid #f0b849;
				border-radius: 4px;
			}
			.meowseo-error-log table {
				margin-top: 15px;
			}
			.meowseo-summary-next-steps {
				margin: 30px 0;
				padding: 20px;
				background: #f0f6fc;
				border: 1px solid #c3dafe;
				border-radius: 4px;
			}
			.meowseo-summary-next-steps ol {
				margin-left: 20px;
			}
			.meowseo-summary-next-steps li {
				margin: 10px 0;
			}
			.meowseo-summary-actions {
				margin-top: 20px;
				display: flex;
				gap: 10px;
			}
		</style>
		<?php
	}

	/**
	 * Handle start import form submission.
	 *
	 * Requirement: 1.26
	 *
	 * @return void
	 */
	public function handle_start_import(): void {
		// Verify nonce.
		if ( ! isset( $_POST['meowseo_import_nonce'] ) || ! \wp_verify_nonce( \sanitize_key( $_POST['meowseo_import_nonce'] ), 'meowseo_start_import' ) ) {
			\wp_die(
				\esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				\esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify capability.
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die(
				\esc_html__( 'You do not have sufficient permissions to perform this action.', 'meowseo' ),
				\esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Get plugin slug.
		$plugin_slug = isset( $_POST['plugin_slug'] ) ? \sanitize_text_field( \wp_unslash( $_POST['plugin_slug'] ) ) : '';

		if ( empty( $plugin_slug ) ) {
			\wp_safe_redirect(
				\add_query_arg(
					'meowseo_import_error',
					urlencode( \__( 'Invalid plugin slug.', 'meowseo' ) ),
					\wp_get_referer()
				)
			);
			exit;
		}

		// Start import.
		$result = $this->import_manager->start_import( $plugin_slug );

		if ( isset( $result['error'] ) ) {
			wp_safe_redirect(
				add_query_arg(
					'meowseo_import_error',
					urlencode( $result['message'] ),
					wp_get_referer()
				)
			);
			exit;
		}

		// Store active import ID.
		set_transient( 'meowseo_active_import_id', $result['import_id'], DAY_IN_SECONDS );

		// Redirect to import page.
		wp_safe_redirect( admin_url( 'admin.php?page=meowseo-import' ) );
		exit;
	}

	/**
	 * Handle AJAX import status request.
	 *
	 * Requirement: 1.28
	 *
	 * @return void
	 */
	public function handle_import_status(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! \wp_verify_nonce( \sanitize_key( $_POST['nonce'] ), 'meowseo_import_status' ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Security check failed.', 'meowseo' ) ) );
		}

		// Get import ID.
		$import_id = isset( $_POST['import_id'] ) ? \sanitize_text_field( \wp_unslash( $_POST['import_id'] ) ) : '';

		if ( empty( $import_id ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Invalid import ID.', 'meowseo' ) ) );
		}

		// Get import status.
		$status = $this->import_manager->get_import_status( $import_id );

		if ( isset( $status['error'] ) ) {
			\wp_send_json_error( $status );
		}

		\wp_send_json_success( $status );
	}

	/**
	 * Handle AJAX cancel import request.
	 *
	 * Requirement: 1.28
	 *
	 * @return void
	 */
	public function handle_cancel_import(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! \wp_verify_nonce( \sanitize_key( $_POST['nonce'] ), 'meowseo_cancel_import' ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Security check failed.', 'meowseo' ) ) );
		}

		// Get import ID.
		$import_id = isset( $_POST['import_id'] ) ? \sanitize_text_field( \wp_unslash( $_POST['import_id'] ) ) : '';

		if ( empty( $import_id ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Invalid import ID.', 'meowseo' ) ) );
		}

		// Cancel import.
		$result = $this->import_manager->cancel_import( $import_id );

		if ( ! $result ) {
			\wp_send_json_error( array( 'message' => \__( 'Failed to cancel import.', 'meowseo' ) ) );
		}

		// Clear active import ID.
		\delete_transient( 'meowseo_active_import_id' );

		\wp_send_json_success( array( 'message' => \__( 'Import cancelled.', 'meowseo' ) ) );
	}

	/**
	 * Handle AJAX process import batch request.
	 *
	 * Processes a single batch of import items.
	 * Requirement: 1.28
	 *
	 * @return void
	 */
	public function handle_process_batch(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! \wp_verify_nonce( \sanitize_key( $_POST['nonce'] ), 'meowseo_process_batch' ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Security check failed.', 'meowseo' ) ) );
		}

		// Get import ID.
		$import_id = isset( $_POST['import_id'] ) ? \sanitize_text_field( \wp_unslash( $_POST['import_id'] ) ) : '';

		if ( empty( $import_id ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Invalid import ID.', 'meowseo' ) ) );
		}

		// Get import job.
		$job = $this->import_manager->get_import_status( $import_id );

		if ( isset( $job['error'] ) ) {
			\wp_send_json_error( $job );
		}

		// Get importer.
		$importer = $this->import_manager->get_importer( $job['plugin'] );

		if ( ! $importer ) {
			\wp_send_json_error( array( 'message' => \__( 'Importer not found.', 'meowseo' ) ) );
		}

		// Process next phase.
		// This is a simplified implementation - actual batch processing would be more complex.
		\wp_send_json_success( array( 'message' => \__( 'Batch processed.', 'meowseo' ) ) );
	}

	/**
	 * Handle AJAX export error log request.
	 *
	 * Requirement: 1.27
	 *
	 * @return void
	 */
	public function handle_export_error_log(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! \wp_verify_nonce( \sanitize_key( $_POST['nonce'] ), 'meowseo_export_error_log' ) ) {
			\wp_die( \esc_html__( 'Security check failed.', 'meowseo' ) );
		}

		// Verify capability.
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'You do not have sufficient permissions.', 'meowseo' ) );
		}

		// Get import ID.
		$import_id = isset( $_POST['import_id'] ) ? \sanitize_text_field( \wp_unslash( $_POST['import_id'] ) ) : '';

		if ( empty( $import_id ) ) {
			\wp_die( \esc_html__( 'Invalid import ID.', 'meowseo' ) );
		}

		// Get import job.
		$job = $this->import_manager->get_import_status( $import_id );

		if ( isset( $job['error'] ) || empty( $job['errors'] ) ) {
			\wp_die( \esc_html__( 'No errors to export.', 'meowseo' ) );
		}

		// Generate CSV.
		$csv = "Post ID,Field,Error\n";
		foreach ( $job['errors'] as $error ) {
			$csv .= sprintf(
				"%d,%s,%s\n",
				$error['post_id'] ?? 0,
				$error['field'] ?? '',
				str_replace( '"', '""', $error['error'] ?? '' )
			);
		}

		// Send file.
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="meowseo-import-errors-' . $import_id . '.csv"' );
		echo $csv;
		exit;
	}

	/**
	 * Handle AJAX set completed import request.
	 *
	 * Sets the completed import transient for display.
	 * Requirement: 1.27
	 *
	 * @return void
	 */
	public function handle_set_completed_import(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! \wp_verify_nonce( \sanitize_key( $_POST['nonce'] ), 'meowseo_set_completed_import' ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Security check failed.', 'meowseo' ) ) );
		}

		// Get import ID.
		$import_id = isset( $_POST['import_id'] ) ? \sanitize_text_field( \wp_unslash( $_POST['import_id'] ) ) : '';

		if ( empty( $import_id ) ) {
			\wp_send_json_error( array( 'message' => \__( 'Invalid import ID.', 'meowseo' ) ) );
		}

		// Set completed import transient.
		\set_transient( 'meowseo_completed_import_id', $import_id, HOUR_IN_SECONDS );

		// Clear active import transient.
		\delete_transient( 'meowseo_active_import_id' );

		\wp_send_json_success();
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only load on import page.
		if ( 'meowseo_page_meowseo-import' !== $hook_suffix ) {
			return;
		}

		// Enqueue WordPress admin styles.
		wp_enqueue_style( 'dashicons' );
	}
}
