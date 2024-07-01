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
 * @author AmirulHassanApon
 * @copyright 2024
 * @category WordPress Plugin
 * @author <a href="https://github.com/AmirulHassanApon">@AmirulHassanApon</a>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AutoReplace {

    public function __construct() {
        add_action('admin_menu', array($this, 'auto_replace_create_admin_page'));
        add_action('wp_ajax_get_plugin_details', array($this, 'auto_replace_get_plugin_details_ajax'));
        add_action('wp_ajax_replace_name', array($this, 'auto_replace_name_ajax'));
    }

    public function auto_replace_create_admin_page() {
        add_menu_page(
            __('Auto Replace', 'auto-replace'),
            __('Auto Replace', 'auto-replace'),
            'manage_options',
            'auto-replace',
            array($this, 'admin_page_html'),
            'dashicons-update',
            20
        );

        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('auto-replace-admin-style', plugins_url('assets/css/admin-style.css', __FILE__));
    }

    public function admin_page_html() {
        ?>
        <div class="auto-replace-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="">
                <label for="plugin-select"><?php esc_html_e('Select Plugin:', 'auto-replace'); ?></label>
                <select id="plugin-select" name="plugin" onchange="fetchPluginDetails(this.value)">
                    <?php $this->get_plugins(); ?>
                </select>
                <div id="plugin-details">
                    <div id="plugin-functions">
                        <h2><?php esc_html_e('Functions', 'auto-replace'); ?></h2>
                        <!-- Functions will be displayed here -->
                    </div>
                    <div id="plugin-classes">
                        <h2><?php esc_html_e('Classes', 'auto-replace'); ?></h2>
                        <!-- Classes will be displayed here -->
                    </div>
                    <div id="plugin-text-domain">
                        <h2><?php esc_html_e('Text Domain', 'auto-replace'); ?></h2>
                        <!-- Text Domain will be displayed here -->
                    </div>
                    
                </div>
            </form>
        </div>
        <script type="text/javascript">
            function fetchPluginDetails(pluginFile) {
                if (pluginFile === '') return;

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'get_plugin_details',
                        plugin: pluginFile
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        jQuery('#plugin-functions').html('<h2><?php esc_html_e('Functions', 'auto-replace'); ?></h2>' + data.functions);
                        jQuery('#plugin-classes').html('<h2><?php esc_html_e('Classes', 'auto-replace'); ?></h2>' + data.classes);
                        jQuery('#plugin-text-domain').html('<h2><?php esc_html_e('Text Domain', 'auto-replace'); ?></h2>' + data.text_domain);
                        
                    }
                });
            }

            function replaceName(pluginFile, oldName, newName, type) {
                if (pluginFile === '' || oldName === '' || newName === '') return;

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'replace_name',
                        plugin: pluginFile,
                        old_name: oldName,
                        new_name: newName,
                        type: type
                    },
                    success: function(response) {
                        alert(response);
                        fetchPluginDetails(pluginFile); // Refresh the details after replacement
                    }
                });
            }
        </script>
        <?php
    }

    public function get_plugins() {
        $plugins = get_plugins();
        foreach ($plugins as $plugin_file => $plugin_data) {
            echo '<option value="' . esc_attr($plugin_file) . '">' . esc_html($plugin_data['Name']) . '</option>';
        }
    }

    public function get_plugin_details($plugin_file) {
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
        if (!is_dir($plugin_dir)) {
            return [
                'functions' => [],
                'classes' => [],
                'text_domain' => '',
                
            ];
        }

        $functions = [];
        $classes = [];
        $text_domain = $this->get_text_domain($plugin_path);
        $files = $this->get_all_files($plugin_dir);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $tokens = token_get_all($content);

            for ($i = 0; $i < count($tokens); $i++) {
                if ($tokens[$i][0] == T_FUNCTION) {
                    $functions[] = [
                        'name' => $tokens[$i + 2][1], // Function name
                        'file' => $file, // File path
                        'line' => $tokens[$i][2] // Line number
                    ];
                }
                if ($tokens[$i][0] == T_CLASS) {
                    $classes[] = [
                        'name' => $tokens[$i + 2][1], // Class name
                        'file' => $file, // File path
                        'line' => $tokens[$i][2] // Line number
                    ];
                }
            }
        }

        return [
            'functions' => $functions,
            'classes' => $classes,
            'text_domain' => $text_domain,
            
        ];
    }

    private function get_text_domain($plugin_path) {
        $headers = [
            'TextDomain' => 'Text Domain',
        ];
        $data = get_file_data($plugin_path, $headers);
        return $data['TextDomain'] ?? '';
    }

    private function get_all_files($dir) {
        $files = [];
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $files = array_merge($files, $this->get_all_files($path));
            } else {
                if (pathinfo($path, PATHINFO_EXTENSION) == 'php') {
                    $files[] = $path;
                }
            }
        }

        return $files;
    }

    public function auto_replace_get_plugin_details_ajax() {
        if (!isset($_POST['plugin'])) {
            wp_die();
        }

        $plugin_file = sanitize_text_field($_POST['plugin']);
        $result = $this->get_plugin_details($plugin_file);

        $functions_html = '<table class="auto-replace-table">';
        $functions_html .= '<tr><th>' . esc_html__('Name', 'auto-replace') . '</th><th>' . esc_html__('File', 'auto-replace') . '</th><th>' . esc_html__('Line', 'auto-replace') . '</th><th>' . esc_html__('Action', 'auto-replace') . '</th></tr>';
        foreach ($result['functions'] as $function) {
            $functions_html .= '<tr>';
            $functions_html .= '<td>' . esc_html($function['name']) . '</td>';
            $functions_html .= '<td>' . esc_html(dirname($function['file'])) . '</td>';
            $functions_html .= '<td>' . esc_html($function['line']) . '</td>';
            $functions_html .= '<td><input type="text" id="new-name-' . esc_attr($function['name']) . '" placeholder="' . esc_attr__('New Name', 'auto-replace') . '">';
            $functions_html .= '<button type="button" onclick="replaceName(\'' . esc_js($plugin_file) . '\', \'' . esc_js($function['name']) . '\', document.getElementById(\'new-name-' . esc_attr($function['name']) . '\').value, \'function\')">' . esc_html__('Replace', 'auto-replace') . '</button></td>';
            $functions_html .= '</tr>';
        }
        $functions_html .= '</table>';

        $classes_html = '<table class="auto-replace-table">';
        $classes_html .= '<tr><th>' . esc_html__('Name', 'auto-replace') . '</th><th>' . esc_html__('File', 'auto-replace') . '</th><th>' . esc_html__('Line', 'auto-replace') . '</th><th>' . esc_html__('Action', 'auto-replace') . '</th></tr>';
        foreach ($result['classes'] as $class) {
            $classes_html .= '<tr>';
            $classes_html .= '<td>' . esc_html($class['name']) . '</td>';
            $classes_html .= '<td>' . esc_html(basename($class['file'])) . '</td>';
            $classes_html .= '<td>' . esc_html($class['line']) . '</td>';
            $classes_html .= '<td><input type="text" id="new-name-' . esc_attr($class['name']) . '" placeholder="' . esc_attr__('New Name', 'auto-replace') . '">';
            $classes_html .= '<button type="button" onclick="replaceName(\'' . esc_js($plugin_file) . '\', \'' . esc_js($class['name']) . '\', document.getElementById(\'new-name-' . esc_attr($class['name']) . '\').value, \'class\')">' . esc_html__('Replace', 'auto-replace') . '</button></td>';
            $classes_html .= '</tr>';
        }
        $classes_html .= '</table>';

        $text_domain_html = '<div>' . esc_html($result['text_domain']) . '</div>';
        

        echo json_encode([
            'functions' => $functions_html,
            'classes' => $classes_html,
            'text_domain' => $text_domain_html,
            
        ]);

        wp_die();
    }

    public function auto_replace_name_ajax() {
        if (!isset($_POST['plugin']) || !isset($_POST['old_name']) || !isset($_POST['new_name']) || !isset($_POST['type'])) {
            wp_die();
        }

        $plugin_file = sanitize_text_field($_POST['plugin']);
        $old_name = sanitize_text_field($_POST['old_name']);
        $new_name = sanitize_text_field($_POST['new_name']);
        $type = sanitize_text_field($_POST['type']);

        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
        $files = $this->get_all_files($plugin_dir);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $updated_content = str_replace($old_name, $new_name, $content);
            file_put_contents($file, $updated_content);
        }

        echo ucfirst($type) . ' ' . esc_html__('Name replacement completed!', 'auto-replace');
        wp_die();
    }
}

new AutoReplace();
