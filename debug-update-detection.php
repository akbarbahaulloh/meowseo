<?php
/**
 * Debug Update Detection Script
 * 
 * Script untuk men-debug kenapa update tidak terdeteksi.
 * Menampilkan informasi lengkap tentang commit installed vs commit terbaru.
 */

// Load WordPress
require_once 'wp-load.php';

// Verify user is logged in and has permission
if ( ! is_user_logged_in() || ! current_user_can( 'update_plugins' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Debug MeowSEO Update Detection</title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; padding: 20px; max-width: 1000px; margin: 0 auto; background: #f0f0f1; }
		.container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
		h1 { color: #23282d; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
		h2 { color: #23282d; margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
		.success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; padding: 12px; margin: 10px 0; }
		.error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 12px; margin: 10px 0; }
		.info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; padding: 12px; margin: 10px 0; }
		.warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; padding: 12px; margin: 10px 0; }
		.code { background: #f4f4f4; padding: 15px; border-radius: 4px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
		table { width: 100%; border-collapse: collapse; margin: 20px 0; }
		th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
		th { background: #f8f9fa; font-weight: 600; }
		.highlight { background: #fff3cd; font-weight: bold; }
		.button { display: inline-block; background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; border: none; cursor: pointer; }
		.button:hover { background: #005177; }
		.button-secondary { background: #6c757d; }
		.button-secondary:hover { background: #5a6268; }
	</style>
</head>
<body>
	<div class="container">
		<h1>🔍 Debug MeowSEO Update Detection</h1>
		
		<?php
		// Get plugin instance
		$plugin = \MeowSEO\Plugin::instance();
		$checker = $plugin->get_updater_checker();
		
		if ( ! $checker ) {
			echo '<div class="error"><strong>❌ ERROR:</strong> Updater checker tidak diinisialisasi!</div>';
			echo '<p>Pastikan perubahan kode sudah diterapkan dengan benar.</p>';
			exit;
		}
		
		echo '<div class="success"><strong>✓ Updater checker berhasil diinisialisasi</strong></div>';
		?>
		
		<h2>📊 Informasi Version</h2>
		
		<?php
		// Get current version from plugin header
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		$plugin_file = MEOWSEO_FILE;
		$plugin_data = get_plugin_data( $plugin_file, false, false );
		$plugin_version = $plugin_data['Version'] ?? 'Unknown';
		
		// Get installed commit from option
		$installed_commit_option = get_option( 'meowseo_installed_commit', '' );
		
		// Extract commit from version string
		$version_commit = '';
		if ( preg_match( '/^[\d.]+-([a-f0-9]{7,40})$/', $plugin_version, $matches ) ) {
			$version_commit = $matches[1];
		}
		
		echo '<table>';
		echo '<tr><th>Source</th><th>Value</th></tr>';
		echo '<tr><td>Plugin Version (Header)</td><td><code>' . esc_html( $plugin_version ) . '</code></td></tr>';
		echo '<tr><td>MEOWSEO_VERSION Constant</td><td><code>' . esc_html( MEOWSEO_VERSION ) . '</code></td></tr>';
		echo '<tr><td>Commit from Version</td><td><code>' . esc_html( $version_commit ?: 'Not found' ) . '</code></td></tr>';
		echo '<tr><td>Option: meowseo_installed_commit</td><td><code>' . esc_html( $installed_commit_option ?: 'Not set' ) . '</code></td></tr>';
		echo '</table>';
		
		// Determine current commit
		$current_commit = $installed_commit_option ?: $version_commit;
		
		if ( empty( $current_commit ) ) {
			echo '<div class="warning"><strong>⚠️ WARNING:</strong> Tidak ada commit ID yang terdeteksi sebagai "installed"!</div>';
			echo '<p>Ini bisa menyebabkan update tidak terdeteksi dengan benar.</p>';
		} else {
			echo '<div class="info"><strong>Current Installed Commit:</strong> <code>' . esc_html( $current_commit ) . '</code></div>';
		}
		?>
		
		<h2>🌐 GitHub API Check</h2>
		
		<?php
		// Clear cache first
		delete_transient( 'meowseo_github_update_info' );
		delete_transient( 'meowseo_github_changelog' );
		delete_option( 'meowseo_github_last_check' );
		
		echo '<div class="info">Cache cleared. Fetching fresh data from GitHub...</div>';
		
		// Get latest commit from GitHub
		$latest_commit = $checker->get_latest_commit();
		
		if ( ! $latest_commit ) {
			echo '<div class="error"><strong>❌ ERROR:</strong> Gagal mengambil data dari GitHub!</div>';
			echo '<p>Kemungkinan penyebab:</p>';
			echo '<ul>';
			echo '<li>Koneksi internet bermasalah</li>';
			echo '<li>GitHub API rate limit exceeded</li>';
			echo '<li>Repository tidak ditemukan</li>';
			echo '<li>Branch tidak ditemukan</li>';
			echo '</ul>';
			
			// Check rate limit
			$rate_limit = $checker->check_rate_limit();
			if ( $rate_limit['is_limited'] ) {
				$minutes = ceil( $rate_limit['retry_after'] / 60 );
				echo '<div class="warning"><strong>⚠️ Rate Limit:</strong> GitHub API rate limit exceeded. Retry after ' . $minutes . ' minute(s).</div>';
			}
		} else {
			echo '<div class="success"><strong>✓ Berhasil mengambil data dari GitHub</strong></div>';
			
			echo '<table>';
			echo '<tr><th>Field</th><th>Value</th></tr>';
			echo '<tr><td>Latest Commit SHA (Full)</td><td><code>' . esc_html( $latest_commit['sha'] ) . '</code></td></tr>';
			echo '<tr><td>Latest Commit SHA (Short)</td><td><code>' . esc_html( $latest_commit['short_sha'] ) . '</code></td></tr>';
			echo '<tr><td>Commit Message</td><td>' . esc_html( $latest_commit['message'] ) . '</td></tr>';
			echo '<tr><td>Author</td><td>' . esc_html( $latest_commit['author'] ) . '</td></tr>';
			echo '<tr><td>Date</td><td>' . esc_html( $latest_commit['date'] ) . '</td></tr>';
			echo '<tr><td>URL</td><td><a href="' . esc_url( $latest_commit['url'] ) . '" target="_blank">View on GitHub</a></td></tr>';
			echo '</table>';
		}
		?>
		
		<h2>🔄 Update Comparison</h2>
		
		<?php
		if ( $latest_commit && ! empty( $current_commit ) ) {
			$latest_short = $latest_commit['short_sha'];
			
			echo '<table>';
			echo '<tr><th>Type</th><th>Commit ID</th></tr>';
			echo '<tr><td>Current Installed</td><td><code>' . esc_html( $current_commit ) . '</code></td></tr>';
			echo '<tr><td>Latest on GitHub</td><td><code>' . esc_html( $latest_short ) . '</code></td></tr>';
			echo '</table>';
			
			if ( $current_commit === $latest_short ) {
				echo '<div class="success"><strong>✓ Plugin sudah menggunakan versi terbaru!</strong></div>';
				echo '<p>Tidak ada update yang tersedia.</p>';
			} else {
				echo '<div class="warning"><strong>⚠️ Update tersedia!</strong></div>';
				echo '<p>Versi baru: <code>' . esc_html( $latest_short ) . '</code></p>';
				echo '<p>Seharusnya tombol "Update Now" muncul di halaman Plugins.</p>';
			}
		} elseif ( empty( $current_commit ) ) {
			echo '<div class="error"><strong>❌ Tidak bisa membandingkan:</strong> Current commit tidak terdeteksi</div>';
		} else {
			echo '<div class="error"><strong>❌ Tidak bisa membandingkan:</strong> Gagal mengambil data dari GitHub</div>';
		}
		?>
		
		<h2>🔧 WordPress Update Transient</h2>
		
		<?php
		// Force WordPress to check for updates
		delete_site_transient( 'update_plugins' );
		
		// Trigger update check
		wp_update_plugins();
		
		// Get update transient
		$update_plugins = get_site_transient( 'update_plugins' );
		
		$plugin_basename = plugin_basename( MEOWSEO_FILE );
		
		if ( isset( $update_plugins->response[ $plugin_basename ] ) ) {
			$update_info = $update_plugins->response[ $plugin_basename ];
			
			echo '<div class="success"><strong>✓ Update terdeteksi di WordPress transient!</strong></div>';
			
			echo '<div class="code">';
			echo '<strong>Update Info:</strong><br>';
			echo 'New Version: ' . esc_html( $update_info->new_version ) . '<br>';
			echo 'Package URL: ' . esc_html( $update_info->package ) . '<br>';
			echo 'Plugin URL: ' . esc_html( $update_info->url ) . '<br>';
			echo '</div>';
			
			echo '<div class="info"><strong>✓ Tombol "Update Now" seharusnya muncul di halaman Plugins!</strong></div>';
		} else {
			echo '<div class="warning"><strong>⚠️ Update TIDAK terdeteksi di WordPress transient</strong></div>';
			echo '<p>Kemungkinan penyebab:</p>';
			echo '<ul>';
			echo '<li>Hook <code>pre_set_site_transient_update_plugins</code> tidak terdaftar</li>';
			echo '<li>Method <code>check_for_update()</code> tidak dipanggil</li>';
			echo '<li>Current commit sama dengan latest commit</li>';
			echo '<li>Error saat mengambil data dari GitHub</li>';
			echo '</ul>';
			
			// Check if hook is registered
			global $wp_filter;
			if ( isset( $wp_filter['pre_set_site_transient_update_plugins'] ) ) {
				echo '<div class="info"><strong>✓ Hook terdaftar</strong></div>';
			} else {
				echo '<div class="error"><strong>❌ Hook TIDAK terdaftar!</strong></div>';
			}
		}
		?>
		
		<h2>💡 Solusi</h2>
		
		<?php
		if ( empty( $current_commit ) ) {
			?>
			<div class="warning">
				<strong>Masalah: Current commit tidak terdeteksi</strong>
				<p><strong>Solusi:</strong></p>
				<ol>
					<li>Update version di <code>meowseo.php</code> dengan commit terbaru</li>
					<li>Atau set option <code>meowseo_installed_commit</code> secara manual</li>
				</ol>
				
				<form method="post" style="margin-top: 15px;">
					<?php wp_nonce_field( 'set_installed_commit', 'commit_nonce' ); ?>
					<input type="hidden" name="action" value="set_installed_commit">
					<label>Set Installed Commit ID:</label><br>
					<input type="text" name="commit_id" value="<?php echo esc_attr( $latest_commit['short_sha'] ?? '' ); ?>" style="width: 300px; padding: 5px;">
					<button type="submit" class="button">Set Commit ID</button>
				</form>
			</div>
			<?php
		} elseif ( $latest_commit && $current_commit !== $latest_commit['short_sha'] ) {
			?>
			<div class="info">
				<strong>Update tersedia tapi tidak muncul?</strong>
				<p><strong>Coba langkah berikut:</strong></p>
				<ol>
					<li>Clear semua cache (sudah dilakukan otomatis)</li>
					<li>Buka halaman <a href="<?php echo admin_url( 'plugins.php' ); ?>">Plugins</a></li>
					<li>Refresh halaman (Ctrl+F5)</li>
					<li>Cek apakah tombol "Update Now" muncul</li>
				</ol>
			</div>
			<?php
		}
		?>
		
		<h2>🔗 Actions</h2>
		
		<a href="<?php echo admin_url( 'plugins.php' ); ?>" class="button">Buka Halaman Plugins</a>
		<a href="<?php echo admin_url( 'update-core.php' ); ?>" class="button">Buka Halaman Updates</a>
		<a href="<?php echo admin_url( 'plugins.php?meowseo_action=check_update&_wpnonce=' . wp_create_nonce( 'meowseo_check_update' ) ); ?>" class="button">Cek Pembaruan Manual</a>
		<button onclick="location.reload()" class="button button-secondary">Refresh Debug</button>
		
		<div class="warning" style="margin-top: 30px;">
			<strong>⚠️ PENTING:</strong> Hapus file ini setelah selesai debugging untuk keamanan!
		</div>
	</div>
</body>
</html>

<?php
// Handle form submission
if ( isset( $_POST['action'] ) && $_POST['action'] === 'set_installed_commit' ) {
	if ( ! isset( $_POST['commit_nonce'] ) || ! wp_verify_nonce( $_POST['commit_nonce'], 'set_installed_commit' ) ) {
		wp_die( 'Security check failed' );
	}
	
	$commit_id = sanitize_text_field( $_POST['commit_id'] );
	if ( ! empty( $commit_id ) ) {
		update_option( 'meowseo_installed_commit', $commit_id );
		echo '<script>alert("Commit ID updated! Refresh page to see changes."); location.reload();</script>';
	}
}
?>
