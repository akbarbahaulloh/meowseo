<?php
require_once dirname(__FILE__) . '/../wp-load.php';

$owner = 'akbarbahaulloh';
$repo = 'meowseo';
$branch = 'main';
$url = "https://api.github.com/repos/$owner/$repo/commits/$branch";

echo "Testing URL: $url\n";

$response = wp_remote_get($url, [
    'timeout'    => 10,
    'user-agent' => 'MeowSEO-Updater/1.0 (WordPress Plugin)',
]);

if (is_wp_error($response)) {
    echo "WP_Error: " . $response->get_error_message() . "\n";
} else {
    $code = wp_remote_retrieve_response_code($response);
    echo "Response Code: $code\n";
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (isset($data['sha'])) {
        echo "Latest Commit SHA: " . $data['sha'] . "\n";
    } else {
        echo "No SHA found in response.\n";
        print_r($data);
    }
}
