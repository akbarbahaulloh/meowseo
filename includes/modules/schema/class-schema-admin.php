<?php
/**
 * Schema Admin UI
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

namespace MeowSEO\Modules\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_Admin class.
 */
class Schema_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Enqueue scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Add Gutenberg sidebar panel.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		// Add admin menu page.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	// Metabox now integrated into Classic Editor class.


	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ): void {
		// Only load on post edit screens.
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$asset_file = \MEOWSEO_PATH . 'build/schema-builder.asset.php';
		$asset_data = file_exists( $asset_file ) ? require $asset_file : array(
			'dependencies' => array(),
			'version'      => '1.0.0',
		);

		// Enqueue React app.
		wp_enqueue_script(
			'meowseo-schema-builder',
			\MEOWSEO_URL . 'build/schema-builder.js',
			array_merge( $asset_data['dependencies'], array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ) ),
			$asset_data['version'],
			true
		);

		// Enqueue styles.
		wp_enqueue_style(
			'meowseo-schema-builder',
			\MEOWSEO_URL . 'includes/modules/schema/assets/css/schema-builder.css',
			array( 'wp-components' ),
			'1.0.0'
		);

		// Localize script with data.
		wp_localize_script(
			'meowseo-schema-builder',
			'meowseoSchema',
			array(
				'restUrl'   => rest_url( 'meowseo/v1' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'postId'    => get_the_ID(),
				'postType'  => get_post_type(),
				'i18n'      => array(
					'addSchema'       => __( 'Add Schema', 'meowseo' ),
					'editSchema'      => __( 'Edit Schema', 'meowseo' ),
					'deleteSchema'    => __( 'Delete Schema', 'meowseo' ),
					'saveSchema'      => __( 'Save Schema', 'meowseo' ),
					'cancel'          => __( 'Cancel', 'meowseo' ),
					'schemaType'      => __( 'Schema Type', 'meowseo' ),
					'selectType'      => __( 'Select a schema type', 'meowseo' ),
					'noSchemas'       => __( 'No schemas added yet', 'meowseo' ),
					'confirmDelete'   => __( 'Are you sure you want to delete this schema?', 'meowseo' ),
					'savingSchema'    => __( 'Saving schema...', 'meowseo' ),
					'deletingSchema'  => __( 'Deleting schema...', 'meowseo' ),
					'loadingSchemas'  => __( 'Loading schemas...', 'meowseo' ),
					'errorLoading'    => __( 'Error loading schemas', 'meowseo' ),
					'errorSaving'     => __( 'Error saving schema', 'meowseo' ),
					'errorDeleting'   => __( 'Error deleting schema', 'meowseo' ),
					'schemaSaved'     => __( 'Schema saved successfully', 'meowseo' ),
					'schemaDeleted'   => __( 'Schema deleted successfully', 'meowseo' ),
					'preview'         => __( 'Preview JSON-LD', 'meowseo' ),
					'closePreview'    => __( 'Close Preview', 'meowseo' ),
					'copyToClipboard' => __( 'Copy to Clipboard', 'meowseo' ),
					'copied'          => __( 'Copied!', 'meowseo' ),
					'shortcode'       => __( 'Shortcode', 'meowseo' ),
					'variables'       => __( 'Available Variables', 'meowseo' ),
					'insertVariable'  => __( 'Insert Variable', 'meowseo' ),
				),
			)
		);
	}

	/**
	 * Enqueue block editor assets.
	 */
	public function enqueue_block_editor_assets(): void {
		$asset_file = \MEOWSEO_PATH . 'build/schema-sidebar.asset.php';
		$asset_data = file_exists( $asset_file ) ? require $asset_file : array(
			'dependencies' => array(),
			'version'      => '1.0.0',
		);

		// Enqueue Gutenberg sidebar.
		wp_enqueue_script(
			'meowseo-schema-sidebar',
			\MEOWSEO_URL . 'build/schema-sidebar.js',
			array_merge( $asset_data['dependencies'], array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-i18n' ) ),
			$asset_data['version'],
			true
		);

		// Enqueue styles.
		wp_enqueue_style(
			'meowseo-schema-sidebar',
			\MEOWSEO_URL . 'includes/modules/schema/assets/css/schema-sidebar.css',
			array( 'wp-components' ),
			'1.0.0'
		);

		// Localize script with data.
		wp_localize_script(
			'meowseo-schema-sidebar',
			'meowseoSchema',
			array(
				'restUrl'  => rest_url( 'meowseo/v1' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'postId'   => get_the_ID(),
				'postType' => get_post_type(),
				'i18n'     => array(
					'title'           => __( 'Schema Generator', 'meowseo' ),
					'addSchema'       => __( 'Add Schema', 'meowseo' ),
					'editSchema'      => __( 'Edit Schema', 'meowseo' ),
					'deleteSchema'    => __( 'Delete Schema', 'meowseo' ),
					'saveSchema'      => __( 'Save Schema', 'meowseo' ),
					'cancel'          => __( 'Cancel', 'meowseo' ),
					'schemaType'      => __( 'Schema Type', 'meowseo' ),
					'selectType'      => __( 'Select a schema type', 'meowseo' ),
					'noSchemas'       => __( 'No schemas added yet', 'meowseo' ),
					'confirmDelete'   => __( 'Are you sure you want to delete this schema?', 'meowseo' ),
					'savingSchema'    => __( 'Saving schema...', 'meowseo' ),
					'deletingSchema'  => __( 'Deleting schema...', 'meowseo' ),
					'loadingSchemas'  => __( 'Loading schemas...', 'meowseo' ),
					'errorLoading'    => __( 'Error loading schemas', 'meowseo' ),
					'errorSaving'     => __( 'Error saving schema', 'meowseo' ),
					'errorDeleting'   => __( 'Error deleting schema', 'meowseo' ),
					'schemaSaved'     => __( 'Schema saved successfully', 'meowseo' ),
					'schemaDeleted'   => __( 'Schema deleted successfully', 'meowseo' ),
					'preview'         => __( 'Preview JSON-LD', 'meowseo' ),
					'closePreview'    => __( 'Close Preview', 'meowseo' ),
					'copyToClipboard' => __( 'Copy to Clipboard', 'meowseo' ),
					'copied'          => __( 'Copied!', 'meowseo' ),
					'shortcode'       => __( 'Shortcode', 'meowseo' ),
					'variables'       => __( 'Available Variables', 'meowseo' ),
					'insertVariable'  => __( 'Insert Variable', 'meowseo' ),
				),
			)
		);
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'meowseo',
			__( 'Schema Generator', 'meowseo' ),
			__( 'Schema Generator', 'meowseo' ),
			'manage_options',
			'meowseo-schema',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Schema Generator', 'meowseo' ); ?></h1>
			
			<div class="meowseo-schema-admin">
				<div class="meowseo-schema-admin__header">
					<p class="description">
						<?php esc_html_e( 'Configure global schema settings and manage schema types.', 'meowseo' ); ?>
					</p>
				</div>

				<div class="meowseo-schema-admin__content">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'meowseo_schema_settings' );
						do_settings_sections( 'meowseo-schema' );
						submit_button();
						?>
					</form>
				</div>

				<div class="meowseo-schema-admin__info">
					<h2><?php esc_html_e( 'Available Schema Types', 'meowseo' ); ?></h2>
					<div class="meowseo-schema-types-grid">
						<?php $this->render_schema_types_grid(); ?>
					</div>
				</div>

				<div class="meowseo-schema-admin__help">
					<h2><?php esc_html_e( 'Documentation', 'meowseo' ); ?></h2>
					<ul>
						<li>
							<a href="https://schema.org/" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Schema.org Documentation', 'meowseo' ); ?>
							</a>
						</li>
						<li>
							<a href="https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Google Structured Data Guide', 'meowseo' ); ?>
							</a>
						</li>
						<li>
							<a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Rich Results Test', 'meowseo' ); ?>
							</a>
						</li>
						<li>
							<a href="https://validator.schema.org/" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Schema Markup Validator', 'meowseo' ); ?>
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render schema types grid.
	 */
	private function render_schema_types_grid(): void {
		$registry = Schema_Registry::get_instance();
		$types    = $registry->get_all();

		if ( empty( $types ) ) {
			echo '<p>' . esc_html__( 'No schema types available.', 'meowseo' ) . '</p>';
			return;
		}

		foreach ( $types as $type_id => $type ) {
			?>
			<div class="meowseo-schema-type-card">
				<div class="meowseo-schema-type-card__icon">
					<span class="dashicons dashicons-<?php echo esc_attr( $type->get_icon() ); ?>"></span>
				</div>
				<div class="meowseo-schema-type-card__content">
					<h3><?php echo esc_html( $type->get_label() ); ?></h3>
					<p><?php echo esc_html( $type->get_description() ); ?></p>
					<code>@type: <?php echo esc_html( $type->get_type() ); ?></code>
				</div>
			</div>
			<?php
		}
	}
}
