<?php
if (!defined('ABSPATH')) {
    exit;
}

class TaxiTheme_Admin_Column {

    public static function init() {
        add_filter('manage_pages_columns',       [__CLASS__, 'add_column']);
        add_action('manage_pages_custom_column', [__CLASS__, 'render_column'], 10, 2);
    }

    public static function add_column($columns) {
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new['taxitheme'] = 'TaxiTheme';
            }
        }
        return $new;
    }

    public static function render_column($column, $post_id) {
        if ($column !== 'taxitheme') {
            return;
        }
        $is_ours = (int) get_post_meta($post_id, TaxiTheme_Installer::META_FLAG, true);
        if (!$is_ours) {
            echo '<span style="color:#c3c4c7;">—</span>';
            return;
        }
        $role = get_post_meta($post_id, TaxiTheme_Installer::META_ROLE, true);
        printf(
            '<span style="display:inline-block;padding:2px 8px;background:#0f0f10;color:#fff;border-radius:4px;font-size:11px;font-weight:600;">TaxiTheme</span> <span style="color:#666;font-size:12px;margin-left:4px;">%s</span>',
            esc_html($role ?: 'page')
        );
    }
}
