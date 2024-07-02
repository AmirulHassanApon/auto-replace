<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AutoReplace_Helpers {

    public static function get_plugins() {
        $plugins = get_plugins();
        foreach ($plugins as $plugin_file => $plugin_data) {
            echo '<option value="' . esc_attr($plugin_file) . '">' . esc_html($plugin_data['Name']) . '</option>';
        }
    }

    public static function get_plugin_details($plugin_file) {
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
        $text_domain = self::get_text_domain($plugin_path);
        $files = self::get_all_files($plugin_dir);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $tokens = token_get_all($content);

            for ($i = 0; $i < count($tokens); $i++) {
                if ($tokens[$i][0] == T_FUNCTION) {
                    $functions[] = [
                        'name' => $tokens[$i + 2][1],
                        'file' => $file,
                        'line' => $tokens[$i][2]
                    ];
                }
                if ($tokens[$i][0] == T_CLASS) {
                    $classes[] = [
                        'name' => $tokens[$i + 2][1],
                        'file' => $file,
                        'line' => $tokens[$i][2]
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

    public static function get_text_domain($plugin_path) {
        $headers = [
            'TextDomain' => 'Text Domain',
        ];
        $data = get_file_data($plugin_path, $headers);
        return $data['TextDomain'] ?? '';
    }

    public static function get_all_files($dir) {
        $files = [];
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $files = array_merge($files, self::get_all_files($path));
            } else {
                if (pathinfo($path, PATHINFO_EXTENSION) == 'php') {
                    $files[] = $path;
                }
            }
        }

        return $files;
    }

    public static function generate_table_html($items, $type, $plugin_file) {
        $type_label = ucfirst($type);
        $html = '<table class="auto-replace-table">';
        $html .= '<tr><th>' . esc_html($type_label) . ' ' . esc_html__('name', 'auto-replace') . '</th><th>' . esc_html__('File', 'auto-replace') . '</th><th>' . esc_html__('Line', 'auto-replace') . '</th><th>' . esc_html__('Action', 'auto-replace') . '</th></tr>';
        foreach ($items as $item) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($item['name']) . '</td>';
            $html .= '<td>' . esc_html(dirname($item['file'])) . '</td>';
            $html .= '<td>' . esc_html($item['line']) . '</td>';
            $html .= '<td><input type="text" id="new-name-' . esc_attr($item['name']) . '" placeholder="' . esc_attr__('New Name', 'auto-replace') . '">';
            $html .= '<button type="button" onclick="replaceName(\'' . esc_js($plugin_file) . '\', \'' . esc_js($item['name']) . '\', document.getElementById(\'new-name-' . esc_attr($item['name']) . '\').value, \'' . esc_js($type) . '\')">' . esc_html__('Replace', 'auto-replace') . '</button></td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
    
        return $html;
    }
    
    

    public static function generate_text_domain_html($text_domain, $plugin_file) {
        $html = '<table class="auto-replace-table">';
        $html .= '<tr><th>' . esc_html__('Text Domain', 'auto-replace') . '</th><th>' . esc_html__('Action', 'auto-replace') . '</th></tr>';
        $html .= '<tr>';
        $html .= '<td>' . esc_html($text_domain) . '</td>';
        $html .= '<td><input type="text" id="new-text-domain" placeholder="' . esc_attr__('New Text Domain', 'auto-replace') . '">';
        $html .= '<button type="button" onclick="replaceName(\'' . esc_js($plugin_file) . '\', \'' . esc_js($text_domain) . '\', document.getElementById(\'new-text-domain\').value, \'text_domain\')">' . esc_html__('Replace', 'auto-replace') . '</button></td>';
        $html .= '</tr>';
        $html .= '</table>';

        return $html;
    }
}
