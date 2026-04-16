<?php
/**
 * Redirects Admin Interface
 *
 * Provides admin UI for managing redirect rules.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Redirects;

use MeowSEO\Options;
use MeowSEO\Helpers\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirects Admin class
 *
 * Handles admin interface for redirect management.
 * Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6
 *
 * @since 1.0.0
 */
class Redirects_Admin {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Redirects per page
	 *
	 * @var int
	 */
	private const REDIRECTS_PER_PAGE = 50;

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
		add_action( 'wp_ajax_meowseo_import_redirects', array( $this, 'handle_csv_import' ) );
		add_action( 'wp_ajax_meowseo_export_redirects', array( $this, 'handle_csv_export' ) );
	}

	/**
	 * Register admin menu
	 *
	 * Adds Redirects submenu under MeowSEO menu.
	 * Requirement: 12.1
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'meowseo-settings',
			__( 'Redirects', 'meowseo' ),
			__( 'Redirects', 'meowseo' ),
			'manage_options',
			'meowseo-redirects',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render admin page
	 *
	 * Outputs the main redirects management page.
	 * Requirements: 12.1, 12.2
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Verify user has manage_options capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		// Handle form submissions
		$this->handle_form_submission();

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php $this->render_form(); ?>
			<?php $this->render_csv_section(); ?>
			<?php $this->render_table(); ?>
		</div>
		<?php
	}

	/**
	 * Handle form submission
	 *
	 * Processes create, update, and delete actions.
	 *
	 * @return void
	 */
	private function handle_form_submission(): void {
		// Check if form was submitted
		if ( ! isset( $_POST['meowseo_redirect_action'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['meowseo_redirect_nonce'] ) || ! wp_verify_nonce( $_POST['meowseo_redirect_nonce'], 'meowseo_redirect_action' ) ) {
			add_settings_error( 'meowseo_redirects', 'invalid_nonce', __( 'Security check failed.', 'meowseo' ), 'error' );
			return;
		}

		$action = sanitize_text_field( $_POST['meowseo_redirect_action'] );

		switch ( $action ) {
			case 'create':
				$this->handle_create_redirect();
				break;
			case 'delete':
				$this->handle_delete_redirect();
				break;
			case 'bulk_delete':
				$this->handle_bulk_delete();
				break;
		}
	}

	/**
	 * Handle create redirect
	 *
	 * Creates a new redirect rule from form data.
	 *
	 * @return void
	 */
	private function handle_create_redirect(): void {
		global $wpdb;

		$source_url = isset( $_POST['source_url'] ) ? sanitize_text_field( $_POST['source_url'] ) : '';
		$target_url = isset( $_POST['target_url'] ) ? esc_url_raw( $_POST['target_url'] ) : '';
		$redirect_type = isset( $_POST['redirect_type'] ) ? absint( $_POST['redirect_type'] ) : 301;
		$is_regex = isset( $_POST['is_regex'] ) ? 1 : 0;

		// Validate required fields
		if ( empty( $source_url ) || empty( $target_url ) ) {
			add_settings_error( 'meowseo_redirects', 'missing_fields', __( 'Source URL and Target URL are required.', 'meowseo' ), 'error' );
			return;
		}

		// Validate redirect type
		if ( ! in_array( $redirect_type, array( 301, 302, 307, 410, 451 ), true ) ) {
			$redirect_type = 301;
		}

		$table = $wpdb->prefix . 'meowseo_redirects';

		// Insert redirect
		$result = $wpdb->insert(
			$table,
			array(
				'source_url'    => $source_url,
				'target_url'    => $target_url,
				'redirect_type' => $redirect_type,
				'is_regex'      => $is_regex,
				'is_active'     => 1,
			),
			array( '%s', '%s', '%d', '%d', '%d' )
		);

		if ( false === $result ) {
			add_settings_error( 'meowseo_redirects', 'db_error', __( 'Failed to create redirect.', 'meowseo' ), 'error' );
			return;
		}

		// Update has_regex_rules flag if this is a regex rule
		if ( $is_regex ) {
			$this->update_regex_rules_flag();
		}

		add_settings_error( 'meowseo_redirects', 'redirect_created', __( 'Redirect created successfully.', 'meowseo' ), 'success' );
	}

	/**
	 * Handle delete redirect
	 *
	 * Deletes a single redirect rule.
	 *
	 * @return void
	 */
	private function handle_delete_redirect(): void {
		global $wpdb;

		$redirect_id = isset( $_POST['redirect_id'] ) ? absint( $_POST['redirect_id'] ) : 0;

		if ( ! $redirect_id ) {
			add_settings_error( 'meowseo_redirects', 'invalid_id', __( 'Invalid redirect ID.', 'meowseo' ), 'error' );
			return;
		}

		$table = $wpdb->prefix . 'meowseo_redirects';

		$result = $wpdb->delete(
			$table,
			array( 'id' => $redirect_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			add_settings_error( 'meowseo_redirects', 'db_error', __( 'Failed to delete redirect.', 'meowseo' ), 'error' );
			return;
		}

		// Update has_regex_rules flag
		$this->update_regex_rules_flag();

		add_settings_error( 'meowseo_redirects', 'redirect_deleted', __( 'Redirect deleted successfully.', 'meowseo' ), 'success' );
	}

	/**
	 * Handle bulk delete
	 *
	 * Deletes multiple redirect rules.
	 *
	 * @return void
	 */
	private function handle_bulk_delete(): void {
		global $wpdb;

		$redirect_ids = isset( $_POST['redirect_ids'] ) ? array_map( 'absint', (array) $_POST['redirect_ids'] ) : array();

		if ( empty( $redirect_ids ) ) {
			add_settings_error( 'meowseo_redirects', 'no_selection', __( 'No redirects selected.', 'meowseo' ), 'error' );
			return;
		}

		$table = $wpdb->prefix . 'meowseo_redirects';
		$placeholders = implode( ',', array_fill( 0, count( $redirect_ids ), '%d' ) );

		$query = $wpdb->prepare(
			"DELETE FROM {$table} WHERE id IN ({$placeholders})",
			$redirect_ids
		);

		$result = $wpdb->query( $query );

		if ( false === $result ) {
			add_settings_error( 'meowseo_redirects', 'db_error', __( 'Failed to delete redirects.', 'meowseo' ), 'error' );
			return;
		}

		// Update has_regex_rules flag
		$this->update_regex_rules_flag();

		add_settings_error( 'meowseo_redirects', 'redirects_deleted', sprintf( __( '%d redirects deleted successfully.', 'meowseo' ), $result ), 'success' );
	}

	/**
	 * Render form for creating new redirects
	 *
	 * Requirements: 12.2, 12.3
	 *
	 * @return void
	 */
	private function render_form(): void {
		?>
		<div class="card">
			<h2><?php esc_html_e( 'Add New Redirect', 'meowseo' ); ?></h2>
			<form method="post" action="">
				<?php wp_nonce_field( 'meowseo_redirect_action', 'meowseo_redirect_nonce' ); ?>
				<input type="hidden" name="meowseo_redirect_action" value="create">

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="source_url"><?php esc_html_e( 'Source URL', 'meowseo' ); ?></label>
						</th>
						<td>
							<input type="text" id="source_url" name="source_url" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'The URL to redirect from (e.g., /old-page/)', 'meowseo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="target_url"><?php esc_html_e( 'Target URL', 'meowseo' ); ?></label>
						</th>
						<td>
							<input type="text" id="target_url" name="target_url" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'The URL to redirect to (e.g., /new-page/)', 'meowseo' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="redirect_type"><?php esc_html_e( 'Redirect Type', 'meowseo' ); ?></label>
						</th>
						<td>
							<select id="redirect_type" name="redirect_type">
								<option value="301"><?php esc_html_e( '301 - Permanent', 'meowseo' ); ?></option>
								<option value="302"><?php esc_html_e( '302 - Temporary', 'meowseo' ); ?></option>
								<option value="307"><?php esc_html_e( '307 - Temporary (Preserve Method)', 'meowseo' ); ?></option>
								<option value="410"><?php esc_html_e( '410 - Gone', 'meowseo' ); ?></option>
								<option value="451"><?php esc_html_e( '451 - Unavailable For Legal Reasons', 'meowseo' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Regex Mode', 'meowseo' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" id="is_regex" name="is_regex" value="1">
								<?php esc_html_e( 'Use regular expression pattern matching', 'meowseo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enable this to use regex patterns in the source URL (e.g., ^/blog/.*)', 'meowseo' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Create Redirect', 'meowseo' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render CSV import/export section
	 *
	 * Requirements: 12.1, 12.2
	 *
	 * @return void
	 */
	private function render_csv_section(): void {
		?>
		<div class="card">
			<h2><?php esc_html_e( 'Import / Export', 'meowseo' ); ?></h2>
			<div style="display: flex; gap: 20px;">
				<?php $this->render_csv_import(); ?>
				<?php $this->render_csv_export(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render CSV import form
	 *
	 * Requirements: 12.3, 12.4, 12.5, 12.6
	 *
	 * @return void
	 */
	private function render_csv_import(): void {
		?>
		<div style="flex: 1;">
			<h3><?php esc_html_e( 'Import from CSV', 'meowseo' ); ?></h3>
			<p><?php esc_html_e( 'Upload a CSV file with columns: source_url, target_url, redirect_type, is_regex', 'meowseo' ); ?></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
				<?php wp_nonce_field( 'meowseo_csv_import', 'meowseo_csv_nonce' ); ?>
				<input type="hidden" name="action" value="meowseo_import_redirects">
				<input type="file" name="csv_file" accept=".csv" required>
				<?php submit_button( __( 'Import CSV', 'meowseo' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render CSV export button
	 *
	 * Requirements: 12.5
	 *
	 * @return void
	 */
	private function render_csv_export(): void {
		?>
		<div style="flex: 1;">
			<h3><?php esc_html_e( 'Export to CSV', 'meowseo' ); ?></h3>
			<p><?php esc_html_e( 'Download all redirect rules as a CSV file.', 'meowseo' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
				<?php wp_nonce_field( 'meowseo_csv_export', 'meowseo_csv_nonce' ); ?>
				<input type="hidden" name="action" value="meowseo_export_redirects">
				<?php submit_button( __( 'Export CSV', 'meowseo' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render redirects table
	 *
	 * Requirements: 12.4, 12.5, 12.6
	 *
	 * @return void
	 */
	private function render_table(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		// Get current page
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset = ( $paged - 1 ) * self::REDIRECTS_PER_PAGE;

		// Get search query
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		// Build query
		$where = '';
		$query_args = array();

		if ( ! empty( $search ) ) {
			$where = $wpdb->prepare( ' WHERE source_url LIKE %s OR target_url LIKE %s', '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
		}

		// Get total count
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}{$where}" );

		// Get redirects
		$redirects = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}{$where} ORDER BY id DESC LIMIT %d OFFSET %d",
				self::REDIRECTS_PER_PAGE,
				$offset
			),
			ARRAY_A
		);

		// Calculate total pages
		$total_pages = ceil( $total / self::REDIRECTS_PER_PAGE );

		?>
		<div class="card">
			<h2><?php esc_html_e( 'Redirects', 'meowseo' ); ?></h2>

			<!-- Search form -->
			<form method="get" action="">
				<input type="hidden" name="page" value="meowseo-redirects">
				<p class="search-box">
					<label class="screen-reader-text" for="redirect-search-input"><?php esc_html_e( 'Search Redirects:', 'meowseo' ); ?></label>
					<input type="search" id="redirect-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<?php submit_button( __( 'Search Redirects', 'meowseo' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
				</p>
			</form>

			<!-- Bulk actions form -->
			<form method="post" action="">
				<?php wp_nonce_field( 'meowseo_redirect_action', 'meowseo_redirect_nonce' ); ?>
				<input type="hidden" name="meowseo_redirect_action" value="bulk_delete">

				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<select name="bulk_action">
							<option value=""><?php esc_html_e( 'Bulk Actions', 'meowseo' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'meowseo' ); ?></option>
						</select>
						<?php submit_button( __( 'Apply', 'meowseo' ), 'action', 'submit', false ); ?>
					</div>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column">
								<input type="checkbox" id="cb-select-all">
							</td>
							<th><?php esc_html_e( 'Source URL', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Target URL', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Type', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Regex', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Hits', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Last Hit', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'meowseo' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $redirects ) ) : ?>
							<tr>
								<td colspan="8"><?php esc_html_e( 'No redirects found.', 'meowseo' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $redirects as $redirect ) : ?>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="redirect_ids[]" value="<?php echo esc_attr( $redirect['id'] ); ?>">
									</th>
									<td><?php echo esc_html( $redirect['source_url'] ); ?></td>
									<td><?php echo esc_html( $redirect['target_url'] ); ?></td>
									<td><?php echo esc_html( $redirect['redirect_type'] ); ?></td>
									<td><?php echo $redirect['is_regex'] ? '✓' : '—'; ?></td>
									<td><?php echo esc_html( $redirect['hit_count'] ?? 0 ); ?></td>
									<td><?php echo $redirect['last_hit'] ? esc_html( $redirect['last_hit'] ) : '—'; ?></td>
									<td>
										<form method="post" action="" style="display: inline;">
											<?php wp_nonce_field( 'meowseo_redirect_action', 'meowseo_redirect_nonce' ); ?>
											<input type="hidden" name="meowseo_redirect_action" value="delete">
											<input type="hidden" name="redirect_id" value="<?php echo esc_attr( $redirect['id'] ); ?>">
											<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this redirect?', 'meowseo' ); ?>')">
												<?php esc_html_e( 'Delete', 'meowseo' ); ?>
											</button>
										</form>
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
									'base'      => add_query_arg( 'paged', '%#%' ),
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
			</form>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Select all checkboxes
			$('#cb-select-all').on('click', function() {
				$('input[name="redirect_ids[]"]').prop('checked', this.checked);
			});
		});
		</script>
		<?php
	}

	/**
	 * Update has_regex_rules option flag
	 *
	 * Checks if any active regex rules exist and updates the option flag.
	 *
	 * @return void
	 */
	private function update_regex_rules_flag(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		// Check if any active regex rules exist
		$has_regex = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE is_regex = 1 AND is_active = 1"
		);

		// Update option flag
		$this->options->set( 'has_regex_rules', $has_regex > 0 );
		$this->options->save();
	}

	/**
	 * Handle CSV import
	 *
	 * AJAX handler for importing redirects from CSV file.
	 * Requirements: 12.3, 12.4, 12.5, 12.6
	 *
	 * @return void
	 */
	public function handle_csv_import(): void {
		// Verify nonce
		if ( ! isset( $_POST['meowseo_csv_nonce'] ) || ! wp_verify_nonce( $_POST['meowseo_csv_nonce'], 'meowseo_csv_import' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'meowseo' ) ) );
		}

		// Check capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to import redirects.', 'meowseo' ) ) );
		}

		// Check if file was uploaded
		if ( empty( $_FILES['csv_file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'meowseo' ) ) );
		}

		$file = $_FILES['csv_file'];

		// Validate file type
		$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $file_ext ) {
			wp_send_json_error( array( 'message' => __( 'File must be in CSV format.', 'meowseo' ) ) );
		}

		// Read CSV file
		$handle = fopen( $file['tmp_name'], 'r' );

		if ( false === $handle ) {
			wp_send_json_error( array( 'message' => __( 'Could not read the uploaded file.', 'meowseo' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_redirects';

		$imported_count = 0;
		$skipped_count = 0;
		$row_number = 0;
		$errors = array();

		// Skip header row if present
		$first_row = fgetcsv( $handle );
		if ( $first_row && ( 'source_url' === strtolower( $first_row[0] ) || 'source' === strtolower( $first_row[0] ) ) ) {
			// Header row detected, continue to next row
			$row_number++;
		} else {
			// No header, rewind to start
			rewind( $handle );
		}

		// Process each row
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_number++;

			// Skip empty rows (Requirement 12.4)
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			// Validate row has at least 2 columns (source and target) (Requirement 12.4)
			if ( count( $row ) < 2 ) {
				$skipped_count++;
				$errors[] = sprintf( 'Row %d: Missing required columns', $row_number );
				continue;
			}

			$source_url = trim( $row[0] );
			$target_url = trim( $row[1] );
			$redirect_type = isset( $row[2] ) ? absint( $row[2] ) : 301;
			$is_regex = isset( $row[3] ) ? (bool) $row[3] : false;

			// Skip rows with missing required fields (Requirement 12.4)
			if ( empty( $source_url ) || empty( $target_url ) ) {
				$skipped_count++;
				$errors[] = sprintf( 'Row %d: Empty source or target URL', $row_number );
				continue;
			}

			// Default redirect_type to 301 if not provided or invalid (Requirement 12.5)
			if ( ! in_array( $redirect_type, array( 301, 302, 307, 410, 451 ), true ) ) {
				$redirect_type = 301;
			}

			// Insert redirect
			$result = $wpdb->insert(
				$table,
				array(
					'source_url'    => $source_url,
					'target_url'    => $target_url,
					'redirect_type' => $redirect_type,
					'is_regex'      => $is_regex ? 1 : 0,
					'is_active'     => 1,
				),
				array( '%s', '%s', '%d', '%d', '%d' )
			);

			if ( false === $result ) {
				$skipped_count++;
				$errors[] = sprintf( 'Row %d: Database insert failed', $row_number );
			} else {
				$imported_count++;
			}
		}

		fclose( $handle );

		// Update regex rules flag
		$this->update_regex_rules_flag();

		// Log import results (Requirement 12.6)
		Logger::info(
			'CSV import completed',
			array(
				'file_name'      => $file['name'],
				'imported_count' => $imported_count,
				'skipped_count'  => $skipped_count,
				'errors'         => $errors,
			)
		);

		// Send response
		if ( $imported_count > 0 ) {
			wp_send_json_success(
				array(
					'message'        => sprintf( __( 'Successfully imported %d redirects. Skipped %d rows.', 'meowseo' ), $imported_count, $skipped_count ),
					'imported_count' => $imported_count,
					'skipped_count'  => $skipped_count,
					'errors'         => $errors,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'No redirects were imported. Please check the CSV format.', 'meowseo' ),
					'errors'  => $errors,
				)
			);
		}
	}

	/**
	 * Handle CSV export
	 *
	 * AJAX handler for exporting redirects to CSV file.
	 * Requirement: 12.5
	 *
	 * @return void
	 */
	public function handle_csv_export(): void {
		// Verify nonce
		if ( ! isset( $_POST['meowseo_csv_nonce'] ) || ! wp_verify_nonce( $_POST['meowseo_csv_nonce'], 'meowseo_csv_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'meowseo' ) );
		}

		// Check capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export redirects.', 'meowseo' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_redirects';

		// Get all redirects
		$redirects = $wpdb->get_results(
			"SELECT source_url, target_url, redirect_type, is_regex FROM {$table} ORDER BY id ASC",
			ARRAY_A
		);

		// Set headers for CSV download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=meowseo-redirects-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Open output stream
		$output = fopen( 'php://output', 'w' );

		// Write header row
		fputcsv( $output, array( 'source_url', 'target_url', 'redirect_type', 'is_regex' ) );

		// Write data rows
		foreach ( $redirects as $redirect ) {
			fputcsv( $output, $redirect );
		}

		fclose( $output );

		// Log export
		Logger::info(
			'CSV export completed',
			array(
				'redirect_count' => count( $redirects ),
			)
		);

		exit;
	}
}
