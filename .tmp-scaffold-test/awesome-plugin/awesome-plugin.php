<?php
/**
Plugin Name: Awesome Plugin
Plugin URI: https://site.io/plugins/awesome
Description: Example plugin created from JSON config
Version: 1.0.0
Author: Your Name
Author URI: https://site.io
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: awesome-plugin
Domain Path: /languages
Requires PHP: 8.2
*/

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use AwesomePlugin\Boot;

new Boot(__FILE__);
