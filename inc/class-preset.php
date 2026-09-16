<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Preset registry — bepaalt de structuur van de homepage.
 * Een preset legt vast welke component-parts in welke volgorde worden geladen.
 * Kleur/font wordt los geregeld via TaxiTheme_Theme_Variant.
 */
class TaxiTheme_Preset {

    const OPTION_KEY    = 'taxitheme_preset';
    const DEFAULT_SLUG  = 'klassiek';

    const PRESETS = [
        'klassiek' => [
            'label'       => 'Klassiek',
            'description' => 'Overzichtelijke opbouw: hero, USPs, waarom, service-gebied, routes.',
        ],
        'bold' => [
            'label'       => 'Bold',
            'description' => 'Grote hero met routes bovenaan en directe contact-CTA.',
        ],
        'onepage' => [
            'label'       => 'One-page',
            'description' => 'Alles op één donkere pagina met scroll naar boekingsformulier. App-style met glass-cards.',
        ],
        'premium' => [
            'label'       => 'Premium',
            'description' => 'Rustig, editorial en fotografie-gedreven. Wit met subtle accent. Voor high-end services.',
        ],
        'simpel' => [
            'label'       => 'Simpel',
            'description' => 'Minimalistisch flat design — geen ronde hoeken, geen shadows. Snel boeken zonder afleiding.',
        ],
    ];

    public static function current() {
        $val = get_option(self::OPTION_KEY, self::DEFAULT_SLUG);
        return isset(self::PRESETS[$val]) ? $val : self::DEFAULT_SLUG;
    }

    public static function save($value) {
        $value = is_string($value) ? sanitize_key($value) : '';
        if (isset(self::PRESETS[$value])) {
            update_option(self::OPTION_KEY, $value);
            return true;
        }
        return false;
    }

    public static function all() {
        return self::PRESETS;
    }

    public static function label($slug = null) {
        $slug = $slug ?? self::current();
        return self::PRESETS[$slug]['label'] ?? '';
    }

    public static function body_class($slug = null) {
        $slug = $slug ?? self::current();
        return 'tt-preset-' . $slug;
    }

    public static function stylesheet_path($slug = null) {
        $slug = $slug ?? self::current();
        $path = get_template_directory() . '/assets/presets/' . $slug . '.css';
        return file_exists($path) ? $path : null;
    }

    public static function stylesheet_uri($slug = null) {
        $slug = $slug ?? self::current();
        if (self::stylesheet_path($slug) === null) {
            return null;
        }
        return get_template_directory_uri() . '/assets/presets/' . $slug . '.css';
    }

    /**
     * Directory met opgesplitste sectie-CSS files.
     * Prefer boven de single-file stylesheet: als deze map bestaat, laden we
     * de losse files (server-side geconcateneerd tot 1 inline blok).
     */
    public static function stylesheet_dir($slug = null) {
        $slug = $slug ?? self::current();
        $dir  = get_template_directory() . '/assets/presets/' . $slug;
        return is_dir($dir) ? $dir : null;
    }

    /**
     * Return alle .css files in de preset-map, alfabetisch gesorteerd.
     * `_base.css` en andere underscore-files komen zo automatisch vooraan.
     * Return leeg array als er geen map is.
     */
    public static function stylesheet_files($slug = null) {
        $slug = $slug ?? self::current();
        $dir  = self::stylesheet_dir($slug);
        if (!$dir) return [];
        $files = glob($dir . '/*.css');
        if (!$files) return [];
        sort($files);
        return $files;
    }

    /**
     * Geconcateneerde CSS content voor de huidige preset.
     * Prefereert de gesplitste map; valt terug op single-file als die er nog is.
     * Return lege string als beide ontbreken (bv. Klassiek — die gebruikt alleen basis-styling).
     */
    public static function inline_css($slug = null) {
        $slug  = $slug ?? self::current();
        $files = self::stylesheet_files($slug);

        if (empty($files)) {
            $single = self::stylesheet_path($slug);
            if ($single) {
                return (string) file_get_contents($single);
            }
            return '';
        }

        $out = '';
        foreach ($files as $file) {
            $name = basename($file);
            $out .= "\n/* ===== {$slug}/{$name} ===== */\n";
            $out .= file_get_contents($file);
            $out .= "\n";
        }
        return $out;
    }

    /**
     * Hoogste mtime van alle preset-CSS bronnen — voor cache-busting via ?ver=.
     */
    public static function assets_version($slug = null) {
        $slug  = $slug ?? self::current();
        $files = self::stylesheet_files($slug);
        if (empty($files)) {
            $single = self::stylesheet_path($slug);
            if ($single) return (string) filemtime($single);
            return '0';
        }
        $mtime = 0;
        foreach ($files as $file) {
            $mtime = max($mtime, (int) filemtime($file));
        }
        return (string) $mtime;
    }

    /**
     * Zoekt het juiste template voor een role + huidige preset.
     * Lookup-volgorde:
     *   1. templates/{preset}/{role}.php   — nieuwe modulaire structuur (aanbevolen)
     *   2. templates/{role}-{preset}.php   — oude preset-suffix structuur (legacy)
     *   3. templates/{role}.php            — role-fallback (geldt voor alle presets)
     * Zo kunnen presets één-voor-één worden gemigreerd zonder breaking changes.
     */
    public static function template_path($role) {
        $role = sanitize_file_name($role);
        $slug = self::current();

        $modular_file = get_template_directory() . '/templates/' . $slug . '/' . $role . '.php';
        if (file_exists($modular_file)) {
            return $modular_file;
        }

        $preset_file = get_template_directory() . '/templates/' . $role . '-' . $slug . '.php';
        if (file_exists($preset_file)) {
            return $preset_file;
        }

        $default_file = get_template_directory() . '/templates/' . $role . '.php';
        if (file_exists($default_file)) {
            return $default_file;
        }

        return null;
    }
}
