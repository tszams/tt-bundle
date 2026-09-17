<?php
if (!defined('ABSPATH')) {
    exit;
}


require_once get_template_directory() . '/inc/class-icons.php';
require_once get_template_directory() . '/inc/class-setup.php';
require_once get_template_directory() . '/inc/class-company-info.php';
require_once get_template_directory() . '/inc/class-theme-variant.php';
require_once get_template_directory() . '/inc/class-preset.php';
require_once get_template_directory() . '/inc/class-installer.php';
require_once get_template_directory() . '/inc/class-home-content.php';
require_once get_template_directory() . '/inc/class-home-renderer.php';
require_once get_template_directory() . '/inc/class-admin-notice.php';
require_once get_template_directory() . '/inc/class-admin-column.php';
require_once get_template_directory() . '/inc/class-settings.php';
require_once get_template_directory() . '/inc/class-page-editor.php';
require_once get_template_directory() . '/inc/class-page-meta.php';
require_once get_template_directory() . '/inc/class-wizard.php';
require_once get_template_directory() . '/inc/class-schema.php';
require_once get_template_directory() . '/inc/class-seo.php';
require_once get_template_directory() . '/inc/class-booking.php';
require_once get_template_directory() . '/inc/class-updater.php';

TaxiTheme_Schema::init();
TaxiTheme_SEO::init();
TaxiTheme_Updater::init();

/**
 * Menu filter — verberg pages die op "onzichtbaar" staan of getrashed zijn.
 * Klant kan pages toggle-verbergen in Bedrijfsgegevens → Pages zonder de menu-DB aan te raken.
 */
add_filter('wp_nav_menu_objects', function ($items) {
    if (empty($items) || !class_exists('TaxiTheme_Installer')) return $items;

    // Bouw map: page_id => role
    $role_by_page = [];
    foreach (array_keys(TaxiTheme_Installer::roles()) as $role) {
        $pid = TaxiTheme_Installer::get_page_id($role);
        if ($pid) $role_by_page[$pid] = $role;
    }

    foreach ($items as $key => $item) {
        if ($item->object !== 'page') continue;
        $pid = (int) $item->object_id;

        // Getrashed page? Weghalen.
        $post = get_post($pid);
        if (!$post || $post->post_status === 'trash') {
            unset($items[$key]);
            continue;
        }

        // Gedeactiveerd of op onzichtbaar gezet in onze admin? Weghalen.
        if (isset($role_by_page[$pid]) && (
            !TaxiTheme_Installer::is_page_active($role_by_page[$pid])
            || !TaxiTheme_Installer::is_page_visible($role_by_page[$pid])
        )) {
            unset($items[$key]);
            continue;
        }

        // Boeken-page: verberg uit menu als de webapp-toggle uit staat.
        if (isset($role_by_page[$pid]) && $role_by_page[$pid] === 'boeken'
            && class_exists('TaxiTheme_Booking')
            && !TaxiTheme_Booking::webapp_page_enabled()) {
            unset($items[$key]);
        }
    }

    return array_values($items);
}, 10, 1);

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    register_nav_menus([
        'primary' => __('Primary Menu', 'taxitheme'),
    ]);
});

add_action('wp_enqueue_scripts', function () {
    /*
     * Skip alle theme-assets op de webapp-boeken pagina (bare template).
     * De webapp heeft z'n eigen styling + safe-area handling; onze CSS
     * (container padding, header/footer regels, body font) interfereert
     * en breekt bv. safe-area op iPhones.
     */
    if (is_page() && class_exists('TaxiTheme_Booking') && TaxiTheme_Booking::webapp_page_enabled()) {
        $role = get_post_meta(get_queried_object_id(), TaxiTheme_Installer::META_ROLE, true);
        if ($role === 'boeken') return;
    }

    wp_enqueue_style(
        'taxitheme-font',
        'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
        [],
        null
    );
    wp_enqueue_style('taxitheme-style', get_stylesheet_uri(), ['taxitheme-font'], wp_get_theme()->get('Version'));

    /*
     * Preset-CSS. Twee bronnen mogelijk:
     *   1. assets/presets/{slug}/*.css   → opgesplitste sectie-files (preferred)
     *   2. assets/presets/{slug}.css     → single-file (legacy)
     * Beide worden server-side geconcateneerd tot 1 inline blok — 0 extra requests.
     * Klassiek heeft geen preset-CSS: basis-styling uit style.css volstaat.
     */
    $preset_css = TaxiTheme_Preset::inline_css();
    if ($preset_css !== '') {
        wp_add_inline_style('taxitheme-style', $preset_css);
    }

    $variant_uri = TaxiTheme_Theme_Variant::stylesheet_uri();
    if ($variant_uri) {
        wp_enqueue_style(
            'taxitheme-variant',
            $variant_uri,
            ['taxitheme-style'],
            wp_get_theme()->get('Version')
        );
    }

    // Embedded booking widget — altijd geladen (verwerkt in preset-hero's).
    // De full webapp voor /boeken laadt via eigen bare template (page-boeken.php).
    wp_enqueue_script('taxibookingform-widget', TaxiTheme_Booking::widget_url(), [], wp_get_theme()->get('Version'), true);
});

add_filter('body_class', function ($classes) {
    $classes[] = TaxiTheme_Theme_Variant::body_class();
    $classes[] = TaxiTheme_Preset::body_class();

    // Voeg TaxiTheme rol toe als body class (bv. tt-role-boeken, tt-role-diensten)
    // zodat CSS per rol kan targeten.
    if (is_page()) {
        $role = get_post_meta(get_queried_object_id(), TaxiTheme_Installer::META_ROLE, true);
        if ($role) {
            $classes[] = 'tt-role-' . sanitize_html_class($role);
        }
    }
    return $classes;
});

add_filter('script_loader_tag', function ($tag, $handle) {
    if ($handle === 'taxibookingform-widget') {
        return '<script type="module" src="' . esc_url(TaxiTheme_Booking::widget_url()) . '" id="taxibookingform-widget-js"></script>';
    }
    return $tag;
}, 10, 2);

/**
 * Route pages met een specifieke TaxiTheme rol naar hun eigen template.
 * Werkt ook als klant de slug/naam heeft aangepast — wij matchen op meta.
 * Preset-aware: probeert eerst templates/{role}-{preset}.php, valt terug op {role}.php.
 */
add_filter('template_include', function ($template) {
    // Paused: laat WP z'n eigen template-hierarchie gebruiken (page.php etc)
    // zodat TaxiTheme's preset templates niet meer draaien op de oude pages.
    if (TaxiTheme_Installer::is_paused()) {
        return $template;
    }
    if (!is_page()) {
        return $template;
    }
    $role = get_post_meta(get_queried_object_id(), TaxiTheme_Installer::META_ROLE, true);
    // Boeken-page: forceer altijd page-boeken.php (ongeacht _wp_page_template meta).
    // Zo werkt de webapp uit-de-doos zonder dat de klant handmatig het template
    // hoeft te selecteren in Page Attributes.
    if ($role === 'boeken') {
        $forced = get_template_directory() . '/page-boeken.php';
        if (file_exists($forced)) return $forced;
    }
    if (!$role) {
        return $template;
    }
    $custom = TaxiTheme_Preset::template_path($role);
    return $custom ?: $template;
});

if (is_admin()) {
    TaxiTheme_Admin_Notice::init();
    TaxiTheme_Admin_Column::init();
    TaxiTheme_Settings::init();
    TaxiTheme_Page_Editor::init();
    TaxiTheme_Wizard::init();

    // One-time migratie: seed defaults op bestaande Over ons pages die
    // aangemaakt zijn vóór de over-ons defaults bestonden.
    add_action('admin_init', function () {
        if (get_option('taxitheme_over_ons_seed_migrated_v1')) return;
        if (!class_exists('TaxiTheme_Installer') || !class_exists('TaxiTheme_Page_Meta')) return;
        $pid = TaxiTheme_Installer::get_page_id('over-ons');
        if ($pid) {
            TaxiTheme_Page_Meta::ensure_over_ons_seeded($pid);
        }
        update_option('taxitheme_over_ons_seed_migrated_v1', 1);
    });
}

/**
 * Reactivation: klant switcht terug naar TaxiTheme na deactivatie.
 * Re-apply page_on_front + primary menu zonder wizard.
 */
add_action('after_switch_theme', function () {
    if (TaxiTheme_Setup::is_completed() && !TaxiTheme_Installer::is_paused()) {
        TaxiTheme_Installer::reapply();
    }
});

/**
 * Deactivation: klant switcht weg van TaxiTheme.
 *   - Restore originele page_on_front + show_on_front
 *   - Hide TaxiTheme pages (draft-status) → geen lek in fallback-menu's van andere thema's
 *   - Menu blijft bestaan maar wordt niet meer gebruikt (WP haalt hem uit primary locatie)
 * Deze hook laadt alleen als TaxiTheme actief is, dus fires alleen bij switch weg van ons.
 */
add_action('switch_theme', function () {
    TaxiTheme_Installer::restore_previous_settings();
    TaxiTheme_Installer::hide_pages();
});
