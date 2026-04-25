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
		// Handle bulk actions early (before output), then redirect.
		add_action( 'admin_init', array( $this, 'process_bulk_actions' ) );
	}

	/**
	 * Process bulk actions on admin_init (before any output).
	 * Redirects back to the page with a success/error notice in the URL.
	 */
	public function process_bulk_actions(): void {
		// Only run on our import page.
		if ( ! isset( $_GET['page'] ) || 'meowseo-import' !== $_GET['page'] ) {
			return;
		}
		if ( ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab    = isset( $_POST['tab'] ) ? \sanitize_text_field( $_POST['tab'] ) : 'posts';
		$action = '';

		if ( isset( $_POST['action'] ) && '-1' !== $_POST['action'] ) {
			$action = \sanitize_text_field( $_POST['action'] );
		} elseif ( isset( $_POST['action2'] ) && '-1' !== $_POST['action2'] ) {
			$action = \sanitize_text_field( $_POST['action2'] );
		}

		// Handle Posts & Media import.
		if ( in_array( $tab, array( 'posts', 'media' ), true )
			&& in_array( $action, array( 'import_rankmath', 'import_yoast' ), true )
			&& isset( $_POST['post'] ) && is_array( $_POST['post'] )
		) {
			\check_admin_referer( 'meowseo_bulk_import_posts' );

			$plugin   = str_replace( 'import_', '', $action );
			$importer = $this->import_manager->get_importer( $plugin );

			if ( $importer ) {
				$post_ids = array_map( 'intval', $_POST['post'] );
				$result   = $importer->import_postmeta( $post_ids );
				$redirect = \add_query_arg( array(
					'page'        => 'meowseo-import',
					'tab'         => $tab,
					'imported'    => $result['imported'],
					'errors'      => $result['errors'],
					'plugin_name' => rawurlencode( $importer->get_plugin_name() ),
					'notice_type' => 'posts',
				), \admin_url( 'admin.php' ) );
				\wp_safe_redirect( $redirect );
				exit;
			}
		}

		// Handle Terms import.
		if ( 'terms' === $tab
			&& in_array( $action, array( 'import_rankmath', 'import_yoast' ), true )
			&& isset( $_POST['term'] ) && is_array( $_POST['term'] )
		) {
			\check_admin_referer( 'meowseo_bulk_import_terms' );

			$plugin   = str_replace( 'import_', '', $action );
			$importer = $this->import_manager->get_importer( $plugin );

			if ( $importer ) {
				$term_ids = array_map( 'intval', $_POST['term'] );
				$result   = $importer->import_termmeta( $term_ids );
				$redirect = \add_query_arg( array(
					'page'        => 'meowseo-import',
					'tab'         => 'terms',
					'imported'    => $result['imported'],
					'errors'      => $result['errors'],
					'plugin_name' => rawurlencode( $importer->get_plugin_name() ),
					'notice_type' => 'terms',
				), \admin_url( 'admin.php' ) );
				\wp_safe_redirect( $redirect );
				exit;
			}
		}

		// Handle Settings & Redirects import.
		if ( 'settings' === $tab && isset( $_POST['import_settings_action'] ) ) {
			\check_admin_referer( 'meowseo_import_settings' );

			$plugin   = \sanitize_text_field( $_POST['plugin'] );
			$action   = \sanitize_text_field( $_POST['import_settings_action'] );
			$importer = $this->import_manager->get_importer( $plugin );

			if ( $importer ) {
				$redirect_args = array(
					'page'        => 'meowseo-import',
					'tab'         => 'settings',
					'plugin_name' => rawurlencode( $importer->get_plugin_name() ),
				);

				if ( 'import_options' === $action ) {
					$importer->import_options();
					$redirect_args['notice_type'] = 'options';
				} elseif ( 'import_redirects' === $action ) {
					$result = $importer->import_redirects();
					$redirect_args['notice_type'] = 'redirects';
					$redirect_args['imported']    = $result['imported'];
				}

				\wp_safe_redirect( \add_query_arg( $redirect_args, \admin_url( 'admin.php' ) ) );
				exit;
			}
		}
	}

	public function render_import_page(): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? \sanitize_text_field( $_GET['tab'] ) : 'posts';

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Import SEO Data', 'meowseo' ); ?></h1>
			<p><?php echo esc_html__( 'Manually import your SEO data from other plugins. Select items and use the Bulk Actions menu.', 'meowseo' ); ?></p>

			<?php $this->render_notices(); ?>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( \admin_url( 'admin.php?page=meowseo-import&tab=posts' ) ); ?>" class="nav-tab <?php echo 'posts' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Posts & Pages', 'meowseo' ); ?></a>
				<a href="<?php echo esc_url( \admin_url( 'admin.php?page=meowseo-import&tab=media' ) ); ?>" class="nav-tab <?php echo 'media' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Media', 'meowseo' ); ?></a>
				<a href="<?php echo esc_url( \admin_url( 'admin.php?page=meowseo-import&tab=terms' ) ); ?>" class="nav-tab <?php echo 'terms' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Categories & Tags', 'meowseo' ); ?></a>
				<a href="<?php echo esc_url( \admin_url( 'admin.php?page=meowseo-import&tab=settings' ) ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Settings & Redirects', 'meowseo' ); ?></a>
			</h2>

			<div class="meowseo-import-content" style="margin-top: 20px;">
				<?php
				if ( 'posts' === $active_tab ) {
					$table = new Import_Posts_List_Table();
					$table->prepare_items();
					?>
					<form method="post" action="<?php echo esc_url( \admin_url( 'admin.php?page=meowseo-import&tab=posts' ) ); ?>">
						<input type="hidden" name="tab" value="posts" />
						<?php \wp_nonce_field( 'meowseo_bulk_import_posts' ); ?>
						<?php $table->display(); ?>
					</form>
					<?php
				} elseif ( 'media' === $active_tab ) {
					$table = new Import_Posts_List_Table( array( 'attachment' ) );
					$table->prepare_items();
					?>
					<form method="post" action="<?php echo esc_url( \admin_url( 'admin.php?page=meowseo-import&tab=media' ) ); ?>">
						<input type="hidden" name="tab" value="media" />
						<?php \wp_nonce_field( 'meowseo_bulk_import_posts' ); ?>
						<?php $table->display(); ?>
					</form>
					<?php
				} elseif ( 'terms' === $active_tab ) {
					$table = new Import_Terms_List_Table();
					$table->prepare_items();
					?>
					<form method="post" action="<?php echo esc_url( \admin_url( 'admin.php?page=meowseo-import&tab=terms' ) ); ?>">
						<input type="hidden" name="tab" value="terms" />
						<?php \wp_nonce_field( 'meowseo_bulk_import_terms' ); ?>
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

	/**
	 * Render admin notices from redirect URL params.
	 */
	private function render_notices(): void {
		if ( ! isset( $_GET['notice_type'] ) ) {
			return;
		}

		$notice_type = \sanitize_text_field( $_GET['notice_type'] );
		$imported    = isset( $_GET['imported'] ) ? (int) $_GET['imported'] : 0;
		$errors      = isset( $_GET['errors'] ) ? (int) $_GET['errors'] : 0;
		$plugin_name = isset( $_GET['plugin_name'] ) ? rawurldecode( \sanitize_text_field( $_GET['plugin_name'] ) ) : '';

		switch ( $notice_type ) {
			case 'posts':
				$message = sprintf(
					/* translators: 1: imported count, 2: plugin name, 3: error count */
					\__( 'Successfully imported SEO data for <strong>%1$d posts</strong> from %2$s. Errors: %3$d.', 'meowseo' ),
					$imported, esc_html( $plugin_name ), $errors
				);
				break;
			case 'terms':
				$message = sprintf(
					/* translators: 1: imported count, 2: plugin name, 3: error count */
					\__( 'Successfully imported SEO data for <strong>%1$d terms</strong> from %2$s. Errors: %3$d.', 'meowseo' ),
					$imported, esc_html( $plugin_name ), $errors
				);
				break;
			case 'options':
				$message = sprintf(
					/* translators: %s: plugin name */
					\__( 'Successfully imported global settings from %s.', 'meowseo' ),
					esc_html( $plugin_name )
				);
				break;
			case 'redirects':
				$message = sprintf(
					/* translators: 1: imported count, 2: plugin name */
					\__( 'Successfully imported <strong>%1$d redirects</strong> from %2$s.', 'meowseo' ),
					$imported, esc_html( $plugin_name )
				);
				break;
			default:
				return;
		}

		$class = $errors > 0 ? 'notice-warning' : 'notice-success';
		printf( '<div class="notice %s is-dismissible"><p>%s</p></div>', esc_attr( $class ), $message );
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
								<form method="post" action="<?php echo esc_url( \admin_url( 'admin.php?page=meowseo-import&tab=settings' ) ); ?>" style="display:inline-block; margin-right: 10px;">
									<?php \wp_nonce_field( 'meowseo_import_settings' ); ?>
									<input type="hidden" name="tab" value="settings">
									<input type="hidden" name="plugin" value="<?php echo esc_attr( $plugin['slug'] ); ?>">
									<input type="hidden" name="import_settings_action" value="import_options">
									<button type="submit" class="button button-primary"><?php esc_html_e( 'Import Global Settings', 'meowseo' ); ?></button>
								</form>
								<form method="post" action="<?php echo esc_url( \admin_url( 'admin.php?page=meowseo-import&tab=settings' ) ); ?>" style="display:inline-block;">
									<?php \wp_nonce_field( 'meowseo_import_settings' ); ?>
									<input type="hidden" name="tab" value="settings">
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
