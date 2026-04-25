<?php
/**
 * Import Admin class for rendering import UI and handling actions.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Import;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-import-posts-table.php';
require_once __DIR__ . '/class-import-terms-table.php';

class Import_Admin {

	private Import_Manager $import_manager;

	public function __construct( Import_Manager $import_manager ) {
		$this->import_manager = $import_manager;
	}

	public function boot(): void {
		// Admin menus are registered in the main Admin class.
	}

	public function render_import_page(): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? \sanitize_text_field( $_GET['tab'] ) : 'posts';

		// Handle bulk actions
		$this->handle_bulk_actions( $active_tab );

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Import SEO Data', 'meowseo' ); ?></h1>
			<p><?php echo esc_html__( 'Manually import your SEO data from other plugins. Select items and use the Bulk Actions menu.', 'meowseo' ); ?></p>
			
			<h2 class="nav-tab-wrapper">
				<a href="?page=meowseo-import&tab=posts" class="nav-tab <?php echo 'posts' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Posts & Pages', 'meowseo' ); ?></a>
				<a href="?page=meowseo-import&tab=terms" class="nav-tab <?php echo 'terms' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Categories & Tags', 'meowseo' ); ?></a>
				<a href="?page=meowseo-import&tab=settings" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Settings & Redirects', 'meowseo' ); ?></a>
			</h2>
			
			<div class="meowseo-import-content" style="margin-top: 20px;">
				<?php
				if ( 'posts' === $active_tab ) {
					$table = new Import_Posts_List_Table();
					$table->prepare_items();
					?>
					<form method="post">
						<?php wp_nonce_field( 'meowseo_bulk_import_posts' ); ?>
						<?php $table->display(); ?>
					</form>
					<?php
				} elseif ( 'terms' === $active_tab ) {
					$table = new Import_Terms_List_Table();
					$table->prepare_items();
					?>
					<form method="post">
						<?php wp_nonce_field( 'meowseo_bulk_import_terms' ); ?>
						<?php $table->display(); ?>
					</form>
					<?php
				} elseif ( 'settings' === $active_tab ) {
					$this->render_settings_tab();
				}
				?>
			</div>
		</div>
		<?php
	}

	private function handle_bulk_actions( string $tab ): void {
		if ( 'posts' === $tab && isset( $_POST['action'] ) && isset( $_POST['post'] ) && is_array( $_POST['post'] ) ) {
			check_admin_referer( 'meowseo_bulk_import_posts' );
			$action = $_POST['action'] === '-1' ? $_POST['action2'] : $_POST['action'];
			
			if ( 'import_rankmath' === $action || 'import_yoast' === $action ) {
				$plugin = str_replace( 'import_', '', $action );
				$importer = $this->import_manager->get_importer( $plugin );
				
				if ( $importer ) {
					$post_ids = array_map( 'intval', $_POST['post'] );
					$result = $importer->import_postmeta( $post_ids );
					
					echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Successfully imported SEO data for %d posts from %s. Errors: %d.', 'meowseo' ), $result['imported'], $importer->get_plugin_name(), $result['errors'] ) . '</p></div>';
				}
			}
		} elseif ( 'terms' === $tab && isset( $_POST['action'] ) && isset( $_POST['term'] ) && is_array( $_POST['term'] ) ) {
			check_admin_referer( 'meowseo_bulk_import_terms' );
			$action = $_POST['action'] === '-1' ? $_POST['action2'] : $_POST['action'];
			
			if ( 'import_rankmath' === $action || 'import_yoast' === $action ) {
				$plugin = str_replace( 'import_', '', $action );
				$importer = $this->import_manager->get_importer( $plugin );
				
				if ( $importer ) {
					$term_ids = array_map( 'intval', $_POST['term'] );
					$result = $importer->import_termmeta( $term_ids );
					
					echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Successfully imported SEO data for %d terms from %s. Errors: %d.', 'meowseo' ), $result['imported'], $importer->get_plugin_name(), $result['errors'] ) . '</p></div>';
				}
			}
		} elseif ( 'settings' === $tab && isset( $_POST['import_settings_action'] ) ) {
			check_admin_referer( 'meowseo_import_settings' );
			$plugin = sanitize_text_field( $_POST['plugin'] );
			$action = sanitize_text_field( $_POST['import_settings_action'] );
			$importer = $this->import_manager->get_importer( $plugin );

			if ( $importer ) {
				if ( 'import_options' === $action ) {
					$result = $importer->import_options();
					echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Successfully imported settings from %s.', 'meowseo' ), $importer->get_plugin_name() ) . '</p></div>';
				} elseif ( 'import_redirects' === $action ) {
					$result = $importer->import_redirects();
					echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Successfully imported %d redirects from %s.', 'meowseo' ), $result['imported'], $importer->get_plugin_name() ) . '</p></div>';
				}
			}
		}
	}

	private function render_settings_tab(): void {
		$detected = $this->import_manager->detect_installed_plugins();
		if ( empty( $detected ) ) {
			echo '<p>' . esc_html__( 'No compatible SEO plugins detected for import.', 'meowseo' ) . '</p>';
			return;
		}
		
		?>
		<div class="meowseo-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;max-width:600px;">
			<h2><?php esc_html_e( 'Global Settings & Redirects', 'meowseo' ); ?></h2>
			<p><?php esc_html_e( 'These items are imported instantly as they do not require batch processing.', 'meowseo' ); ?></p>
			
			<table class="form-table">
				<tbody>
					<?php foreach ( $detected as $plugin ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $plugin['name'] ); ?></th>
							<td>
								<form method="post" style="display:inline-block; margin-right: 10px;">
									<?php wp_nonce_field( 'meowseo_import_settings' ); ?>
									<input type="hidden" name="plugin" value="<?php echo esc_attr( $plugin['slug'] ); ?>">
									<input type="hidden" name="import_settings_action" value="import_options">
									<button type="submit" class="button button-primary"><?php esc_html_e( 'Import Global Settings', 'meowseo' ); ?></button>
								</form>
								<form method="post" style="display:inline-block;">
									<?php wp_nonce_field( 'meowseo_import_settings' ); ?>
									<input type="hidden" name="plugin" value="<?php echo esc_attr( $plugin['slug'] ); ?>">
									<input type="hidden" name="import_settings_action" value="import_redirects">
									<button type="submit" class="button"><?php esc_html_e( 'Import Redirects', 'meowseo' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
