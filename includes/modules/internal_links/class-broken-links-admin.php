<?php
/**
 * Broken Links Admin Interface
 *
 * Provides admin UI for managing broken links.
 *
 * @package MeowSEO
 * @subpackage Modules\Internal_Links
 */

namespace MeowSEO\Modules\Internal_Links;

use MeowSEO\Options;
use MeowSEO\Helpers\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Broken_Links_Admin class.
 */
class Broken_Links_Admin {

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
	public function __construct( $options ) {
		$this->options = $options;
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
	}

	/**
	 * Add Dashboard Widget.
	 */
	public function add_dashboard_widget(): void {
		$role_required = $this->options->get( 'broken_links_widget_role', 'editor' );
		if ( 'nobody' === $role_required ) {
			return;
		}

		$cap = ( 'administrator' === $role_required ) ? 'manage_options' : 'edit_posts';
		if ( ! current_user_can( $cap ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'meowseo_broken_links_widget',
			__( 'MeowSEO: Broken Links', 'meowseo' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render Dashboard Widget.
	 */
	public function render_dashboard_widget(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_link_checks';
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_broken = 1" );
		
		echo '<p>' . sprintf( _n( 'Found %s broken link.', 'Found %s broken links.', $count, 'meowseo' ), '<strong>' . number_format_i18n( $count ) . '</strong>' ) . '</p>';
		echo '<p><a href="' . admin_url( 'admin.php?page=meowseo-broken-links' ) . '" class="button">' . __( 'View all broken links', 'meowseo' ) . '</a></p>';
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_link_checks';

		// Handle actions.
		$this->handle_actions();

		// Get current tab.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'broken';

		// Get search query.
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		// Build WHERE clause based on tab and search.
		$where = " WHERE 1=1";
		if ( 'broken' === $current_tab ) {
			$where .= " AND is_broken = 1";
		} elseif ( 'redirects' === $current_tab ) {
			$where .= " AND http_status IN (301, 302, 307, 308)";
		} elseif ( 'all' === $current_tab ) {
			// No extra filter.
		}

		if ( ! empty( $search ) ) {
			$where .= $wpdb->prepare( " AND (target_url LIKE %s OR anchor_text LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
		}

		// Pagination.
		$per_page = 20;
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset = ( $paged - 1 ) * $per_page;

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}{$where}" );
		$links = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table}{$where} ORDER BY last_checked DESC LIMIT %d OFFSET %d", $per_page, $offset ), ARRAY_A );

		$total_pages = ceil( $total / $per_page );

		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'broken' ) ); ?>" class="nav-tab <?php echo 'broken' === $current_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Broken Links', 'meowseo' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'redirects' ) ); ?>" class="nav-tab <?php echo 'redirects' === $current_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Redirects', 'meowseo' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'all' ) ); ?>" class="nav-tab <?php echo 'all' === $current_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'All Links', 'meowseo' ); ?></a>
			</h2>

			<div class="meowseo-broken-links-actions" style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
				<form method="post" action="">
					<?php wp_nonce_field( 'meowseo_broken_links_action', 'meowseo_nonce' ); ?>
					<button type="submit" name="meowseo_action" value="scan_all" class="button button-primary"><?php esc_html_e( 'Scan All Posts', 'meowseo' ); ?></button>
					<span class="description" style="margin-left: 10px;"><?php esc_html_e( 'Scans all published posts and pages for links in the background.', 'meowseo' ); ?></span>
				</form>

				<form method="get" action="">
					<input type="hidden" name="page" value="meowseo-broken-links">
					<input type="hidden" name="tab" value="<?php echo esc_attr( $current_tab ); ?>">
					<p class="search-box" style="position: static; float: none; margin: 0;">
						<label class="screen-reader-text" for="link-search-input"><?php esc_html_e( 'Search Links:', 'meowseo' ); ?></label>
						<input type="search" id="link-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
						<?php submit_button( __( 'Search Links', 'meowseo' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
					</p>
				</form>
			</div>

			<form method="post" action="">
				<?php wp_nonce_field( 'meowseo_broken_links_action', 'meowseo_nonce' ); ?>
				
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'URL', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Status', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Anchor Text', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Source Post', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Last Checked', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'meowseo' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $links ) ) : ?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'No links found.', 'meowseo' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $links as $link ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( $link['target_url'] ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( $link['target_url'] ); ?>
										</a>
										<?php if ( ! empty( $link['redirect_url'] ) ) : ?>
											<div class="description" style="color: #666;">
												↳ <?php echo esc_html( $link['redirect_url'] ); ?>
											</div>
										<?php endif; ?>
									</td>
									<td>
										<?php $this->render_status_badge( $link ); ?>
									</td>
									<td><?php echo esc_html( $link['anchor_text'] ); ?></td>
									<td>
										<?php 
										$post = get_post( $link['source_post_id'] );
										if ( $post ) {
											echo sprintf(
												'<a href="%s">%s</a>',
												get_edit_post_link( $post->ID ),
												esc_html( $post->post_title )
											);
										} else {
											echo '<em>' . esc_html__( 'Post not found', 'meowseo' ) . '</em>';
										}
										?>
									</td>
									<td>
										<?php 
										echo $link['last_checked'] ? human_time_diff( strtotime( $link['last_checked'] ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'meowseo' ) : '—';
										?>
									</td>
									<td>
										<div class="row-actions" style="visibility: visible; position: static;">
											<?php 
											$enabled_actions = $this->options->get( 'broken_links_enabled_actions', array( 'edit', 'unlink', 'dismiss', 'recheck' ) );
											
											if ( in_array( 'recheck', $enabled_actions, true ) ) : ?>
												<button type="submit" name="meowseo_action" value="recheck:<?php echo esc_attr( $link['id'] ); ?>" class="button button-small" title="<?php esc_attr_e( 'Recheck this link', 'meowseo' ); ?>"><?php esc_html_e( 'Recheck', 'meowseo' ); ?></button>
											<?php endif; ?>
											
											<?php if ( ! empty( $link['source_post_id'] ) ) : ?>
												<?php if ( in_array( 'edit', $enabled_actions, true ) ) : ?>
													<button type="button" class="button button-small meowseo-edit-link" data-id="<?php echo esc_attr( $link['id'] ); ?>" data-url="<?php echo esc_url( $link['target_url'] ); ?>" title="<?php esc_attr_e( 'Change this URL', 'meowseo' ); ?>"><?php esc_html_e( 'Edit URL', 'meowseo' ); ?></button>
												<?php endif; ?>
												
												<?php if ( in_array( 'unlink', $enabled_actions, true ) ) : ?>
													<button type="submit" name="meowseo_action" value="unlink:<?php echo esc_attr( $link['id'] ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to remove this link tag?', 'meowseo' ); ?>');" title="<?php esc_attr_e( 'Remove link but keep text', 'meowseo' ); ?>"><?php esc_html_e( 'Unlink', 'meowseo' ); ?></button>
												<?php endif; ?>
											<?php endif; ?>

											<?php if ( in_array( 'dismiss', $enabled_actions, true ) ) : ?>
												<button type="submit" name="meowseo_action" value="dismiss:<?php echo esc_attr( $link['id'] ); ?>" class="button button-small" title="<?php esc_attr_e( 'Hide this issue', 'meowseo' ); ?>"><?php esc_html_e( 'Dismiss', 'meowseo' ); ?></button>
											<?php endif; ?>

											<?php if ( in_array( 'not_broken', $enabled_actions, true ) ) : ?>
												<button type="submit" name="meowseo_action" value="dismiss:<?php echo esc_attr( $link['id'] ); ?>" class="button button-small" title="<?php esc_attr_e( 'Mark as fixed', 'meowseo' ); ?>"><?php esc_html_e( 'Not broken', 'meowseo' ); ?></button>
											<?php endif; ?>
										</div>
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

		<!-- Edit URL Modal -->
		<div id="meowseo-edit-link-modal" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
			<div style="background: #fff; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 400px; border-radius: 4px;">
				<h3><?php esc_html_e( 'Edit Link URL', 'meowseo' ); ?></h3>
				<form method="post" action="">
					<?php wp_nonce_field( 'meowseo_broken_links_action', 'meowseo_nonce' ); ?>
					<input type="hidden" name="meowseo_action" value="edit_url">
					<input type="hidden" name="link_id" id="meowseo-edit-link-id">
					
					<p>
						<label for="meowseo-new-url"><strong><?php esc_html_e( 'New URL:', 'meowseo' ); ?></strong></label><br>
						<input type="url" name="new_url" id="meowseo-new-url" class="large-text" required>
					</p>
					
					<div style="margin-top: 20px; text-align: right;">
						<button type="button" class="button" onclick="document.getElementById('meowseo-edit-link-modal').style.display='none'"><?php esc_html_e( 'Cancel', 'meowseo' ); ?></button>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Update URL', 'meowseo' ); ?></button>
					</div>
				</form>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var editButtons = document.querySelectorAll('.meowseo-edit-link');
			var modal = document.getElementById('meowseo-edit-link-modal');
			var idInput = document.getElementById('meowseo-edit-link-id');
			var urlInput = document.getElementById('meowseo-new-url');

			editButtons.forEach(function(btn) {
				btn.addEventListener('click', function() {
					idInput.value = this.getAttribute('data-id');
					urlInput.value = this.getAttribute('data-url');
					modal.style.display = 'block';
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render status badge for a link.
	 *
	 * @param array $link Link data.
	 * @return void
	 */
	private function render_status_badge( array $link ): void {
		$status = $link['http_status'];
		$color = '#46b450'; // Green.
		$label = $status ?: __( 'Unknown', 'meowseo' );

		if ( $link['is_broken'] ) {
			$color = '#dc3232'; // Red.
		} elseif ( in_array( $status, array( 301, 302, 307, 308 ) ) ) {
			$color = '#ffb900'; // Orange.
		}

		echo sprintf(
			'<span style="background: %s; color: #fff; padding: 2px 6px; border-radius: 3px; font-weight: 600; font-size: 11px;">%s</span>',
			$color,
			esc_html( $label )
		);

		if ( ! empty( $link['error_log'] ) ) {
			echo ' <span class="dashicons dashicons-info" title="' . esc_attr( $link['error_log'] ) . '" style="font-size: 16px; color: #999; cursor: help;"></span>';
		}
	}

	/**
	 * Handle actions (Recheck, Dismiss, etc.).
	 *
	 * @return void
	 */
	private function handle_actions(): void {
		if ( ! isset( $_POST['meowseo_action'] ) || ! isset( $_POST['meowseo_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['meowseo_nonce'], 'meowseo_broken_links_action' ) ) {
			return;
		}

		$action_raw = sanitize_text_field( $_POST['meowseo_action'] );
		$id         = 0;
		$action     = $action_raw;

		if ( strpos( $action_raw, ':' ) !== false ) {
			list( $action, $id ) = explode( ':', $action_raw );
			$id = absint( $id );
		}

		if ( 'scan_all' !== $action && ! $id ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_link_checks';

		if ( 'recheck' === $action ) {
			$link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
			if ( $link ) {
				$module = new Internal_Links( $this->options );
				$module->check_link_status( $link['source_post_id'], $link['target_url_hash'] );
				add_settings_error( 'meowseo_broken_links', 'recheck_complete', __( 'Link rechecked.', 'meowseo' ), 'success' );
			}
		} elseif ( 'dismiss' === $action ) {
			$wpdb->update( $table, array( 'is_broken' => 0 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
			add_settings_error( 'meowseo_broken_links', 'dismissed', __( 'Link dismissed.', 'meowseo' ), 'success' );
		} elseif ( 'scan_all' === $action ) {
			$this->trigger_scan_all();
			add_settings_error( 'meowseo_broken_links', 'scan_triggered', __( 'Full site scan triggered. This will run in the background.', 'meowseo' ), 'success' );
		} elseif ( 'unlink' === $action ) {
			$link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
			if ( $link ) {
				$module = new Internal_Links( $this->options );
				if ( $module->unlink_link( $link['source_post_id'], $link['target_url'] ) ) {
					add_settings_error( 'meowseo_broken_links', 'unlinked', __( 'Link removed from content.', 'meowseo' ), 'success' );
				} else {
					add_settings_error( 'meowseo_broken_links', 'unlink_failed', __( 'Could not remove link from content.', 'error' ) );
				}
			}
		} elseif ( 'edit_url' === $action ) {
			$id = absint( $_POST['link_id'] );
			$new_url = esc_url_raw( $_POST['new_url'] );
			$link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
			if ( $link && ! empty( $new_url ) ) {
				$module = new Internal_Links( $this->options );
				if ( $module->edit_link( $link['source_post_id'], $link['target_url'], $new_url ) ) {
					add_settings_error( 'meowseo_broken_links', 'edited', __( 'Link URL updated.', 'meowseo' ), 'success' );
				} else {
					add_settings_error( 'meowseo_broken_links', 'edit_failed', __( 'Could not update link URL.', 'error' ) );
				}
			}
		} elseif ( 'forced_recheck' === $action ) {
			$wpdb->query( "TRUNCATE TABLE {$table}" );
			add_settings_error( 'meowseo_broken_links', 'recheck_forced', __( 'Database cleared. A full site scan has been triggered.', 'meowseo' ), 'success' );
			$this->trigger_scan_all();
		}
		
		settings_errors( 'meowseo_broken_links' );
	}

	/**
	 * Trigger a scan of all published posts and pages.
	 *
	 * @return void
	 */
	private function trigger_scan_all(): void {
		$posts = get_posts( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		if ( empty( $posts ) ) {
			return;
		}

		$module = new Internal_Links( $this->options );
		foreach ( $posts as $post_id ) {
			$module->schedule_link_scan( $post_id, get_post( $post_id ) );
		}
	}
}
