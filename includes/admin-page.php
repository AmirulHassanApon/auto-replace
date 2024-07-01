<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AutoReplace_Admin {

    public static function create_admin_page() {
        add_menu_page(
            __('Auto Replace', 'auto-replace'),
            __('Auto Replace', 'auto-replace'),
            'manage_options',
            'auto-replace',
            array(__CLASS__, 'admin_page_html'),
            'dashicons-update',
            20
        );

        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }

    public static function enqueue_admin_scripts() {
        wp_enqueue_style('auto-replace-admin-style', plugins_url('../assets/css/admin-style.css', __FILE__));
        wp_enqueue_script('auto-replace-admin-script', plugins_url('../assets/js/admin.js', __FILE__), array('jquery'), null, true);
    }

    public static function admin_page_html() {
        ?>
        <div class="auto-replace-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="">
                <label for="plugin-select"><?php esc_html_e('Select Plugin:', 'auto-replace'); ?></label>
                <select id="plugin-select" name="plugin" onchange="fetchPluginDetails(this.value)">
                    <?php AutoReplace_Helpers::get_plugins(); ?>
                </select>
                <div id="plugin-details">
                    <div id="plugin-functions">
                        <h2><?php esc_html_e('Functions', 'auto-replace'); ?></h2>
                    </div>
                    <div id="plugin-classes">
                        <h2><?php esc_html_e('Classes', 'auto-replace'); ?></h2>
                    </div>
                    <div id="plugin-text-domain">
                        <h2><?php esc_html_e('Text Domain', 'auto-replace'); ?></h2>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}
