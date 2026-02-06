<?php
/*
Plugin Name: Duplicate Post RB
Plugin URI: https://www.rbplugins.com/duplicate-post-rb
Description: Duplicate Post RB is a simple and lightweight plugin that allows you to duplicate any post easily 
Version: 1.5.8
Author: rbPlugins
Author URI: https://profiles.wordpress.org/rbplugins/
License: GPL2
Text Domain: duplicate-post-rb
Domain Path: /languages/
*/

if (!defined('WPINC')) {
    die();
}
define("RB_DUPLICATE_POST_VERSION", '1.5.8');
define("RB_DUPLICATE_POST_MAIN_FILE", __FILE__);
define("RB_DUPLICATE_POST_PATH", plugin_dir_path(__FILE__));
define("RB_DUPLICATE_POST_URL", plugin_dir_url(__FILE__));

define("RB_DUPLICATE_POST_PROFILE_TYPE_POST", 'rb-duplicate-post' );
define("RB_DUPLICATE_POST_ASSETS_PREFIX", 'rb-duplicate-post-');

include_once( RB_DUPLICATE_POST_PATH.'autoload.php' );
new \rbDuplicatePost\Bootstrap();
