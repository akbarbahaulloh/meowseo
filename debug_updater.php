<?php
require_once 'wp-load.php';

echo "Installed Commit (Option): " . get_option( 'meowseo_installed_commit', 'NOT SET' ) . "\n";
echo "GitHub Last Check (Option): " . get_option( 'meowseo_github_last_check', 'NOT SET' ) . "\n";
echo "Update Info (Transient): " . (get_transient( 'meowseo_github_update_info' ) ? 'CACHED' : 'NOT CACHED') . "\n";

$plugin = \MeowSEO\Plugin::instance();
$checker = $plugin->get_updater_checker();

$transient = new stdClass();
$transient->checked = ['meowseo/meowseo.php' => '1.0.0-b1b0d0d'];
$transient->response = [];

$result = $checker->check_for_update($transient);

echo "Update Object Found: " . (isset($result->response['meowseo/meowseo.php']) ? 'YES' : 'NO') . "\n";
if (isset($result->response['meowseo/meowseo.php'])) {
    echo "New Version: " . $result->response['meowseo/meowseo.php']->new_version . "\n";
}
