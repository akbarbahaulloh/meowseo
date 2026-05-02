<?php
/**
 * Content Refresh Admin Dashboard
 *
 * @package MeowSEO
 * @subpackage Modules\Content_Refresh
 */

namespace MeowSEO\Modules\Content_Refresh;

defined( 'ABSPATH' ) || exit;

/**
 * Content_Refresh_Admin class.
 */
class Content_Refresh_Admin {

	/**
	 * Options instance.
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param $options Options instance.
	 */
	public function __construct( $options ) {
		$this->options = $options;
	}

	/**
	 * Render the settings page tab.
	 */
	public function render_settings_tab(): void {
		$enabled = $this->options->get( 'content_refresh_enabled', false );
		$interval = $this->options->get( 'content_refresh_interval', 24 );
		$min_age = $this->options->get( 'content_refresh_min_age', 30 );
		$method = $this->options->get( 'content_refresh_method', 'modified' );
		$auto_redirect = $this->options->get( 'content_refresh_auto_redirect', true );
		$add_notice = $this->options->get( 'content_refresh_add_notice', true );
		$selected_post_types = $this->options->get( 'content_refresh_post_types', array( 'post' ) );
		
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<h2><?php esc_html_e( 'Safe Content Refresh', 'meowseo' ); ?></h2>
		<p><?php esc_html_e( 'Maximize SEO by automatically re-publishing or refreshing old content to signal freshness to search engines.', 'meowseo' ); ?></p>

		<div class="meowseo-settings-section" style="margin-bottom: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Auto Refresh', 'meowseo' ); ?></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="content_refresh_enabled" value="1" <?php checked( $enabled ); ?>>
							<span class="slider round"></span>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="content_refresh_interval"><?php esc_html_e( 'Refresh Interval', 'meowseo' ); ?></label></th>
					<td>
						<input type="number" name="content_refresh_interval" id="content_refresh_interval" value="<?php echo esc_attr( $interval ); ?>" class="small-text" min="1">
						<span><?php esc_html_e( 'hours', 'meowseo' ); ?></span>
						<p class="description"><?php esc_html_e( 'How often should a post be refreshed? (e.g., every 24 hours)', 'meowseo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="content_refresh_min_age"><?php esc_html_e( 'Minimum Post Age', 'meowseo' ); ?></label></th>
					<td>
						<input type="number" name="content_refresh_min_age" id="content_refresh_min_age" value="<?php echo esc_attr( $min_age ); ?>" class="small-text" min="1">
						<span><?php esc_html_e( 'days', 'meowseo' ); ?></span>
						<p class="description"><?php esc_html_e( 'Only refresh posts older than this many days.', 'meowseo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Post Types', 'meowseo' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $post_types as $pt ) : ?>
								<label style="margin-right: 15px;">
									<input type="checkbox" name="content_refresh_post_types[]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $selected_post_types, true ) ); ?>>
									<?php echo esc_html( $pt->label ); ?>
								</label>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Refresh Method', 'meowseo' ); ?></th>
					<td>
						<label style="display: block; margin-bottom: 10px;">
							<input type="radio" name="content_refresh_method" value="republish" <?php checked( $method, 'republish' ); ?>>
							<strong><?php esc_html_e( 'Full Republish (Date Change)', 'meowseo' ); ?></strong>
							<p class="description"><?php esc_html_e( 'Changes the post date to today. Post moves to the top of the feed. Higher SEO impact, higher risk.', 'meowseo' ); ?></p>
						</label>
						<label style="display: block;">
							<input type="radio" name="content_refresh_method" value="modified" <?php checked( $method, 'modified' ); ?>>
							<strong><?php esc_html_e( 'Soft Refresh (Modified Date Only)', 'meowseo' ); ?></strong>
							<p class="description"><?php esc_html_e( 'Only updates the "Last Modified" date. Safer and prevents URL changes.', 'meowseo' ); ?></p>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Safety Features', 'meowseo' ); ?></th>
					<td>
						<label style="display: block; margin-bottom: 10px;">
							<input type="checkbox" name="content_refresh_auto_redirect" value="1" <?php checked( $auto_redirect ); ?>>
							<?php esc_html_e( 'Automatic 301 Redirects (Highly Recommended)', 'meowseo' ); ?>
							<p class="description"><?php esc_html_e( 'If the URL changes during re-publishing, MeowSEO will automatically redirect the old URL to the new one.', 'meowseo' ); ?></p>
						</label>
						<label style="display: block;">
							<input type="checkbox" name="content_refresh_add_notice" value="1" <?php checked( $add_notice ); ?>>
							<?php esc_html_e( 'Show "Last Updated" notice on frontend', 'meowseo' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Included Categories', 'meowseo' ); ?></th>
					<td>
						<input type="text" placeholder="<?php esc_attr_e( 'Search categories...', 'meowseo' ); ?>" class="meowseo-tax-search large-text" style="margin-bottom: 5px;" onkeyup="meowseoFilterTax(this)">
						<?php
						$categories = get_categories( array( 'hide_empty' => false ) );
						$included_cats = $this->options->get( 'content_refresh_included_categories', array() );
						?>
						<div class="meowseo-tax-list" style="max-height: 150px; overflow-y: auto; padding: 10px; border: 1px solid #ccd0d4; background: #f9f9f9;">
							<?php foreach ( $categories as $cat ) : ?>
								<label style="display: block; margin-bottom: 5px;" class="meowseo-tax-item">
									<input type="checkbox" name="content_refresh_included_categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $included_cats ) ); ?>>
									<span class="meowseo-tax-name"><?php echo esc_html( $cat->name ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
						<p class="description"><?php esc_html_e( 'Only posts in these categories will be eligible for refreshing.', 'meowseo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Included Tags', 'meowseo' ); ?></th>
					<td>
						<input type="text" placeholder="<?php esc_attr_e( 'Search tags...', 'meowseo' ); ?>" class="meowseo-tax-search large-text" style="margin-bottom: 5px;" onkeyup="meowseoFilterTax(this)">
						<?php
						$tags = get_tags( array( 'hide_empty' => false ) );
						$included_tags = $this->options->get( 'content_refresh_included_tags', array() );
						?>
						<div class="meowseo-tax-list" style="max-height: 150px; overflow-y: auto; padding: 10px; border: 1px solid #ccd0d4; background: #f9f9f9;">
							<?php if ( empty( $tags ) ) : ?>
								<p class="description"><?php esc_html_e( 'No tags found.', 'meowseo' ); ?></p>
							<?php else : ?>
								<?php foreach ( $tags as $tag ) : ?>
									<label style="display: block; margin-bottom: 5px;" class="meowseo-tax-item">
										<input type="checkbox" name="content_refresh_included_tags[]" value="<?php echo esc_attr( $tag->term_id ); ?>" <?php checked( in_array( $tag->term_id, $included_tags ) ); ?>>
										<span class="meowseo-tax-name"><?php echo esc_html( $tag->name ); ?></span>
									</label>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<p class="description"><?php esc_html_e( 'Only posts with these tags will be eligible.', 'meowseo' ); ?></p>
					</td>
				</tr>
				<script>
					function meowseoFilterTax(input) {
						var filter = input.value.toLowerCase();
						var list = input.nextElementSibling;
						var items = list.getElementsByClassName('meowseo-tax-item');
						for (var i = 0; i < items.length; i++) {
							var name = items[i].querySelector('.meowseo-tax-name').innerText.toLowerCase();
							if (name.indexOf(filter) > -1) {
								items[i].style.display = "";
							} else {
								items[i].style.display = "none";
							}
						}
					}
				</script>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the dedicated Dashboard page.
	 */
	public function render_dashboard_page(): void {
		global $wpdb;
		$total_refreshed = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '_meowseo_last_refreshed'" );
		$last_run = get_option( 'meowseo_content_refresh_last_run', 0 );
		
		// Get recent history.
		$history = $wpdb->get_results( "
			SELECT p.ID, p.post_title, p.post_date, pm.meta_value as refreshed_at
			FROM $wpdb->posts p
			JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
			WHERE pm.meta_key = '_meowseo_last_refreshed'
			ORDER BY pm.meta_value DESC
			LIMIT 20
		" );
		?>
		<div class="wrap meowseo-dashboard">
			<h1><?php esc_html_e( 'Content Refresh Dashboard', 'meowseo' ); ?></h1>
			<p><?php esc_html_e( 'Monitor and manage your automated content re-publishing.', 'meowseo' ); ?></p>

			<div class="meowseo-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
				<div class="meowseo-stat-card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px;">
					<span class="dashicons dashicons-update" style="font-size: 30px; width: 30px; height: 30px; color: #0073aa;"></span>
					<h3 style="margin: 10px 0;"><?php esc_html_e( 'Total Refreshed', 'meowseo' ); ?></h3>
					<div style="font-size: 24px; font-weight: bold;"><?php echo (int) $total_refreshed; ?></div>
				</div>
				<div class="meowseo-stat-card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px;">
					<span class="dashicons dashicons-clock" style="font-size: 30px; width: 30px; height: 30px; color: #46b450;"></span>
					<h3 style="margin: 10px 0;"><?php esc_html_e( 'Last Pulse', 'meowseo' ); ?></h3>
					<div style="font-size: 16px;">
						<?php echo $last_run ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_run ) : __( 'Never', 'meowseo' ); ?>
					</div>
				</div>
				<div class="meowseo-stat-card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px;">
					<span class="dashicons dashicons-admin-settings" style="font-size: 30px; width: 30px; height: 30px; color: #d63638;"></span>
					<h3 style="margin: 10px 0;"><?php esc_html_e( 'Status', 'meowseo' ); ?></h3>
					<div>
						<?php if ( $this->options->get( 'content_refresh_enabled', false ) ) : ?>
							<span style="color: #46b450; font-weight: bold;">● <?php esc_html_e( 'Active', 'meowseo' ); ?></span>
						<?php else : ?>
							<span style="color: #d63638; font-weight: bold;">○ <?php esc_html_e( 'Paused', 'meowseo' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="meowseo-content-box" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px;">
				<h2><?php esc_html_e( 'Recent Refresh History', 'meowseo' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Post Title', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Current Date', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Refreshed On', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'meowseo' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $history ) ) : ?>
							<tr><td colspan="4"><?php esc_html_e( 'No posts have been refreshed yet.', 'meowseo' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $history as $row ) : ?>
								<tr>
									<td><strong><a href="<?php echo get_edit_post_link( $row->ID ); ?>"><?php echo esc_html( $row->post_title ); ?></a></strong></td>
									<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $row->post_date ) ); ?></td>
									<td><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $row->refreshed_at ); ?></td>
									<td>
										<a href="<?php echo get_permalink( $row->ID ); ?>" class="button button-small" target="_blank"><?php esc_html_e( 'View', 'meowseo' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div style="margin-top: 20px;">
				<a href="<?php echo admin_url( 'admin.php?page=meowseo-settings&tab=content-refresh' ); ?>" class="button button-primary">
					<?php esc_html_e( 'Configure Settings', 'meowseo' ); ?>
				</a>
			</div>
		</div>
		<?php
	}
}
