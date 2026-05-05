<?php
/**
 * Step 6: Ready
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div style="text-align:center; padding:20px 0;">
	<div style="width:80px; height:80px; background:#22c55e; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:25px; box-shadow:0 0 20px rgba(34, 197, 94, 0.4);">
		<span class="dashicons dashicons-yes" style="font-size:48px; width:48px; height:48px; color:white;"></span>
	</div>
	
	<h2><?php esc_html_e( 'Your Site is Ready!', 'meowseo' ); ?></h2>
	<p><?php esc_html_e( 'MeowSEO is now configured and protecting your search rankings. You can further refine your settings in the dashboard.', 'meowseo' ); ?></p>
	
	<div class="next-steps" style="margin-top:40px; display:grid; grid-template-columns:1fr 1fr; gap:20px; text-align:left;">
		<div class="step-card" style="padding:15px; background:var(--meow-bg); border-radius:8px;">
			<h4 style="margin:0 0 5px 0; color:var(--meow-primary);"><?php esc_html_e( 'Connect Search Console', 'meowseo' ); ?></h4>
			<p style="margin:0; font-size:12px;"><?php esc_html_e( 'Get real-time performance data from Google.', 'meowseo' ); ?></p>
		</div>
		<div class="step-card" style="padding:15px; background:var(--meow-bg); border-radius:8px;">
			<h4 style="margin:0 0 5px 0; color:var(--meow-primary);"><?php esc_html_e( 'Join the Community', 'meowseo' ); ?></h4>
			<p style="margin:0; font-size:12px;"><?php esc_html_e( 'Get SEO tips and help from other users.', 'meowseo' ); ?></p>
		</div>
	</div>
</div>
