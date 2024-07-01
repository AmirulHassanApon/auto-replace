<?php
/**
 * Plugin Name: Auto Replace
 * Description: A plugin to automatically replace functions and classes of selected plugins.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AutoReplace {

    public function __construct() {
        add_action('admin_menu', array($this, 'create_admin_page'));
        add_action('wp_ajax_get_plugin_details', array($this, 'get_plugin_details_ajax'));
    }

    public function create_admin_page() {
        add_menu_page(
            'Auto Replace',
            'Auto Replace',
            'manage_options',
            'auto-replace',
            array($this, 'admin_page_html')
        );
    }

    public function admin_page_html() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="">
                <label for="plugin-select">Select Plugin:</label>
                <select id="plugin-select" name="plugin" onchange="fetchPluginDetails(this.value)">
                    <?php $this->get_plugins(); ?>
                </select>
                <div id="plugin-details">
                    <div id="plugin-functions">
                        <h2>Functions</h2>
                        <!-- Functions will be displayed here -->
                    </div>
                    <div id="plugin-classes">
                        <h2>Classes</h2>
                        <!-- Classes will be displayed here -->
                    </div>
                    <div id="plugin-text-domain">
                        <h2>Text Domain</h2>
                        <!-- Text Domain will be displayed here -->
                    </div>
                </div>
                <button type="submit" name="replace">Replace</button>
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
                        jQuery('#plugin-functions').html('<h2>Functions</h2>' + data.functions);
                        jQuery('#plugin-classes').html('<h2>Classes</h2>' + data.classes);
                        jQuery('#plugin-text-domain').html('<h2>Text Domain</h2>' + data.text_domain);
                    }
                });
            }
        </script>
        <style>
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            table, th, td {
                border: 1px solid #ddd;
            }
            th, td {
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
        </style>
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
                    $line = $tokens[$i][2]; // Line number
                    $function_name = $tokens[$i + 2][1]; // Function name
                    $functions[] = [
                        'name' => $function_name,
                        'file' => str_replace(WP_PLUGIN_DIR, '', $file),
                        'line' => $line,
                    ];
                }
                if ($tokens[$i][0] == T_CLASS) {
                    $line = $tokens[$i][2]; // Line number
                    $class_name = $tokens[$i + 2][1]; // Class name
                    $classes[] = [
                        'name' => $class_name,
                        'file' => str_replace(WP_PLUGIN_DIR, '', $file),
                        'line' => $line,
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

    public function get_plugin_details_ajax() {
        if (!isset($_POST['plugin'])) {
            wp_die();
        }

        $plugin_file = sanitize_text_field($_POST['plugin']);
        $result = $this->get_plugin_details($plugin_file);

        $functions_html = '<table><thead><tr><th>Name</th><th>File</th><th>Line</th></tr></thead><tbody>';
        foreach ($result['functions'] as $function) {
            $functions_html .= '<tr><td>' . esc_html($function['name']) . '</td><td>' . esc_html($function['file']) . '</td><td>' . esc_html($function['line']) . '</td></tr>';
        }
        $functions_html .= '</tbody></table>';

        $classes_html = '<table><thead><tr><th>Name</th><th>File</th><th>Line</th></tr></thead><tbody>';
        foreach ($result['classes'] as $class) {
            $classes_html .= '<tr><td>' . esc_html($class['name']) . '</td><td>' . esc_html($class['file']) . '</td><td>' . esc_html($class['line']) . '</td></tr>';
        }
        $classes_html .= '</tbody></table>';

        $text_domain_html = '<div>' . esc_html($result['text_domain']) . '</div>';

        echo json_encode([
            'functions' => $functions_html,
            'classes' => $classes_html,
            'text_domain' => $text_domain_html,
        ]);

        wp_die();
    }
}

new AutoReplace();
