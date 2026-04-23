<?php
/**
 * AI Settings class for managing AI module configuration.
 *
 * Handles rendering of the AI settings page with provider configuration,
 * status display, generation settings, and image settings.
 *
 * @package MeowSEO\Modules\AI
 */

namespace MeowSEO\Modules\AI;

use MeowSEO\Options;
use MeowSEO\Helpers\Logger;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI_Settings class.
 *
 * Manages AI module settings page rendering and configuration.
 * Requirements: 2.1, 2.2, 3.1-3.4, 12.1, 12.6-12.7, 13.1-13.5, 14.1-14.5, 15.1-15.5, 16.1-16.5
 */
class AI_Settings {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Provider Manager instance.
	 *
	 * @var AI_Provider_Manager
	 */
	private AI_Provider_Manager $provider_manager;

	/**
	 * Constructor.
	 *
	 * @param Options              $options           Options instance.
	 * @param AI_Provider_Manager  $provider_manager  Provider Manager instance.
	 */
	public function __construct( Options $options, AI_Provider_Manager $provider_manager ) {
		$this->options           = $options;
		$this->provider_manager  = $provider_manager;

		// Register settings on admin_init hook.
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register all AI settings with WordPress Settings API.
	 *
	 * Registers settings with appropriate sanitization callbacks.
	 * Called on admin_init hook.
	 * Requirements: 2.1-2.10
	 *
	 * @return void
	 */
	public function register_settings(): void {
		// Provider API keys (encrypted).
		$providers = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle', 'deepseek', 'glm', 'qwen' ];
		foreach ( $providers as $provider ) {
			register_setting(
				'meowseo_ai_settings',
				"meowseo_ai_{$provider}_api_key",
				[
					'type'              => 'string',
					'sanitize_callback' => [ $this, 'sanitize_api_key' ],
					'show_in_rest'      => false,
				]
			);
		}

		// Gemini specific models.
		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_gemini_model',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'gemini-2.0-flash',
				'show_in_rest'      => false,
			]
		);

		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_gemini_image_model',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'gemini-3.1-flash-image-preview',
				'show_in_rest'      => false,
			]
		);

		// Provider order array.
		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_provider_order',
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_provider_order' ],
				'show_in_rest'      => false,
			]
		);

		// Active providers array.
		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_active_providers',
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_active_providers' ],
				'show_in_rest'      => false,
			]
		);

		// Auto-generation flags.
		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_auto_generate_on_save',
			[
				'type'              => 'boolean',
				'sanitize_callback' => [ $this, 'sanitize_boolean' ],
				'show_in_rest'      => false,
			]
		);

		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_auto_generate_image',
			[
				'type'              => 'boolean',
				'sanitize_callback' => [ $this, 'sanitize_boolean' ],
				'show_in_rest'      => false,
			]
		);

		// Overwrite behavior dropdown.
		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_overwrite_behavior',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_overwrite_behavior' ],
				'show_in_rest'      => false,
			]
		);

		// Output language dropdown.
		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_output_language',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_output_language' ],
				'show_in_rest'      => false,
			]
		);

		// Custom instructions textarea.
		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_custom_instructions',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_custom_instructions' ],
				'show_in_rest'      => false,
			]
		);

		// Image generation settings.
		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_image_generation_enabled',
			[
				'type'              => 'boolean',
				'sanitize_callback' => [ $this, 'sanitize_boolean' ],
				'show_in_rest'      => false,
			]
		);

		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_visual_style',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_visual_style' ],
				'show_in_rest'      => false,
			]
		);

		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_color_palette_hint',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_text_field' ],
				'show_in_rest'      => false,
			]
		);

		register_setting(
			'meowseo_ai_settings',
			'meowseo_ai_save_to_media_library',
			[
				'type'              => 'boolean',
				'sanitize_callback' => [ $this, 'sanitize_boolean' ],
				'show_in_rest'      => false,
			]
		);
	}

	/**
	 * Sanitize API key - encrypt before storage.
	 *
	 * Validates and encrypts API keys using AES-256-CBC.
	 * Requirements: 2.3, 24.1-24.6
	 *
	 * @param mixed $value The API key value to sanitize.
	 * @return string|false Encrypted API key or false if empty.
	 */
	public function sanitize_api_key( $value ) {
		// If empty, return empty string (will be deleted).
		if ( empty( $value ) ) {
			return '';
		}

		// Sanitize as text field first.
		$sanitized = sanitize_text_field( $value );

		// If still empty after sanitization, return empty.
		if ( empty( $sanitized ) ) {
			return '';
		}

		// Encrypt the API key.
		$encrypted = $this->provider_manager->encrypt_key( $sanitized );

		// Return encrypted key or empty string if encryption failed.
		return $encrypted ?: '';
	}

	/**
	 * Sanitize provider order array.
	 *
	 * Validates that all provider slugs are valid.
	 * Requirements: 2.9, 24.1-24.6
	 *
	 * @param mixed $value The provider order array.
	 * @return array Sanitized provider order array.
	 */
	public function sanitize_provider_order( $value ) {
		// Valid provider slugs.
		$valid_slugs = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle', 'deepseek', 'glm', 'qwen' ];

		// If not an array, return empty array.
		if ( ! is_array( $value ) ) {
			return [];
		}

		// Filter to only valid slugs.
		$sanitized = [];
		foreach ( $value as $slug ) {
			$slug = sanitize_text_field( $slug );
			if ( in_array( $slug, $valid_slugs, true ) ) {
				$sanitized[] = $slug;
			}
		}

		// Ensure all valid slugs are present (add missing ones at the end).
		foreach ( $valid_slugs as $slug ) {
			if ( ! in_array( $slug, $sanitized, true ) ) {
				$sanitized[] = $slug;
			}
		}

		// Clear provider status cache when order changes.
		$this->provider_manager->clear_provider_status_cache();

		return $sanitized;
	}

	/**
	 * Sanitize active providers array.
	 *
	 * Validates that all provider slugs are valid.
	 * Requirements: 2.8, 24.1-24.6
	 *
	 * @param mixed $value The active providers array.
	 * @return array Sanitized active providers array.
	 */
	public function sanitize_active_providers( $value ) {
		// Valid provider slugs.
		$valid_slugs = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle', 'deepseek', 'glm', 'qwen' ];

		// If not an array, return empty array.
		if ( ! is_array( $value ) ) {
			return [];
		}

		// Filter to only valid slugs.
		$sanitized = [];
		foreach ( $value as $slug ) {
			$slug = sanitize_text_field( $slug );
			if ( in_array( $slug, $valid_slugs, true ) ) {
				$sanitized[] = $slug;
			}
		}

		// Clear provider status cache when active providers change.
		$this->provider_manager->clear_provider_status_cache();

		return $sanitized;
	}

	/**
	 * Sanitize boolean value.
	 *
	 * Converts value to boolean.
	 * Requirements: 26.1-26.5
	 *
	 * @param mixed $value The value to sanitize.
	 * @return bool Sanitized boolean value.
	 */
	public function sanitize_boolean( $value ) {
		return (bool) $value;
	}

	/**
	 * Sanitize overwrite behavior dropdown.
	 *
	 * Validates dropdown selection against whitelist.
	 * Requirements: 13.1-13.5, 26.1-26.5
	 *
	 * @param mixed $value The overwrite behavior value.
	 * @return string Sanitized overwrite behavior.
	 */
	public function sanitize_overwrite_behavior( $value ) {
		$valid_values = [ 'always', 'never', 'ask' ];
		$sanitized = sanitize_text_field( $value );

		if ( in_array( $sanitized, $valid_values, true ) ) {
			return $sanitized;
		}

		// Default to 'ask' if invalid.
		return 'ask';
	}

	/**
	 * Sanitize output language dropdown.
	 *
	 * Validates dropdown selection against whitelist.
	 * Requirements: 14.1-14.5, 26.1-26.5
	 *
	 * @param mixed $value The output language value.
	 * @return string Sanitized output language.
	 */
	public function sanitize_output_language( $value ) {
		$valid_values = [ 'auto-detect', 'english', 'indonesian' ];
		$sanitized = sanitize_text_field( $value );

		if ( in_array( $sanitized, $valid_values, true ) ) {
			return $sanitized;
		}

		// Default to 'auto-detect' if invalid.
		return 'auto-detect';
	}

	/**
	 * Sanitize custom instructions textarea.
	 *
	 * Sanitizes text field and enforces character limit.
	 * Requirements: 15.1-15.5, 26.1-26.5
	 *
	 * @param mixed $value The custom instructions value.
	 * @return string Sanitized custom instructions.
	 */
	public function sanitize_custom_instructions( $value ) {
		// Sanitize as textarea field.
		$sanitized = sanitize_textarea_field( $value );

		// Enforce 500 character limit.
		if ( strlen( $sanitized ) > 500 ) {
			$sanitized = substr( $sanitized, 0, 500 );
		}

		return $sanitized;
	}

	/**
	 * Sanitize visual style dropdown.
	 *
	 * Validates dropdown selection against whitelist.
	 * Requirements: 16.1-16.5, 26.1-26.5
	 *
	 * @param mixed $value The visual style value.
	 * @return string Sanitized visual style.
	 */
	public function sanitize_visual_style( $value ) {
		$valid_values = [ 'professional', 'modern', 'minimal', 'illustrative', 'photography' ];
		$sanitized = sanitize_text_field( $value );

		if ( in_array( $sanitized, $valid_values, true ) ) {
			return $sanitized;
		}

		// Default to 'professional' if invalid.
		return 'professional';
	}

	/**
	 * Sanitize text field.
	 *
	 * Generic text field sanitization using WordPress function.
	 * Requirements: 26.1-26.5
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string Sanitized text field.
	 */
	public function sanitize_text_field( $value ) {
		return sanitize_text_field( $value );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * Enqueues JavaScript and CSS for the AI settings page.
	 * Called from AI_Module::enqueue_admin_scripts().
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		// Enqueue settings JavaScript
		wp_enqueue_script(
			'meowseo-ai-settings',
			\MEOWSEO_URL . 'includes/modules/ai/assets/js/ai-settings.js',
			array(),
			\MEOWSEO_VERSION,
			true
		);

		// Localize script with nonce and REST endpoints
		wp_localize_script(
			'meowseo-ai-settings',
			'meowseoAISettings',
			array(
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'restUrl' => rest_url(),
			)
		);

		// Enqueue settings CSS
		wp_enqueue_style(
			'meowseo-ai-settings',
			\MEOWSEO_URL . 'includes/modules/ai/assets/css/ai-settings.css',
			array(),
			\MEOWSEO_VERSION
		);
	}


	/**
	 * Render AI settings tab.
	 *
	 * Main render method that displays all AI settings sections.
	 * This method is called by AI_Module::render_settings_tab().
	 * Requirements: 2.1, 2.2, 3.1-3.4, 12.1, 12.6-12.7, 13.1-13.5, 14.1-14.5, 15.1-15.5, 16.1-16.5
	 *
	 * @return void
	 */
	public function render_tab(): void {
		$this->render_ai_tab();
	}

	/**
	 * Render AI settings tab content.
	 *
	 * Main render method that displays all AI settings sections.
	 * Requirements: 2.1, 2.2, 3.1-3.4, 12.1, 12.6-12.7, 13.1-13.5, 14.1-14.5, 15.1-15.5, 16.1-16.5
	 *
	 * @return void
	 */
	private function render_ai_tab(): void {
		?>
		<h2><?php esc_html_e( 'AI Generation Settings', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure AI providers and generation settings for automatic SEO metadata and image generation.', 'meowseo' ); ?></p>

		<?php
		$this->render_provider_configuration_section();
		$this->render_provider_status_section();
		$this->render_generation_settings_section();
		$this->render_image_settings_section();
	}

	/**
	 * Render provider configuration section.
	 *
	 * Displays provider list with drag-and-drop support, API key inputs,
	 * active/inactive toggles, test connection buttons, and capability indicators.
	 * Requirements: 2.1, 2.2, 2.8, 2.9, 2.10, 6.7-6.9
	 *
	 * @return void
	 */
	private function render_provider_configuration_section(): void {
		$profiles = $this->options->get( 'ai_profiles', array() );
		$available_providers = [
			'gemini'    => 'Google Gemini',
			'openai'    => 'OpenAI',
			'anthropic' => 'Anthropic Claude',
			'imagen'    => 'Google Imagen',
			'dalle'     => 'OpenAI DALL-E',
			'deepseek'  => 'DeepSeek',
			'glm'       => 'Zhipu AI GLM',
			'qwen'      => 'Alibaba Qwen',
		];
		?>
		<div class="meowseo-ai-section">
			<h3><?php esc_html_e( 'AI Profiles', 'meowseo' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Manage your AI provider profiles. You can add multiple profiles for the same provider (e.g., Free and Pro keys).', 'meowseo' ); ?></p>

			<div id="meowseo-ai-profiles-list">
				<?php if ( ! empty( $profiles ) ) : ?>
					<?php foreach ( $profiles as $index => $profile ) : ?>
						<?php $this->render_profile_item( $profile, $index, $available_providers ); ?>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="meowseo-no-profiles"><?php esc_html_e( 'No AI profiles configured. Click the button below to add one.', 'meowseo' ); ?></p>
				<?php endif; ?>
			</div>

			<button type="button" id="meowseo-add-ai-profile" class="button button-secondary">
				<span class="dashicons dashicons-plus-alt" style="vertical-align: middle; margin-top: 3px;"></span>
				<?php esc_html_e( 'Add New AI Profile', 'meowseo' ); ?>
			</button>
		</div>

		<script type="text/template" id="meowseo-ai-profile-template">
			<?php $this->render_profile_item( array( 'id' => 'PROFILE_ID', 'label' => '', 'provider' => 'gemini', 'api_key' => '', 'model' => 'gemini-2.0-flash', 'active' => true ), 'INDEX', $available_providers ); ?>
		</script>

		<script>
			jQuery(document).ready(function($) {
				var profileIndex = <?php echo count( $profiles ); ?>;
				
				// Default models for each provider
				var defaultModels = {
					'gemini': 'gemini-3-flash-preview',
					'openai': 'gpt-4o',
					'anthropic': 'claude-3-5-sonnet-20241022',
					'imagen': 'imagen-4.0-generate-001',
					'dalle': 'dall-e-3',
					'deepseek': 'deepseek-chat',
					'glm': 'glm-4-plus',
					'qwen': 'qwen-max'
				};
				
				// Add new profile
				$('#meowseo-add-ai-profile').on('click', function() {
					var template = $('#meowseo-ai-profile-template').html();
					var id = 'profile_' + Date.now();
					var html = template.replace(/INDEX/g, profileIndex).replace(/PROFILE_ID/g, id);
					
					$('.meowseo-no-profiles').hide();
					$('#meowseo-ai-profiles-list').append(html);
					profileIndex++;
				});

				// Remove profile
				$(document).on('click', '.meowseo-profile-remove', function(e) {
					e.preventDefault();
					if (confirm('<?php esc_attr_e( 'Are you sure you want to remove this profile?', 'meowseo' ); ?>')) {
						$(this).closest('.meowseo-profile-item').remove();
						if ($('#meowseo-ai-profiles-list').children().length === 0) {
							$('.meowseo-no-profiles').show();
						}
					}
				});
				
				// Auto-fill model when provider changes
				$(document).on('change', '.meowseo-provider-select', function() {
					var provider = $(this).val();
					var index = $(this).data('index');
					var modelInput = $(this).closest('.meowseo-profile-item').find('.meowseo-model-input');
					
					if (modelInput.length && defaultModels[provider]) {
						// Only auto-fill if empty or still has default value
						var currentValue = modelInput.val().trim();
						if (!currentValue || Object.values(defaultModels).indexOf(currentValue) !== -1) {
							modelInput.val(defaultModels[provider]);
							modelInput.attr('placeholder', defaultModels[provider]);
						}
						
						// Update description
						var description = modelInput.next('.description');
						if (description.length) {
							description.find('code').text(defaultModels[provider]);
						}
					}
				});
				
				// Show API key on focus (for editing)
				$(document).on('focus', '.meowseo-api-key-input', function() {
					var $input = $(this);
					var isEncrypted = $input.data('is-encrypted');
					
					// If encrypted (saved), clear the masked value so user can enter new key
					if (isEncrypted && $input.val().indexOf('...') !== -1) {
						$input.val('');
						$input.attr('placeholder', '<?php esc_attr_e( 'Enter new API key to replace', 'meowseo' ); ?>');
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Render a single AI profile item
	 *
	 * @param array $profile Profile data.
	 * @param int|string $index Index or placeholder.
	 * @param array $available_providers List of providers.
	 * @return void
	 */
	private function render_profile_item( array $profile, $index, array $available_providers ): void {
		$id = $profile['id'] ?? '';
		$provider = $profile['provider'] ?? 'gemini';
		
		// Get default model for provider
		$default_models = array(
			'gemini'    => 'gemini-3-flash-preview',
			'openai'    => 'gpt-4o',
			'anthropic' => 'claude-3-5-sonnet-20241022',
			'imagen'    => 'imagen-4.0-generate-001',
			'dalle'     => 'dall-e-3',
			'deepseek'  => 'deepseek-chat',
			'glm'       => 'glm-4-plus',
			'qwen'      => 'qwen-max',
		);
		
		$default_model = $default_models[ $provider ] ?? '';
		$current_model = $profile['model'] ?? $default_model;
		
		// Handle API key display
		$api_key = $profile['api_key'] ?? '';
		$is_encrypted = ! empty( $api_key ) && ( strpos( $api_key, '...' ) !== false || strlen( $api_key ) > 50 );
		
		// If encrypted/saved, show partial key
		if ( $is_encrypted ) {
			$display_key = substr( $api_key, 0, 4 ) . '...' . substr( $api_key, -4 );
		} else {
			// Show full key for new input (not yet saved)
			$display_key = $api_key;
		}
		?>
		<div class="meowseo-profile-item">
			<div class="meowseo-profile-header">
				<input type="text" name="ai_profiles[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $profile['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Profile Label (e.g., gemini free)', 'meowseo' ); ?>" class="regular-text" required>
				<input type="hidden" name="ai_profiles[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $id ); ?>">
				<div class="meowseo-profile-actions">
					<label>
						<input type="checkbox" name="ai_profiles[<?php echo esc_attr( $index ); ?>][active]" value="1" <?php checked( ! empty( $profile['active'] ) ); ?>>
						<?php esc_html_e( 'Active', 'meowseo' ); ?>
					</label>
					<a href="#" class="meowseo-profile-remove"><?php esc_html_e( 'Remove', 'meowseo' ); ?></a>
				</div>
			</div>

			<div class="meowseo-profile-grid">
				<div class="meowseo-profile-field">
					<label><?php esc_html_e( 'AI Provider', 'meowseo' ); ?></label>
					<select name="ai_profiles[<?php echo esc_attr( $index ); ?>][provider]" class="meowseo-provider-select" data-index="<?php echo esc_attr( $index ); ?>">
						<?php foreach ( $available_providers as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $provider, $slug ); ?> data-default-model="<?php echo esc_attr( $default_models[ $slug ] ?? '' ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="meowseo-profile-field">
					<label><?php esc_html_e( 'API Key', 'meowseo' ); ?></label>
					<input type="text" name="ai_profiles[<?php echo esc_attr( $index ); ?>][api_key]" value="<?php echo esc_attr( $display_key ); ?>" class="regular-text meowseo-api-key-input" placeholder="<?php esc_attr_e( 'Enter API Key', 'meowseo' ); ?>" data-is-encrypted="<?php echo $is_encrypted ? '1' : '0'; ?>">
					<p class="description" style="margin-top: 5px; font-size: 11px; color: #666;">
						<?php if ( $is_encrypted ) : ?>
							<?php esc_html_e( 'Key tersimpan. Masukkan key baru untuk mengubah.', 'meowseo' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Key akan disembunyikan setelah disimpan.', 'meowseo' ); ?>
						<?php endif; ?>
					</p>
				</div>
				<div class="meowseo-profile-field">
					<label><?php esc_html_e( 'Model Version', 'meowseo' ); ?></label>
					<input type="text" name="ai_profiles[<?php echo esc_attr( $index ); ?>][model]" value="<?php echo esc_attr( $current_model ); ?>" class="regular-text meowseo-model-input" placeholder="<?php echo esc_attr( $default_model ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
					<p class="description" style="margin-top: 5px; font-size: 11px; color: #666;">
						<?php esc_html_e( 'Default:', 'meowseo' ); ?> <code><?php echo esc_html( $default_model ); ?></code>
					</p>
				</div>
				<div class="meowseo-profile-field">
					<button type="button" class="button meowseo-test-connection-btn" data-profile-id="<?php echo esc_attr( $id ); ?>">
						<?php esc_html_e( 'Test Connection', 'meowseo' ); ?>
					</button>
					<span class="meowseo-test-status" id="test-status-<?php echo esc_attr( $id ); ?>"></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render provider status section.
	 *
	 * Displays real-time provider status with indicators, rate limit countdown,
	 * and error messages.
	 * Requirements: 3.1, 3.2, 3.3, 3.4
	 *
	 * @return void
	 */
	private function render_provider_status_section(): void {
		$statuses = $this->provider_manager->get_provider_statuses();
		?>
		<div class="meowseo-ai-section">
			<h3><?php esc_html_e( 'Provider Status', 'meowseo' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Real-time status of your AI providers. Status updates every 30 seconds.', 'meowseo' ); ?></p>

			<table class="meowseo-provider-status-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Provider', 'meowseo' ); ?></th>
						<th><?php esc_html_e( 'Status', 'meowseo' ); ?></th>
						<th><?php esc_html_e( 'Details', 'meowseo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $statuses as $provider_slug => $status ) : ?>
						<tr class="meowseo-provider-status-row" data-provider="<?php echo esc_attr( $provider_slug ); ?>">
							<td class="meowseo-provider-name">
								<?php echo esc_html( $status['label'] ); ?>
							</td>
							<td class="meowseo-provider-status-indicator">
								<?php $this->render_status_indicator( $status ); ?>
							</td>
							<td class="meowseo-provider-status-details">
								<?php $this->render_status_details( $status ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render status indicator badge.
	 *
	 * Displays status indicator with appropriate color and icon.
	 * Requirements: 3.2
	 *
	 * @param array $status Provider status array.
	 * @return void
	 */
	private function render_status_indicator( array $status ): void {
		$status_text = '';
		$status_class = '';

		if ( ! $status['active'] ) {
			$status_text = __( 'Inactive', 'meowseo' );
			$status_class = 'meowseo-status-inactive';
		} elseif ( ! $status['has_api_key'] ) {
			$status_text = __( 'No API Key', 'meowseo' );
			$status_class = 'meowseo-status-no-key';
		} elseif ( $status['rate_limited'] ) {
			$status_text = __( 'Rate Limited', 'meowseo' );
			$status_class = 'meowseo-status-rate-limited';
		} else {
			$status_text = __( 'Active', 'meowseo' );
			$status_class = 'meowseo-status-active';
		}

		?>
		<span class="meowseo-status-badge <?php echo esc_attr( $status_class ); ?>">
			<?php echo esc_html( $status_text ); ?>
		</span>
		<?php
	}

	/**
	 * Render status details.
	 *
	 * Displays additional status information like rate limit countdown or error messages.
	 * Requirements: 3.3, 3.4
	 *
	 * @param array $status Provider status array.
	 * @return void
	 */
	private function render_status_details( array $status ): void {
		if ( $status['rate_limited'] && $status['rate_limit_remaining'] > 0 ) {
			$minutes = ceil( $status['rate_limit_remaining'] / 60 );
			?>
			<span class="meowseo-status-detail">
				<?php
				printf(
					/* translators: %d is the number of minutes */
					esc_html__( 'Rate limit resets in %d minute(s)', 'meowseo' ),
					intval( $minutes )
				);
				?>
			</span>
			<?php
		} elseif ( ! $status['has_api_key'] ) {
			?>
			<span class="meowseo-status-detail meowseo-status-warning">
				<?php esc_html_e( 'Configure API key to enable', 'meowseo' ); ?>
			</span>
			<?php
		} elseif ( $status['active'] ) {
			?>
			<span class="meowseo-status-detail meowseo-status-success">
				<?php
				$capabilities = array();
				if ( $status['supports_text'] ) {
					$capabilities[] = __( 'Text', 'meowseo' );
				}
				if ( $status['supports_image'] ) {
					$capabilities[] = __( 'Image', 'meowseo' );
				}
				echo esc_html( implode( ', ', $capabilities ) );
				?>
			</span>
			<?php
		}
	}

	/**
	 * Render generation settings section.
	 *
	 * Displays auto-generation toggles, overwrite behavior dropdown,
	 * output language dropdown, and custom instructions textarea.
	 * Requirements: 12.1, 12.6-12.7, 13.1-13.5, 14.1-14.5, 15.1-15.5
	 *
	 * @return void
	 */
	private function render_generation_settings_section(): void {
		$auto_generate_on_save = $this->options->get( 'ai_auto_generate_on_save', false );
		$auto_generate_image = $this->options->get( 'ai_auto_generate_image', false );
		$overwrite_behavior = $this->options->get( 'ai_overwrite_behavior', 'ask' );
		$output_language = $this->options->get( 'ai_output_language', 'auto-detect' );
		$custom_instructions = $this->options->get( 'ai_custom_instructions', '' );
		$custom_instructions_length = strlen( $custom_instructions );
		?>
		<div class="meowseo-ai-section">
			<h3><?php esc_html_e( 'Generation Settings', 'meowseo' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure how AI content is generated and applied to your posts.', 'meowseo' ); ?></p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Auto-Generation', 'meowseo' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input
									type="checkbox"
									name="ai_auto_generate_on_save"
									value="1"
									<?php checked( $auto_generate_on_save ); ?>
								>
								<?php esc_html_e( 'Auto-generate SEO metadata on first draft save', 'meowseo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Automatically generate SEO metadata when you first save a post (if content is > 300 words).', 'meowseo' ); ?></p>

							<label style="display: block; margin-top: 10px;">
								<input
									type="checkbox"
									name="ai_auto_generate_image"
									value="1"
									<?php checked( $auto_generate_image ); ?>
								>
								<?php esc_html_e( 'Auto-generate featured image if missing', 'meowseo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Automatically generate a featured image if the post doesn\'t have one.', 'meowseo' ); ?></p>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="ai_overwrite_behavior"><?php esc_html_e( 'Overwrite Existing Metadata', 'meowseo' ); ?></label></th>
					<td>
						<select name="ai_overwrite_behavior" id="ai_overwrite_behavior">
							<option value="ask" <?php selected( $overwrite_behavior, 'ask' ); ?>><?php esc_html_e( 'Ask (recommended)', 'meowseo' ); ?></option>
							<option value="always" <?php selected( $overwrite_behavior, 'always' ); ?>><?php esc_html_e( 'Always overwrite', 'meowseo' ); ?></option>
							<option value="never" <?php selected( $overwrite_behavior, 'never' ); ?>><?php esc_html_e( 'Never overwrite', 'meowseo' ); ?></option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Controls whether generated content overwrites existing metadata:', 'meowseo' ); ?><br>
							<strong><?php esc_html_e( 'Ask:', 'meowseo' ); ?></strong> <?php esc_html_e( 'Show checkboxes for each field', 'meowseo' ); ?><br>
							<strong><?php esc_html_e( 'Always:', 'meowseo' ); ?></strong> <?php esc_html_e( 'Overwrite all fields', 'meowseo' ); ?><br>
							<strong><?php esc_html_e( 'Never:', 'meowseo' ); ?></strong> <?php esc_html_e( 'Skip fields with existing values', 'meowseo' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="ai_output_language"><?php esc_html_e( 'Output Language', 'meowseo' ); ?></label></th>
					<td>
						<select name="ai_output_language" id="ai_output_language">
							<option value="auto-detect" <?php selected( $output_language, 'auto-detect' ); ?>><?php esc_html_e( 'Auto-detect (recommended)', 'meowseo' ); ?></option>
							<option value="english" <?php selected( $output_language, 'english' ); ?>><?php esc_html_e( 'English', 'meowseo' ); ?></option>
							<option value="indonesian" <?php selected( $output_language, 'indonesian' ); ?>><?php esc_html_e( 'Indonesian', 'meowseo' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Language for generated content. Auto-detect will use the post\'s language.', 'meowseo' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="ai_custom_instructions"><?php esc_html_e( 'Custom Instructions', 'meowseo' ); ?></label></th>
					<td>
						<textarea
							name="ai_custom_instructions"
							id="ai_custom_instructions"
							rows="4"
							class="large-text"
							maxlength="500"
							placeholder="<?php esc_attr_e( 'E.g., Write in a professional tone, focus on technical accuracy, include statistics...', 'meowseo' ); ?>"
						><?php echo esc_textarea( $custom_instructions ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Additional instructions to guide AI generation. These will be included in all generation prompts.', 'meowseo' ); ?><br>
							<span id="ai_custom_instructions_count">
								<?php
								printf(
									/* translators: %1$d is current length, %2$d is max length */
									esc_html__( '%1$d / %2$d characters', 'meowseo' ),
									intval( $custom_instructions_length ),
									500
								);
								?>
							</span>
						</p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render image settings section.
	 *
	 * Displays image generation toggle, visual style dropdown,
	 * color palette hint field, and save to media library checkbox.
	 * Requirements: 16.1-16.5
	 *
	 * @return void
	 */
	private function render_image_settings_section(): void {
		$image_generation_enabled = $this->options->get( 'ai_image_generation_enabled', true );
		$visual_style = $this->options->get( 'ai_visual_style', 'professional' );
		$color_palette_hint = $this->options->get( 'ai_color_palette_hint', '' );
		$save_to_media_library = $this->options->get( 'ai_save_to_media_library', true );

		$style_options = array(
			'professional'    => __( 'Professional', 'meowseo' ),
			'modern'          => __( 'Modern', 'meowseo' ),
			'minimal'         => __( 'Minimal', 'meowseo' ),
			'illustrative'    => __( 'Illustrative', 'meowseo' ),
			'photography'     => __( 'Photography-style', 'meowseo' ),
		);
		?>
		<div class="meowseo-ai-section">
			<h3><?php esc_html_e( 'Image Generation Settings', 'meowseo' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure how AI-generated featured images are created and saved.', 'meowseo' ); ?></p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Image Generation', 'meowseo' ); ?></th>
					<td>
						<label>
							<input
								type="checkbox"
								name="ai_image_generation_enabled"
								value="1"
								<?php checked( $image_generation_enabled ); ?>
							>
							<?php esc_html_e( 'Enable AI image generation', 'meowseo' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Generate featured images using AI providers that support image generation (OpenAI DALL-E, Google Imagen).', 'meowseo' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="ai_visual_style"><?php esc_html_e( 'Visual Style', 'meowseo' ); ?></label></th>
					<td>
						<select name="ai_visual_style" id="ai_visual_style">
							<?php foreach ( $style_options as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $visual_style, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Preferred visual style for generated images. This influences the appearance and tone of generated images.', 'meowseo' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="ai_color_palette_hint"><?php esc_html_e( 'Color Palette Hint', 'meowseo' ); ?></label></th>
					<td>
						<input
							type="text"
							name="ai_color_palette_hint"
							id="ai_color_palette_hint"
							value="<?php echo esc_attr( $color_palette_hint ); ?>"
							class="regular-text"
							placeholder="<?php esc_attr_e( 'E.g., blue and white, warm earth tones, vibrant neon', 'meowseo' ); ?>"
						>
						<p class="description"><?php esc_html_e( 'Optional color preferences for generated images. Examples: "blue and white", "warm earth tones", "vibrant neon".', 'meowseo' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Media Library', 'meowseo' ); ?></th>
					<td>
						<label>
							<input
								type="checkbox"
								name="ai_save_to_media_library"
								value="1"
								<?php checked( $save_to_media_library ); ?>
							>
							<?php esc_html_e( 'Save generated images to media library', 'meowseo' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Generated images will be saved to your WordPress media library and can be reused.', 'meowseo' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}
