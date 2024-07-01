<?php
/**
 * Plugin Name: Auto Replace
 * Description: A plugin to automatically replace functions and classes of selected plugins.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: auto-replace
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 * Requires PHP: 7.0
 * Requires at least: 5.5
 * Plugin URI:  https://wordpress.org/plugins/auto-replace/
 * GitHub Plugin URI: https://github.com/AmirulHassanApon/auto-replace
 * 
 * @package auto-replace
 * @version 1.0.0
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AutoReplace {

    public function __construct() {
        $this->includes();
        $this->hooks();
    }

    private function includes() {
        require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
        require_once plugin_dir_path(__FILE__) . 'includes/ajax-actions.php';
        require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
    }

    private function hooks() {
        add_action('admin_menu', array('AutoReplace_Admin', 'create_admin_page'));
        add_action('wp_ajax_get_plugin_details', array('AutoReplace_Ajax', 'get_plugin_details_ajax'));
        add_action('wp_ajax_replace_name', array('AutoReplace_Ajax', 'replace_name_ajax'));
    }
}

new AutoReplace();
