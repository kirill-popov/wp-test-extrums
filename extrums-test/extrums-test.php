<?php
/*
 * Plugin Name: Extrums Test
 * Description: Test Plugin
 * Author: Kyrylo Popov
 * Author URI: https://github.com/kirill-popov/
 */

use ExtrumsTest\Classes\ExtrumsTestPlugin;

if (file_exists( __DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

define('DIR_PATH', plugin_dir_path(__FILE__));
define('DIR_URL', plugin_dir_url(__FILE__));

if (class_exists('ExtrumsTestPlugin')) {
    $plugin = new ExtrumsTestPlugin();
    $plugin->run();
}
