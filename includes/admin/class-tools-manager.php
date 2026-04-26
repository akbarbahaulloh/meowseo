<?php
/**
 * Tools_Manager class for MeowSEO plugin.
 *
 * Handles import/export, database maintenance, and bulk SEO operations.
 *
 * @package MeowSEO
 * @subpackage MeowSEO\Admin
 * @since 1.0.0
 */

namespace MeowSEO\Admin;

use MeowSEO\Options;
use MeowSEO\Helpers\Logger;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools_Manager class
 *
 * Manages import/export, database maintenance, and bulk SEO operations.
 * Requirements: 10.1, 10.2, 10.3, 10.4
 *
 * @since 1.0.0
 */
class Tools_Manager {

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Render tools page
	 *
	 * Outputs the tools UI with sections for Import/Export, Database Maintenance, and SEO Data.
	 * Requirements: 10.1, 10.2, 10.3, 10.4, 28.2, 28.5
	 *
	 * Note: Capability check (manage_options) is performed in Admin::render_tools_page()
	 * before calling this method.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_tools_page(): void {
		// Generate unique nonces for each tool action (Requirement 28.5).
		$nonces = array(
			'export_settings'   => wp_create_nonce( 'meowseo_tools_export_settings' ),
			'export_redirects'  => wp_create_nonce( 'meowseo_tools_export_redirects' ),
			'import_settings'   => wp_create_nonce( 'meowseo_tools_import_settings' ),
			'import_redirects'  => wp_create_nonce( 'meowseo_tools_import_redirects' ),
			'clear_logs'        => wp_create_nonce( 'meowseo_tools_clear_logs' ),
			'repair_tables'     => wp_create_nonce( 'meowseo_tools_repair_tables' ),
			'flush_caches'      => wp_create_nonce( 'meowseo_tools_flush_caches' ),
			'bulk_descriptions' => wp_create_nonce( 'meowseo_tools_bulk_descriptions' ),
			'scan_missing'      => wp_create_nonce( 'meowseo_tools_scan_missing' ),
		);
		?>
		<div class="meowseo-tools-container">
			<h2><?php esc_html_e( 'Tools', 'meowseo' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Manage plugin data, perform maintenance, and bulk operations.', 'meowseo' ); ?></p>

			<!-- Import/Export Section -->
			<div class="meowseo-tools-section">
				<h3><?php esc_html_e( 'Import / Export', 'meowseo' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Backup and restore plugin settings and redirects.', 'meowseo' ); ?></p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Export Settings', 'meowseo' ); ?></th>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
								<?php wp_nonce_field( 'meowseo_tools_export_settings', 'meowseo_tools_nonce' ); ?>
								<input type="hidden" name="action" value="meowseo_export_settings">
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Download Settings (JSON)', 'meowseo' ); ?></button>
								<p class="description"><?php esc_html_e( 'Export all plugin settings as a JSON file for backup or migration.', 'meowseo' ); ?></p>
							</form>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Export Redirects', 'meowseo' ); ?></th>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
								<?php wp_nonce_field( 'meowseo_tools_export_redirects', 'meowseo_tools_nonce' ); ?>
								<input type="hidden" name="action" value="meowseo_export_redirects">
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Download Redirects (CSV)', 'meowseo' ); ?></button>
								<p class="description"><?php esc_html_e( 'Export all redirects as a CSV file.', 'meowseo' ); ?></p>
							</form>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="import_settings_file"><?php esc_html_e( 'Import Settings', 'meowseo' ); ?></label></th>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
								<?php wp_nonce_field( 'meowseo_tools_import_settings', 'meowseo_tools_nonce' ); ?>
								<input type="hidden" name="action" value="meowseo_import_settings">
								<input type="file" name="import_settings_file" id="import_settings_file" accept=".json" required>
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Import Settings', 'meowseo' ); ?></button>
								<p class="description"><?php esc_html_e( 'Upload a previously exported settings JSON file.', 'meowseo' ); ?></p>
							</form>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="import_redirects_file"><?php esc_html_e( 'Import Redirects', 'meowseo' ); ?></label></th>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
								<?php wp_nonce_field( 'meowseo_tools_import_redirects', 'meowseo_tools_nonce' ); ?>
								<input type="hidden" name="action" value="meowseo_import_redirects">
								<input type="file" name="import_redirects_file" id="import_redirects_file" accept=".csv" required>
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Import Redirects', 'meowseo' ); ?></button>
								<p class="description"><?php esc_html_e( 'Upload a CSV file with redirects (source_url, target_url, type).', 'meowseo' ); ?></p>
							</form>
						</td>
					</tr>
				</table>
			</div>

			<!-- Database Maintenance Section -->
			<div class="meowseo-tools-section">
				<h3><?php esc_html_e( 'Database Maintenance', 'meowseo' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Optimize plugin performance and clean up old data.', 'meowseo' ); ?></p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Clear Old Logs', 'meowseo' ); ?></th>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
								<?php wp_nonce_field( 'meowseo_tools_clear_logs', 'meowseo_tools_nonce' ); ?>
								<input type="hidden" name="action" value="meowseo_clear_logs">
								<button type="submit" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Delete all log entries older than 90 days? This cannot be undone.', 'meowseo' ); ?>');"><?php esc_html_e( 'Clear Logs', 'meowseo' ); ?></button>
								<p class="description"><?php esc_html_e( 'Delete 404 logs and GSC logs older than 90 days.', 'meowseo' ); ?></p>
							</form>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Repair Tables', 'meowseo' ); ?></th>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
								<?php wp_nonce_field( 'meowseo_tools_repair_tables', 'meowseo_tools_nonce' ); ?>
								<input type="hidden" name="action" value="meowseo_repair_tables">
								<button type="submit" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Repair all plugin database tables? This may take a moment.', 'meowseo' ); ?>');"><?php esc_html_e( 'Repair Tables', 'meowseo' ); ?></button>
								<p class="description"><?php esc_html_e( 'Run REPAIR TABLE on all plugin database tables.', 'meowseo' ); ?></p>
							</form>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Flush Caches', 'meowseo' ); ?></th>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
								<?php wp_nonce_field( 'meowseo_tools_flush_caches', 'meowseo_tools_nonce' ); ?>
								<input type="hidden" name="action" value="meowseo_flush_caches">
								<button type="submit" class="button button-secondary"><?php esc_html_e( 'Flush Caches', 'meowseo' ); ?></button>
								<p class="description"><?php esc_html_e( 'Delete all plugin transients and object cache entries.', 'meowseo' ); ?></p>
							</form>
						</td>
					</tr>
				</table>
			</div>

			<!-- SEO Data Tools Section -->
			<div class="meowseo-tools-section">
				<h3><?php esc_html_e( 'SEO Data Tools', 'meowseo' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Generate missing metadata and identify content issues.', 'meowseo' ); ?></p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Bulk Generate Descriptions', 'meowseo' ); ?></th>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
								<?php wp_nonce_field( 'meowseo_tools_bulk_descriptions', 'meowseo_tools_nonce' ); ?>
								<input type="hidden" name="action" value="meowseo_bulk_descriptions">
								<button type="submit" class="button button-secondary"><?php esc_html_e( 'Generate Descriptions', 'meowseo' ); ?></button>
								<p class="description"><?php esc_html_e( 'Generate meta descriptions for posts missing descriptions from excerpts or content.', 'meowseo' ); ?></p>
							</form>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Scan Missing SEO Data', 'meowseo' ); ?></th>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
								<?php wp_nonce_field( 'meowseo_tools_scan_missing', 'meowseo_tools_nonce' ); ?>
								<input type="hidden" name="action" value="meowseo_scan_missing">
								<button type="submit" class="button button-secondary"><?php esc_html_e( 'Scan for Missing Data', 'meowseo' ); ?></button>
								<p class="description"><?php esc_html_e( 'Identify posts missing title, description, or focus keyword.', 'meowseo' ); ?></p>
							</form>
						</td>
					</tr>
				</table>
			</div>

			<!-- MeowIndex Submission History Section -->
			<div class="meowseo-tools-section">
				<h3><?php esc_html_e( 'MeowIndex Submission History', 'meowseo' ); ?></h3>
				<p class="description"><?php esc_html_e( 'View recent instant indexing submissions and their status.', 'meowseo' ); ?></p>
 
				<?php
				// Get MeowIndex module and logger.
				$plugin = \MeowSEO\Plugin::instance();
				if ( $plugin ) {
					$module_manager = $plugin->get_module_manager();
					$meowindex_module = $module_manager ? $module_manager->get_module( 'meowindex' ) : null;
				} else {
					$meowindex_module = null;
				}
 
				if ( $meowindex_module ) {
					$logger = $meowindex_module->get_logger();
					$history = $logger->get_history( 100 );
 
					if ( empty( $history ) ) {
						echo '<p>' . esc_html__( 'No submissions yet.', 'meowseo' ) . '</p>';
					} else {
						?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Timestamp', 'meowseo' ); ?></th>
									<th><?php esc_html_e( 'Engine', 'meowseo' ); ?></th>
									<th><?php esc_html_e( 'URLs', 'meowseo' ); ?></th>
									<th><?php esc_html_e( 'Status', 'meowseo' ); ?></th>
									<th><?php esc_html_e( 'Error', 'meowseo' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $history as $entry ) : ?>
									<tr>
										<td><?php echo esc_html( $entry['timestamp'] ); ?></td>
										<td>
											<?php 
											$engine = isset( $entry['engine'] ) ? $entry['engine'] : 'meowindex';
											echo esc_html( ucfirst( $engine ) );
											?>
										</td>
										<td>
											<?php
											if ( ! empty( $entry['urls'] ) ) {
												$url_count = count( $entry['urls'] );
												if ( $url_count === 1 ) {
													echo '<code title="' . esc_attr( $entry['urls'][0] ) . '">' . esc_html( wp_trim_words( $entry['urls'][0], 10 ) ) . '</code>';
												} else {
													echo esc_html( sprintf( _n( '%d URL', '%d URLs', $url_count, 'meowseo' ), $url_count ) );
												}
											}
											?>
										</td>
										<td>
											<?php
											if ( $entry['success'] ) {
												echo '<span style="color: green;">✓ ' . esc_html__( 'Success', 'meowseo' ) . '</span>';
											} else {
												echo '<span style="color: red;">✗ ' . esc_html__( 'Failed', 'meowseo' ) . '</span>';
											}
											?>
										</td>
										<td>
											<?php
											if ( ! empty( $entry['error'] ) ) {
												echo esc_html( $entry['error'] );
											} else {
												echo '—';
											}
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php
					}
				} else {
					echo '<p>' . esc_html__( 'MeowIndex module is not active.', 'meowseo' ) . '</p>';
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Export settings as JSON
	 *
	 * Requirements: 11.1, 11.5
	 *
	 * @since 1.0.0
	 * @return string JSON string of all settings.
	 */
	public function export_settings(): string {
		$settings = $this->options->get_all();
		
		// Remove sensitive data.
		unset( $settings['gsc_credentials'] );
		
		return wp_json_encode( $settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Export redirects as CSV
	 *
	 * Requirements: 11.2, 11.5
	 *
	 * @since 1.0.0
	 * @return string CSV string of all redirects.
	 */
	public function export_redirects(): string {
		global $wpdb;
		
		$table = $wpdb->prefix . 'meowseo_redirects';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
		
		if ( ! $table_exists ) {
			return '';
		}
		
		$redirects = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT source_url, target_url, redirect_type FROM {$table} WHERE is_active = %d ORDER BY source_url ASC",
				1
			),
			ARRAY_A
		);
		
		if ( empty( $redirects ) ) {
			return '';
		}
		
		// Build CSV.
		$csv = "source_url,target_url,redirect_type\n";
		foreach ( $redirects as $redirect ) {
			$csv .= sprintf(
				"\"%s\",\"%s\",\"%s\"\n",
				str_replace( '"', '""', $redirect['source_url'] ),
				str_replace( '"', '""', $redirect['target_url'] ),
				$redirect['redirect_type']
			);
		}
		
		return $csv;
	}

	/**
	 * Import settings from JSON
	 *
	 * Requirements: 11.3, 11.4, 11.6, 11.7
	 *
	 * @since 1.0.0
	 * @param array $file File upload array.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function import_settings( array $file ) {
		// Validate file (Requirement 32.3).
		if ( empty( $file['tmp_name'] ) ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::warning(
					'Settings import failed: no file uploaded',
					array(
						'user_id' => get_current_user_id(),
					)
				);
			}
			return new \WP_Error( 'no_file', __( 'No file uploaded.', 'meowseo' ) );
		}
		
		// Check file size (max 5MB) (Requirement 32.3).
		if ( $file['size'] > 5 * 1024 * 1024 ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::warning(
					'Settings import failed: file too large',
					array(
						'user_id'   => get_current_user_id(),
						'file_size' => $file['size'],
					)
				);
			}
			return new \WP_Error( 'file_too_large', __( 'File is too large. Maximum size is 5MB.', 'meowseo' ) );
		}
		
		// Read file (Requirement 32.3).
		$content = file_get_contents( $file['tmp_name'] );
		if ( false === $content ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::error(
					'Settings import failed: could not read file',
					array(
						'user_id'    => get_current_user_id(),
						'file_name'  => $file['name'],
					)
				);
			}
			return new \WP_Error( 'read_error', __( 'Could not read file. Please ensure the file is readable.', 'meowseo' ) );
		}
		
		// Parse JSON (Requirement 32.3).
		$settings = json_decode( $content, true );
		if ( null === $settings || ! is_array( $settings ) ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::warning(
					'Settings import failed: invalid JSON format',
					array(
						'user_id'   => get_current_user_id(),
						'file_name' => $file['name'],
					)
				);
			}
			return new \WP_Error( 'invalid_json', __( 'Invalid JSON format. Please ensure the file is a valid JSON export.', 'meowseo' ) );
		}
		
		try {
			// Save settings.
			foreach ( $settings as $key => $value ) {
				// Skip sensitive fields.
				if ( in_array( $key, array( 'gsc_credentials' ), true ) ) {
					continue;
				}
				
				$this->options->set( $key, $value );
			}
			
			$saved = $this->options->save();
			
			if ( ! $saved ) {
				if ( function_exists( 'get_current_user_id' ) ) {
					Logger::error(
						'Settings import failed: could not save settings',
						array(
							'user_id' => get_current_user_id(),
							'count'   => count( $settings ),
						)
					);
				}
				return new \WP_Error( 'save_error', __( 'Failed to save imported settings. Please try again.', 'meowseo' ) );
			}
			
			// Log successful import (Requirement 33.2).
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::info(
					'Settings imported',
					array(
						'user_id' => get_current_user_id(),
						'count'   => count( $settings ),
					)
				);
			}
			
			return true;
		} catch ( \Exception $e ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::error(
					'Settings import failed: exception occurred',
					array(
						'user_id'   => get_current_user_id(),
						'error_msg' => $e->getMessage(),
					)
				);
			}
			return new \WP_Error( 'import_error', __( 'An error occurred during import. Please try again.', 'meowseo' ) );
		}
	}

	/**
	 * Import redirects from CSV
	 *
	 * Requirements: 11.3, 11.4, 11.6, 11.7
	 *
	 * @since 1.0.0
	 * @param array $file File upload array.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function import_redirects( array $file ) {
		global $wpdb;
		
		// Validate file (Requirement 32.3).
		if ( empty( $file['tmp_name'] ) ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::warning(
					'Redirects import failed: no file uploaded',
					array(
						'user_id' => get_current_user_id(),
					)
				);
			}
			return new \WP_Error( 'no_file', __( 'No file uploaded.', 'meowseo' ) );
		}
		
		// Check file size (max 10MB) (Requirement 32.3).
		if ( $file['size'] > 10 * 1024 * 1024 ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::warning(
					'Redirects import failed: file too large',
					array(
						'user_id'   => get_current_user_id(),
						'file_size' => $file['size'],
					)
				);
			}
			return new \WP_Error( 'file_too_large', __( 'File is too large. Maximum size is 10MB.', 'meowseo' ) );
		}
		
		// Read file (Requirement 32.3).
		$content = file_get_contents( $file['tmp_name'] );
		if ( false === $content ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::error(
					'Redirects import failed: could not read file',
					array(
						'user_id'    => get_current_user_id(),
						'file_name'  => $file['name'],
					)
				);
			}
			return new \WP_Error( 'read_error', __( 'Could not read file. Please ensure the file is readable.', 'meowseo' ) );
		}
		
		// Parse CSV (Requirement 32.3).
		$lines = explode( "\n", trim( $content ) );
		if ( empty( $lines ) ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::warning(
					'Redirects import failed: empty file',
					array(
						'user_id'   => get_current_user_id(),
						'file_name' => $file['name'],
					)
				);
			}
			return new \WP_Error( 'empty_file', __( 'File is empty. Please ensure the CSV file contains data.', 'meowseo' ) );
		}
		
		// Skip header.
		array_shift( $lines );
		
		$table = $wpdb->prefix . 'meowseo_redirects';
		$imported = 0;
		$skipped = 0;
		
		try {
			foreach ( $lines as $line ) {
				if ( empty( trim( $line ) ) ) {
					continue;
				}
				
				// Parse CSV line.
				$parts = str_getcsv( $line );
				if ( count( $parts ) < 3 ) {
					$skipped++;
					continue;
				}
				
				$source_url = sanitize_text_field( $parts[0] );
				$target_url = esc_url_raw( $parts[1] );
				$redirect_type = sanitize_text_field( $parts[2] );
				
				if ( empty( $source_url ) || empty( $target_url ) ) {
					$skipped++;
					continue;
				}
				
				// Insert redirect.
				$result = $wpdb->insert(
					$table,
					array(
						'source_url'    => $source_url,
						'target_url'    => $target_url,
						'redirect_type' => $redirect_type,
						'is_active'     => 1,
						'created_at'    => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%d', '%s' )
				);
				
				if ( false === $result ) {
					if ( function_exists( 'get_current_user_id' ) ) {
						Logger::warning(
							'Failed to insert redirect during import',
							array(
								'user_id'      => get_current_user_id(),
								'source_url'   => $source_url,
								'error_msg'    => $wpdb->last_error,
							)
						);
					}
					$skipped++;
					continue;
				}
				
				$imported++;
			}
			
			// Log import (Requirement 33.2).
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::info(
					'Redirects imported',
					array(
						'user_id' => get_current_user_id(),
						'count'   => $imported,
						'skipped' => $skipped,
					)
				);
			}
			
			return true;
		} catch ( \Exception $e ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::error(
					'Redirects import failed: exception occurred',
					array(
						'user_id'   => get_current_user_id(),
						'error_msg' => $e->getMessage(),
					)
				);
			}
			return new \WP_Error( 'import_error', __( 'An error occurred during import. Please try again.', 'meowseo' ) );
		}
	}

	/**
	 * Clear old logs
	 *
	 * Deletes 404 and GSC logs older than 90 days.
	 * Requirements: 12.1, 12.4
	 *
	 * @since 1.0.0
	 * @return int Number of deleted entries.
	 */
	public function clear_old_logs(): int {
		global $wpdb;
		
		$deleted = 0;
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( '-90 days' ) );
		
		try {
			// Delete old 404 logs.
			$table_404 = $wpdb->prefix . 'meowseo_404_log';
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_404 ) ) === $table_404;
			
			if ( $table_exists ) {
				$result = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$table_404} WHERE last_seen < %s",
						$cutoff_date
					)
				);
				
				if ( false === $result ) {
					if ( function_exists( 'get_current_user_id' ) ) {
						Logger::error(
							'Failed to delete old 404 logs',
							array(
								'user_id'   => get_current_user_id(),
								'error_msg' => $wpdb->last_error,
							)
						);
					}
				} else {
					$deleted += $result;
				}
			}
			
			// Delete old GSC logs.
			$table_gsc = $wpdb->prefix . 'meowseo_gsc_data';
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_gsc ) ) === $table_gsc;
			
			if ( $table_exists ) {
				$result = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$table_gsc} WHERE date < %s",
						gmdate( 'Y-m-d', strtotime( '-90 days' ) )
					)
				);
				
				if ( false === $result ) {
					if ( function_exists( 'get_current_user_id' ) ) {
						Logger::error(
							'Failed to delete old GSC logs',
							array(
								'user_id'   => get_current_user_id(),
								'error_msg' => $wpdb->last_error,
							)
						);
					}
				} else {
					$deleted += $result;
				}
			}
			
			// Log operation (Requirement 33.3).
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::info(
					'Old logs cleared',
					array(
						'user_id' => get_current_user_id(),
						'deleted' => $deleted,
						'type'    => 'clear_logs',
						'result'  => 'success',
					)
				);
			}
			
			return $deleted;
		} catch ( \Exception $e ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::error(
					'Failed to clear old logs',
					array(
						'user_id'   => get_current_user_id(),
						'error_msg' => $e->getMessage(),
					)
				);
			}
			return 0;
		}
	}

	/**
	 * Repair database tables
	 *
	 * Runs REPAIR TABLE on all plugin tables.
	 * Requirements: 12.2, 12.5
	 *
	 * @since 1.0.0
	 * @return bool True on success.
	 */
	public function repair_tables(): bool {
		global $wpdb;
		
		$tables = array(
			$wpdb->prefix . 'meowseo_404_log',
			$wpdb->prefix . 'meowseo_redirects',
			$wpdb->prefix . 'meowseo_gsc_data',
			$wpdb->prefix . 'meowseo_gsc_queue',
		);
		
		$repaired = 0;
		$failed = 0;
		
		try {
			foreach ( $tables as $table ) {
				$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
				
				if ( $table_exists ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$result = $wpdb->query( "REPAIR TABLE {$table}" );
					
					if ( false === $result ) {
						if ( function_exists( 'get_current_user_id' ) ) {
							Logger::warning(
								'Failed to repair database table',
								array(
									'user_id'   => get_current_user_id(),
									'table'     => $table,
									'error_msg' => $wpdb->last_error,
								)
							);
						}
						$failed++;
					} else {
						$repaired++;
					}
				}
			}
			
			// Log operation (Requirement 33.3).
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::info(
					'Database tables repaired',
					array(
						'user_id'  => get_current_user_id(),
						'repaired' => $repaired,
						'failed'   => $failed,
						'type'     => 'repair_tables',
						'result'   => $failed > 0 ? 'partial' : 'success',
					)
				);
			}
			
			return true;
		} catch ( \Exception $e ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::error(
					'Failed to repair database tables',
					array(
						'user_id'   => get_current_user_id(),
						'error_msg' => $e->getMessage(),
					)
				);
			}
			return false;
		}
	}

	/**
	 * Flush all plugin caches
	 *
	 * Deletes all transients and object cache entries with meowseo prefix.
	 * Requirements: 12.3, 12.6
	 *
	 * @since 1.0.0
	 * @return bool True on success.
	 */
	public function flush_caches(): bool {
		global $wpdb;
		
		try {
			// Delete transients.
			$result = $wpdb->query(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%meowseo%' AND option_name LIKE '%transient%'"
			);
			
			if ( false === $result ) {
				if ( function_exists( 'get_current_user_id' ) ) {
					Logger::warning(
						'Failed to delete transients',
						array(
							'user_id'   => get_current_user_id(),
							'error_msg' => $wpdb->last_error,
						)
					);
				}
			}
			
			// Clear object cache if available.
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}
			
			// Log operation (Requirement 33.3).
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::info(
					'Caches flushed',
					array(
						'user_id' => get_current_user_id(),
						'type'    => 'flush_caches',
						'result'  => 'success',
					)
				);
			}
			
			return true;
		} catch ( \Exception $e ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::error(
					'Failed to flush caches',
					array(
						'user_id'   => get_current_user_id(),
						'error_msg' => $e->getMessage(),
					)
				);
			}
			return false;
		}
	}

	/**
	 * Bulk generate descriptions
	 *
	 * Generates meta descriptions for posts missing descriptions.
	 * Requirements: 13.1, 13.3, 13.5
	 *
	 * @since 1.0.0
	 * @param int $batch_size Number of posts to process per batch.
	 * @return array Operation result with count and status.
	 */
	public function bulk_generate_descriptions( int $batch_size = 50 ): array {
		global $wpdb;
		
		try {
			// Get posts missing descriptions.
			$posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT p.ID, p.post_excerpt, p.post_content 
					FROM {$wpdb->posts} p 
					LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s 
					WHERE p.post_status = 'publish' 
					AND p.post_type IN ('post', 'page') 
					AND (pm.meta_value IS NULL OR pm.meta_value = '') 
					LIMIT %d",
					'meowseo_description',
					$batch_size
				)
			);
			
			if ( null === $posts ) {
				if ( function_exists( 'get_current_user_id' ) ) {
					Logger::error(
						'Failed to query posts for bulk description generation',
						array(
							'user_id'   => get_current_user_id(),
							'error_msg' => $wpdb->last_error,
						)
					);
				}
				return array(
					'generated' => 0,
					'status'    => 'error',
					'message'   => __( 'Failed to query posts. Please try again.', 'meowseo' ),
				);
			}
			
			$generated = 0;
			$failed = 0;
			
			foreach ( $posts as $post ) {
				try {
					// Generate description from excerpt or content.
					$description = '';
					
					if ( ! empty( $post->post_excerpt ) ) {
						$description = wp_strip_all_tags( $post->post_excerpt );
					} else {
						$description = wp_strip_all_tags( $post->post_content );
					}
					
					// Limit to 160 characters.
					$description = substr( $description, 0, 160 );
					
					if ( ! empty( $description ) ) {
						$result = update_post_meta( $post->ID, 'meowseo_description', $description );
						if ( $result ) {
							$generated++;
						} else {
							$failed++;
						}
					}
				} catch ( \Exception $e ) {
					if ( function_exists( 'get_current_user_id' ) ) {
						Logger::warning(
							'Failed to generate description for post',
							array(
								'user_id'   => get_current_user_id(),
								'post_id'   => $post->ID,
								'error_msg' => $e->getMessage(),
							)
						);
					}
					$failed++;
				}
			}
			
			// Log operation (Requirement 33.4).
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::info(
					'Bulk descriptions generated',
					array(
						'user_id'   => get_current_user_id(),
						'generated' => $generated,
						'failed'    => $failed,
						'type'      => 'bulk_descriptions',
						'result'    => $failed > 0 ? 'partial' : 'success',
					)
				);
			}
			
			return array(
				'generated' => $generated,
				'failed'    => $failed,
				'status'    => 'success',
			);
		} catch ( \Exception $e ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::error(
					'Failed to generate bulk descriptions',
					array(
						'user_id'   => get_current_user_id(),
						'error_msg' => $e->getMessage(),
					)
				);
			}
			return array(
				'generated' => 0,
				'status'    => 'error',
				'message'   => __( 'An error occurred during bulk generation. Please try again.', 'meowseo' ),
			);
		}
	}

	/**
	 * Scan for missing SEO data
	 *
	 * Identifies posts missing title, description, or focus keyword.
	 * Requirements: 13.2, 13.4
	 *
	 * @since 1.0.0
	 * @return array Report of missing SEO data.
	 */
	public function scan_missing_seo_data(): array {
		global $wpdb;
		
		$report = array(
			'total_posts'            => 0,
			'missing_title'          => array(),
			'missing_description'    => array(),
			'missing_focus_keyword'  => array(),
		);
		
		try {
			// Get all published posts.
			// Note: Post types are hardcoded and safe; no user input.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$posts = $wpdb->get_results(
				"SELECT ID, post_title FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post', 'page')"
			);
			
			if ( null === $posts ) {
				if ( function_exists( 'get_current_user_id' ) ) {
					Logger::error(
						'Failed to query posts for SEO data scan',
						array(
							'user_id'   => get_current_user_id(),
							'error_msg' => $wpdb->last_error,
						)
					);
				}
				return array(
					'total_posts'            => 0,
					'missing_title'          => array(),
					'missing_description'    => array(),
					'missing_focus_keyword'  => array(),
					'error'                  => __( 'Failed to scan posts. Please try again.', 'meowseo' ),
				);
			}
			
			$report['total_posts'] = count( $posts );
			
			foreach ( $posts as $post ) {
				try {
					// Check for missing title.
					$title = get_post_meta( $post->ID, 'meowseo_title', true );
					if ( empty( $title ) ) {
						$report['missing_title'][] = array(
							'post_id'    => $post->ID,
							'post_title' => $post->post_title,
						);
					}
					
					// Check for missing description.
					$description = get_post_meta( $post->ID, 'meowseo_description', true );
					if ( empty( $description ) ) {
						$report['missing_description'][] = array(
							'post_id'    => $post->ID,
							'post_title' => $post->post_title,
						);
					}
					
					// Check for missing focus keyword.
					$focus_keyword = get_post_meta( $post->ID, 'meowseo_focus_keyword', true );
					if ( empty( $focus_keyword ) ) {
						$report['missing_focus_keyword'][] = array(
							'post_id'    => $post->ID,
							'post_title' => $post->post_title,
						);
					}
				} catch ( \Exception $e ) {
					if ( function_exists( 'get_current_user_id' ) ) {
						Logger::warning(
							'Failed to scan post for missing SEO data',
							array(
								'user_id'   => get_current_user_id(),
								'post_id'   => $post->ID,
								'error_msg' => $e->getMessage(),
							)
						);
					}
				}
			}
			
			// Log operation (Requirement 33.4).
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::info(
					'SEO data scan completed',
					array(
						'user_id'                => get_current_user_id(),
						'total_posts'            => $report['total_posts'],
						'missing_title'          => count( $report['missing_title'] ),
						'missing_description'    => count( $report['missing_description'] ),
						'missing_focus_keyword'  => count( $report['missing_focus_keyword'] ),
						'type'                   => 'scan_missing_data',
						'result'                 => 'success',
					)
				);
			}
			
			return $report;
		} catch ( \Exception $e ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				Logger::error(
					'Failed to scan for missing SEO data',
					array(
						'user_id'   => get_current_user_id(),
						'error_msg' => $e->getMessage(),
					)
				);
			}
			return array(
				'total_posts'            => 0,
				'missing_title'          => array(),
				'missing_description'    => array(),
				'missing_focus_keyword'  => array(),
				'error'                  => __( 'An error occurred during scan. Please try again.', 'meowseo' ),
			);
		}
	}
}
