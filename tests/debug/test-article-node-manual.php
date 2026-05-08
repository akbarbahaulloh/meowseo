<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Starting test...\n";

require 'vendor/autoload.php';
echo "Loaded autoloader\n";

require 'tests/bootstrap.php';
echo "Loaded bootstrap\n";

require_once 'includes/helpers/class-abstract-schema-node.php';
echo "Loaded abstract node\n";

require_once 'includes/helpers/schema-nodes/class-article-node.php';
echo "Loaded article node\n";

use MeowSEO\Helpers\Schema_Nodes\Article_Node;
use MeowSEO\Options;

$post = new WP_Post([
	'ID' => 1,
	'post_type' => 'post',
	'post_title' => 'Test Article',
	'post_content' => 'This is test content for the article.',
	'post_author' => 1,
	'post_date_gmt' => '2024-01-01 12:00:00',
	'post_modified_gmt' => '2024-01-02 12:00:00',
	'comment_count' => 5,
]);
echo "Created post\n";

$options = new Options();
echo "Created options\n";

$node = new Article_Node(1, $post, $options);
echo "Created node\n";

echo "is_needed: " . ($node->is_needed() ? 'TRUE' : 'FALSE') . "\n";
$schema = $node->generate();
echo "@type: " . $schema['@type'] . "\n";
echo "Has speakable: " . (isset($schema['speakable']) ? 'YES' : 'NO') . "\n";
echo "Has wordCount: " . (isset($schema['wordCount']) ? 'YES' : 'NO') . "\n";
echo "Has commentCount: " . (isset($schema['commentCount']) ? 'YES' : 'NO') . "\n";
echo "\nSUCCESS!\n";
