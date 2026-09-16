<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Palette registry — kleurenkeuze per preset.
 * Elke palette is gekoppeld aan één preset. De picker in de admin toont alleen
 * de palettes die passen bij de huidige preset.
 *
 * Standaard palette per preset heeft geen eigen CSS-file (kleuren komen dan
 * uit style.css + de preset-CSS zelf).
 */
class TaxiTheme_Theme_Variant {

    const OPTION_KEY = 'taxitheme_theme_variant';

    const PALETTES = [
        // ---------- Klassiek ----------
        'klassiek-navy' => [
            'label'       => 'Navy + Geel',
            'description' => 'Donker navy header, taxi-geel accent, warme crème.',
            'preset'      => 'klassiek',
            'swatch'      => ['#14161f', '#f5b800', '#fdfcf7'],
            'stylesheet'  => null,
        ],
        'klassiek-licht' => [
            'label'       => 'Licht + Blauw',
            'description' => 'Wit ontwerp met blauw accent, luchtig en modern.',
            'preset'      => 'klassiek',
            'swatch'      => ['#ffffff', '#2563eb', '#f8fafc'],
            'stylesheet'  => 'klassiek-licht.css',
        ],

        // ---------- Bold ----------
        'bold-geel' => [
            'label'       => 'Zwart + Geel',
            'description' => 'Klassieke taxi-look.',
            'preset'      => 'bold',
            'swatch'      => ['#14161f', '#f5b800', '#ffffff'],
            'stylesheet'  => null,
        ],
        'bold-rood' => [
            'label'       => 'Zwart + Rood',
            'description' => 'Fel rood accent voor high-impact.',
            'preset'      => 'bold',
            'swatch'      => ['#14161f', '#dc2626', '#ffffff'],
            'stylesheet'  => 'bold-rood.css',
        ],
        'bold-blauw' => [
            'label'       => 'Zwart + Blauw',
            'description' => 'Elektrisch blauw accent.',
            'preset'      => 'bold',
            'swatch'      => ['#14161f', '#2563eb', '#ffffff'],
            'stylesheet'  => 'bold-blauw.css',
        ],

        // ---------- One-page ----------
        'onepage-geel' => [
            'label'       => 'Dark + Geel',
            'description' => 'Taxi-geel accent op app-donker.',
            'preset'      => 'onepage',
            'swatch'      => ['#0a0b10', '#f5b800', '#1a1a24'],
            'stylesheet'  => null,
        ],
        'onepage-cyaan' => [
            'label'       => 'Dark + Cyaan',
            'description' => 'Neon cyaan accent, tech-vibe.',
            'preset'      => 'onepage',
            'swatch'      => ['#0a0b10', '#06b6d4', '#1a1a24'],
            'stylesheet'  => 'onepage-cyaan.css',
        ],
        'onepage-rood' => [
            'label'       => 'Dark + Rood',
            'description' => 'Diep rood accent, krachtig contrast.',
            'preset'      => 'onepage',
            'swatch'      => ['#0a0b10', '#dc2626', '#1a1a24'],
            'stylesheet'  => 'onepage-rood.css',
        ],

        // ---------- Premium ----------
        'premium-amber' => [
            'label'       => 'Amber',
            'description' => 'Zachte amber accent op wit — warm en luxueus.',
            'preset'      => 'premium',
            'swatch'      => ['#ffffff', '#f59e0b', '#0f172a'],
            'stylesheet'  => 'premium-amber.css',
        ],
        'premium-mono' => [
            'label'       => 'Monochrome',
            'description' => 'Geen kleur-accent — puur zwart-wit, ultra-minimaal.',
            'preset'      => 'premium',
            'swatch'      => ['#ffffff', '#0f172a', '#f8fafc'],
            'stylesheet'  => 'premium-mono.css',
        ],
        'premium-emerald' => [
            'label'       => 'Emerald',
            'description' => 'Diep groen accent — vertrouwenwekkend en modern.',
            'preset'      => 'premium',
            'swatch'      => ['#ffffff', '#059669', '#0f172a'],
            'stylesheet'  => 'premium-emerald.css',
        ],

        // ---------- Simpel ----------
        'simpel-dark' => [
            'label'       => 'Dark + Blauw',
            'description' => 'Zwarte achtergrond met helderblauw accent. Taxi Service 365 stijl.',
            'preset'      => 'simpel',
            'swatch'      => ['#0a0a0a', '#00bcd4', '#ffffff'],
            'stylesheet'  => 'simpel-dark.css',
        ],
        'simpel-licht' => [
            'label'       => 'Licht + Blauw',
            'description' => 'Wit met helderblauw accent. Staxi stijl.',
            'preset'      => 'simpel',
            'swatch'      => ['#ffffff', '#00a3e0', '#1a1a1a'],
            'stylesheet'  => 'simpel-licht.css',
        ],
    ];

    public static function current() {
        $val = get_option(self::OPTION_KEY, self::default_for_preset(TaxiTheme_Preset::current()));
        return isset(self::PALETTES[$val]) ? $val : self::default_for_preset(TaxiTheme_Preset::current());
    }

    public static function save($value) {
        $value = is_string($value) ? sanitize_key($value) : '';
        if (isset(self::PALETTES[$value])) {
            update_option(self::OPTION_KEY, $value);
            return true;
        }
        return false;
    }

    public static function all() {
        return self::PALETTES;
    }

    /**
     * Alle palettes die bij een preset horen.
     */
    public static function for_preset($preset) {
        return array_filter(self::PALETTES, function ($p) use ($preset) {
            return ($p['preset'] ?? null) === $preset;
        });
    }

    /**
     * Default palette-slug voor een preset (eerste in de registry).
     */
    public static function default_for_preset($preset) {
        foreach (self::PALETTES as $slug => $meta) {
            if (($meta['preset'] ?? null) === $preset) {
                return $slug;
            }
        }
        return 'klassiek-navy';
    }

    /**
     * Preset waar een palette bij hoort.
     */
    public static function preset_of($slug) {
        return self::PALETTES[$slug]['preset'] ?? null;
    }

    public static function label($slug = null) {
        $slug = $slug ?? self::current();
        return self::PALETTES[$slug]['label'] ?? '';
    }

    public static function body_class($slug = null) {
        $slug = $slug ?? self::current();
        return 'tt-palette-' . $slug;
    }

    public static function stylesheet_path($slug = null) {
        $slug = $slug ?? self::current();
        $file = self::PALETTES[$slug]['stylesheet'] ?? null;
        if (!$file) return null;
        $path = get_template_directory() . '/assets/palettes/' . $file;
        return file_exists($path) ? $path : null;
    }

    public static function stylesheet_uri($slug = null) {
        $slug = $slug ?? self::current();
        $file = self::PALETTES[$slug]['stylesheet'] ?? null;
        if (!$file || self::stylesheet_path($slug) === null) {
            return null;
        }
        return get_template_directory_uri() . '/assets/palettes/' . $file;
    }
}
