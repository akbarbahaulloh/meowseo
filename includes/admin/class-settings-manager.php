<?php
/**
 * Settings_Manager class for MeowSEO plugin.
 *
 * Handles rendering of tabbed settings interface with validation, sanitization,
 * and logging of settings changes.
 *
 * @package MeowSEO
 * @subpackage MeowSEO\Admin
 * @since 1.0.0
 */

namespace MeowSEO\Admin;

use MeowSEO\Options;
use MeowSEO\Module_Manager;
use MeowSEO\Helpers\Logger;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings_Manager class
 *
 * Renders tabbed settings interface with validation and sanitization.
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
 *
 * @since 1.0.0
 */
class Settings_Manager {

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Module_Manager instance
	 *
	 * @since 1.0.0
	 * @var Module_Manager
	 */
	private Module_Manager $module_manager;

	/**
	 * Settings tabs definition
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private array $tabs;

	/**
	 * Validation errors
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private array $errors = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options        $options        Options instance.
	 * @param Module_Manager $module_manager Module_Manager instance.
	 */
	public function __construct( Options $options, Module_Manager $module_manager ) {
		$this->options        = $options;
		$this->module_manager = $module_manager;

		$this->tabs = array(
			'general'         => array(
				'title'  => __( 'General', 'meowseo' ),
				'icon'   => 'dashicons-admin-settings',
				'method' => 'render_general_tab',
			),
			'organization'    => array(
				'title'  => __( 'Organization', 'meowseo' ),
				'icon'   => 'dashicons-building',
				'method' => 'render_organization_tab',
			),
			'social-profiles' => array(
				'title'  => __( 'Social Profiles', 'meowseo' ),
				'icon'   => 'dashicons-share',
				'method' => 'render_social_profiles_tab',
			),
			'modules'         => array(
				'title'  => __( 'Modules', 'meowseo' ),
				'icon'   => 'dashicons-admin-plugins',
				'method' => 'render_modules_tab',
			),
			'advanced'        => array(
				'title'  => __( 'Advanced', 'meowseo' ),
				'icon'   => 'dashicons-admin-tools',
				'method' => 'render_advanced_tab',
			),
			'breadcrumbs'     => array(
				'title'  => __( 'Breadcrumbs', 'meowseo' ),
				'icon'   => 'dashicons-admin-links',
				'method' => 'render_breadcrumbs_tab',
			),
		);
	}

	/**
	 * Render settings tabs navigation
	 *
	 * Requirements: 4.1, 4.3, 4.4
	 *
	 * @since 1.0.0
	 * @param string $active_tab Currently active tab slug.
	 * @return void
	 */
	public function render_settings_tabs( string $active_tab = 'general' ): void {
		?>
		<div class="meowseo-settings-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings tabs', 'meowseo' ); ?>">
			<?php foreach ( $this->tabs as $tab_slug => $tab_config ) : ?>
				<button
					type="button"
					role="tab"
					class="meowseo-tab-button <?php echo $active_tab === $tab_slug ? 'active' : ''; ?>"
					id="meowseo-tab-<?php echo esc_attr( $tab_slug ); ?>"
					aria-selected="<?php echo $active_tab === $tab_slug ? 'true' : 'false'; ?>"
					aria-controls="meowseo-tabpanel-<?php echo esc_attr( $tab_slug ); ?>"
					data-tab="<?php echo esc_attr( $tab_slug ); ?>"
				>
					<span class="<?php echo esc_attr( $tab_config['icon'] ); ?> meowseo-tab-icon"></span>
					<span class="meowseo-tab-title"><?php echo esc_html( $tab_config['title'] ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
		<?php
		$this->render_tab_switching_script();
	}

	/**
	 * Render tab switching JavaScript
	 *
	 * Requirement: 4.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_switching_script(): void {
		?>
		<script>
		(function() {
			document.addEventListener('DOMContentLoaded', function() {
				var tabs = document.querySelectorAll('.meowseo-tab-button');
				var panels = document.querySelectorAll('.meowseo-tab-panel');

				tabs.forEach(function(tab) {
					tab.addEventListener('click', function() {
						var targetTab = this.getAttribute('data-tab');
						tabs.forEach(function(t) {
							t.classList.remove('active');
							t.setAttribute('aria-selected', 'false');
						});
						this.classList.add('active');
						this.setAttribute('aria-selected', 'true');
						panels.forEach(function(panel) {
							if (panel.getAttribute('data-tab') === targetTab) {
								panel.classList.add('active');
								panel.removeAttribute('hidden');
							} else {
								panel.classList.remove('active');
								panel.setAttribute('hidden', '');
							}
						});
						if (history.pushState) {
							history.pushState(null, null, '#tab-' + targetTab);
						}
					});
				});

				var hash = window.location.hash.replace('#tab-', '');
				if (hash) {
					var targetTab = document.querySelector('[data-tab="' + hash + '"]');
					if (targetTab) {
						targetTab.click();
					}
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * Render settings form wrapper
	 *
	 * Requirements: 4.2, 28.1, 28.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_form(): void {
		$active_tab = 'general';
		if ( isset( $_GET['tab'] ) ) {
			$tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
			if ( isset( $this->tabs[ $tab ] ) ) {
				$active_tab = $tab;
			}
		}

		// Generate unique nonce for this settings page (Requirement 28.5).
		$nonce_action = 'meowseo_settings_' . $active_tab . '_save';

		$this->render_admin_notices();
		?>
		<div class="meowseo-settings-container">
			<?php $this->render_settings_tabs( $active_tab ); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="meowseo-settings-form" id="meowseo-settings-form">
				<?php wp_nonce_field( $nonce_action, 'meowseo_settings_nonce' ); ?>
				<input type="hidden" name="action" value="meowseo_save_settings">
				<input type="hidden" name="meowseo_active_tab" value="<?php echo esc_attr( $active_tab ); ?>">
				<input type="hidden" name="meowseo_nonce_action" value="<?php echo esc_attr( $nonce_action ); ?>">

				<div class="meowseo-tab-panels">
					<?php foreach ( $this->tabs as $tab_slug => $tab_config ) : ?>
						<div
							class="meowseo-tab-panel <?php echo $active_tab === $tab_slug ? 'active' : ''; ?>"
							id="meowseo-tabpanel-<?php echo esc_attr( $tab_slug ); ?>"
							role="tabpanel"
							aria-labelledby="meowseo-tab-<?php echo esc_attr( $tab_slug ); ?>"
							data-tab="<?php echo esc_attr( $tab_slug ); ?>"
							<?php echo $active_tab !== $tab_slug ? 'hidden' : ''; ?>
						>
							<?php
							if ( method_exists( $this, $tab_config['method'] ) ) {
								call_user_func( array( $this, $tab_config['method'] ) );
							}
							?>
						</div>
					<?php endforeach; ?>
				</div>

				<?php submit_button( __( 'Save Settings', 'meowseo' ), 'primary', 'meowseo_submit', true ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render admin notices
	 *
	 * Requirements: 4.6, 4.7
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_admin_notices(): void {
		if ( isset( $_GET['meowseo_settings_saved'] ) && '1' === $_GET['meowseo_settings_saved'] ) {
			echo '<div class="notice notice-success is-dismissible" role="alert"><p>' . esc_html__( 'Settings saved successfully.', 'meowseo' ) . '</p></div>';
		}

		if ( isset( $_GET['meowseo_settings_error'] ) ) {
			$error_message = sanitize_text_field( wp_unslash( $_GET['meowseo_settings_error'] ) );
			echo '<div class="notice notice-error is-dismissible" role="alert"><p>' . esc_html( $error_message ) . '</p></div>';
		}

		$errors = get_transient( 'meowseo_settings_errors' );
		if ( $errors && is_array( $errors ) ) {
			delete_transient( 'meowseo_settings_errors' );
			foreach ( $errors as $field => $message ) {
				echo '<div class="notice notice-error is-dismissible" role="alert"><p><strong>' . esc_html( $field ) . ':</strong> ' . esc_html( $message ) . '</p></div>';
			}
		}
	}

	/**
	 * Render General settings tab
	 *
	 * Requirements: 5.1, 5.2, 5.3
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_general_tab(): void {
		$homepage_title         = $this->options->get( 'homepage_title', '' );
		$homepage_description   = $this->options->get( 'homepage_description', '' );
		$separator              = $this->options->get( 'separator', '|' );
		$title_pattern_post     = $this->options->get( 'title_pattern_post', '%title% %sep% %sitename%' );
		$title_pattern_page     = $this->options->get( 'title_pattern_page', '%title% %sep% %sitename%' );
		$title_pattern_category = $this->options->get( 'title_pattern_category', '%title% %sep% %sitename%' );
		$title_pattern_tag      = $this->options->get( 'title_pattern_tag', '%title% %sep% %sitename%' );
		$title_pattern_archive  = $this->options->get( 'title_pattern_archive', '%title% %sep% %sitename%' );
		$title_pattern_search   = $this->options->get( 'title_pattern_search', 'Search Results for %search_query% %sep% %sitename%' );

		$separators = array(
			'|' => __( 'Pipe (|)', 'meowseo' ),
			'-' => __( 'Hyphen (-)', 'meowseo' ),
			'–' => __( 'En Dash (–)', 'meowseo' ),
			'—' => __( 'Em Dash (—)', 'meowseo' ),
			'·' => __( 'Middle Dot (·)', 'meowseo' ),
			'•' => __( 'Bullet (•)', 'meowseo' ),
		);

		$pattern_variables = array( '%title%', '%sitename%', '%sep%', '%page%', '%category%', '%date%', '%search_query%' );
		?>
		<h2><?php esc_html_e( 'General Settings', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure homepage SEO and title patterns for your site.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Homepage Settings', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><label for="homepage_title"><?php esc_html_e( 'Homepage Title', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="homepage_title" id="homepage_title" value="<?php echo esc_attr( $homepage_title ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Custom title for your homepage. Leave empty to use the site title.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="homepage_description"><?php esc_html_e( 'Homepage Description', 'meowseo' ); ?></label></th>
				<td>
					<textarea name="homepage_description" id="homepage_description" rows="3" class="large-text"><?php echo esc_textarea( $homepage_description ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Meta description for your homepage. Recommended length: 150-160 characters.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="separator"><?php esc_html_e( 'Title Separator', 'meowseo' ); ?></label></th>
				<td>
					<select name="separator" id="separator">
						<?php foreach ( $separators as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $separator, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Character used to separate title parts.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Title Patterns', 'meowseo' ); ?></h3></th></tr>
			<tr><th scope="row" colspan="2"><p class="description"><?php esc_html_e( 'Available pattern variables:', 'meowseo' ); ?> <code><?php echo implode( '</code>, <code>', $pattern_variables ); ?></code></p></th></tr>
			<tr>
				<th scope="row"><label for="title_pattern_post"><?php esc_html_e( 'Posts', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_post" id="title_pattern_post" value="<?php echo esc_attr( $title_pattern_post ); ?>" class="regular-text" data-preview="post"><p class="description meowseo-title-preview" data-for="title_pattern_post"></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="title_pattern_page"><?php esc_html_e( 'Pages', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_page" id="title_pattern_page" value="<?php echo esc_attr( $title_pattern_page ); ?>" class="regular-text" data-preview="page"><p class="description meowseo-title-preview" data-for="title_pattern_page"></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="title_pattern_category"><?php esc_html_e( 'Categories', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_category" id="title_pattern_category" value="<?php echo esc_attr( $title_pattern_category ); ?>" class="regular-text" data-preview="category"><p class="description meowseo-title-preview" data-for="title_pattern_category"></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="title_pattern_tag"><?php esc_html_e( 'Tags', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_tag" id="title_pattern_tag" value="<?php echo esc_attr( $title_pattern_tag ); ?>" class="regular-text" data-preview="tag"><p class="description meowseo-title-preview" data-for="title_pattern_tag"></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="title_pattern_archive"><?php esc_html_e( 'Archives', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_archive" id="title_pattern_archive" value="<?php echo esc_attr( $title_pattern_archive ); ?>" class="regular-text" data-preview="archive"><p class="description meowseo-title-preview" data-for="title_pattern_archive"></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="title_pattern_search"><?php esc_html_e( 'Search Results', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_search" id="title_pattern_search" value="<?php echo esc_attr( $title_pattern_search ); ?>" class="regular-text" data-preview="search"><p class="description meowseo-title-preview" data-for="title_pattern_search"></p></td>
			</tr>
		</table>

		<?php $this->render_archive_patterns_section(); ?>
		<?php
		$this->render_title_preview_script();
		$this->render_archive_pattern_preview_script();
	}

	/**
	 * Render title pattern preview JavaScript
	 *
	 * Requirements: 5.4, 5.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_title_preview_script(): void {
		$separator = $this->options->get( 'separator', '|' );
		$sitename  = get_bloginfo( 'name' );
		?>
		<script>
		(function() {
			var separator = <?php echo wp_json_encode( $separator ); ?>;
			var sitename = <?php echo wp_json_encode( $sitename ); ?>;
			var exampleTitles = { post: 'Example Post Title', page: 'Example Page Title', category: 'Example Category', tag: 'Example Tag', archive: 'Author Name', search: 'wordpress' };
			function updatePreview(input) {
				var pattern = input.value;
				var type = input.getAttribute('data-preview');
				var preview = document.querySelector('.meowseo-title-preview[data-for="' + input.id + '"]');
				if (!preview) return;
				var title = exampleTitles[type] || 'Example Title';
				var rendered = pattern.replace(/%title%/g, title).replace(/%sitename%/g, sitename).replace(/%sep%/g, separator).replace(/%page%/g, 'Page 2').replace(/%category%/g, 'Category Name').replace(/%date%/g, 'January 1, 2024').replace(/%search_query%/g, exampleTitles.search);
				preview.textContent = '<?php esc_html_e( 'Preview:', 'meowseo' ); ?> ' + rendered;
			}
			document.addEventListener('DOMContentLoaded', function() {
				var inputs = document.querySelectorAll('[data-preview]');
				inputs.forEach(function(input) {
					updatePreview(input);
					input.addEventListener('input', function() { updatePreview(this); });
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * Render Archive Patterns section
	 *
	 * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12, 5.13, 5.14, 5.15, 5.16
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_archive_patterns_section(): void {
		// Get stored patterns from options
		$title_patterns = $this->options->get( 'title_patterns', array() );
		
		// Archive pattern types
		$archive_types = array(
			'category_archive' => __( 'Category Archives', 'meowseo' ),
			'tag_archive' => __( 'Tag Archives', 'meowseo' ),
			'custom_taxonomy_archive' => __( 'Custom Taxonomy Archives', 'meowseo' ),
			'author_page' => __( 'Author Pages', 'meowseo' ),
			'search_results' => __( 'Search Results', 'meowseo' ),
			'date_archive' => __( 'Date Archives', 'meowseo' ),
			'404_page' => __( '404 Pages', 'meowseo' ),
			'homepage' => __( 'Homepage', 'meowseo' ),
		);

		// Default patterns (using %% syntax for UI)
		$default_patterns = array(
			'category_archive' => array(
				'title' => '%%category%% Archives %%sep%% %%sitename%%',
				'description' => 'Browse all posts in %%category%%',
			),
			'tag_archive' => array(
				'title' => '%%tag%% Tag %%sep%% %%sitename%%',
				'description' => 'Posts tagged with %%tag%%',
			),
			'custom_taxonomy_archive' => array(
				'title' => '%%term%% %%sep%% %%sitename%%',
				'description' => 'Browse all posts in %%term%%',
			),
			'author_page' => array(
				'title' => '%%name%% %%sep%% %%sitename%%',
				'description' => 'Posts by %%name%%',
			),
			'search_results' => array(
				'title' => 'Search Results for %%searchphrase%% %%sep%% %%sitename%%',
				'description' => 'Search results for %%searchphrase%%',
			),
			'date_archive' => array(
				'title' => '%%date%% Archives %%sep%% %%sitename%%',
				'description' => 'Posts from %%date%%',
			),
			'404_page' => array(
				'title' => 'Page Not Found %%sep%% %%sitename%%',
				'description' => 'The page you are looking for could not be found',
			),
			'homepage' => array(
				'title' => '%%sitename%% %%sep%% %%tagline%%',
				'description' => '',
			),
		);

		// Available variables
		$pattern_variables = array(
			'%%category%%' => __( 'Category name', 'meowseo' ),
			'%%tag%%' => __( 'Tag name', 'meowseo' ),
			'%%term%%' => __( 'Taxonomy term name', 'meowseo' ),
			'%%date%%' => __( 'Archive date', 'meowseo' ),
			'%%name%%' => __( 'Author display name', 'meowseo' ),
			'%%searchphrase%%' => __( 'Search query', 'meowseo' ),
			'%%posttype%%' => __( 'Post type label', 'meowseo' ),
			'%%sep%%' => __( 'Separator', 'meowseo' ),
			'%%page%%' => __( 'Page number', 'meowseo' ),
			'%%sitename%%' => __( 'Site name', 'meowseo' ),
			'%%title%%' => __( 'Archive title', 'meowseo' ),
		);
		?>
		<h3><?php esc_html_e( 'Archive Patterns', 'meowseo' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Configure title and description patterns for archive pages. Use variables to dynamically insert content.', 'meowseo' ); ?>
		</p>
		
		<div class="meowseo-archive-patterns-help" style="margin: 15px 0; padding: 12px; background: #f0f6fc; border-left: 4px solid #0073aa; border-radius: 3px;">
			<p style="margin: 0 0 8px 0; font-weight: 600;">
				<?php esc_html_e( 'Available Variables:', 'meowseo' ); ?>
			</p>
			<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; font-size: 12px;">
				<?php foreach ( $pattern_variables as $var => $description ) : ?>
					<div>
						<code style="background: #fff; padding: 2px 6px; border-radius: 2px;"><?php echo esc_html( $var ); ?></code>
						<span style="color: #666;"> - <?php echo esc_html( $description ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<table class="form-table" role="presentation">
			<?php foreach ( $archive_types as $type_key => $type_label ) : ?>
				<?php
				// Get stored values and convert from {} to %% syntax for display
				$title_value = isset( $title_patterns[ $type_key ]['title'] ) ? $this->convert_pattern_to_display( $title_patterns[ $type_key ]['title'] ) : '';
				$description_value = isset( $title_patterns[ $type_key ]['description'] ) ? $this->convert_pattern_to_display( $title_patterns[ $type_key ]['description'] ) : '';
				$title_placeholder = $default_patterns[ $type_key ]['title'];
				$description_placeholder = $default_patterns[ $type_key ]['description'];
				?>
				<tr>
					<th scope="row" colspan="2">
						<h4 style="margin: 20px 0 10px 0;"><?php echo esc_html( $type_label ); ?></h4>
					</th>
				</tr>
				<tr>
					<th scope="row">
						<label for="archive_pattern_<?php echo esc_attr( $type_key ); ?>_title">
							<?php esc_html_e( 'Title Pattern', 'meowseo' ); ?>
						</label>
					</th>
					<td>
						<input 
							type="text" 
							name="archive_pattern_<?php echo esc_attr( $type_key ); ?>_title" 
							id="archive_pattern_<?php echo esc_attr( $type_key ); ?>_title" 
							value="<?php echo esc_attr( $title_value ); ?>" 
							placeholder="<?php echo esc_attr( $title_placeholder ); ?>"
							class="large-text meowseo-archive-pattern-input" 
							data-archive-type="<?php echo esc_attr( $type_key ); ?>"
							data-pattern-type="title"
						>
						<p class="description meowseo-archive-pattern-preview" data-for="archive_pattern_<?php echo esc_attr( $type_key ); ?>_title"></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="archive_pattern_<?php echo esc_attr( $type_key ); ?>_description">
							<?php esc_html_e( 'Description Pattern', 'meowseo' ); ?>
						</label>
					</th>
					<td>
						<input 
							type="text" 
							name="archive_pattern_<?php echo esc_attr( $type_key ); ?>_description" 
							id="archive_pattern_<?php echo esc_attr( $type_key ); ?>_description" 
							value="<?php echo esc_attr( $description_value ); ?>" 
							placeholder="<?php echo esc_attr( $description_placeholder ); ?>"
							class="large-text meowseo-archive-pattern-input" 
							data-archive-type="<?php echo esc_attr( $type_key ); ?>"
							data-pattern-type="description"
						>
						<p class="description meowseo-archive-pattern-preview" data-for="archive_pattern_<?php echo esc_attr( $type_key ); ?>_description"></p>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Render archive pattern preview JavaScript
	 *
	 * Requirements: 5.4, 5.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_archive_pattern_preview_script(): void {
		$separator = $this->options->get( 'separator', '|' );
		$sitename  = get_bloginfo( 'name' );
		$tagline   = get_bloginfo( 'description' );
		?>
		<script>
		(function() {
			var separator = <?php echo wp_json_encode( $separator ); ?>;
			var sitename = <?php echo wp_json_encode( $sitename ); ?>;
			var tagline = <?php echo wp_json_encode( $tagline ); ?>;
			
			// Example data for each archive type
			var exampleData = {
				'category_archive': {
					'%%category%%': 'Technology',
					'%%term%%': 'Technology',
					'%%title%%': 'Technology'
				},
				'tag_archive': {
					'%%tag%%': 'WordPress',
					'%%term%%': 'WordPress',
					'%%title%%': 'WordPress'
				},
				'custom_taxonomy_archive': {
					'%%term%%': 'Portfolio',
					'%%title%%': 'Portfolio'
				},
				'author_page': {
					'%%name%%': 'John Doe',
					'%%title%%': 'John Doe'
				},
				'search_results': {
					'%%searchphrase%%': 'wordpress seo',
					'%%title%%': 'Search Results'
				},
				'date_archive': {
					'%%date%%': 'January 2024',
					'%%title%%': 'January 2024'
				},
				'404_page': {
					'%%title%%': 'Page Not Found'
				},
				'homepage': {
					'%%title%%': sitename
				}
			};
			
			function updateArchivePreview(input) {
				var pattern = input.value;
				var archiveType = input.getAttribute('data-archive-type');
				var preview = document.querySelector('.meowseo-archive-pattern-preview[data-for="' + input.id + '"]');
				
				if (!preview) return;
				
				// Use placeholder if input is empty
				if (!pattern) {
					pattern = input.getAttribute('placeholder');
				}
				
				// Get example data for this archive type
				var data = exampleData[archiveType] || {};
				
				// Replace variables
				var rendered = pattern;
				
				// Replace archive-specific variables
				for (var variable in data) {
					rendered = rendered.replace(new RegExp(variable.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), data[variable]);
				}
				
				// Replace common variables
				rendered = rendered.replace(/%%sitename%%/g, sitename);
				rendered = rendered.replace(/%%sep%%/g, separator);
				rendered = rendered.replace(/%%page%%/g, '2');
				rendered = rendered.replace(/%%posttype%%/g, 'Posts');
				rendered = rendered.replace(/%%tagline%%/g, tagline);
				
				preview.textContent = '<?php esc_html_e( 'Preview:', 'meowseo' ); ?> ' + rendered;
			}
			
			document.addEventListener('DOMContentLoaded', function() {
				var inputs = document.querySelectorAll('.meowseo-archive-pattern-input');
				inputs.forEach(function(input) {
					updateArchivePreview(input);
					input.addEventListener('input', function() { 
						updateArchivePreview(this); 
					});
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * Render Organization settings tab
	 *
	 * Requirements: 1.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_organization_tab(): void {
		$organization = $this->options->get( 'organization', array() );
		$organization_name = $organization['name'] ?? '';
		$organization_logo_url = $organization['logo_url'] ?? '';
		$organization_logo_width = $organization['logo_width'] ?? '';
		$organization_logo_height = $organization['logo_height'] ?? '';
		$organization_contact_email = $organization['contact_email'] ?? '';
		$social_profiles = $organization['social_profiles'] ?? array();
		$facebook_url = $social_profiles['facebook'] ?? '';
		$twitter_url = $social_profiles['twitter'] ?? '';
		$instagram_url = $social_profiles['instagram'] ?? '';
		$linkedin_url = $social_profiles['linkedin'] ?? '';
		$youtube_url = $social_profiles['youtube'] ?? '';
		?>
		<h2><?php esc_html_e( 'Organization Settings', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure your organization information for schema.org structured data. This helps search engines display rich results like knowledge panels and sitelinks search box.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Organization Identity', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><label for="organization_name"><?php esc_html_e( 'Organization Name', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="organization_name" id="organization_name" value="<?php echo esc_attr( $organization_name ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'The official name of your organization or business.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="organization_contact_email"><?php esc_html_e( 'Contact Email', 'meowseo' ); ?></label></th>
				<td>
					<input type="email" name="organization_contact_email" id="organization_contact_email" value="<?php echo esc_attr( $organization_contact_email ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Customer service or general contact email address.', 'meowseo' ); ?></p>
				</td>
			</tr>

			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Organization Logo', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><label for="organization_logo_url"><?php esc_html_e( 'Logo URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="organization_logo_url" id="organization_logo_url" value="<?php echo esc_url( $organization_logo_url ); ?>" class="large-text">
					<p class="description">
						<?php esc_html_e( 'Full URL to your organization logo. Google recommends a logo that is 600px wide and 60px tall.', 'meowseo' ); ?>
						<br>
						<?php esc_html_e( 'Learn more about logo requirements:', 'meowseo' ); ?>
						<a href="https://developers.google.com/search/docs/appearance/structured-data/logo" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Google Logo Guidelines', 'meowseo' ); ?>
						</a>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="organization_logo_width"><?php esc_html_e( 'Logo Width', 'meowseo' ); ?></label></th>
				<td>
					<input type="number" name="organization_logo_width" id="organization_logo_width" value="<?php echo esc_attr( $organization_logo_width ); ?>" class="small-text" min="1" step="1">
					<span><?php esc_html_e( 'pixels', 'meowseo' ); ?></span>
					<p class="description"><?php esc_html_e( 'Width of your logo in pixels.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="organization_logo_height"><?php esc_html_e( 'Logo Height', 'meowseo' ); ?></label></th>
				<td>
					<input type="number" name="organization_logo_height" id="organization_logo_height" value="<?php echo esc_attr( $organization_logo_height ); ?>" class="small-text" min="1" step="1">
					<span><?php esc_html_e( 'pixels', 'meowseo' ); ?></span>
					<p class="description"><?php esc_html_e( 'Height of your logo in pixels.', 'meowseo' ); ?></p>
				</td>
			</tr>

			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Social Media Profiles', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<td colspan="2">
					<p class="description">
						<?php esc_html_e( 'Add your organization\'s social media profile URLs. These will be included in your Organization schema markup to help search engines understand your brand presence.', 'meowseo' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="organization_facebook_url"><?php esc_html_e( 'Facebook URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="organization_facebook_url" id="organization_facebook_url" value="<?php echo esc_url( $facebook_url ); ?>" class="regular-text" placeholder="https://facebook.com/yourpage">
					<p class="description"><?php esc_html_e( 'Full URL to your Facebook page.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="organization_twitter_url"><?php esc_html_e( 'Twitter URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="organization_twitter_url" id="organization_twitter_url" value="<?php echo esc_url( $twitter_url ); ?>" class="regular-text" placeholder="https://twitter.com/yourhandle">
					<p class="description"><?php esc_html_e( 'Full URL to your Twitter profile.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="organization_instagram_url"><?php esc_html_e( 'Instagram URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="organization_instagram_url" id="organization_instagram_url" value="<?php echo esc_url( $instagram_url ); ?>" class="regular-text" placeholder="https://instagram.com/yourprofile">
					<p class="description"><?php esc_html_e( 'Full URL to your Instagram profile.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="organization_linkedin_url"><?php esc_html_e( 'LinkedIn URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="organization_linkedin_url" id="organization_linkedin_url" value="<?php echo esc_url( $linkedin_url ); ?>" class="regular-text" placeholder="https://linkedin.com/company/yourcompany">
					<p class="description"><?php esc_html_e( 'Full URL to your LinkedIn company page or profile.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="organization_youtube_url"><?php esc_html_e( 'YouTube URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="organization_youtube_url" id="organization_youtube_url" value="<?php echo esc_url( $youtube_url ); ?>" class="regular-text" placeholder="https://youtube.com/@yourchannel">
					<p class="description"><?php esc_html_e( 'Full URL to your YouTube channel.', 'meowseo' ); ?></p>
				</td>
			</tr>
		</table>

		<div class="meowseo-schema-help" style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #0073aa; border-radius: 3px;">
			<h4 style="margin-top: 0;"><?php esc_html_e( 'About Schema.org Structured Data', 'meowseo' ); ?></h4>
			<p>
				<?php esc_html_e( 'This information is used to generate Organization and WebSite schema markup that helps search engines understand your business. This can result in:', 'meowseo' ); ?>
			</p>
			<ul style="margin-left: 20px;">
				<li><?php esc_html_e( 'Knowledge panels in Google search results', 'meowseo' ); ?></li>
				<li><?php esc_html_e( 'Sitelinks search box for your website', 'meowseo' ); ?></li>
				<li><?php esc_html_e( 'Enhanced brand visibility in search results', 'meowseo' ); ?></li>
				<li><?php esc_html_e( 'Better understanding of your organization by search engines', 'meowseo' ); ?></li>
			</ul>
			<p>
				<a href="https://schema.org/Organization" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Learn more about Organization schema', 'meowseo' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render Social Profiles settings tab
	 *
	 * Requirements: 6.1, 6.2
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_social_profiles_tab(): void {
		$social_profiles    = $this->options->get( 'meowseo_schema_social_profiles', array() );
		$facebook_url       = $social_profiles['facebook'] ?? '';
		$twitter_username   = $this->options->get( 'twitter_username', '' );
		$instagram_url      = $social_profiles['instagram'] ?? '';
		$linkedin_url       = $social_profiles['linkedin'] ?? '';
		$youtube_url        = $social_profiles['youtube'] ?? '';
		?>
		<h2><?php esc_html_e( 'Social Profiles', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure social media profiles for schema markup. These will appear in search results.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="facebook_url"><?php esc_html_e( 'Facebook URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="facebook_url" id="facebook_url" value="<?php echo esc_url( $facebook_url ); ?>" class="regular-text" placeholder="https://facebook.com/yourpage">
					<p class="description"><?php esc_html_e( 'Full URL to your Facebook page.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="twitter_username"><?php esc_html_e( 'Twitter Username', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="twitter_username" id="twitter_username" value="<?php echo esc_attr( $twitter_username ); ?>" class="regular-text" placeholder="username">
					<p class="description"><?php esc_html_e( 'Twitter username without the @ symbol.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="instagram_url"><?php esc_html_e( 'Instagram URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="instagram_url" id="instagram_url" value="<?php echo esc_url( $instagram_url ); ?>" class="regular-text" placeholder="https://instagram.com/yourprofile">
					<p class="description"><?php esc_html_e( 'Full URL to your Instagram profile.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="linkedin_url"><?php esc_html_e( 'LinkedIn URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="linkedin_url" id="linkedin_url" value="<?php echo esc_url( $linkedin_url ); ?>" class="regular-text" placeholder="https://linkedin.com/company/yourcompany">
					<p class="description"><?php esc_html_e( 'Full URL to your LinkedIn company page or profile.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="youtube_url"><?php esc_html_e( 'YouTube URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="youtube_url" id="youtube_url" value="<?php echo esc_url( $youtube_url ); ?>" class="regular-text" placeholder="https://youtube.com/@yourchannel">
					<p class="description"><?php esc_html_e( 'Full URL to your YouTube channel.', 'meowseo' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Modules settings tab
	 *
	 * Requirements: 7.1, 7.2, 7.3, 7.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_modules_tab(): void {
		$enabled_modules = $this->options->get_enabled_modules();

		$modules = array(
			'meta'          => array( 'name' => __( 'Meta Tags', 'meowseo' ), 'description' => __( 'Manage title tags, meta descriptions, and robots meta for all content types.', 'meowseo' ) ),
			'schema'        => array( 'name' => __( 'Schema Markup', 'meowseo' ), 'description' => __( 'Generate structured data (JSON-LD) for rich results in search engines.', 'meowseo' ) ),
			'sitemap'       => array( 'name' => __( 'XML Sitemaps', 'meowseo' ), 'description' => __( 'Generate XML sitemaps for better search engine crawling.', 'meowseo' ) ),
			'redirects'     => array( 'name' => __( 'Redirects', 'meowseo' ), 'description' => __( 'Manage URL redirects to prevent 404 errors and preserve SEO value.', 'meowseo' ) ),
			'monitor_404'   => array( 'name' => __( '404 Monitor', 'meowseo' ), 'description' => __( 'Track 404 errors and identify broken links on your site.', 'meowseo' ) ),
			'internal_links' => array( 'name' => __( 'Internal Links', 'meowseo' ), 'description' => __( 'Suggest internal links while editing content to improve SEO structure.', 'meowseo' ) ),
			'gsc'           => array( 'name' => __( 'Google Search Console', 'meowseo' ), 'description' => __( 'Connect to Google Search Console for search analytics and indexing.', 'meowseo' ) ),
			'social'        => array( 'name' => __( 'Social Meta', 'meowseo' ), 'description' => __( 'Add Open Graph and Twitter Card meta tags for social sharing.', 'meowseo' ) ),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$modules['woocommerce'] = array( 'name' => __( 'WooCommerce SEO', 'meowseo' ), 'description' => __( 'Enhanced SEO features for WooCommerce products and categories.', 'meowseo' ) );
		}
		?>
		<h2><?php esc_html_e( 'Modules', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Enable or disable plugin modules. Disabled modules will not load on next page load.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<?php foreach ( $modules as $module_id => $module_config ) : ?>
				<tr>
					<th scope="row"><label for="module_<?php echo esc_attr( $module_id ); ?>"><?php echo esc_html( $module_config['name'] ); ?></label></th>
					<td>
						<fieldset>
							<label for="module_<?php echo esc_attr( $module_id ); ?>">
								<input type="checkbox" name="enabled_modules[]" id="module_<?php echo esc_attr( $module_id ); ?>" value="<?php echo esc_attr( $module_id ); ?>" <?php checked( in_array( $module_id, $enabled_modules, true ) ); ?>>
							</label>
							<p class="description"><?php echo esc_html( $module_config['description'] ); ?></p>
						</fieldset>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Render Advanced settings tab
	 *
	 * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_advanced_tab(): void {
		$noindex_post_types     = $this->options->get( 'noindex_post_types', array() );
		$noindex_taxonomies     = $this->options->get( 'noindex_taxonomies', array() );
		$noindex_archives       = $this->options->get( 'noindex_archives', array() );
		$force_trailing_slash   = $this->options->get( 'canonical_force_trailing_slash', false );
		$force_https            = $this->options->get( 'canonical_force_https', false );
		$rss_before_content     = $this->options->get( 'rss_before_content', '' );
		$rss_after_content      = $this->options->get( 'rss_after_content', '' );
		$delete_on_uninstall    = $this->options->get( 'delete_on_uninstall', false );

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$archive_types = array( 'author' => __( 'Author Archives', 'meowseo' ), 'date' => __( 'Date Archives', 'meowseo' ) );
		?>
		<h2><?php esc_html_e( 'Advanced Settings', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure advanced SEO options for your site.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Noindex Settings', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Noindex Post Types', 'meowseo' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $post_types as $post_type ) : ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="noindex_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $noindex_post_types, true ) ); ?>>
								<?php echo esc_html( $post_type->label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Prevent search engines from indexing selected post types.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Noindex Taxonomies', 'meowseo' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $taxonomies as $taxonomy ) : ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="noindex_taxonomies[]" value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php checked( in_array( $taxonomy->name, $noindex_taxonomies, true ) ); ?>>
								<?php echo esc_html( $taxonomy->label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Prevent search engines from indexing selected taxonomies.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Noindex Archives', 'meowseo' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $archive_types as $archive_value => $archive_label ) : ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="noindex_archives[]" value="<?php echo esc_attr( $archive_value ); ?>" <?php checked( in_array( $archive_value, $noindex_archives, true ) ); ?>>
								<?php echo esc_html( $archive_label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Prevent search engines from indexing selected archive types.', 'meowseo' ); ?></p>
				</td>
			</tr>

			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Archive Robots', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<td colspan="2">
					<p class="description" style="margin-top: 0;">
						<?php esc_html_e( 'Configure robots meta tags for archive pages. These are global defaults that can be overridden on individual taxonomy terms.', 'meowseo' ); ?>
					</p>
					<table class="widefat" style="margin-top: 10px;">
						<thead>
							<tr>
								<th style="width: 40%;"><?php esc_html_e( 'Archive Type', 'meowseo' ); ?></th>
								<th style="width: 30%; text-align: center;"><?php esc_html_e( 'Noindex', 'meowseo' ); ?></th>
								<th style="width: 30%; text-align: center;"><?php esc_html_e( 'Nofollow', 'meowseo' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$archive_robots_types = array(
								'robots_author_archive'   => __( 'Author Archives', 'meowseo' ),
								'robots_date_archive'     => __( 'Date Archives', 'meowseo' ),
								'robots_category_archive' => __( 'Category Archives', 'meowseo' ),
								'robots_tag_archive'      => __( 'Tag Archives', 'meowseo' ),
								'robots_search_results'   => __( 'Search Results', 'meowseo' ),
								'robots_attachment'       => __( 'Media Attachments', 'meowseo' ),
							);

							// Add custom post type archives
							$post_types = get_post_types( array( 'public' => true, 'has_archive' => true ), 'objects' );
							foreach ( $post_types as $post_type ) {
								if ( ! in_array( $post_type->name, array( 'post', 'page', 'attachment' ), true ) ) {
									$archive_robots_types[ 'robots_post_type_archive_' . $post_type->name ] = sprintf(
										/* translators: %s: Post type label */
										__( '%s Archives', 'meowseo' ),
										$post_type->label
									);
								}
							}

							foreach ( $archive_robots_types as $setting_key => $label ) :
								$setting_value = $this->options->get( $setting_key, array( 'noindex' => false, 'nofollow' => false ) );
								$noindex       = isset( $setting_value['noindex'] ) ? $setting_value['noindex'] : false;
								$nofollow      = isset( $setting_value['nofollow'] ) ? $setting_value['nofollow'] : false;
								?>
								<tr>
									<td><?php echo esc_html( $label ); ?></td>
									<td style="text-align: center;">
										<input type="checkbox" 
											   name="<?php echo esc_attr( $setting_key ); ?>[noindex]" 
											   value="1" 
											   <?php checked( $noindex ); ?>
											   aria-label="<?php echo esc_attr( sprintf( __( 'Noindex %s', 'meowseo' ), $label ) ); ?>">
									</td>
									<td style="text-align: center;">
										<input type="checkbox" 
											   name="<?php echo esc_attr( $setting_key ); ?>[nofollow]" 
											   value="1" 
											   <?php checked( $nofollow ); ?>
											   aria-label="<?php echo esc_attr( sprintf( __( 'Nofollow %s', 'meowseo' ), $label ) ); ?>">
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</td>
			</tr>

			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Canonical URL Settings', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Force Trailing Slash', 'meowseo' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="canonical_force_trailing_slash" value="1" <?php checked( $force_trailing_slash ); ?>>
						<?php esc_html_e( 'Add trailing slash to all URLs', 'meowseo' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Ensures all canonical URLs end with a trailing slash.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Force HTTPS', 'meowseo' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="canonical_force_https" value="1" <?php checked( $force_https ); ?>>
						<?php esc_html_e( 'Force HTTPS for all canonical URLs', 'meowseo' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Ensures all canonical URLs use HTTPS protocol.', 'meowseo' ); ?></p>
				</td>
			</tr>

			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'RSS Feed Settings', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><label for="rss_before_content"><?php esc_html_e( 'Content Before Posts', 'meowseo' ); ?></label></th>
				<td>
					<textarea name="rss_before_content" id="rss_before_content" rows="2" class="large-text"><?php echo esc_textarea( $rss_before_content ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Content to add before each post in RSS feeds. HTML allowed.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rss_after_content"><?php esc_html_e( 'Content After Posts', 'meowseo' ); ?></label></th>
				<td>
					<textarea name="rss_after_content" id="rss_after_content" rows="2" class="large-text"><?php echo esc_textarea( $rss_after_content ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Content to add after each post in RSS feeds. HTML allowed.', 'meowseo' ); ?></p>
				</td>
			</tr>

			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Data Settings', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Delete on Uninstall', 'meowseo' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( $delete_on_uninstall ); ?>>
						<?php esc_html_e( 'Delete all plugin data on uninstall', 'meowseo' ); ?>
					</label>
					<p class="description" style="color: #d63638;"><?php esc_html_e( 'Warning: This will permanently delete all settings, redirects, and 404 logs when the plugin is uninstalled.', 'meowseo' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Breadcrumbs settings tab
	 *
	 * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_breadcrumbs_tab(): void {
		$breadcrumbs_enabled           = $this->options->get( 'breadcrumbs_enabled', false );
		$breadcrumbs_separator         = $this->options->get( 'breadcrumbs_separator', ' &gt; ' );
		$breadcrumbs_home_label        = $this->options->get( 'breadcrumbs_home_label', __( 'Home', 'meowseo' ) );
		$breadcrumbs_prefix            = $this->options->get( 'breadcrumbs_prefix', '' );
		$breadcrumbs_position          = $this->options->get( 'breadcrumbs_position', 'before_content' );
		$breadcrumbs_show_on_post_types = $this->options->get( 'breadcrumbs_show_on_post_types', array( 'post', 'page' ) );
		$breadcrumbs_show_on_taxonomies = $this->options->get( 'breadcrumbs_show_on_taxonomies', array( 'category', 'post_tag' ) );

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		$position_options = array(
			'before_content' => __( 'Before Content', 'meowseo' ),
			'after_content'  => __( 'After Content', 'meowseo' ),
			'manual'         => __( 'Manual (use shortcode or PHP)', 'meowseo' ),
		);
		?>
		<h2><?php esc_html_e( 'Breadcrumbs Settings', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure breadcrumb navigation for your site.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Breadcrumbs', 'meowseo' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="breadcrumbs_enabled" value="1" <?php checked( $breadcrumbs_enabled ); ?>>
						<?php esc_html_e( 'Enable breadcrumb navigation', 'meowseo' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="breadcrumbs_separator"><?php esc_html_e( 'Separator', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="breadcrumbs_separator" id="breadcrumbs_separator" value="<?php echo esc_attr( $breadcrumbs_separator ); ?>" class="small-text">
					<p class="description"><?php esc_html_e( 'Character(s) used to separate breadcrumb items. Example: &gt; / »', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="breadcrumbs_home_label"><?php esc_html_e( 'Home Label', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="breadcrumbs_home_label" id="breadcrumbs_home_label" value="<?php echo esc_attr( $breadcrumbs_home_label ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Label for the home breadcrumb item.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="breadcrumbs_prefix"><?php esc_html_e( 'Prefix', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="breadcrumbs_prefix" id="breadcrumbs_prefix" value="<?php echo esc_attr( $breadcrumbs_prefix ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Text to display before the breadcrumb trail. Example: "You are here:"', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="breadcrumbs_position"><?php esc_html_e( 'Position', 'meowseo' ); ?></label></th>
				<td>
					<select name="breadcrumbs_position" id="breadcrumbs_position">
						<?php foreach ( $position_options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $breadcrumbs_position, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Where to display breadcrumbs on the page.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show on Post Types', 'meowseo' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $post_types as $post_type ) : ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="breadcrumbs_show_on_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $breadcrumbs_show_on_post_types, true ) ); ?>>
								<?php echo esc_html( $post_type->label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Show breadcrumbs on selected post types.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show on Taxonomies', 'meowseo' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $taxonomies as $taxonomy ) : ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="breadcrumbs_show_on_taxonomies[]" value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php checked( in_array( $taxonomy->name, $breadcrumbs_show_on_taxonomies, true ) ); ?>>
								<?php echo esc_html( $taxonomy->label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Show breadcrumbs on selected taxonomy archives.', 'meowseo' ); ?></p>
				</td>
			</tr>
		</table>

		<?php if ( $breadcrumbs_enabled ) : ?>
		<div class="meowseo-breadcrumb-example" style="margin-top: 20px; padding: 15px; background: #f7f7f7; border: 1px solid #ddd; border-radius: 4px;">
			<h4><?php esc_html_e( 'Example Output', 'meowseo' ); ?></h4>
			<p class="description">
				<?php
				$example = array();
				if ( $breadcrumbs_prefix ) {
					$example[] = esc_html( $breadcrumbs_prefix );
				}
				$example[] = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( $breadcrumbs_home_label ) . '</a>';
				$example[] = '<a href="#">' . esc_html__( 'Category', 'meowseo' ) . '</a>';
				$example[] = esc_html__( 'Example Post', 'meowseo' );
				echo implode( esc_html( $breadcrumbs_separator ), $example );
				?>
			</p>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Validate settings
	 *
	 * Validates all settings and returns validated array or WP_Error.
	 * Requirements: 4.5, 4.6, 30.1, 30.2, 30.3, 30.4
	 *
	 * @since 1.0.0
	 * @param array $settings Settings to validate.
	 * @return array|\WP_Error Validated settings or WP_Error on failure.
	 */
	public function validate_settings( array $settings ) {
		$this->errors = array();
		$validated    = array();

		// Validate homepage title.
		if ( isset( $settings['homepage_title'] ) ) {
			$validated['homepage_title'] = sanitize_text_field( $settings['homepage_title'] );
		}

		// Validate homepage description.
		if ( isset( $settings['homepage_description'] ) ) {
			$validated['homepage_description'] = sanitize_textarea_field( $settings['homepage_description'] );
		}

		// Validate separator.
		if ( isset( $settings['separator'] ) ) {
			$valid_separators = array( '|', '-', '–', '—', '·', '•' );
			if ( in_array( $settings['separator'], $valid_separators, true ) ) {
				$validated['separator'] = $settings['separator'];
			} else {
				$this->errors['separator'] = __( 'Invalid separator selected.', 'meowseo' );
			}
		}

		// Validate title patterns.
		$title_pattern_fields = array(
			'title_pattern_post',
			'title_pattern_page',
			'title_pattern_category',
			'title_pattern_tag',
			'title_pattern_archive',
			'title_pattern_search',
		);

		foreach ( $title_pattern_fields as $field ) {
			if ( isset( $settings[ $field ] ) ) {
				$validated[ $field ] = sanitize_text_field( $settings[ $field ] );
			}
		}

		// Validate social URLs.
		$social_url_fields = array( 'facebook_url', 'instagram_url', 'linkedin_url', 'youtube_url' );
		$social_profiles   = array();

		foreach ( $social_url_fields as $field ) {
			if ( isset( $settings[ $field ] ) && ! empty( $settings[ $field ] ) ) {
				$sanitized_url = $this->sanitize_social_url( $settings[ $field ] );
				if ( is_wp_error( $sanitized_url ) ) {
					$this->errors[ $field ] = $sanitized_url->get_error_message();
				} else {
					$network = str_replace( '_url', '', $field );
					if ( ! empty( $sanitized_url ) ) {
						$social_profiles[ $network ] = $sanitized_url;
					}
				}
			}
		}

		if ( ! empty( $social_profiles ) ) {
			$validated['meowseo_schema_social_profiles'] = $social_profiles;
		}

		// Validate Twitter username.
		if ( isset( $settings['twitter_username'] ) ) {
			$twitter_username = sanitize_text_field( $settings['twitter_username'] );
			// Remove @ if present.
			$twitter_username = ltrim( $twitter_username, '@' );
			$validated['twitter_username'] = $twitter_username;
		}

		// Validate organization settings (Requirement 1.4).
		$organization = array();
		
		// Validate organization name.
		if ( isset( $settings['organization_name'] ) && ! empty( $settings['organization_name'] ) ) {
			$organization['name'] = sanitize_text_field( $settings['organization_name'] );
		}
		
		// Validate organization contact email.
		if ( isset( $settings['organization_contact_email'] ) && ! empty( $settings['organization_contact_email'] ) ) {
			$email = sanitize_email( $settings['organization_contact_email'] );
			if ( is_email( $email ) ) {
				$organization['contact_email'] = $email;
			} else {
				$this->errors['organization_contact_email'] = __( 'Please enter a valid email address.', 'meowseo' );
			}
		}
		
		// Validate organization logo URL.
		if ( isset( $settings['organization_logo_url'] ) && ! empty( $settings['organization_logo_url'] ) ) {
			$logo_url = esc_url_raw( trim( $settings['organization_logo_url'] ) );
			if ( filter_var( $logo_url, FILTER_VALIDATE_URL ) ) {
				$organization['logo_url'] = $logo_url;
			} else {
				$this->errors['organization_logo_url'] = __( 'Please enter a valid logo URL.', 'meowseo' );
			}
		}
		
		// Validate organization logo width.
		if ( isset( $settings['organization_logo_width'] ) && ! empty( $settings['organization_logo_width'] ) ) {
			$logo_width = absint( $settings['organization_logo_width'] );
			if ( $logo_width > 0 ) {
				$organization['logo_width'] = $logo_width;
			} else {
				$this->errors['organization_logo_width'] = __( 'Logo width must be a positive number.', 'meowseo' );
			}
		}
		
		// Validate organization logo height.
		if ( isset( $settings['organization_logo_height'] ) && ! empty( $settings['organization_logo_height'] ) ) {
			$logo_height = absint( $settings['organization_logo_height'] );
			if ( $logo_height > 0 ) {
				$organization['logo_height'] = $logo_height;
			} else {
				$this->errors['organization_logo_height'] = __( 'Logo height must be a positive number.', 'meowseo' );
			}
		}
		
		// Validate organization social profiles.
		$social_profiles = array();
		$social_url_fields = array(
			'organization_facebook_url' => 'facebook',
			'organization_twitter_url' => 'twitter',
			'organization_instagram_url' => 'instagram',
			'organization_linkedin_url' => 'linkedin',
			'organization_youtube_url' => 'youtube',
		);
		
		foreach ( $social_url_fields as $field => $network ) {
			if ( isset( $settings[ $field ] ) && ! empty( $settings[ $field ] ) ) {
				$sanitized_url = $this->sanitize_social_url( $settings[ $field ] );
				if ( is_wp_error( $sanitized_url ) ) {
					$this->errors[ $field ] = $sanitized_url->get_error_message();
				} else {
					$social_profiles[ $network ] = $sanitized_url;
				}
			}
		}
		
		if ( ! empty( $social_profiles ) ) {
			$organization['social_profiles'] = $social_profiles;
		}
		
		// Save organization settings if any fields are set.
		if ( ! empty( $organization ) ) {
			$validated['organization'] = $organization;
		}

		// Validate enabled modules.
		if ( isset( $settings['enabled_modules'] ) && is_array( $settings['enabled_modules'] ) ) {
			$valid_modules = array( 'meta', 'schema', 'sitemap', 'redirects', 'monitor_404', 'internal_links', 'gsc', 'social', 'woocommerce' );
			$validated['enabled_modules'] = array_values( array_intersect( $settings['enabled_modules'], $valid_modules ) );
		}

		// Validate noindex settings.
		$noindex_fields = array( 'noindex_post_types', 'noindex_taxonomies', 'noindex_archives' );
		foreach ( $noindex_fields as $field ) {
			if ( isset( $settings[ $field ] ) && is_array( $settings[ $field ] ) ) {
				$validated[ $field ] = array_map( 'sanitize_key', $settings[ $field ] );
			} else {
				$validated[ $field ] = array();
			}
		}

		// Validate canonical settings.
		$validated['canonical_force_trailing_slash'] = ! empty( $settings['canonical_force_trailing_slash'] );
		$validated['canonical_force_https'] = ! empty( $settings['canonical_force_https'] );

		// Validate archive robots settings.
		$archive_robots_keys = array(
			'robots_author_archive',
			'robots_date_archive',
			'robots_category_archive',
			'robots_tag_archive',
			'robots_search_results',
			'robots_attachment',
		);

		// Add custom post type archive robots settings.
		$post_types = get_post_types( array( 'public' => true, 'has_archive' => true ), 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( ! in_array( $post_type->name, array( 'post', 'page', 'attachment' ), true ) ) {
				$archive_robots_keys[] = 'robots_post_type_archive_' . $post_type->name;
			}
		}

		foreach ( $archive_robots_keys as $key ) {
			if ( isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ) {
				$validated[ $key ] = array(
					'noindex'  => ! empty( $settings[ $key ]['noindex'] ),
					'nofollow' => ! empty( $settings[ $key ]['nofollow'] ),
				);
			} else {
				// Set default values if not provided.
				$validated[ $key ] = array(
					'noindex'  => false,
					'nofollow' => false,
				);
			}
		}

		// Validate RSS settings.
		if ( isset( $settings['rss_before_content'] ) ) {
			$validated['rss_before_content'] = wp_kses_post( $settings['rss_before_content'] );
		}
		if ( isset( $settings['rss_after_content'] ) ) {
			$validated['rss_after_content'] = wp_kses_post( $settings['rss_after_content'] );
		}

		// Validate delete on uninstall.
		$validated['delete_on_uninstall'] = ! empty( $settings['delete_on_uninstall'] );

		// Validate breadcrumbs settings.
		$validated['breadcrumbs_enabled'] = ! empty( $settings['breadcrumbs_enabled'] );
		if ( isset( $settings['breadcrumbs_separator'] ) ) {
			$validated['breadcrumbs_separator'] = sanitize_text_field( $settings['breadcrumbs_separator'] );
		}
		if ( isset( $settings['breadcrumbs_home_label'] ) ) {
			$validated['breadcrumbs_home_label'] = sanitize_text_field( $settings['breadcrumbs_home_label'] );
		}
		if ( isset( $settings['breadcrumbs_prefix'] ) ) {
			$validated['breadcrumbs_prefix'] = sanitize_text_field( $settings['breadcrumbs_prefix'] );
		}
		if ( isset( $settings['breadcrumbs_position'] ) ) {
			$valid_positions = array( 'before_content', 'after_content', 'manual' );
			$validated['breadcrumbs_position'] = in_array( $settings['breadcrumbs_position'], $valid_positions, true ) ? $settings['breadcrumbs_position'] : 'before_content';
		}
		if ( isset( $settings['breadcrumbs_show_on_post_types'] ) && is_array( $settings['breadcrumbs_show_on_post_types'] ) ) {
			$validated['breadcrumbs_show_on_post_types'] = array_map( 'sanitize_key', $settings['breadcrumbs_show_on_post_types'] );
		} else {
			$validated['breadcrumbs_show_on_post_types'] = array();
		}
		if ( isset( $settings['breadcrumbs_show_on_taxonomies'] ) && is_array( $settings['breadcrumbs_show_on_taxonomies'] ) ) {
			$validated['breadcrumbs_show_on_taxonomies'] = array_map( 'sanitize_key', $settings['breadcrumbs_show_on_taxonomies'] );
		} else {
			$validated['breadcrumbs_show_on_taxonomies'] = array();
		}

		// Validate archive patterns (Requirements: 5.1-5.16).
		$validated['title_patterns'] = $this->validate_archive_patterns( $settings );
		if ( is_wp_error( $validated['title_patterns'] ) ) {
			$this->errors = array_merge( $this->errors, $validated['title_patterns']->get_error_data() );
			unset( $validated['title_patterns'] );
		}

		// Return errors if any.
		if ( ! empty( $this->errors ) ) {
			return new \WP_Error( 'validation_failed', __( 'Settings validation failed.', 'meowseo' ), $this->errors );
		}

		return $validated;
	}

	/**
	 * Validate archive patterns
	 *
	 * Validates archive pattern syntax and sanitizes input.
	 * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12, 5.13, 5.14, 5.15, 5.16
	 *
	 * @since 1.0.0
	 * @param array $settings Settings array containing archive patterns.
	 * @return array|\WP_Error Validated patterns array or WP_Error on failure.
	 */
	private function validate_archive_patterns( array $settings ) {
		$archive_types = array(
			'category_archive',
			'tag_archive',
			'custom_taxonomy_archive',
			'author_page',
			'search_results',
			'date_archive',
			'404_page',
			'homepage',
		);

		$validated_patterns = array();
		$errors = array();

		foreach ( $archive_types as $type ) {
			$title_key = 'archive_pattern_' . $type . '_title';
			$description_key = 'archive_pattern_' . $type . '_description';

			// Validate title pattern.
			if ( isset( $settings[ $title_key ] ) ) {
				$title_pattern = sanitize_text_field( $settings[ $title_key ] );
				
				// Validate pattern syntax (check for unmatched %% delimiters).
				$validation_result = $this->validate_pattern_syntax( $title_pattern );
				if ( is_wp_error( $validation_result ) ) {
					$errors[ $title_key ] = sprintf(
						/* translators: %1$s: Archive type label, %2$s: Error message */
						__( '%1$s title pattern: %2$s', 'meowseo' ),
						ucwords( str_replace( '_', ' ', $type ) ),
						$validation_result->get_error_message()
					);
				} else {
					// Convert %% syntax to {} syntax for internal storage.
					$validated_patterns[ $type ]['title'] = $this->convert_pattern_to_internal( $title_pattern );
				}
			}

			// Validate description pattern.
			if ( isset( $settings[ $description_key ] ) ) {
				$description_pattern = sanitize_text_field( $settings[ $description_key ] );
				
				// Validate pattern syntax.
				$validation_result = $this->validate_pattern_syntax( $description_pattern );
				if ( is_wp_error( $validation_result ) ) {
					$errors[ $description_key ] = sprintf(
						/* translators: %1$s: Archive type label, %2$s: Error message */
						__( '%1$s description pattern: %2$s', 'meowseo' ),
						ucwords( str_replace( '_', ' ', $type ) ),
						$validation_result->get_error_message()
					);
				} else {
					// Convert %% syntax to {} syntax for internal storage.
					$validated_patterns[ $type ]['description'] = $this->convert_pattern_to_internal( $description_pattern );
				}
			}
		}

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'pattern_validation_failed', __( 'Pattern validation failed.', 'meowseo' ), $errors );
		}

		return $validated_patterns;
	}

	/**
	 * Validate pattern syntax
	 *
	 * Checks for unmatched %% delimiters in pattern string.
	 * Requirements: 5.2, 5.3
	 *
	 * @since 1.0.0
	 * @param string $pattern Pattern string to validate.
	 * @return bool|\WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_pattern_syntax( string $pattern ) {
		// Empty patterns are valid.
		if ( empty( $pattern ) ) {
			return true;
		}

		// Count %% occurrences.
		$count = substr_count( $pattern, '%%' );
		
		// Must have even number of %% (pairs).
		if ( $count % 2 !== 0 ) {
			return new \WP_Error( 'unmatched_delimiters', __( 'Unmatched %% delimiters. Variables must be wrapped in pairs like %%variable%%.', 'meowseo' ) );
		}

		// Check for valid variable names between %% pairs.
		preg_match_all( '/%%([^%]+)%%/', $pattern, $matches );
		
		if ( ! empty( $matches[1] ) ) {
			$valid_variables = array(
				'title', 'sitename', 'category', 'tag', 'term', 'date', 'name',
				'searchphrase', 'posttype', 'sep', 'page', 'tagline',
			);

			foreach ( $matches[1] as $variable ) {
				if ( ! in_array( $variable, $valid_variables, true ) ) {
					return new \WP_Error(
						'invalid_variable',
						sprintf(
							/* translators: %s: Variable name */
							__( 'Invalid variable: %%%s%%. Please use only supported variables.', 'meowseo' ),
							esc_html( $variable )
						)
					);
				}
			}
		}

		return true;
	}

	/**
	 * Convert pattern from %% syntax to {} syntax
	 *
	 * Converts user-friendly %% syntax to internal {} syntax used by Title_Patterns class.
	 * Requirements: 5.1, 5.2
	 *
	 * @since 1.0.0
	 * @param string $pattern Pattern string with %% syntax.
	 * @return string Pattern string with {} syntax.
	 */
	private function convert_pattern_to_internal( string $pattern ): string {
		// Map of %% variables to {} variables.
		$variable_map = array(
			'%%title%%' => '{title}',
			'%%sitename%%' => '{site_name}',
			'%%category%%' => '{category}',
			'%%tag%%' => '{tag}',
			'%%term%%' => '{term}',
			'%%date%%' => '{date}',
			'%%name%%' => '{name}',
			'%%searchphrase%%' => '{searchphrase}',
			'%%posttype%%' => '{posttype}',
			'%%sep%%' => '{sep}',
			'%%page%%' => '{page}',
			'%%tagline%%' => '{tagline}',
		);

		return str_replace( array_keys( $variable_map ), array_values( $variable_map ), $pattern );
	}

	/**
	 * Convert pattern from {} syntax to %% syntax
	 *
	 * Converts internal {} syntax to user-friendly %% syntax for display in UI.
	 * Requirements: 5.1, 5.2
	 *
	 * @since 1.0.0
	 * @param string $pattern Pattern string with {} syntax.
	 * @return string Pattern string with %% syntax.
	 */
	private function convert_pattern_to_display( string $pattern ): string {
		// Map of {} variables to %% variables.
		$variable_map = array(
			'{title}' => '%%title%%',
			'{site_name}' => '%%sitename%%',
			'{category}' => '%%category%%',
			'{tag}' => '%%tag%%',
			'{term}' => '%%term%%',
			'{date}' => '%%date%%',
			'{name}' => '%%name%%',
			'{searchphrase}' => '%%searchphrase%%',
			'{posttype}' => '%%posttype%%',
			'{sep}' => '%%sep%%',
			'{page}' => '%%page%%',
			'{tagline}' => '%%tagline%%',
		);

		return str_replace( array_keys( $variable_map ), array_values( $variable_map ), $pattern );
	}

	/**
	 * Sanitize social URL
	 *
	 * Validates and sanitizes a social media profile URL.
	 * Requirements: 6.3, 6.4, 6.5
	 *
	 * @since 1.0.0
	 * @param string $url URL to sanitize.
	 * @return string|\WP_Error Sanitized URL or WP_Error on failure.
	 */
	public function sanitize_social_url( string $url ) {
		// Allow empty URLs.
		if ( empty( $url ) ) {
			return '';
		}

		// Sanitize URL.
		$url = esc_url_raw( trim( $url ) );

		if ( empty( $url ) ) {
			return new \WP_Error( 'invalid_url', __( 'Please enter a valid URL starting with https://', 'meowseo' ) );
		}

		// Validate URL format.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new \WP_Error( 'invalid_url', __( 'The URL format is not valid.', 'meowseo' ) );
		}

		// Ensure HTTPS.
		if ( strpos( $url, 'https://' ) !== 0 ) {
			return new \WP_Error( 'insecure_url', __( 'Please use a secure URL (https://).', 'meowseo' ) );
		}

		return $url;
	}

	/**
	 * Get validation errors
	 *
	 * @since 1.0.0
	 * @return array<string, string> Validation errors.
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Save settings
	 *
	 * Handles settings form submission with nonce verification and logging.
	 * Requirements: 28.1, 28.4, 33.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function save_settings(): void {
		// Get the nonce action from the form (Requirement 28.5).
		$nonce_action = isset( $_POST['meowseo_nonce_action'] ) ? sanitize_key( $_POST['meowseo_nonce_action'] ) : 'meowseo_settings_save';

		// Verify nonce (Requirement 28.1).
		if ( ! isset( $_POST['meowseo_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_settings_nonce'] ), $nonce_action ) ) {
			// Return HTTP 403 for failed nonce verification (Requirement 28.4).
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify user capabilities (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Get current settings for comparison.
		$old_settings = $this->options->get_all();

		// Validate settings.
		$validated = $this->validate_settings( $_POST );

		if ( is_wp_error( $validated ) ) {
			$errors = $validated->get_error_data();
			if ( is_array( $errors ) ) {
				set_transient( 'meowseo_settings_errors', $errors, 30 );
			}
			wp_safe_redirect( add_query_arg( 'meowseo_settings_error', urlencode( $validated->get_error_message() ), wp_get_referer() ) );
			exit;
		}

		// Track changed fields for logging.
		$changed_fields = array();

		foreach ( $validated as $key => $value ) {
			$old_value = $old_settings[ $key ] ?? null;

			// Compare values (handle arrays specially).
			if ( is_array( $value ) && is_array( $old_value ) ) {
				if ( ! empty( array_diff( $value, $old_value ) ) || ! empty( array_diff( $old_value, $value ) ) ) {
					$changed_fields[] = $key;
				}
			} elseif ( $value !== $old_value ) {
				$changed_fields[] = $key;
			}

			// Save setting.
			$this->options->set( $key, $value );
		}

		// Save to database.
		$saved = $this->options->save();

		if ( $saved ) {
			// Log settings save (Requirement 33.1).
			Logger::info(
				'Settings saved',
				array(
					'user_id'       => get_current_user_id(),
					'changed_fields' => $changed_fields,
					'tab'           => sanitize_key( $_POST['meowseo_active_tab'] ?? 'general' ),
				)
			);

			wp_safe_redirect( add_query_arg( 'meowseo_settings_saved', '1', wp_get_referer() ) );
		} else {
			wp_safe_redirect( add_query_arg( 'meowseo_settings_error', urlencode( __( 'Failed to save settings. Please try again.', 'meowseo' ) ), wp_get_referer() ) );
		}

		exit;
	}

	/**
	 * Register admin-post action handler
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_handlers(): void {
		add_action( 'admin_post_meowseo_save_settings', array( $this, 'save_settings' ) );
	}
}
