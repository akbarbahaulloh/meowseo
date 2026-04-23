<?php
/**
 * Test Updater Hooks Script
 * 
 * Script untuk memverifikasi bahwa hook updater MeowSEO terdaftar dengan benar.
 * Script ini akan menampilkan semua hook yang terdaftar pada filter
 * 'pre_set_site_transient_update_plugins' dan memverifikasi bahwa
 * GitHub_Update_Checker ada di dalamnya.
 */

// Load WordPress
require_once 'wp-load.php';

// Verify user is logged in and has permission
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Test MeowSEO Updater Hooks</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			padding: 20px;
			max-width: 1200px;
			margin: 0 auto;
			background: #f0f0f1;
		}
		.container {
			background: white;
			padding: 20px;
			border-radius: 8px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		h1 { color: #23282d; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
		h2 { color: #23282d; margin-top: 30px; }
		.success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; padding: 12px; margin: 10px 0; }
		.error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 12px; margin: 10px 0; }
		.info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; padding: 12px; margin: 10px 0; }
		.warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; padding: 12px; margin: 10px 0; }
		table { width: 100%; border-collapse: collapse; margin: 20px 0; }
		th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
		th { background: #f8f9fa; font-weight: 600; }
		tr:hover { background: #f8f9fa; }
		.code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 0.9em; }
		.highlight { background: #fff3cd; font-weight: bold; }
		.badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 0.85em; font-weight: 600; }
		.badge-success { background: #28a745; color: white; }
		.badge-danger { background: #dc3545; color: white; }
		.badge-info { background: #17a2b8; color: white; }
	</style>
</head>
<body>
	<div class="container">
		<h1>🔍 Test MeowSEO Updater Hooks</h1>
		
		<?php
		// Check if updater is initialized
		$plugin = \MeowSEO\Plugin::instance();
		$checker = $plugin->get_updater_checker();
		
		if ( $checker ) {
			echo '<div class="success">✓ <strong>Updater Instance Found:</strong> ' . get_class( $checker ) . '</div>';
		} else {
			echo '<div class="error">✗ <strong>Updater Not Initialized!</strong> Plugin instance does not have updater checker.</div>';
		}
		
		// Check global variable
		if ( isset( $GLOBALS['meowseo_updater_checker'] ) ) {
			echo '<div class="success">✓ <strong>Global Variable Set:</strong> $GLOBALS[\'meowseo_updater_checker\'] exists</div>';
		} else {
			echo '<div class="warning">⚠ <strong>Global Variable Not Set:</strong> $GLOBALS[\'meowseo_updater_checker\'] does not exist</div>';
		}
		?>
		
		<h2>📋 Registered Hooks on 'pre_set_site_transient_update_plugins'</h2>
		
		<?php
		global $wp_filter;
		
		if ( ! isset( $wp_filter['pre_set_site_transient_update_plugins'] ) ) {
			echo '<div class="error">✗ <strong>No hooks registered on this filter!</strong></div>';
		} else {
			$hooks = $wp_filter['pre_set_site_transient_update_plugins']->callbacks;
			$meowseo_found = false;
			
			echo '<table>';
			echo '<thead><tr><th>Priority</th><th>Callback</th><th>Class/Function</th><th>Status</th></tr></thead>';
			echo '<tbody>';
			
			foreach ( $hooks as $priority => $callbacks ) {
				foreach ( $callbacks as $idx => $callback ) {
					$callback_info = '';
					$class_name = '';
					$is_meowseo = false;
					
					if ( is_array( $callback['function'] ) ) {
						if ( is_object( $callback['function'][0] ) ) {
							$class_name = get_class( $callback['function'][0] );
							$method_name = $callback['function'][1];
							$callback_info = $class_name . '::' . $method_name . '()';
							
							if ( strpos( $class_name, 'MeowSEO' ) !== false || strpos( $class_name, 'GitHub_Update_Checker' ) !== false ) {
								$is_meowseo = true;
								$meowseo_found = true;
							}
						} elseif ( is_string( $callback['function'][0] ) ) {
							$class_name = $callback['function'][0];
							$method_name = $callback['function'][1];
							$callback_info = $class_name . '::' . $method_name . '()';
						}
					} elseif ( is_string( $callback['function'] ) ) {
						$callback_info = $callback['function'] . '()';
					} elseif ( $callback['function'] instanceof Closure ) {
						$callback_info = 'Closure';
					} else {
						$callback_info = 'Unknown';
					}
					
					$row_class = $is_meowseo ? ' class="highlight"' : '';
					$badge = $is_meowseo ? '<span class="badge badge-success">MeowSEO</span>' : '<span class="badge badge-info">Other</span>';
					
					echo '<tr' . $row_class . '>';
					echo '<td><span class="code">' . esc_html( $priority ) . '</span></td>';
					echo '<td><span class="code">' . esc_html( $callback_info ) . '</span></td>';
					echo '<td>' . esc_html( $class_name ) . '</td>';
					echo '<td>' . $badge . '</td>';
					echo '</tr>';
				}
			}
			
			echo '</tbody></table>';
			
			if ( $meowseo_found ) {
				echo '<div class="success">✓ <strong>MeowSEO updater hook is registered!</strong></div>';
			} else {
				echo '<div class="error">✗ <strong>MeowSEO updater hook is NOT registered!</strong></div>';
				echo '<div class="warning">Possible causes:<ul>';
				echo '<li>Updater initialization failed</li>';
				echo '<li>Hook priority issue</li>';
				echo '<li>Code changes not applied correctly</li>';
				echo '</ul></div>';
			}
		}
		?>
		
		<h2>🔧 Other Important Hooks</h2>
		
		<?php
		$other_hooks = array(
			'plugins_api' => 'Plugin information popup',
			'upgrader_pre_download' => 'Modify package download URL',
			'upgrader_source_selection' => 'Rename GitHub folder',
			'upgrader_process_complete' => 'Update installed commit',
		);
		
		foreach ( $other_hooks as $hook_name => $description ) {
			$registered = isset( $wp_filter[ $hook_name ] );
			$meowseo_registered = false;
			
			if ( $registered ) {
				$hooks = $wp_filter[ $hook_name ]->callbacks;
				foreach ( $hooks as $priority => $callbacks ) {
					foreach ( $callbacks as $callback ) {
						if ( is_array( $callback['function'] ) && is_object( $callback['function'][0] ) ) {
							$class = get_class( $callback['function'][0] );
							if ( strpos( $class, 'MeowSEO' ) !== false || strpos( $class, 'GitHub_Update_Checker' ) !== false ) {
								$meowseo_registered = true;
								break 2;
							}
						}
					}
				}
			}
			
			if ( $meowseo_registered ) {
				echo '<div class="success">✓ <strong>' . esc_html( $hook_name ) . '</strong>: ' . esc_html( $description ) . '</div>';
			} else {
				echo '<div class="warning">⚠ <strong>' . esc_html( $hook_name ) . '</strong>: Not registered or not MeowSEO</div>';
			}
		}
		?>
		
		<h2>📊 Configuration</h2>
		
		<?php
		if ( isset( $GLOBALS['meowseo_updater_config'] ) ) {
			$config = $GLOBALS['meowseo_updater_config'];
			echo '<table>';
			echo '<tr><th>Setting</th><th>Value</th></tr>';
			echo '<tr><td>Repository Owner</td><td><span class="code">' . esc_html( $config->get_repo_owner() ) . '</span></td></tr>';
			echo '<tr><td>Repository Name</td><td><span class="code">' . esc_html( $config->get_repo_name() ) . '</span></td></tr>';
			echo '<tr><td>Branch</td><td><span class="code">' . esc_html( $config->get_branch() ) . '</span></td></tr>';
			echo '<tr><td>Auto Update Enabled</td><td><span class="code">' . ( $config->is_auto_update_enabled() ? 'Yes' : 'No' ) . '</span></td></tr>';
			echo '<tr><td>Check Frequency</td><td><span class="code">' . esc_html( $config->get_check_frequency() ) . ' seconds</span></td></tr>';
			echo '</table>';
		} else {
			echo '<div class="warning">⚠ Configuration not available in global variable</div>';
		}
		?>
		
		<h2>🎯 Next Steps</h2>
		<div class="info">
			<ol>
				<li>If all hooks are registered correctly, go to <a href="<?php echo admin_url( 'plugins.php' ); ?>">Plugins page</a></li>
				<li>Click "Cek Pembaruan" link under MeowSEO plugin</li>
				<li>Check if "Update Now" button appears (if there's a new commit on GitHub)</li>
				<li>Delete this test file after verification</li>
			</ol>
		</div>
		
		<div class="warning" style="margin-top: 30px;">
			<strong>⚠️ IMPORTANT:</strong> Delete this file after testing for security!
		</div>
	</div>
</body>
</html>
