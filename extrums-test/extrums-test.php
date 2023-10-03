<?php
/*
 * Plugin Name: Extrums Test
 * Description: Test Plugin
 * Author: Kyrylo Popov
 * Author URI: https://github.com/kirill-popov/
 */

use Extrums\ExtrumsTestPlugin;

function load_files(array $files=[]): void
{
    if (!empty($files)) {
        foreach ($files as $file) {
            require_once($file);
        }
    }
}

// require_once __DIR__ . '/vendor/autoload.php';
$path = plugin_dir_path(__FILE__) . '/src/*.php';
$files = glob($path);
load_files($files);

$plugin = new ExtrumsTestPlugin();
$plugin->run();
