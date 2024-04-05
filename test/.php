<?php
/*
Plugin Name: 
Plugin URI: 
Description: 
Version: 1.0
Author: 
Author URI: 
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: text-domain
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once(ABSPATH . 'wp-admin/includes/plugin.php');

use \Boot.php;
use \Main.php;

new Boot(__FILE__);

add_action('plugins_loaded', function () {
    load_plugin_textdomain(
        '-plugin',
        false,
        '//languages/'
    );

    new Main();
});