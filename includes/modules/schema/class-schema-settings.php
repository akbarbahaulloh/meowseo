<?php
/**
 * Schema Settings
 *
 * Manages schema module settings including automatic/default schemas.
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

namespace MeowSEO\Modules\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_Settings class.
 */
class Schema_Settings {

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'meowseo_schema_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults = array(
		// Automatic/Global Schemas
		'auto_website'      => true,
		'auto_organization' => true,
		'auto_breadcrumbs'  => true,
		'auto_author'       => true,
		'auto_webpage'      => true,

		// Organization Settings
		'organization_type' => 'Organization',
		'organization_name' => '',
		'organization_logo' => '',
		'organization_logo_width' => '',
		'organization_logo_height' => '',

		// Social Profiles
		'facebook_url'  => '',
		'twitter_url'   => '',
		'instagram_url' => '',
		'linkedin_url'  => '',
		'youtube_url'   => '',
		'pinterest_url' => '',

		// Advanced Settings
		'enable_schema_output' => true,
		'schema_output_format' => 'graph', // 'graph' or 'separate'
	);

	/**
	 * Setup hooks.
	 */
	public function setup(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		// Standalone menu removed for consolidation into main Settings.
	}

	/**
	 * Register settings.
	 */
	public function register_settings(): void {
		register_setting(
			'meowseo_schema_settings',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->defaults,
			)
		);

		// Automatic Schemas Section.
		add_settings_section(
			'meowseo_schema_automatic',
			__( 'Automatic Schemas', 'meowseo' ),
			array( $this, 'render_automatic_section' ),
			'meowseo-schema-settings'
		);

		// Auto Website.
		add_settings_field(
			'auto_website',
			__( 'Website Schema', 'meowseo' ),
			array( $this, 'render_checkbox_field' ),
			'meowseo-schema-settings',
			'meowseo_schema_automatic',
			array(
				'label_for'   => 'auto_website',
				'description' => __( 'Automatically add Website schema to all pages', 'meowseo' ),
			)
		);

		// Auto Organization.
		add_settings_field(
			'auto_organization',
			__( 'Organization Schema', 'meowseo' ),
			array( $this, 'render_checkbox_field' ),
			'meowseo-schema-settings',
			'meowseo_schema_automatic',
			array(
				'label_for'   => 'auto_organization',
				'description' => __( 'Automatically add Organization schema to all pages', 'meowseo' ),
			)
		);

		// Auto Breadcrumbs.
		add_settings_field(
			'auto_breadcrumbs',
			__( 'Breadcrumb Schema', 'meowseo' ),
			array( $this, 'render_checkbox_field' ),
			'meowseo-schema-settings',
			'meowseo_schema_automatic',
			array(
				'label_for'   => 'auto_breadcrumbs',
				'description' => __( 'Automatically add BreadcrumbList schema to all pages (except homepage)', 'meowseo' ),
			)
		);

		// Auto Author.
		add_settings_field(
			'auto_author',
			__( 'Author Schema', 'meowseo' ),
			array( $this, 'render_checkbox_field' ),
			'meowseo-schema-settings',
			'meowseo_schema_automatic',
			array(
				'label_for'   => 'auto_author',
				'description' => __( 'Automatically add Person (author) schema to all posts', 'meowseo' ),
			)
		);

		// Auto WebPage.
		add_settings_field(
			'auto_webpage',
			__( 'WebPage Schema', 'meowseo' ),
			array( $this, 'render_checkbox_field' ),
			'meowseo-schema-settings',
			'meowseo_schema_automatic',
			array(
				'label_for'   => 'auto_webpage',
				'description' => __( 'Automatically add WebPage schema to all pages', 'meowseo' ),
			)
		);

		// Organization Settings Section.
		add_settings_section(
			'meowseo_schema_organization',
			__( 'Organization Settings', 'meowseo' ),
			array( $this, 'render_organization_section' ),
			'meowseo-schema-settings'
		);

		// Organization Type.
		add_settings_field(
			'organization_type',
			__( 'Organization Type', 'meowseo' ),
			array( $this, 'render_select_field' ),
			'meowseo-schema-settings',
			'meowseo_schema_organization',
			array(
				'label_for' => 'organization_type',
				'options'   => array(
					'Organization'              => __( 'Organization', 'meowseo' ),
					'Corporation'               => __( 'Corporation', 'meowseo' ),
					'EducationalOrganization'   => __( 'Educational Organization', 'meowseo' ),
					'GovernmentOrganization'    => __( 'Government Organization', 'meowseo' ),
					'LocalBusiness'             => __( 'Local Business', 'meowseo' ),
					'NGO'                       => __( 'NGO', 'meowseo' ),
					'PerformingGroup'           => __( 'Performing Group', 'meowseo' ),
					'SportsOrganization'        => __( 'Sports Organization', 'meowseo' ),
					'MedicalOrganization'       => __( 'Medical Organization', 'meowseo' ),
					'NewsMediaOrganization'     => __( 'News Media Organization', 'meowseo' ),
				),
			)
		);

		// Organization Name.
		add_settings_field(
			'organization_name',
			__( 'Organization Name', 'meowseo' ),
			array( $this, 'render_text_field' ),
			'meowseo-schema-settings',
			'meowseo_schema_organization',
			array(
				'label_for'   => 'organization_name',
				'placeholder' => get_bloginfo( 'name' ),
				'description' => __( 'Leave empty to use site name', 'meowseo' ),
			)
		);

		// Organization Logo.
		add_settings_field(
			'organization_logo',
			__( 'Organization Logo', 'meowseo' ),
			array( $this, 'render_image_field' ),
			'meowseo-schema-settings',
			'meowseo_schema_organization',
			array(
				'label_for'   => 'organization_logo',
				'description' => __( 'Recommended: 600x60px or larger, PNG/JPG format', 'meowseo' ),
			)
		);

		// Social Profiles Section.
		add_settings_section(
			'meowseo_schema_social',
			__( 'Social Profiles', 'meowseo' ),
			array( $this, 'render_social_section' ),
			'meowseo-schema-settings'
		);

		$social_networks = array(
			'facebook'  => __( 'Facebook', 'meowseo' ),
			'twitter'   => __( 'Twitter', 'meowseo' ),
			'instagram' => __( 'Instagram', 'meowseo' ),
			'linkedin'  => __( 'LinkedIn', 'meowseo' ),
			'youtube'   => __( 'YouTube', 'meowseo' ),
			'pinterest' => __( 'Pinterest', 'meowseo' ),
		);

		foreach ( $social_networks as $network => $label ) {
			add_settings_field(
				$network . '_url',
				$label,
				array( $this, 'render_url_field' ),
				'meowseo-schema-settings',
				'meowseo_schema_social',
				array(
					'label_for'   => $network . '_url',
					'placeholder' => 'https://' . $network . '.com/yourprofile',
				)
			);
		}
	}

	/**
	 * Add settings page.
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			'meowseo',
			__( 'Schema Settings', 'meowseo' ),
			__( 'Schema', 'meowseo' ),
			'manage_options',
			'meowseo-schema-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submission.
		if ( isset( $_POST['meowseo_schema_settings_nonce'] ) ) {
			check_admin_referer( 'meowseo_schema_settings', 'meowseo_schema_settings_nonce' );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="meowseo-schema-settings-header">
				<p class="description">
					<?php esc_html_e( 'Configure automatic schema generation and organization settings. These schemas will be automatically added to your pages without manual configuration.', 'meowseo' ); ?>
				</p>
			</div>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'meowseo_schema_settings' );
				do_settings_sections( 'meowseo-schema-settings' );
				submit_button( __( 'Save Settings', 'meowseo' ) );
				?>
			</form>

			<div class="meowseo-schema-info">
				<h2><?php esc_html_e( 'About Automatic Schemas', 'meowseo' ); ?></h2>
				<p><?php esc_html_e( 'Automatic schemas are foundational structured data that should be present on every website:', 'meowseo' ); ?></p>
				<ul>
					<li><strong><?php esc_html_e( 'Website Schema:', 'meowseo' ); ?></strong> <?php esc_html_e( 'Identifies your website and enables sitelinks search box in Google.', 'meowseo' ); ?></li>
					<li><strong><?php esc_html_e( 'Organization Schema:', 'meowseo' ); ?></strong> <?php esc_html_e( 'Provides information about your organization for knowledge panels.', 'meowseo' ); ?></li>
					<li><strong><?php esc_html_e( 'BreadcrumbList Schema:', 'meowseo' ); ?></strong> <?php esc_html_e( 'Shows page hierarchy in search results for better navigation.', 'meowseo' ); ?></li>
					<li><strong><?php esc_html_e( 'Author Schema:', 'meowseo' ); ?></strong> <?php esc_html_e( 'Identifies content authors and builds author authority.', 'meowseo' ); ?></li>
					<li><strong><?php esc_html_e( 'WebPage Schema:', 'meowseo' ); ?></strong> <?php esc_html_e( 'Provides basic information about each page.', 'meowseo' ); ?></li>
				</ul>
				<p class="description">
					<?php esc_html_e( 'These schemas work together to create a complete structured data foundation for your website. It is recommended to keep all of them enabled.', 'meowseo' ); ?>
				</p>
			</div>
		</div>

		<style>
			.meowseo-schema-settings-header {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-left: 4px solid #2271b1;
				padding: 15px;
				margin: 20px 0;
			}
			.meowseo-schema-info {
				background: #f0f6fc;
				border: 1px solid #c3e6ff;
				border-radius: 4px;
				padding: 20px;
				margin-top: 30px;
			}
			.meowseo-schema-info h2 {
				margin-top: 0;
			}
			.meowseo-schema-info ul {
				list-style: disc;
				margin-left: 20px;
			}
			.meowseo-schema-info ul li {
				margin-bottom: 10px;
			}
		</style>
		<?php
	}

	/**
	 * Render automatic section description.
	 */
	public function render_automatic_section(): void {
		echo '<p>' . esc_html__( 'Enable or disable automatic schema generation. These schemas will be added to all pages without manual configuration.', 'meowseo' ) . '</p>';
	}

	/**
	 * Render organization section description.
	 */
	public function render_organization_section(): void {
		echo '<p>' . esc_html__( 'Configure your organization information for the Organization schema.', 'meowseo' ) . '</p>';
	}

	/**
	 * Render social section description.
	 */
	public function render_social_section(): void {
		echo '<p>' . esc_html__( 'Add your social media profile URLs. These will be included in the Organization schema.', 'meowseo' ) . '</p>';
	}

	/**
	 * Render checkbox field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_checkbox_field( array $args ): void {
		$settings = $this->get_settings();
		$field_id = $args['label_for'];
		$checked  = ! empty( $settings[ $field_id ] );

		?>
		<label>
			<input 
				type="checkbox" 
				id="<?php echo esc_attr( $field_id ); ?>" 
				name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field_id . ']' ); ?>" 
				value="1" 
				<?php checked( $checked ); ?>
			/>
			<?php echo esc_html( $args['description'] ?? '' ); ?>
		</label>
		<?php
	}

	/**
	 * Render text field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_text_field( array $args ): void {
		$settings = $this->get_settings();
		$field_id = $args['label_for'];
		$value    = $settings[ $field_id ] ?? '';

		?>
		<input 
			type="text" 
			id="<?php echo esc_attr( $field_id ); ?>" 
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field_id . ']' ); ?>" 
			value="<?php echo esc_attr( $value ); ?>" 
			placeholder="<?php echo esc_attr( $args['placeholder'] ?? '' ); ?>"
			class="regular-text"
		/>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render URL field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_url_field( array $args ): void {
		$settings = $this->get_settings();
		$field_id = $args['label_for'];
		$value    = $settings[ $field_id ] ?? '';

		?>
		<input 
			type="url" 
			id="<?php echo esc_attr( $field_id ); ?>" 
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field_id . ']' ); ?>" 
			value="<?php echo esc_attr( $value ); ?>" 
			placeholder="<?php echo esc_attr( $args['placeholder'] ?? '' ); ?>"
			class="regular-text"
		/>
		<?php
	}

	/**
	 * Render select field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_select_field( array $args ): void {
		$settings = $this->get_settings();
		$field_id = $args['label_for'];
		$value    = $settings[ $field_id ] ?? '';
		$options  = $args['options'] ?? array();

		?>
		<select 
			id="<?php echo esc_attr( $field_id ); ?>" 
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field_id . ']' ); ?>"
		>
			<?php foreach ( $options as $option_value => $option_label ) : ?>
				<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
					<?php echo esc_html( $option_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render image field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_image_field( array $args ): void {
		$settings = $this->get_settings();
		$field_id = $args['label_for'];
		$value    = $settings[ $field_id ] ?? '';

		?>
		<div class="meowseo-image-field">
			<input 
				type="url" 
				id="<?php echo esc_attr( $field_id ); ?>" 
				name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field_id . ']' ); ?>" 
				value="<?php echo esc_attr( $value ); ?>" 
				class="regular-text meowseo-image-url"
			/>
			<button type="button" class="button meowseo-upload-image">
				<?php esc_html_e( 'Upload Image', 'meowseo' ); ?>
			</button>
			<?php if ( $value ) : ?>
				<div class="meowseo-image-preview">
					<img src="<?php echo esc_url( $value ); ?>" style="max-width: 200px; height: auto; margin-top: 10px;" />
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $args['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
			<?php endif; ?>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('.meowseo-upload-image').on('click', function(e) {
				e.preventDefault();
				var button = $(this);
				var input = button.prev('input');
				
				var frame = wp.media({
					title: '<?php esc_html_e( 'Select Logo', 'meowseo' ); ?>',
					button: {
						text: '<?php esc_html_e( 'Use this image', 'meowseo' ); ?>'
					},
					multiple: false
				});
				
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					input.val(attachment.url);
					
					// Update preview
					var preview = button.siblings('.meowseo-image-preview');
					if (preview.length) {
						preview.find('img').attr('src', attachment.url);
					} else {
						button.after('<div class="meowseo-image-preview"><img src="' + attachment.url + '" style="max-width: 200px; height: auto; margin-top: 10px;" /></div>');
					}
				});
				
				frame.open();
			});
		});
		</script>
		<?php
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( array $input ): array {
		$sanitized = array();

		// Checkboxes.
		$checkboxes = array( 'auto_website', 'auto_organization', 'auto_breadcrumbs', 'auto_author', 'auto_webpage', 'enable_schema_output' );
		foreach ( $checkboxes as $checkbox ) {
			$sanitized[ $checkbox ] = ! empty( $input[ $checkbox ] );
		}

		// Text fields.
		$text_fields = array( 'organization_type', 'organization_name', 'schema_output_format' );
		foreach ( $text_fields as $field ) {
			$sanitized[ $field ] = isset( $input[ $field ] ) ? sanitize_text_field( $input[ $field ] ) : '';
		}

		// URL fields.
		$url_fields = array( 'organization_logo', 'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'pinterest_url' );
		foreach ( $url_fields as $field ) {
			$sanitized[ $field ] = isset( $input[ $field ] ) ? esc_url_raw( $input[ $field ] ) : '';
		}

		// Number fields.
		$number_fields = array( 'organization_logo_width', 'organization_logo_height' );
		foreach ( $number_fields as $field ) {
			$sanitized[ $field ] = isset( $input[ $field ] ) ? absint( $input[ $field ] ) : '';
		}

		return $sanitized;
	}

	/**
	 * Get settings.
	 *
	 * @return array Settings array.
	 */
	public function get_settings(): array {
		$options = new \MeowSEO\Options();
		return array(
			'auto_website'            => $options->get( 'schema_auto_website', true ),
			'auto_organization'       => $options->get( 'schema_auto_organization', true ),
			'auto_breadcrumbs'        => $options->get( 'schema_auto_breadcrumbs', true ),
			'auto_author'             => $options->get( 'schema_auto_author', true ),
			'auto_webpage'            => $options->get( 'schema_auto_webpage', true ),
			'organization_type'       => $options->get( 'schema_organization_type', 'Organization' ),
			'organization_name'       => $options->get( 'schema_business_name', '' ),
			'organization_logo'       => $options->get( 'schema_organization_logo', '' ),
			'organization_logo_width' => $options->get( 'schema_organization_logo_width', '' ),
			'organization_logo_height' => $options->get( 'schema_organization_logo_height', '' ),
			'facebook_url'            => $options->get( 'social_facebook_url', '' ),
			'twitter_url'             => $options->get( 'social_twitter_url', '' ),
			'instagram_url'           => $options->get( 'social_instagram_url', '' ),
			'linkedin_url'            => $options->get( 'social_linkedin_url', '' ),
			'youtube_url'             => $options->get( 'social_youtube_url', '' ),
			'pinterest_url'           => $options->get( 'social_pinterest_url', '' ),
			'enable_schema_output'    => $options->get( 'schema_enable_output', true ),
			'schema_output_format'    => $options->get( 'schema_output_format', 'graph' ),
		);
	}

	/**
	 * Get single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	public function get( string $key, $default = null ) {
		$settings = $this->get_settings();
		return $settings[ $key ] ?? $default;
	}
}
