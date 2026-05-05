<?php
/**
 * Step 4: Site Identity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_type = $this->options->get( 'schema_organization_type', 'Organization' );
$site_name = $this->options->get( 'schema_business_name', get_bloginfo( 'name' ) );
$logo      = $this->options->get( 'schema_organization_logo', '' );

?>

<h2><?php esc_html_e( 'Site Identity (E-E-A-T)', 'meowseo' ); ?></h2>
<p><?php esc_html_e( 'Tell us about your site. This information is used for Google Knowledge Graph and essential SEO signals.', 'meowseo' ); ?></p>

<div class="site-identity-form" style="display:flex; flex-direction:column; gap:20px;">
	<div class="form-group">
		<label for="org_type" style="display:block; margin-bottom:8px;"><?php esc_html_e( 'This site represents a:', 'meowseo' ); ?></label>
		<select name="schema_organization_type" id="org_type" style="width:100%;">
			<option value="Organization" <?php selected( $site_type, 'Organization' ); ?>><?php esc_html_e( 'Organization / Business', 'meowseo' ); ?></option>
			<option value="Person" <?php selected( $site_type, 'Person' ); ?>><?php esc_html_e( 'Personal Blog / Person', 'meowseo' ); ?></option>
		</select>
	</div>

	<div class="form-group">
		<label for="org_name" style="display:block; margin-bottom:8px;"><?php esc_html_e( 'Name:', 'meowseo' ); ?></label>
		<input type="text" name="schema_business_name" id="org_name" value="<?php echo esc_attr( $site_name ); ?>" style="width:100%;" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	</div>

	<div class="form-group">
		<label style="display:block; margin-bottom:8px;"><?php esc_html_e( 'Logo for Google:', 'meowseo' ); ?></label>
		<div style="display:flex; gap:10px; align-items:center;">
			<input type="text" name="schema_organization_logo" id="org_logo" value="<?php echo esc_attr( $logo ); ?>" style="flex:1;">
			<button type="button" class="button button-secondary" id="meowseo-upload-logo"><?php esc_html_e( 'Upload', 'meowseo' ); ?></button>
		</div>
		<p class="description" style="margin-top:5px; font-size:12px;"><?php esc_html_e( 'Recommended size: 112x112px minimum.', 'meowseo' ); ?></p>
	</div>

	<div class="form-group" style="margin-top:10px; padding-top:20px; border-top:1px solid var(--meow-border);">
		<h3 style="margin-top:0;"><?php esc_html_e( 'Social Profiles', 'meowseo' ); ?></h3>
		<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
			<input type="url" name="social_facebook_url" placeholder="Facebook URL" value="<?php echo esc_attr( $this->options->get( 'social_facebook_url', '' ) ); ?>">
			<input type="url" name="social_twitter_url" placeholder="Twitter / X URL" value="<?php echo esc_attr( $this->options->get( 'social_twitter_url', '' ) ); ?>">
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$('#meowseo-upload-logo').on('click', function(e) {
		e.preventDefault();
		var frame = wp.media({
			title: '<?php esc_html_e( 'Select Logo', 'meowseo' ); ?>',
			multiple: false
		}).on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			$('#org_logo').val(attachment.url);
		}).open();
	});
});
</script>
