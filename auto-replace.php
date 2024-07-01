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
        add_action('wp_ajax_get_plugin_functions_classes', array($this, 'get_plugin_functions_classes_ajax'));
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
                <select id="plugin-select" name="plugin" onchange="fetchFunctionsClasses(this.value)">
                    <?php $this->get_plugins(); ?>
                </select>
                <div id="plugin-functions-classes">
                    <div id="plugin-functions">
                        <h2>Functions</h2>
                        <!-- Functions will be displayed here -->
                    </div>
                    <div id="plugin-classes">
                        <h2>Classes</h2>
                        <!-- Classes will be displayed here -->
                    </div>
                </div>
                <button type="submit" name="replace">Replace</button>
            </form>
        </div>
        <script type="text/javascript">
            function fetchFunctionsClasses(pluginFile) {
                if (pluginFile === '') return;

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'get_plugin_functions_classes',
                        plugin: pluginFile
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        jQuery('#plugin-functions').html('<h2>Functions</h2>' + data.functions);
                        jQuery('#plugin-classes').html('<h2>Classes</h2>' + data.classes);
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

    public function get_plugin_functions_classes($plugin_file) {
        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
        if (!is_dir($plugin_dir)) {
            return ['functions' => [], 'classes' => []];
        }

        $functions = [];
        $classes = [];
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

        return ['functions' => $functions, 'classes' => $classes];
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

    public function get_plugin_functions_classes_ajax() {
        if (!isset($_POST['plugin'])) {
            wp_die();
        }

        $plugin_file = sanitize_text_field($_POST['plugin']);
        $result = $this->get_plugin_functions_classes($plugin_file);

        $functions_html = '';
        foreach ($result['functions'] as $function) {
            $functions_html .= '<div>' . esc_html($function) . '</div>';
        }

        $classes_html = '';
        foreach ($result['classes'] as $class) {
            $classes_html .= '<div>' . esc_html($class) . '</div>';
        }

        echo json_encode([
            'functions' => $functions_html,
            'classes' => $classes_html,
        ]);

        wp_die();
    }
}

new AutoReplace();
?>
