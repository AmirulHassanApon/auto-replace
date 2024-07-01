<?php
/**
 * Plugin Name: Auto Replace
 * Description: A plugin to automatically replace functions and classes of selected plugins.
 * Version: 1.1
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AutoReplace {

    public function __construct() {
        add_action('admin_menu', array($this, 'create_admin_page'));
        add_action('wp_ajax_get_plugin_details', array($this, 'get_plugin_details_ajax'));
        add_action('wp_ajax_replace_names', array($this, 'replace_names_ajax'));
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
                <label for="old-name">Old Name:</label>
                <input type="text" id="old-name" name="old_name">
                <label for="new-name">New Name:</label>
                <input type="text" id="new-name" name="new_name">
                <button type="button" onclick="replaceNames()">Replace</button>
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

            function replaceNames() {
                var pluginFile = jQuery('#plugin-select').val();
                var oldName = jQuery('#old-name').val();
                var newName = jQuery('#new-name').val();

                if (pluginFile === '' || oldName === '' || newName === '') return;

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'replace_names',
                        plugin: pluginFile,
                        old_name: oldName,
                        new_name: newName
                    },
                    success: function(response) {
                        alert(response);
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
                    $functions[] = $tokens[$i + 2][1]; // Function name
                }
                if ($tokens[$i][0] == T_CLASS) {
                    $classes[] = $tokens[$i + 2][1]; // Class name
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

        $functions_html = '<table>';
        foreach ($result['functions'] as $function) {
            $functions_html .= '<tr><td>' . esc_html($function) . '</td></tr>';
        }
        $functions_html .= '</table>';

        $classes_html = '<table>';
        foreach ($result['classes'] as $class) {
            $classes_html .= '<tr><td>' . esc_html($class) . '</td></tr>';
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

    public function replace_names_ajax() {
        if (!isset($_POST['plugin']) || !isset($_POST['old_name']) || !isset($_POST['new_name'])) {
            wp_die();
        }

        $plugin_file = sanitize_text_field($_POST['plugin']);
        $old_name = sanitize_text_field($_POST['old_name']);
        $new_name = sanitize_text_field($_POST['new_name']);

        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
        $files = $this->get_all_files($plugin_dir);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $updated_content = str_replace($old_name, $new_name, $content);
            file_put_contents($file, $updated_content);
        }

        echo 'Replacement completed!';
        wp_die();
    }
}

new AutoReplace();
?>
