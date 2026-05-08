<?php
/**
 * GitHub Update Settings Page View
 *
 * @package MeowSEO
 * @subpackage Updater
 * @since 1.0.0
 *
 * Variables passed from Admin::render_update_settings_page():
 * - $current_version   string
 * - $latest_sha        string
 * - $installed_sha     string
 * - $update_available  bool
 * - $has_token         bool
 * - $token_placeholder string   masked token for display
 * - $branch            string
 * - $nonce             string
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'MeowSEO — Updates', 'meowseo' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Configure automatic updates from the private GitHub repository.', 'meowseo' ); ?></p>

	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'meowseo' ); ?></p></div>
	<?php endif; ?>

	<?php if ( isset( $_GET['cache_cleared'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Update cache cleared.', 'meowseo' ); ?></p></div>
	<?php endif; ?>

	<!-- Status -->
	<h2><?php esc_html_e( 'Status', 'meowseo' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Current Version', 'meowseo' ); ?></th>
			<td><code><?php echo esc_html( $current_version ); ?></code></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Installed Commit', 'meowseo' ); ?></th>
			<td><code><?php echo esc_html( $installed_sha ? substr( $installed_sha, 0, 7 ) : __( 'unknown', 'meowseo' ) ); ?></code></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Latest Commit', 'meowseo' ); ?></th>
			<td><code><?php echo esc_html( $latest_sha ? substr( $latest_sha, 0, 7 ) : __( 'unable to fetch', 'meowseo' ) ); ?></code></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Update Available', 'meowseo' ); ?></th>
			<td>
				<?php if ( $update_available ) : ?>
					<span style="color:#46b450;" class="dashicons dashicons-yes"></span>
					<strong><?php esc_html_e( 'Yes', 'meowseo' ); ?></strong>
				<?php elseif ( $latest_sha ) : ?>
					<span style="color:#0073aa;" class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Up to date', 'meowseo' ); ?>
				<?php else : ?>
					<span style="color:#dc3545;" class="dashicons dashicons-warning"></span>
					<?php esc_html_e( 'Could not check — verify your GitHub token.', 'meowseo' ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'GitHub Token', 'meowseo' ); ?></th>
			<td>
				<?php if ( $has_token ) : ?>
					<span style="color:#46b450;" class="dashicons dashicons-yes"></span>
					<?php esc_html_e( 'Configured', 'meowseo' ); ?>
				<?php else : ?>
					<span style="color:#dc3545;" class="dashicons dashicons-no"></span>
					<?php esc_html_e( 'Not configured — updates from the private repo will fail.', 'meowseo' ); ?>
				<?php endif; ?>
			</td>
		</tr>
	</table>

	<!-- Actions -->
	<p>
		<form method="post" style="display:inline;">
			<?php wp_nonce_field( 'meowseo_check_update_now', 'meowseo_check_nonce' ); ?>
			<input type="hidden" name="meowseo_update_action" value="check_now">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Check for Updates Now', 'meowseo' ); ?></button>
		</form>
		&nbsp;
		<form method="post" style="display:inline;">
			<?php wp_nonce_field( 'meowseo_clear_update_cache', 'meowseo_cache_nonce' ); ?>
			<input type="hidden" name="meowseo_update_action" value="clear_cache">
			<button type="submit" class="button"><?php esc_html_e( 'Clear Cache', 'meowseo' ); ?></button>
		</form>
	</p>

	<hr>

	<!-- Configuration -->
	<h2><?php esc_html_e( 'Configuration', 'meowseo' ); ?></h2>
	<form method="post" action="">
		<?php wp_nonce_field( 'meowseo_save_update_settings', 'meowseo_update_settings_nonce' ); ?>
		<input type="hidden" name="meowseo_update_action" value="save_settings">

		<table class="form-table">
			<!-- Token -->
			<tr>
				<th scope="row">
					<label for="meowseo_github_token"><?php esc_html_e( 'GitHub Personal Access Token', 'meowseo' ); ?></label>
				</th>
				<td>
					<input
						type="password"
						id="meowseo_github_token"
						name="meowseo_github_token"
						value=""
						class="regular-text"
						autocomplete="new-password"
						placeholder="<?php echo esc_attr( $token_placeholder ); ?>"
					>
					<p class="description">
						<?php
						printf(
							/* translators: %s: GitHub token settings URL */
							wp_kses(
								__( 'Create a <a href="%s" target="_blank" rel="noopener">fine-grained PAT</a> with <strong>Contents: Read-only</strong> access to the <code>pustekno/meowseo</code> repository. Leave blank to keep the current token.', 'meowseo' ),
								array(
									'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
									'strong' => array(),
									'code'   => array(),
								)
							),
							'https://github.com/settings/personal-access-tokens/new'
						);
						?>
					</p>
				</td>
			</tr>

			<!-- Branch -->
			<tr>
				<th scope="row">
					<label for="meowseo_update_branch"><?php esc_html_e( 'Branch', 'meowseo' ); ?></label>
				</th>
				<td>
					<select id="meowseo_update_branch" name="meowseo_update_branch">
						<?php foreach ( array( 'main', 'master', 'develop' ) as $b ) : ?>
							<option value="<?php echo esc_attr( $b ); ?>" <?php selected( $branch, $b ); ?>>
								<?php echo esc_html( $b ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Branch to track for updates.', 'meowseo' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'meowseo' ) ); ?>
	</form>
</div>
