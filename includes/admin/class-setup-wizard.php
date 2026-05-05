<?php
/**
 * Setup Wizard class.
 *
 * Handles the onboarding process for new installations.
 *
 * @package MeowSEO
 * @subpackage Admin
 */

namespace MeowSEO\Admin;

use MeowSEO\Options;
use MeowSEO\Module_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup_Wizard class.
 */
class Setup_Wizard {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Module Manager instance.
	 *
	 * @var Module_Manager
	 */
	private Module_Manager $module_manager;

	/**
	 * Wizard slug.
	 *
	 * @var string
	 */
	private string $slug = 'meowseo-wizard';

	/**
	 * Current step.
	 *
	 * @var string
	 */
	private string $step = 'welcome';

	/**
	 * Constructor.
	 *
	 * @param Options        $options        Options instance.
	 * @param Module_Manager $module_manager Module Manager instance.
	 */
	public function __construct( Options $options, Module_Manager $module_manager ) {
		$this->options        = $options;
		$this->module_manager = $module_manager;
	}

	/**
	 * Initialize the wizard.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_wizard_page' ) );
		add_action( 'admin_init', array( $this, 'handle_redirect' ) );
		
		if ( $this->is_wizard_page() ) {
			add_action( 'admin_init', array( $this, 'dispatch_wizard' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		// AJAX handlers for import.
		add_action( 'wp_ajax_meowseo_wizard_initiate_import', array( $this, 'ajax_initiate_import' ) );
		add_action( 'wp_ajax_meowseo_wizard_get_import_stats', array( $this, 'ajax_get_import_stats' ) );
		add_action( 'wp_ajax_meowseo_wizard_import_options', array( $this, 'ajax_import_options' ) );
		add_action( 'wp_ajax_meowseo_wizard_import_batch', array( $this, 'ajax_import_batch' ) );
	}

	/**
	 * Register the wizard page.
	 *
	 * @return void
	 */
	public function register_wizard_page(): void {
		add_submenu_page(
			'', // No parent menu to keep it hidden
			__( 'MeowSEO Setup Wizard', 'meowseo' ),
			__( 'Setup Wizard', 'meowseo' ),
			'manage_options',
			$this->slug,
			array( $this, 'render_wizard' )
		);
	}

	/**
	 * Handle redirect to wizard on fresh install.
	 *
	 * @return void
	 */
	public function handle_redirect(): void {
		if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// Check if wizard is pending.
		$is_pending = get_transient( 'meowseo_setup_wizard_pending' );
		if ( ! $is_pending ) {
			return;
		}

		// Only redirect once.
		delete_transient( 'meowseo_setup_wizard_pending' );

		// Redirect to wizard.
		wp_safe_redirect( admin_url( 'admin.php?page=' . $this->slug ) );
		exit;
	}

	/**
	 * Check if current page is the wizard page.
	 *
	 * @return bool
	 */
	private function is_wizard_page(): bool {
		return isset( $_GET['page'] ) && $_GET['page'] === $this->slug;
	}

	/**
	 * Enqueue wizard assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'meowseo-setup-wizard',
			\MEOWSEO_URL . 'admin/css/setup-wizard.css',
			array(),
			\MEOWSEO_VERSION
		);

		wp_enqueue_script(
			'meowseo-setup-wizard',
			\MEOWSEO_URL . 'assets/js/setup-wizard.js',
			array( 'jquery', 'wp-util' ),
			\MEOWSEO_VERSION,
			true
		);

		wp_enqueue_media();

		wp_localize_script( 'meowseo-setup-wizard', 'meowseoWizard', array(
			'nonce'   => wp_create_nonce( 'meowseo_wizard_nonce' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'i18n'    => array(
				'importing'      => __( 'Importing...', 'meowseo' ),
				'starting'       => __( 'Starting migration...', 'meowseo' ),
				'options'        => __( 'Importing options...', 'meowseo' ),
				'posts'          => __( 'Importing posts...', 'meowseo' ),
				'terms'          => __( 'Importing terms...', 'meowseo' ),
				'redirects'      => __( 'Importing redirects...', 'meowseo' ),
				'cleaning'       => __( 'Cleaning up...', 'meowseo' ),
				'complete'       => __( 'Import complete!', 'meowseo' ),
				'import_success' => __( 'Import Successful', 'meowseo' ),
				'upload_logo'    => __( 'Select Logo', 'meowseo' ),
			),
		) );
	}

	/**
	 * Dispatch wizard actions.
	 *
	 * @return void
	 */
	public function dispatch_wizard(): void {
		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : 'welcome';
		
		// Handle form submission.
		if ( isset( $_POST['meowseo_wizard_save'] ) ) {
			check_admin_referer( 'meowseo_wizard_step_' . $this->step );
			$this->save_step_data();
			
			$next_step = $this->get_next_step();
			if ( $next_step ) {
				wp_safe_redirect( admin_url( 'admin.php?page=' . $this->slug . '&step=' . $next_step ) );
				exit;
			} else {
				// Finished!
				update_option( 'meowseo_setup_complete', 1 );
				wp_safe_redirect( admin_url( 'admin.php?page=meowseo' ) );
				exit;
			}
		}
	}

	/**
	 * Save data for current step.
	 *
	 * @return void
	 */
	private function save_step_data(): void {
		switch ( $this->step ) {
			case 'welcome':
				$mode = isset( $_POST['setup_mode'] ) ? sanitize_key( $_POST['setup_mode'] ) : 'easy';
				update_option( 'meowseo_setup_mode', $mode );
				break;

			case 'site-identity':
				if ( isset( $_POST['schema_organization_type'] ) ) {
					$this->options->set( 'schema_organization_type', sanitize_text_field( $_POST['schema_organization_type'] ) );
				}
				if ( isset( $_POST['schema_business_name'] ) ) {
					$this->options->set( 'schema_business_name', sanitize_text_field( $_POST['schema_business_name'] ) );
				}
				if ( isset( $_POST['schema_organization_logo'] ) ) {
					$this->options->set( 'schema_organization_logo', sanitize_url( $_POST['schema_organization_logo'] ) );
				}
				if ( isset( $_POST['social_facebook_url'] ) ) {
					$this->options->set( 'social_facebook_url', sanitize_url( $_POST['social_facebook_url'] ) );
				}
				if ( isset( $_POST['social_twitter_url'] ) ) {
					$this->options->set( 'social_twitter_url', sanitize_url( $_POST['social_twitter_url'] ) );
				}
				$this->options->save();
				break;

			case 'seo-defaults':
				$this->options->set( 'sitemap_enabled', isset( $_POST['sitemap_enabled'] ) );
				
				// Handle category indexing
				$cat_robots = $this->options->get( 'robots_category', array( 'noindex' => false, 'nofollow' => false ) );
				$cat_robots['noindex'] = ! isset( $_POST['index_categories'] );
				$this->options->set( 'robots_category', $cat_robots );

				if ( isset( $_POST['title_separator'] ) ) {
					$this->options->set( 'title_separator', sanitize_text_field( $_POST['title_separator'] ) );
				}
				$this->options->save();
				break;
		}
	}

	/**
	 * Get next step slug.
	 *
	 * @return string|null
	 */
	private function get_next_step(): ?string {
		$steps = array( 'welcome', 'compatibility', 'import', 'site-identity', 'seo-defaults', 'ready' );
		$current_index = array_search( $this->step, $steps );
		
		if ( false !== $current_index && isset( $steps[ $current_index + 1 ] ) ) {
			return $steps[ $current_index + 1 ];
		}
		
		return null;
	}

	/**
	 * Render the wizard.
	 *
	 * @return void
	 */
	public function render_wizard(): void {
		// Clear output buffer to ensure full screen experience.
		if ( ob_get_length() ) {
			ob_end_clean();
		}

		include \MEOWSEO_PATH . 'includes/admin/views/setup-wizard.php';
		exit;
	}

	/**
	 * AJAX: Initiate import.
	 */
	public function ajax_initiate_import(): void {
		check_ajax_referer( 'meowseo_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden' );
		}

		$plugin = isset( $_POST['plugin'] ) ? sanitize_key( $_POST['plugin'] ) : '';
		$import_manager = $this->module_manager->get_module( 'import' )->get_import_manager();
		
		$import_id = $import_manager->initiate_import( $plugin );

		if ( ! $import_id ) {
			wp_send_json_error( 'Failed to initiate import' );
		}

		wp_send_json_success( array( 'import_id' => $import_id ) );
	}

	/**
	 * AJAX: Get import stats.
	 */
	public function ajax_get_import_stats(): void {
		check_ajax_referer( 'meowseo_wizard_nonce', 'nonce' );

		$import_id = isset( $_POST['import_id'] ) ? sanitize_key( $_POST['import_id'] ) : '';
		$job = get_transient( 'meowseo_import_' . $import_id );

		if ( ! $job ) {
			wp_send_json_error( 'Job not found' );
		}

		$import_manager = $this->module_manager->get_module( 'import' )->get_import_manager();
		$importer = $import_manager->get_importer( $job['plugin'] );

		if ( ! $importer ) {
			wp_send_json_error( 'Importer not found' );
		}

		wp_send_json_success( array(
			'posts'     => $importer->get_total_posts(),
			'terms'     => $importer->get_total_terms(),
			'redirects' => $importer->get_total_redirects(),
		) );
	}

	/**
	 * AJAX: Import options.
	 */
	public function ajax_import_options(): void {
		check_ajax_referer( 'meowseo_wizard_nonce', 'nonce' );

		$import_id = isset( $_POST['import_id'] ) ? sanitize_key( $_POST['import_id'] ) : '';
		$job = get_transient( 'meowseo_import_' . $import_id );

		if ( ! $job ) {
			wp_send_json_error( 'Job not found' );
		}

		$import_manager = $this->module_manager->get_module( 'import' )->get_import_manager();
		$importer = $import_manager->get_importer( $job['plugin'] );

		$result = $importer->import_options();
		$import_manager->update_progress( $import_id, array( 'options' => 100 ) );

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: Import batch.
	 */
	public function ajax_import_batch(): void {
		check_ajax_referer( 'meowseo_wizard_nonce', 'nonce' );

		$import_id = isset( $_POST['import_id'] ) ? sanitize_key( $_POST['import_id'] ) : '';
		$type      = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : 'posts';
		$offset    = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;
		$limit     = 50;

		$job = get_transient( 'meowseo_import_' . $import_id );
		if ( ! $job ) {
			wp_send_json_error( 'Job not found' );
		}

		$import_manager = $this->module_manager->get_module( 'import' )->get_import_manager();
		$importer = $import_manager->get_importer( $job['plugin'] );

		$result = array( 'imported' => 0, 'errors' => 0 );

		if ( 'posts' === $type ) {
			$ids = get_posts( array(
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'fields'         => 'ids',
			) );
			if ( ! empty( $ids ) ) {
				$result = $importer->import_postmeta( $ids );
			}
		} elseif ( 'terms' === $type ) {
			global $wpdb;
			$taxonomies = get_taxonomies( array( 'public' => true ) );
			$in_tax = "'" . implode( "','", array_map( 'esc_sql', $taxonomies ) ) . "'";
			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ($in_tax) LIMIT %d, %d", $offset, $limit ) );
			if ( ! empty( $ids ) ) {
				$result = $importer->import_termmeta( $ids );
			}
		} elseif ( 'redirects' === $type ) {
			$result = $importer->import_redirects();
		}

		wp_send_json_success( array(
			'imported' => $result['imported'],
			'errors'   => $result['errors'],
			'count'    => isset( $ids ) ? count( $ids ) : $result['imported'],
		) );
	}
}
