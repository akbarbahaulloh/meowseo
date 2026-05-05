<?php
/**
 * Step 1: Welcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php esc_html_e( 'Welcome to MeowSEO!', 'meowseo' ); ?></h2>
<p><?php esc_html_e( 'Thank you for choosing MeowSEO. This wizard will help you configure the essential settings to get your site ready for Google Discover, AI Overviews, and more.', 'meowseo' ); ?></p>

<div class="mode-selection">
	<label class="mode-card active">
		<input type="radio" name="setup_mode" value="easy" checked style="display:none;">
		<h3><?php esc_html_e( 'Easy Mode', 'meowseo' ); ?></h3>
		<p><?php esc_html_e( 'Perfect for most sites. We\'ll handle the complex technical stuff with smart defaults.', 'meowseo' ); ?></p>
	</label>
	
	<label class="mode-card">
		<input type="radio" name="setup_mode" value="advanced" style="display:none;">
		<h3><?php esc_html_e( 'Advanced Mode', 'meowseo' ); ?></h3>
		<p><?php esc_html_e( 'For SEO experts who want full control over every technical detail and module.', 'meowseo' ); ?></p>
	</label>
</div>

<script>
jQuery(document).ready(function($) {
	$('.mode-card').on('click', function() {
		$('.mode-card').removeClass('active');
		$(this).addClass('active');
		$(this).find('input').prop('checked', true);
	});
});
</script>
