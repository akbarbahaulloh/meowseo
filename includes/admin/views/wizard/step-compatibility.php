<?php
/**
 * Step 2: Compatibility
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$php_version = phpversion();
$wp_version  = get_bloginfo( 'version' );
$is_php_ok   = version_compare( $php_version, '8.0', '>=' );
$is_wp_ok    = version_compare( $wp_version, '6.0', '>=' );

// Check for conflicting plugins
$conflicting_plugins = array();
if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
	$conflicting_plugins[] = 'Yoast SEO';
}
if ( is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) {
	$conflicting_plugins[] = 'Rank Math SEO';
}
if ( is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) ) {
	$conflicting_plugins[] = 'All in One SEO';
}

?>

<h2><?php esc_html_e( 'Compatibility Check', 'meowseo' ); ?></h2>
<p><?php esc_html_e( 'We\'re checking your system to ensure everything runs smoothly.', 'meowseo' ); ?></p>

<div class="compatibility-list" style="display:flex; flex-direction:column; gap:15px;">
	<div class="check-item" style="display:flex; align-items:center; justify-content:space-between; padding:15px; background:var(--meow-bg); border-radius:8px;">
		<span><?php printf( __( 'PHP Version: %s', 'meowseo' ), $php_version ); ?></span>
		<span class="dashicons <?php echo $is_php_ok ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" style="color:<?php echo $is_php_ok ? '#22c55e' : '#ef4444'; ?>;"></span>
	</div>

	<div class="check-item" style="display:flex; align-items:center; justify-content:space-between; padding:15px; background:var(--meow-bg); border-radius:8px;">
		<span><?php printf( __( 'WordPress Version: %s', 'meowseo' ), $wp_version ); ?></span>
		<span class="dashicons <?php echo $is_wp_ok ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" style="color:<?php echo $is_wp_ok ? '#22c55e' : '#ef4444'; ?>;"></span>
	</div>

	<?php if ( ! empty( $conflicting_plugins ) ) : ?>
		<div class="check-item warning" style="padding:15px; background:rgba(239, 68, 68, 0.1); border:1px solid #ef4444; border-radius:8px;">
			<p style="margin:0; color:#ef4444; font-size:14px;">
				<span class="dashicons dashicons-warning" style="vertical-align:text-bottom; margin-right:5px;"></span>
				<?php printf( __( 'Conflicting plugins detected: %s. We recommend deactivating them after you import your data in the next step.', 'meowseo' ), implode( ', ', $conflicting_plugins ) ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="check-item success" style="padding:15px; background:rgba(34, 197, 94, 0.1); border:1px solid #22c55e; border-radius:8px;">
			<p style="margin:0; color:#22c55e; font-size:14px;">
				<span class="dashicons dashicons-yes-alt" style="vertical-align:text-bottom; margin-right:5px;"></span>
				<?php esc_html_e( 'No conflicting SEO plugins active. You\'re good to go!', 'meowseo' ); ?>
			</p>
		</div>
	<?php endif; ?>
</div>
