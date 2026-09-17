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
        'bold' => [
            'routes' => 'templates/bold/routes-cards',
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
            $scheme = sanitize_key($content['section_backgrounds'][$preset][$key] ?? 'auto');
            $scheme_map = [
                'klassiek' => ['base', 'surface', 'dark', 'accent'],
                'bold'     => ['base', 'alternate', 'light', 'white', 'accent'],
                'onepage'  => ['base', 'surface', 'accent'],
                'premium'  => ['base', 'surface', 'dark', 'accent'],
                'simpel'   => ['base', 'surface', 'contrast', 'accent'],
            ];
            $allowed_schemes = $scheme_map[$preset] ?? [];

            if (!in_array($scheme, $allowed_schemes, true)) {
                get_template_part($template_slug);
                continue;
            }

            // De templates beheren hun eigen section-markup. Een lichte wrapper
            // laat ons kleurvariabelen per component scopen zonder alle templates
            // preset-specifiek te maken. Lege/uitgeschakelde templates geven geen wrapper.
            ob_start();
            get_template_part($template_slug);
            $section_html = ob_get_clean();
            if (trim($section_html) === '') continue;

            printf(
                '<div class="tt-home-component tt-home-component--%1$s tt-section-scheme--%2$s">%3$s</div>',
                esc_attr($key),
                esc_attr($scheme),
                $section_html
            );
        }
    }
}
