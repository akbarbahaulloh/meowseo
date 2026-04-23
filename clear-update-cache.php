<?php
/**
 * Clear Update Cache Script
 * 
 * Script untuk membersihkan semua cache terkait update MeowSEO.
 * Jalankan script ini melalui browser atau CLI untuk memaksa WordPress
 * melakukan pengecekan update ulang.
 * 
 * CARA PAKAI:
 * 1. Upload file ini ke root WordPress (sejajar dengan wp-load.php)
 * 2. Akses melalui browser: http://yoursite.com/clear-update-cache.php
 * 3. Hapus file ini setelah selesai (untuk keamanan)
 */

// Load WordPress
require_once 'wp-load.php';

// Verify user is logged in and has permission
if ( ! is_user_logged_in() || ! current_user_can( 'update_plugins' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

echo '<html><head><title>Clear MeowSEO Update Cache</title>';
echo '<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
h1 { color: #23282d; }
.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin: 10px 0; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 12px; border-radius: 4px; margin: 10px 0; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px; border-radius: 4px; margin: 10px 0; }
.code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
.button { display: inline-block; background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; }
.button:hover { background: #005177; }
</style></head><body>';

echo '<h1>🧹 Clear MeowSEO Update Cache</h1>';

// Clear MeowSEO specific caches
$cleared = array();

if ( delete_transient( 'meowseo_github_update_info' ) ) {
	$cleared[] = 'meowseo_github_update_info (transient)';
}

if ( delete_transient( 'meowseo_github_changelog' ) ) {
	$cleared[] = 'meowseo_github_changelog (transient)';
}

if ( delete_transient( 'meowseo_github_rate_limit' ) ) {
	$cleared[] = 'meowseo_github_rate_limit (transient)';
}

if ( delete_option( 'meowseo_github_last_check' ) ) {
	$cleared[] = 'meowseo_github_last_check (option)';
}

// Clear WordPress update cache
if ( delete_site_transient( 'update_plugins' ) ) {
	$cleared[] = 'update_plugins (site transient)';
}

// Display results
if ( ! empty( $cleared ) ) {
	echo '<div class="success"><strong>✓ Cache berhasil dibersihkan:</strong><ul>';
	foreach ( $cleared as $item ) {
		echo '<li>' . esc_html( $item ) . '</li>';
	}
	echo '</ul></div>';
} else {
	echo '<div class="info"><strong>ℹ️ Tidak ada cache yang perlu dibersihkan.</strong></div>';
}

// Check if updater is initialized
echo '<h2>🔍 Status Updater</h2>';

$plugin = \MeowSEO\Plugin::instance();
$checker = $plugin->get_updater_checker();

if ( $checker ) {
	echo '<div class="success"><strong>✓ Updater berhasil diinisialisasi</strong></div>';
	
	// Check if hook is registered
	global $wp_filter;
	$hook_registered = false;
	
	if ( isset( $wp_filter['pre_set_site_transient_update_plugins'] ) ) {
		$hooks = $wp_filter['pre_set_site_transient_update_plugins']->callbacks;
		
		foreach ( $hooks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && is_object( $callback['function'][0] ) ) {
					$class = get_class( $callback['function'][0] );
					if ( strpos( $class, 'GitHub_Update_Checker' ) !== false ) {
						$hook_registered = true;
						echo '<div class="success"><strong>✓ Hook terdaftar pada priority ' . esc_html( $priority ) . '</strong></div>';
						break 2;
					}
				}
			}
		}
	}
	
	if ( ! $hook_registered ) {
		echo '<div class="warning"><strong>⚠️ Hook belum terdaftar!</strong><br>Coba refresh halaman ini atau deactivate/activate plugin.</div>';
	}
	
	// Get current and latest commit
	echo '<h2>📊 Informasi Versi</h2>';
	
	$current_commit = get_option( 'meowseo_installed_commit', '' );
	if ( empty( $current_commit ) ) {
		// Try to extract from version
		if ( defined( 'MEOWSEO_VERSION' ) ) {
			$version = MEOWSEO_VERSION;
			if ( preg_match( '/^[\d.]+-([a-f0-9]{7,40})$/', $version, $matches ) ) {
				$current_commit = $matches[1];
			}
		}
	}
	
	echo '<div class="code">';
	echo '<strong>Current Installed Commit:</strong> ' . ( $current_commit ? esc_html( $current_commit ) : '<em>Not set</em>' ) . '<br>';
	echo '<strong>Plugin Version:</strong> ' . ( defined( 'MEOWSEO_VERSION' ) ? esc_html( MEOWSEO_VERSION ) : '<em>Unknown</em>' );
	echo '</div>';
	
	// Try to get latest commit from GitHub
	echo '<div class="info"><strong>ℹ️ Mengambil informasi commit terbaru dari GitHub...</strong></div>';
	
	$latest_commit = $checker->get_latest_commit();
	
	if ( $latest_commit ) {
		echo '<div class="code">';
		echo '<strong>Latest GitHub Commit:</strong> ' . esc_html( $latest_commit['short_sha'] ) . '<br>';
		echo '<strong>Commit Message:</strong> ' . esc_html( $latest_commit['message'] ) . '<br>';
		echo '<strong>Author:</strong> ' . esc_html( $latest_commit['author'] ) . '<br>';
		echo '<strong>Date:</strong> ' . esc_html( $latest_commit['date'] );
		echo '</div>';
		
		// Check if update is available
		if ( $current_commit && $current_commit !== $latest_commit['short_sha'] ) {
			echo '<div class="success"><strong>🎉 Update tersedia!</strong><br>';
			echo 'Versi baru: ' . esc_html( $latest_commit['short_sha'] ) . '</div>';
		} elseif ( $current_commit === $latest_commit['short_sha'] ) {
			echo '<div class="info"><strong>✓ Plugin sudah menggunakan versi terbaru</strong></div>';
		}
	} else {
		echo '<div class="warning"><strong>⚠️ Gagal mengambil informasi dari GitHub</strong><br>';
		echo 'Periksa koneksi internet atau rate limit GitHub API.</div>';
	}
	
} else {
	echo '<div class="warning"><strong>⚠️ Updater belum diinisialisasi!</strong><br>';
	echo 'Pastikan perubahan kode sudah diterapkan dengan benar.</div>';
}

// Action buttons
echo '<h2>🔗 Langkah Selanjutnya</h2>';
echo '<a href="' . admin_url( 'plugins.php' ) . '" class="button">Buka Halaman Plugins</a>';
echo '<a href="' . admin_url( 'update-core.php' ) . '" class="button">Buka Halaman Updates</a>';
echo '<a href="' . admin_url( 'plugins.php?meowseo_action=check_update&_wpnonce=' . wp_create_nonce( 'meowseo_check_update' ) ) . '" class="button">Cek Pembaruan Manual</a>';

echo '<div class="warning" style="margin-top: 30px;"><strong>⚠️ PENTING:</strong> Hapus file ini setelah selesai testing untuk keamanan!</div>';

echo '</body></html>';
