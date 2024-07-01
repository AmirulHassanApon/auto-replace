<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AutoReplace_Ajax {

    public static function get_plugin_details_ajax() {
        if (!isset($_POST['plugin'])) {
            wp_die();
        }

        $plugin_file = sanitize_text_field($_POST['plugin']);
        $result = AutoReplace_Helpers::get_plugin_details($plugin_file);

        $functions_html = AutoReplace_Helpers::generate_table_html($result['functions'], 'function', $plugin_file);
        $classes_html = AutoReplace_Helpers::generate_table_html($result['classes'], 'class', $plugin_file);
        $text_domain_html = AutoReplace_Helpers::generate_text_domain_html($result['text_domain'], $plugin_file);

        echo json_encode([
            'functions' => $functions_html,
            'classes' => $classes_html,
            'text_domain' => $text_domain_html,
        ]);

        wp_die();
    }

    public static function replace_name_ajax() {
        if (!isset($_POST['plugin']) || !isset($_POST['old_name']) || !isset($_POST['new_name']) || !isset($_POST['type'])) {
            wp_die();
        }

        $plugin_file = sanitize_text_field($_POST['plugin']);
        $old_name = sanitize_text_field($_POST['old_name']);
        $new_name = sanitize_text_field($_POST['new_name']);
        $type = sanitize_text_field($_POST['type']);

        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
        $files = AutoReplace_Helpers::get_all_files($plugin_dir);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $updated_content = str_replace($old_name, $new_name, $content);
            file_put_contents($file, $updated_content);
        }

        echo ucfirst($type) . ' ' . esc_html__('name replacement completed!', 'auto-replace');
        wp_die();
    }
}
