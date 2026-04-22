<?php
require_once 'wp-load.php';
$logs = get_option('meowseo_github_update_logs', []);
echo json_encode($logs, JSON_PRETTY_PRINT);
