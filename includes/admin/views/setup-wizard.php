<?php
/**
 * Setup Wizard view.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$steps = array(
	'welcome'       => __( 'Welcome', 'meowseo' ),
	'compatibility' => __( 'Compatibility', 'meowseo' ),
	'import'        => __( 'Import', 'meowseo' ),
	'site-identity' => __( 'Site Identity', 'meowseo' ),
	'seo-defaults'  => __( 'SEO Defaults', 'meowseo' ),
	'ready'         => __( 'Ready', 'meowseo' ),
);

$current_step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : 'welcome';
$step_keys    = array_keys( $steps );
$step_index   = array_search( $current_step, $step_keys );
$progress     = ( ( $step_index + 1 ) / count( $steps ) ) * 100;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php esc_html_e( 'MeowSEO &rsaquo; Setup Wizard', 'meowseo' ); ?></title>
	<?php do_action( 'admin_print_scripts' ); ?>
	<?php do_action( 'admin_print_styles' ); ?>
</head>
<body class="meowseo-setup-wizard wp-core-ui">
	<div id="meowseo-wizard-container">
		<header class="wizard-header">
			<div class="logo">
				<img src="<?php echo esc_url( \MEOWSEO_URL . 'admin/img/logo-white.svg' ); ?>" alt="MeowSEO">
				<h1><?php esc_html_e( 'Setup Wizard', 'meowseo' ); ?></h1>
			</div>
			<div class="progress-bar-container">
				<div class="progress-bar" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>
			</div>
			<nav class="wizard-steps">
				<ul>
					<?php foreach ( $steps as $key => $label ) : 
						$status_class = '';
						if ( $key === $current_step ) {
							$status_class = 'active';
						} elseif ( array_search( $key, $step_keys ) < $step_index ) {
							$status_class = 'completed';
						}
					?>
						<li class="<?php echo esc_attr( $status_class ); ?>">
							<span><?php echo esc_html( $label ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		</header>

		<main class="wizard-content">
			<form method="post" class="wizard-form">
				<?php wp_nonce_field( 'meowseo_wizard_step_' . $current_step ); ?>
				
				<div class="step-body">
					<?php 
					// Include step-specific view
					$view_file = \MEOWSEO_PATH . 'includes/admin/views/wizard/step-' . $current_step . '.php';
					if ( file_exists( $view_file ) ) {
						include $view_file;
					} else {
						printf( '<p>%s</p>', esc_html__( 'Step view not found.', 'meowseo' ) );
					}
					?>
				</div>

				<footer class="wizard-footer">
					<?php if ( $current_step !== 'ready' ) : ?>
						<button type="submit" name="meowseo_wizard_save" class="button button-primary button-hero button-next">
							<?php echo $current_step === 'welcome' ? esc_html__( 'Start Wizard', 'meowseo' ) : esc_html__( 'Continue', 'meowseo' ); ?>
						</button>
					<?php else : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=meowseo' ) ); ?>" class="button button-primary button-hero">
							<?php esc_html_e( 'Go to Dashboard', 'meowseo' ); ?>
						</a>
					<?php endif; ?>

					<?php if ( $current_step !== 'welcome' && $current_step !== 'ready' ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=meowseo' ) ); ?>" class="skip-link">
							<?php esc_html_e( 'Skip this setup', 'meowseo' ); ?>
						</a>
					<?php endif; ?>
				</footer>
			</form>
		</main>
	</div>
	<?php do_action( 'admin_print_footer_scripts' ); ?>
</body>
</html>
