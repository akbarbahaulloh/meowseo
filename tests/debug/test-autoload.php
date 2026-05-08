<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...\n";

define('MEOWSEO_PATH', __DIR__ . '/');
define('ABSPATH', __DIR__ . '/');
echo "Constants defined\n";

require 'vendor/autoload.php';
echo "Composer autoload loaded\n";

require 'includes/class-autoloader.php';
echo "Autoloader loaded\n";

\MeowSEO\Autoloader::register();

try {
    $p = new \MeowSEO\Modules\Image_SEO\Pattern_Processor();
    echo "Pattern_Processor loaded successfully\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    $h = new \MeowSEO\Modules\Image_SEO\Image_SEO_Handler(
        new \MeowSEO\Options(),
        new \MeowSEO\Modules\Image_SEO\Pattern_Processor()
    );
    echo "Image_SEO_Handler loaded successfully\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
