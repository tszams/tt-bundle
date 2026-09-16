<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rendert de reorderable secties van de home-page in de door de klant
 * ingestelde volgorde (home_content['section_order']).
 *
 * Elke key mapt naar een template_part in templates/shared/{key}.php.
 * Simpel-preset gebruikt z'n eigen services template (simpel/services) —
 * die override zit in de $preset_overrides map.
 */
class TaxiTheme_Home_Renderer {

    /**
     * Preset-specifieke templates voor bepaalde section keys.
     * Als een preset niet in de map staat, wordt templates/shared/{key} gebruikt.
     */
    private static $preset_overrides = [
        'simpel' => [
            'services' => 'templates/simpel/services',
        ],
    ];

    public static function render_sections() {
        $content = TaxiTheme_Home_Content::all();
        $order   = $content['section_order'] ?? array_keys(TaxiTheme_Home_Content::REORDERABLE_SECTIONS);
        $preset  = class_exists('TaxiTheme_Preset') ? TaxiTheme_Preset::current() : '';
        $overrides = self::$preset_overrides[$preset] ?? [];

        foreach ($order as $key) {
            $key = sanitize_key($key);
            if (!isset(TaxiTheme_Home_Content::REORDERABLE_SECTIONS[$key])) continue;

            $template_slug = $overrides[$key] ?? 'templates/shared/' . $key;
            get_template_part($template_slug);
        }
    }
}
