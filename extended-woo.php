<?php

/*
Plugin Name: Extended Woo
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: rockerBoy
Author URI:
License: A "Slug" license name e.g. GPL-3.0
*/

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

if (! defined('EWOO_PLUGIN_FILE')) {
    define('EWOO_PLUGIN_FILE', __FILE__);
}


use ExtendedWoo\Kernel;


$app = (new Kernel())->init();
