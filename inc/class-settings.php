<?php
if (!defined('ABSPATH')) {
    exit;
}

class TaxiTheme_Settings {

    const NONCE_ACTION = 'taxitheme_settings_save';
    const NONCE_FIELD  = 'taxitheme_settings_nonce';

    const TABS = [
        'theme'    => ['label' => 'Stijl',            'icon' => 'sparkles'],
        'pages'    => ['label' => 'Pages',            'icon' => 'home'],
        'company'  => ['label' => 'Bedrijfsgegevens', 'icon' => 'briefcase'],
        'settings' => ['label' => 'Instellingen',     'icon' => 'settings'],
    ];

    private static $errors = [];
    private static $saved  = false;

    public static function init() {
        add_action('admin_init', [__CLASS__, 'handle_submit']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_styles']);
    }

    public static function enqueue_styles($hook) {
        if ($hook !== 'toplevel_page_' . TaxiTheme_Setup::WIZARD_SLUG) {
            return;
        }
        wp_enqueue_style(
            'taxitheme-settings-font',
            'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
            [],
            null
        );
        // Voor de logo-picker in de Bedrijfsgegevens-tab
        wp_enqueue_media();
    }

    public static function handle_submit() {
        if (empty($_POST[self::NONCE_FIELD])) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!wp_verify_nonce($_POST[self::NONCE_FIELD], self::NONCE_ACTION)) {
            wp_die('Beveiligingscheck mislukt.');
        }

        $action = $_POST['taxitheme_action'] ?? '';

        if ($action === 'save_company') {
            $input = $_POST['company'] ?? [];
            $errors = TaxiTheme_Company_Info::validate($input);
            if (!empty($errors)) {
                self::$errors = $errors;
                return;
            }
            TaxiTheme_Company_Info::save($input);
            self::$saved = true;
        }

        if ($action === 'save_theme') {
            $new_preset = isset($_POST['preset']) ? sanitize_key(wp_unslash($_POST['preset'])) : null;
            if ($new_preset) {
                TaxiTheme_Preset::save($new_preset);
            }

            $active_preset  = $new_preset ?: TaxiTheme_Preset::current();
            $chosen_palette = isset($_POST['theme_variant']) ? sanitize_key(wp_unslash($_POST['theme_variant'])) : null;

            if ($chosen_palette && TaxiTheme_Theme_Variant::preset_of($chosen_palette) === $active_preset) {
                TaxiTheme_Theme_Variant::save($chosen_palette);
            } else {
                // Gekozen palette hoort niet bij nieuwe preset (of geen keuze) — reset naar default van die preset.
                TaxiTheme_Theme_Variant::save(TaxiTheme_Theme_Variant::default_for_preset($active_preset));
            }

            // Boeken-toggle zit ook in de Stijl-form
            TaxiTheme_Booking::save($_POST['booking'] ?? []);

            self::$saved = true;
        }

        if ($action === 'save_pages') {
            $visible = $_POST['page_visible'] ?? [];
            if (!is_array($visible)) $visible = [];
            $slugs   = $_POST['page_slug'] ?? [];
            if (!is_array($slugs)) $slugs = [];
            self::$errors = self::$errors ?? [];

            foreach (array_keys(TaxiTheme_Installer::roles()) as $role) {
                TaxiTheme_Installer::set_page_visible($role, !empty($visible[$role]));

                // Slug wijzigen — alleen niet-home (home is de front-page, slug irrelevant)
                if ($role === 'home') continue;
                $page_id = TaxiTheme_Installer::get_page_id($role);
                if (!$page_id) continue;
                $current_page = get_post($page_id);
                if (!$current_page || $current_page->post_status === 'trash') continue;

                $new_slug_raw = isset($slugs[$role]) ? wp_unslash($slugs[$role]) : '';
                $new_slug     = sanitize_title($new_slug_raw);
                if ($new_slug === '' || $new_slug === $current_page->post_name) continue;

                // Check of slug al bestaat op een ANDERE page/post
                $existing = get_page_by_path($new_slug, OBJECT, 'page');
                if ($existing && (int) $existing->ID !== $page_id) {
                    self::$errors[] = sprintf(
                        'Slug "%s" is al in gebruik door een andere pagina — %s niet gewijzigd.',
                        $new_slug,
                        $current_page->post_title
                    );
                    continue;
                }

                wp_update_post([
                    'ID'        => $page_id,
                    'post_name' => $new_slug,
                ]);
            }
            self::$saved = true;
        }

        if ($action === 'deactivate_page') {
            $role = sanitize_key(wp_unslash($_POST['role'] ?? ''));
            if ($role && $role !== 'home') {
                TaxiTheme_Installer::deactivate_page($role);
                self::$saved = true;
            }
        }

        if ($action === 'activate_page') {
            $role = sanitize_key(wp_unslash($_POST['role'] ?? ''));
            if ($role && $role !== 'home') {
                TaxiTheme_Installer::activate_page($role);
                self::$saved = true;
            }
        }

        if ($action === 'recreate_page') {
            $role = sanitize_key(wp_unslash($_POST['role'] ?? ''));
            if ($role) {
                TaxiTheme_Installer::recreate_page($role);
                self::$saved = true;
            }
        }

        if ($action === 'toggle_pause') {
            if (TaxiTheme_Installer::is_paused()) {
                TaxiTheme_Installer::unpause();
            } else {
                TaxiTheme_Installer::pause();
            }
            wp_safe_redirect(admin_url('admin.php?page=' . TaxiTheme_Setup::WIZARD_SLUG . '&tab=settings'));
            exit;
        }

        if ($action === 'uninstall') {
            $delete_company = !empty($_POST['delete_company']);
            TaxiTheme_Installer::uninstall($delete_company);
            wp_safe_redirect(TaxiTheme_Setup::wizard_url(1));
            exit;
        }
    }

    public static function render() {
        $active_tab = $_GET['tab'] ?? 'theme';
        if (!isset(self::TABS[$active_tab])) {
            $active_tab = 'theme';
        }
        ?>
        <?php self::render_inline_styles(); ?>
        <div class="tt-set">
            <header class="tt-set__header">
                <div class="tt-set__brand">
                    <div>
                        <div class="tt-set__brandtxt">TaxiTheme</div>
                        <div class="tt-set__brandsub">Beheer je taxi-website</div>
                    </div>
                </div>
                <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="tt-set__btn tt-set__btn--ghost">
                    Bekijk site ↗
                </a>
            </header>

            <?php if (self::$saved) : ?>
                <div class="tt-set__toast">✓ Wijzigingen opgeslagen</div>
            <?php endif; ?>

            <?php
            // Numeric-keyed errors (generic messages, bv. slug conflicts) tonen als banner.
            // Field-keyed errors worden per veld getoond in de betreffende form.
            $general_errors = [];
            foreach ((array) self::$errors as $k => $msg) {
                if (is_int($k)) $general_errors[] = $msg;
            }
            if (!empty($general_errors)) : ?>
                <div class="tt-set__error-banner">
                    <?php foreach ($general_errors as $err) : ?>
                        <div>⚠ <?php echo esc_html($err); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="tt-set__body">
                <aside class="tt-set__sidebar">
                    <?php self::render_tabs($active_tab); ?>
                </aside>
                <div class="tt-set__panel">
                    <?php self::render_tab($active_tab); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_inline_styles() {
        ?>
        <style>
            #wpcontent, #wpbody-content { padding: 0 !important; }
            .auto-fold #wpcontent { margin-left: 36px; }
            @media (min-width: 961px) { .auto-fold #wpcontent { margin-left: 160px; } }
            #wpfooter, .update-nag, .notice, div.error, div.updated { display: none !important; }

            .tt-set {
                font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
                max-width: none;
                margin: 0;
                padding: 32px 48px 60px;
                color: #14161f;
            }
            .tt-set * { box-sizing: border-box; }

            /* Header */
            .tt-set__header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 24px;
            }
            .tt-set__brand {
                display: flex;
                align-items: center;
                gap: 14px;
            }
            .tt-set__logo {
                width: 44px; height: 44px;
                background: #f5b800;
                border-radius: 10px;
            }
            .tt-set__brandtxt {
                font-size: 1.4rem;
                font-weight: 800;
                letter-spacing: -0.02em;
                line-height: 1.1;
            }
            .tt-set__brandsub {
                font-size: 0.85rem;
                color: #6b7280;
                margin-top: 2px;
            }

            /* Layout: sidebar + content */
            .tt-set__body {
                display: grid;
                grid-template-columns: 260px 1fr;
                gap: 24px;
                align-items: start;
            }
            .tt-set__sidebar {
                position: sticky;
                top: 40px;
            }

            /* Sidebar tabs — vertical */
            .tt-set__tabs {
                display: flex;
                flex-direction: column;
                gap: 4px;
                background: #fff;
                padding: 10px;
                border: 1px solid #e6e2d5;
                border-radius: 12px;
            }
            .tt-set__tab {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 14px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 0.92rem;
                color: #6b7280;
                text-decoration: none;
                white-space: nowrap;
                transition: all 0.15s;
                border: 1.5px solid transparent;
            }
            .tt-set__tab:hover { color: #14161f; background: #fdfcf7; }
            .tt-set__tab.is-active {
                background: #14161f;
                color: #fff;
            }
            .tt-set__tab.is-danger.is-active {
                background: #dc2626;
                color: #fff;
            }
            .tt-set__tab.is-danger:not(.is-active) { color: #dc2626; }
            .tt-set__tab.is-danger:hover:not(.is-active) { background: #fef2f2; color: #dc2626; }
            .tt-set__tab-icon {
                width: 20px;
                height: 20px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .tt-set__tab-icon svg { display: block; }

            /* Toast */
            .tt-set__toast {
                background: #14161f;
                color: #fff;
                padding: 12px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-weight: 600;
                font-size: 0.9rem;
                display: inline-block;
            }
            /* Error banner (generieke errors, bv. slug conflicts) */
            .tt-set__error-banner {
                background: #fef2f2;
                border: 1px solid #fecaca;
                color: #991b1b;
                padding: 14px 18px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-size: 0.9rem;
                font-weight: 500;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            /* Panel */
            .tt-set__panel {
                background: #fff;
                border: 1px solid #e6e2d5;
                border-radius: 16px;
                padding: 40px;
                box-shadow: 0 4px 20px -8px rgba(20, 22, 31, 0.06);
            }

            .tt-set__panel-head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 32px;
                gap: 24px;
                flex-wrap: wrap;
            }
            .tt-set__panel-head h2 {
                font-size: 1.5rem;
                font-weight: 800;
                letter-spacing: -0.025em;
                margin: 0 0 6px;
                color: #14161f;
            }
            .tt-set__panel-head p {
                color: #6b7280;
                margin: 0;
                font-size: 0.95rem;
            }
            .tt-set__autosave-note {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 7px 11px;
                color: #4b5563;
                background: #f3f4f6;
                border-radius: 999px;
                font-size: 0.78rem;
                font-weight: 700;
                white-space: nowrap;
            }
            .tt-set__autosave-note::before {
                content: '';
                width: 7px;
                height: 7px;
                background: #16a34a;
                border-radius: 50%;
            }
            .tt-set__autosave-note.is-saving::before {
                background: #f5b800;
                animation: tt-set-pulse 0.8s ease-in-out infinite alternate;
            }
            @keyframes tt-set-pulse {
                to { opacity: 0.35; }
            }

            /* Form grid */
            .tt-set__grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px 24px;
            }
            .tt-set__field { display: flex; flex-direction: column; }
            .tt-set__field--full { grid-column: 1 / -1; }
            .tt-set__field label {
                font-size: 0.9rem;
                font-weight: 600;
                margin-bottom: 6px;
            }
            .tt-set__field label .req { color: #dc2626; margin-left: 3px; }
            .tt-set__field input {
                padding: 12px 14px;
                border: 1.5px solid #e6e2d5;
                border-radius: 8px;
                font-size: 0.95rem;
                font-family: inherit;
                background: #fff;
                color: #14161f;
                transition: border-color 0.15s, box-shadow 0.15s;
            }
            .tt-set__field input:focus,
            .tt-set__field textarea:focus {
                outline: none;
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }
            .tt-set__field textarea {
                padding: 12px 14px;
                border: 1.5px solid #e6e2d5;
                border-radius: 8px;
                font-size: 0.95rem;
                font-family: inherit;
                background: #fff;
                color: #14161f;
                resize: vertical;
                min-height: 60px;
                transition: border-color 0.15s, box-shadow 0.15s;
            }
            .tt-set__field .err {
                color: #dc2626;
                font-size: 0.85rem;
                margin-top: 6px;
            }
            .tt-set__hint {
                color: #9ca3af;
                font-weight: 500;
                font-size: 0.8rem;
                margin-left: 6px;
            }

            /* Groups (sections in homepage tab) */
            .tt-set__group {
                margin-bottom: 32px;
                padding-bottom: 32px;
                border-bottom: 1px solid #f0ede2;
            }
            .tt-set__group:last-of-type { border-bottom: none; margin-bottom: 0; }
            .tt-set__group-head {
                margin-bottom: 20px;
            }
            .tt-set__group-head h3 {
                font-size: 1.15rem;
                font-weight: 700;
                margin: 0 0 4px;
                color: #14161f;
            }
            .tt-set__group-head p {
                font-size: 0.9rem;
                color: #6b7280;
                margin: 0;
            }

            /* Logo picker */
            .tt-set__logo-picker { max-width: 520px; }
            .tt-set__logo-preview {
                background: #111827;
                padding: 24px 32px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 120px;
                margin-bottom: 14px;
                border: 1.5px solid #e6e2d5;
            }
            .tt-set__logo-preview img {
                max-height: 64px;
                max-width: 280px;
                width: auto;
                height: auto;
                display: block;
            }
            .tt-set__logo-empty {
                color: #9ca3af;
                font-size: 0.9rem;
                font-weight: 500;
            }
            .tt-set__logo-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .tt-set__logo-hint {
                margin: 12px 0 0;
                color: #9ca3af;
                font-size: 0.82rem;
            }

            /* Openingstijden editor */
            .tt-set__hours {
                margin-top: 20px;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .tt-set__hours-row {
                display: grid;
                grid-template-columns: 140px 130px 1fr;
                gap: 16px;
                align-items: center;
                padding: 10px 14px;
                background: #fdfcf7;
                border: 1px solid #f0ede2;
                border-radius: 8px;
            }
            .tt-set__hours-day {
                font-weight: 600;
                font-size: 0.92rem;
            }
            .tt-set__hours-closed {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.85rem;
                color: #6b7280;
                cursor: pointer;
            }
            .tt-set__hours-times {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                font-size: 0.85rem;
                color: #9ca3af;
            }
            .tt-set__hours-times input[type="time"] {
                padding: 8px 10px;
                border: 1.5px solid #e6e2d5;
                border-radius: 6px;
                font-family: inherit;
                font-size: 0.9rem;
                background: #fff;
            }
            .tt-set__hours-times input[type="time"]:focus {
                outline: none;
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }
            @media (max-width: 700px) {
                .tt-set__hours-row {
                    grid-template-columns: 1fr;
                    gap: 8px;
                }
            }

            /* Toggle switch */
            .tt-set__toggle {
                display: flex;
                align-items: center;
                gap: 12px;
                cursor: pointer;
                margin-bottom: 4px;
            }
            .tt-set__toggle input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .tt-set__toggle-track {
                position: relative;
                display: inline-block;
                width: 40px;
                height: 22px;
                background: #d1d5db;
                border-radius: 999px;
                transition: background 0.2s;
                flex-shrink: 0;
            }
            .tt-set__toggle-thumb {
                position: absolute;
                top: 2px; left: 2px;
                width: 18px; height: 18px;
                background: #fff;
                border-radius: 50%;
                transition: transform 0.2s;
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            }
            .tt-set__toggle input:checked ~ .tt-set__toggle-track { background: #14161f; }
            .tt-set__toggle input:checked ~ .tt-set__toggle-track .tt-set__toggle-thumb { transform: translateX(18px); }

            /* Preset picker */
            .tt-set__preset-grid {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 12px;
            }
            .tt-set__preset-card {
                position: relative;
                overflow: hidden;
                background: #fff;
                border: 1.5px solid #e6e2d5;
                border-radius: 12px;
                transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
            }
            .tt-set__preset-card:hover {
                border-color: #14161f;
                transform: translateY(-1px);
            }
            .tt-set__preset-card.is-active,
            .tt-set__preset-card:has(input:checked) {
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }
            .tt-set__preset-choice {
                display: block;
                cursor: pointer;
            }
            .tt-set__preset-choice input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .tt-set__preset-choice input:focus-visible ~ .tt-set__preset-image {
                outline: 3px solid #2271b1;
                outline-offset: -3px;
            }
            .tt-set__preset-image {
                display: block;
                aspect-ratio: 4 / 5;
                overflow: hidden;
                background: #eef1f5;
                border-bottom: 1px solid #e6e2d5;
            }
            .tt-set__preset-image img {
                display: block;
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: top center;
            }
            .tt-set__preset-content {
                display: block;
                padding: 13px;
            }
            .tt-set__preset-heading {
                display: block;
            }
            .tt-set__preset-title {
                color: #14161f;
                font-size: 0.94rem;
                font-weight: 800;
            }
            /* Variant picker */
            .tt-set__variants {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 12px;
            }
            .tt-set__variant {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 16px;
                background: #fdfcf7;
                border: 1.5px solid #e6e2d5;
                border-radius: 10px;
                cursor: pointer;
                transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
            }
            .tt-set__variant:hover { border-color: #14161f; }
            .tt-set__variant.is-active,
            .tt-set__variant:has(input:checked) {
                border-color: #f5b800;
                background: #fff;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }
            .tt-set__variant input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .tt-set__variant-swatch {
                display: inline-flex;
                width: 44px;
                height: 44px;
                border-radius: 8px;
                overflow: hidden;
                border: 1px solid #e6e2d5;
                flex-shrink: 0;
            }
            .tt-set__variant-swatch span { flex: 1; display: block; }
            .tt-set__variant-body {
                display: flex;
                flex-direction: column;
                gap: 2px;
                min-width: 0;
            }
            .tt-set__variant-label {
                font-weight: 700;
                font-size: 0.95rem;
                color: #14161f;
            }
            .tt-set__variant-desc {
                font-size: 0.82rem;
                color: #6b7280;
                line-height: 1.4;
            }

            /* USP rows */
            .tt-set__usp-row {
                display: flex;
                gap: 16px;
                padding: 20px;
                background: #fdfcf7;
                border: 1px solid #f0ede2;
                border-radius: 10px;
                margin-bottom: 12px;
            }
            .tt-set__usp-num {
                background: #14161f;
                color: #fff;
                padding: 4px 10px;
                border-radius: 5px;
                font-size: 0.75rem;
                font-weight: 700;
                letter-spacing: 0.03em;
                white-space: nowrap;
                height: fit-content;
            }
            .tt-set__usp-grid {
                display: grid;
                grid-template-columns: 80px 1fr;
                gap: 12px 16px;
                flex: 1;
            }
            .tt-set__field--icon input { text-align: center; font-size: 1.1rem; }

            /* Buttons */
            .tt-set__actions {
                margin-top: 32px;
                padding-top: 24px;
                border-top: 1px solid #f0ede2;
            }
            .tt-set__btn {
                display: inline-block;
                padding: 12px 22px;
                border-radius: 8px;
                font-family: inherit;
                font-weight: 700;
                font-size: 0.9rem;
                cursor: pointer;
                border: 1.5px solid transparent;
                text-decoration: none;
                transition: transform 0.15s, box-shadow 0.15s;
            }
            .tt-set__btn--primary {
                background: #f5b800;
                color: #14161f;
                box-shadow: 0 4px 12px -3px rgba(245, 184, 0, 0.5);
            }
            .tt-set__btn--primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 18px -3px rgba(245, 184, 0, 0.6);
                color: #14161f;
            }
            .tt-set__btn--ghost {
                background: transparent;
                color: #6b7280;
                border-color: #e6e2d5;
            }
            .tt-set__btn--ghost:hover { color: #14161f; border-color: #14161f; }
            .tt-set__btn:disabled,
            .tt-set__btn:disabled:hover {
                color: #9ca3af;
                background: #f3f4f6;
                border-color: #e5e7eb;
                box-shadow: none;
                cursor: not-allowed;
                opacity: 0.75;
                transform: none;
            }
            .tt-set__btn--danger {
                background: #dc2626;
                color: #fff;
            }
            .tt-set__btn--danger:hover {
                background: #b91c1c;
                color: #fff;
                transform: translateY(-1px);
            }

            /* Pages table */
            .tt-set__table {
                width: 100%;
                border-collapse: collapse;
            }
            .tt-set__table thead th {
                text-align: left;
                font-size: 0.8rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #6b7280;
                padding: 12px 16px;
                border-bottom: 1px solid #f0ede2;
            }
            .tt-set__table tbody td {
                padding: 16px;
                border-bottom: 1px solid #f0ede2;
                font-size: 0.95rem;
            }
            .tt-set__table tbody tr:last-child td { border-bottom: none; }
            .tt-set__table tbody tr:hover td { background: #fdfcf7; }
            .tt-set__badge {
                display: inline-block;
                padding: 3px 10px;
                background: #14161f;
                color: #fff;
                border-radius: 5px;
                font-size: 0.75rem;
                font-weight: 700;
                letter-spacing: 0.02em;
            }
            .tt-set__slug { color: #9ca3af; font-size: 0.85rem; }
            .tt-set__slug-edit {
                display: inline-flex;
                align-items: center;
                background: #fdfcf7;
                border: 1px solid #e6e2d5;
                border-radius: 6px;
                padding: 2px 8px;
                gap: 2px;
                transition: border-color 0.15s;
            }
            .tt-set__slug-edit:focus-within {
                border-color: #14161f;
                background: #fff;
            }
            .tt-set__slug-prefix {
                color: #9ca3af;
                font-size: 0.85rem;
                user-select: none;
            }
            .tt-set__slug-input {
                border: none;
                background: transparent;
                padding: 4px 0;
                font-size: 0.85rem;
                color: #14161f;
                font-family: inherit;
                width: 140px;
                outline: none;
            }
            .tt-set__slug-input:invalid { color: #dc2626; }
            .tt-set__row-actions { display: flex; gap: 8px; }
            .tt-set__table .tt-set__row-actions .tt-set__btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 112px;
                width: 112px;
                min-width: 112px;
                text-align: center;
                white-space: nowrap;
            }

            /* Info box */
            .tt-set__info {
                background: #fef9c3;
                border-left: 4px solid #f5b800;
                padding: 14px 18px;
                border-radius: 6px;
                font-size: 0.9rem;
                color: #78350f;
                margin: 20px 0;
            }
            .tt-set__info--danger {
                background: #fef2f2;
                border-color: #dc2626;
                color: #991b1b;
            }

            /* Danger zone */
            .tt-set__danger-list {
                list-style: none;
                padding: 0;
                margin: 20px 0;
            }
            .tt-set__danger-list li {
                padding: 8px 0;
                padding-left: 24px;
                position: relative;
                font-size: 0.95rem;
            }
            .tt-set__danger-list li::before {
                content: "•";
                position: absolute;
                left: 8px;
                color: #dc2626;
                font-weight: 800;
            }
            .tt-set__checkbox {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px 16px;
                background: #fdfcf7;
                border: 1px solid #e6e2d5;
                border-radius: 8px;
                margin-bottom: 16px;
                cursor: pointer;
                font-size: 0.9rem;
                font-weight: 500;
            }
            .tt-set__checkbox input { margin: 0; }

            /* Settings cards (uitschakelen + deinstalleren) */
            .tt-set__card {
                background: #fff;
                border: 1px solid #e6e2d5;
                border-radius: 12px;
                padding: 24px 26px;
                margin-bottom: 20px;
            }
            .tt-set__card--danger {
                border-color: #fecaca;
                background: #fffafa;
            }
            .tt-set__card-head h3 {
                margin: 0 0 6px;
                font-size: 1.15rem;
                font-weight: 700;
                color: #14161f;
            }
            .tt-set__card-head p {
                margin: 0 0 16px;
                color: #6b7280;
                font-size: 0.95rem;
                line-height: 1.6;
            }
            .tt-set__bullets {
                list-style: none;
                padding: 0;
                margin: 0 0 20px;
            }
            .tt-set__bullets li {
                padding: 6px 0 6px 22px;
                position: relative;
                font-size: 0.9rem;
                color: #475569;
            }
            .tt-set__bullets li::before {
                content: "✓";
                position: absolute;
                left: 0;
                color: #059669;
                font-weight: 700;
            }
            .tt-set__bullets code {
                background: #fdfcf7;
                padding: 1px 6px;
                border-radius: 4px;
                font-size: 0.85rem;
            }

            /* Mobile */
            @media (max-width: 1200px) {
                .tt-set__preset-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .tt-set__preset-image { aspect-ratio: 4 / 3; }
            }
            @media (max-width: 900px) {
                .tt-set { padding: 20px 16px 40px; }
                .tt-set__header { flex-direction: column; align-items: flex-start; gap: 16px; }
                .tt-set__body { grid-template-columns: 1fr; }
                .tt-set__sidebar { position: static; }
                .tt-set__tabs { flex-direction: row; overflow-x: auto; padding: 6px; }
                .tt-set__tab { padding: 10px 14px; font-size: 0.85rem; }
                .tt-set__panel { padding: 24px 20px; }
                .tt-set__grid { grid-template-columns: 1fr; }
                .tt-set__table { font-size: 0.85rem; }
                .tt-set__table tbody td { padding: 12px 8px; }
                .tt-set__row-actions { flex-direction: column; }
                .tt-set__preset-grid { grid-template-columns: 1fr; }
            }
        </style>
        <?php
    }

    private static function tab_url($tab) {
        return admin_url('admin.php?page=' . TaxiTheme_Setup::WIZARD_SLUG . '&tab=' . $tab);
    }

    private static function render_tabs($active) {
        ?>
        <nav class="tt-set__tabs">
            <?php foreach (self::TABS as $slug => $meta) :
                $classes = 'tt-set__tab';
                if ($active === $slug) $classes .= ' is-active';
                if (!empty($meta['danger'])) $classes .= ' is-danger';
            ?>
                <a href="<?php echo esc_url(self::tab_url($slug)); ?>" class="<?php echo $classes; ?>">
                    <span class="tt-set__tab-icon"><?php echo TaxiTheme_Icons::svg($meta['icon'], 18); ?></span>
                    <?php echo esc_html($meta['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    private static function render_tab($tab) {
        switch ($tab) {
            case 'theme':    self::tab_theme();    break;
            case 'company':  self::tab_company();  break;
            case 'pages':    self::tab_pages();    break;
            case 'settings': self::tab_settings(); break;
        }
    }

    private static function tab_theme() {
        $current_preset  = TaxiTheme_Preset::current();
        $presets         = TaxiTheme_Preset::all();
        $current_variant = TaxiTheme_Theme_Variant::current();
        $variants        = TaxiTheme_Theme_Variant::for_preset($current_preset);
        ?>
        <div class="tt-set__panel-head">
            <div>
                <h2>Website-stijl</h2>
                <p>Kies eerst een preset (structuur), daarna de kleuren. De inhoud van je pagina's blijft hetzelfde.</p>
            </div>
            <div class="tt-set__autosave-note" id="tt_style_save_status" role="status" aria-live="polite">
                Automatisch opslaan actief
            </div>
        </div>

        <form method="post" id="tt_style_form">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
            <input type="hidden" name="taxitheme_action" value="save_theme">

            <div class="tt-set__group">
                <div class="tt-set__group-head">
                    <h3>Preset</h3>
                    <p>Bepaalt welke secties op je homepage staan en in welke volgorde.</p>
                </div>
                <div class="tt-set__preset-grid">
                    <?php foreach ($presets as $slug => $meta) :
                        $checked = ($slug === $current_preset);
                        $preview_url = get_template_directory_uri() . '/assets/images/presets/' . $slug . '.png';
                    ?>
                        <div class="tt-set__preset-card<?php echo $checked ? ' is-active' : ''; ?>">
                            <label class="tt-set__preset-choice">
                                <input
                                    type="radio"
                                    name="preset"
                                    value="<?php echo esc_attr($slug); ?>"
                                    <?php checked($checked); ?>
                                >
                                <span class="tt-set__preset-image">
                                    <img
                                        src="<?php echo esc_url($preview_url); ?>"
                                        alt="Voorbeeld van de preset <?php echo esc_attr($meta['label']); ?>"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </span>
                                <span class="tt-set__preset-content">
                                    <span class="tt-set__preset-heading">
                                        <span class="tt-set__preset-title"><?php echo esc_html($meta['label']); ?></span>
                                    </span>
                                </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tt-set__group">
                <div class="tt-set__group-head">
                    <h3>Kleurenpalet</h3>
                    <p>Alleen palettes die passen bij "<?php echo esc_html(TaxiTheme_Preset::label($current_preset)); ?>". Wisselen van preset zet het palet terug op de standaard.</p>
                </div>
                <div class="tt-set__variants">
                    <?php foreach ($variants as $slug => $meta) :
                        $checked = ($slug === $current_variant);
                        $swatch  = $meta['swatch'] ?? ['#e5e7eb', '#9ca3af', '#f9fafb'];
                    ?>
                        <label class="tt-set__variant<?php echo $checked ? ' is-active' : ''; ?>">
                            <input
                                type="radio"
                                name="theme_variant"
                                value="<?php echo esc_attr($slug); ?>"
                                <?php checked($checked); ?>
                            >
                            <span class="tt-set__variant-swatch" aria-hidden="true">
                                <?php foreach ($swatch as $color) : ?>
                                    <span style="background: <?php echo esc_attr($color); ?>;"></span>
                                <?php endforeach; ?>
                            </span>
                            <span class="tt-set__variant-body">
                                <span class="tt-set__variant-label"><?php echo esc_html($meta['label']); ?></span>
                                <span class="tt-set__variant-desc"><?php echo esc_html($meta['description']); ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tt-set__group">
                <div class="tt-set__group-head">
                    <h3>Boeken</h3>
                    <p>Als aan: <code>/boeken</code> laadt de volledige webapp (eigen navbar + UX). Als uit: geen boeken-link in menu of header.</p>
                </div>
                <label class="tt-set__toggle">
                    <input type="checkbox" name="booking[webapp_page_enabled]" value="1" <?php checked(TaxiTheme_Booking::webapp_page_enabled()); ?>>
                    <span class="tt-set__toggle-track"><span class="tt-set__toggle-thumb"></span></span>
                    <span style="margin-left:12px;font-weight:600;">Webapp op /boeken activeren</span>
                </label>
            </div>

            <noscript>
                <div class="tt-set__actions">
                    <button type="submit" class="tt-set__btn tt-set__btn--primary">Stijl opslaan</button>
                </div>
            </noscript>
        </form>

        <script>
        (function () {
            var cards = document.querySelectorAll('.tt-set__preset-card');
            var form = document.getElementById('tt_style_form');
            var saveStatus = document.getElementById('tt_style_save_status');
            if (!cards.length || !form) return;

            cards.forEach(function (card) {
                var input = card.querySelector('input[name="preset"]');

                if (input) {
                    input.addEventListener('change', function () {
                        cards.forEach(function (item) { item.classList.remove('is-active'); });
                        card.classList.add('is-active');
                    });
                }
            });

            form.querySelectorAll(
                'input[name="preset"], input[name="theme_variant"], input[name="booking[webapp_page_enabled]"]'
            ).forEach(function (input) {
                input.addEventListener('change', function () {
                    form.setAttribute('aria-busy', 'true');
                    if (saveStatus) {
                        saveStatus.textContent = 'Wijziging opslaan...';
                        saveStatus.classList.add('is-saving');
                    }

                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });
        })();
        </script>
        <?php
    }

    private static function tab_company() {
        // Vangnet: als admin_enqueue_scripts om welke reden dan ook niet vuurde,
        // zorg dat wp.media alsnog beschikbaar is voor de logo picker.
        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        $data     = TaxiTheme_Company_Info::all();
        $socials  = TaxiTheme_Company_Info::socials();
        $is_247   = TaxiTheme_Company_Info::is_247();
        $hours    = TaxiTheme_Company_Info::hours();
        $logo_id  = TaxiTheme_Company_Info::logo_id();
        $logo_url = TaxiTheme_Company_Info::logo_url();
        ?>
        <div class="tt-set__panel-head">
            <div>
                <h2>Bedrijfsgegevens</h2>
                <p>Deze gegevens verschijnen in je header, footer en SEO-schema (Google).</p>
            </div>
        </div>

        <form method="post">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
            <input type="hidden" name="taxitheme_action" value="save_company">

            <div class="tt-set__group">
                <div class="tt-set__group-head">
                    <h3>Logo</h3>
                    <p>Verschijnt in de header en footer. Aanbevolen: <strong>400×96px</strong> (breedte × hoogte), PNG met transparante achtergrond of SVG. Als er geen logo is, tonen we de bedrijfsnaam als tekst.</p>
                </div>
                <div class="tt-set__logo-picker">
                    <div class="tt-set__logo-preview" id="tt_logo_preview">
                        <?php if ($logo_url) : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Logo preview">
                        <?php else : ?>
                            <span class="tt-set__logo-empty">Nog geen logo gekozen</span>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="company[logo_id]" id="tt_logo_id" value="<?php echo esc_attr($logo_id); ?>">
                    <div class="tt-set__logo-actions">
                        <button type="button" class="tt-set__btn tt-set__btn--primary" id="tt_logo_choose">
                            <?php echo $logo_id ? 'Wijzigen' : 'Kies logo'; ?>
                        </button>
                        <button type="button" class="tt-set__btn tt-set__btn--ghost" id="tt_logo_remove" <?php echo $logo_id ? '' : 'style="display:none;"'; ?>>
                            Verwijderen
                        </button>
                    </div>
                    <p class="tt-set__logo-hint">Preview op donkere achtergrond zoals je header/footer. Wit of licht logo werkt het best.</p>
                </div>
            </div>

            <div class="tt-set__group">
                <div class="tt-set__group-head">
                    <h3>Contact & Bedrijf</h3>
                    <p>Kerngegevens die op de site en in Google-schema gebruikt worden.</p>
                </div>
                <div class="tt-set__grid">
                    <?php foreach (TaxiTheme_Company_Info::FIELDS as $field => $meta) :
                        $value    = $data[$field] ?? '';
                        $error    = self::$errors[$field] ?? '';
                        $required = !empty($meta['required']);
                        $wide     = in_array($field, ['address'], true);
                    ?>
                        <div class="tt-set__field <?php echo $wide ? 'tt-set__field--full' : ''; ?>">
                            <label for="c_<?php echo esc_attr($field); ?>">
                                <?php echo esc_html($meta['label']); ?>
                                <?php if ($required) : ?><span class="req">*</span><?php endif; ?>
                            </label>
                            <input
                                type="<?php echo esc_attr($meta['type']); ?>"
                                name="company[<?php echo esc_attr($field); ?>]"
                                id="c_<?php echo esc_attr($field); ?>"
                                value="<?php echo esc_attr($value); ?>"
                                <?php echo $required ? 'required' : ''; ?>
                            >
                            <?php if ($error) : ?>
                                <div class="err"><?php echo esc_html($error); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tt-set__group">
                <div class="tt-set__group-head">
                    <h3>Openingstijden</h3>
                    <p>Gebruikt voor Google's <code>openingHoursSpecification</code> schema.</p>
                </div>
                <label class="tt-set__toggle">
                    <input type="checkbox" name="company[hours_247]" value="1" <?php checked($is_247); ?>>
                    <span class="tt-set__toggle-track"><span class="tt-set__toggle-thumb"></span></span>
                    <span style="margin-left:12px;font-weight:600;">24/7 geopend</span>
                </label>
                <div class="tt-set__hours" <?php echo $is_247 ? 'style="display:none;"' : ''; ?>>
                    <?php foreach (TaxiTheme_Company_Info::DAYS as $key => $label) :
                        $day = $hours[$key];
                    ?>
                        <div class="tt-set__hours-row">
                            <div class="tt-set__hours-day"><?php echo esc_html($label); ?></div>
                            <label class="tt-set__hours-closed">
                                <input type="checkbox" name="company[hours][<?php echo esc_attr($key); ?>][closed]" value="1" <?php checked($day['closed']); ?>>
                                <span>Gesloten</span>
                            </label>
                            <div class="tt-set__hours-times">
                                <input type="time" name="company[hours][<?php echo esc_attr($key); ?>][open]" value="<?php echo esc_attr($day['open']); ?>">
                                <span>tot</span>
                                <input type="time" name="company[hours][<?php echo esc_attr($key); ?>][close]" value="<?php echo esc_attr($day['close']); ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tt-set__group">
                <div class="tt-set__group-head">
                    <h3>Social media</h3>
                    <p>URLs verschijnen als <code>sameAs</code> in schema + optioneel in de footer.</p>
                </div>
                <div class="tt-set__grid">
                    <?php foreach (TaxiTheme_Company_Info::SOCIAL_FIELDS as $field => $meta) :
                        $value = $socials[$field] ?? '';
                    ?>
                        <div class="tt-set__field">
                            <label for="c_<?php echo esc_attr($field); ?>">
                                <?php echo esc_html($meta['label']); ?>
                            </label>
                            <input
                                type="url"
                                name="company[<?php echo esc_attr($field); ?>]"
                                id="c_<?php echo esc_attr($field); ?>"
                                value="<?php echo esc_attr($value); ?>"
                                placeholder="<?php echo esc_attr($meta['placeholder']); ?>"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tt-set__actions">
                <button type="submit" class="tt-set__btn tt-set__btn--primary">Wijzigingen opslaan</button>
            </div>
        </form>

        <script>
        (function () {
            var toggle = document.querySelector('input[name="company[hours_247]"]');
            var wrap = document.querySelector('.tt-set__hours');
            if (toggle && wrap) {
                toggle.addEventListener('change', function () {
                    wrap.style.display = toggle.checked ? 'none' : '';
                });
            }

            // Logo media picker
            var chooseBtn = document.getElementById('tt_logo_choose');
            var removeBtn = document.getElementById('tt_logo_remove');
            var input     = document.getElementById('tt_logo_id');
            var preview   = document.getElementById('tt_logo_preview');
            if (!chooseBtn) return;

            var frame;
            chooseBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!window.wp || !window.wp.media) {
                    console.error('TaxiTheme: wp.media niet geladen — media library kon niet openen.');
                    alert('WordPress Media Library is niet geladen. Ververs de pagina en probeer opnieuw. Als het probleem aanhoudt, deactiveer conflicterende plugins.');
                    return;
                }
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title: 'Kies of upload een logo',
                    button: { text: 'Gebruik dit logo' },
                    multiple: false,
                    library: { type: 'image' }
                });
                frame.on('select', function () {
                    var att = frame.state().get('selection').first().toJSON();
                    input.value = att.id;
                    preview.innerHTML = '<img src="' + att.url + '" alt="Logo preview">';
                    chooseBtn.textContent = 'Wijzigen';
                    if (removeBtn) removeBtn.style.display = '';
                });
                frame.open();
            });

            if (removeBtn) {
                removeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    input.value = '';
                    preview.innerHTML = '<span class="tt-set__logo-empty">Nog geen logo gekozen</span>';
                    chooseBtn.textContent = 'Kies logo';
                    removeBtn.style.display = 'none';
                });
            }
        })();
        </script>
        <?php
    }

    private static function tab_pages() {
        $roles = TaxiTheme_Installer::roles();
        ?>
        <div class="tt-set__panel-head">
            <div>
                <h2>TaxiTheme pages</h2>
                <p>Beheer welke pages actief zijn en in het menu staan. Deactiveren bewaart alle content.</p>
            </div>
        </div>

        <form method="post">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
            <input type="hidden" name="taxitheme_action" value="save_pages">

            <table class="tt-set__table">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Slug</th>
                        <th style="text-align:center;">In menu</th>
                        <th style="text-align:right;">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role => $meta) :
                        $page_id = TaxiTheme_Installer::get_page_id($role);
                        $page    = $page_id ? get_post($page_id) : null;
                        $trashed = $page && $page->post_status === 'trash';
                        $exists  = $page && !$trashed;
                        $active  = $exists && TaxiTheme_Installer::is_page_active($role);
                        $visible = TaxiTheme_Installer::is_page_visible($role);
                    ?>
                        <tr>
                            <td>
                                <?php if ($exists) : ?>
                                    <strong><?php echo esc_html($page->post_title); ?></strong>
                                    <?php if (!$active) : ?>
                                        <div style="margin-top:3px;color:#d97706;font-size:0.78rem;font-weight:600;">Gedeactiveerd</div>
                                    <?php endif; ?>
                                <?php elseif ($trashed) : ?>
                                    <span style="color:#d97706;">In prullenbak</span>
                                <?php else : ?>
                                    <span style="color:#dc2626;">Niet aanwezig</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($exists && $role !== 'home') : ?>
                                    <div class="tt-set__slug-edit">
                                        <span class="tt-set__slug-prefix">/</span>
                                        <input type="text" name="page_slug[<?php echo esc_attr($role); ?>]" value="<?php echo esc_attr($page->post_name); ?>" class="tt-set__slug-input" pattern="[a-z0-9-]+" title="Alleen kleine letters, cijfers en streepjes">
                                    </div>
                                <?php elseif ($exists) : ?>
                                    <span class="tt-set__slug" title="Home is de front-page — slug wordt niet in de URL gebruikt">/<?php echo esc_html($page->post_name); ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if (!$active) : ?>
                                    <span style="color:#9ca3af;font-size:0.8rem;">Gedeactiveerd</span>
                                <?php elseif ($role === 'boeken') : ?>
                                    <span style="color:#9ca3af;font-size:0.8rem;" title="Wordt beheerd via Stijl-tab (Webapp op /boeken)">Via Stijl-tab</span>
                                <?php elseif ($exists) : ?>
                                    <label class="tt-set__toggle" style="display:inline-flex;">
                                        <input type="checkbox" name="page_visible[<?php echo esc_attr($role); ?>]" value="1" <?php checked($visible); ?>>
                                        <span class="tt-set__toggle-track"><span class="tt-set__toggle-thumb"></span></span>
                                    </label>
                                <?php else : ?>
                                    <span style="color:#9ca3af;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <div class="tt-set__row-actions" style="justify-content:flex-end;">
                                    <?php if ($exists) :
                                        $has_editor = TaxiTheme_Page_Editor::role_has_editor($role);
                                        $edit_url   = $has_editor
                                            ? TaxiTheme_Page_Editor::edit_url_for_page($page_id)
                                            : admin_url('post.php?post=' . $page_id . '&action=edit');
                                    ?>
                                        <a href="<?php echo esc_url($edit_url); ?>" class="tt-set__btn tt-set__btn--ghost" style="padding:6px 14px;font-size:0.85rem;">Bewerken</a>
                                        <?php if ($active) : ?>
                                            <a href="<?php echo esc_url(get_permalink($page_id)); ?>" target="_blank" class="tt-set__btn tt-set__btn--ghost" style="padding:6px 14px;font-size:0.85rem;">Bekijken</a>
                                        <?php else : ?>
                                            <button type="button" class="tt-set__btn tt-set__btn--ghost" style="padding:6px 14px;font-size:0.85rem;" title="Activeer de pagina om haar te bekijken" disabled>Bekijken</button>
                                        <?php endif; ?>

                                        <?php if ($role === 'home') : ?>
                                            <button type="button" class="tt-set__btn tt-set__btn--ghost" style="padding:6px 14px;font-size:0.85rem;" title="De homepage kan niet worden gedeactiveerd" disabled>Deactiveren</button>
                                        <?php elseif (!$active) : ?>
                                            <button type="submit" name="taxitheme_action" value="activate_page" class="tt-set__btn tt-set__btn--primary" style="padding:6px 14px;font-size:0.85rem;" onclick="this.form.querySelector('input[name=role]').value='<?php echo esc_js($role); ?>';">Activeren</button>
                                        <?php else : ?>
                                            <button type="submit" name="taxitheme_action" value="deactivate_page" class="tt-set__btn tt-set__btn--ghost" style="padding:6px 14px;font-size:0.85rem;" onclick="this.form.querySelector('input[name=role]').value='<?php echo esc_js($role); ?>'; return confirm('Page \'<?php echo esc_js($meta['label']); ?>\' deactiveren? Alle content blijft bewaard.');">Deactiveren</button>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <button type="submit" name="taxitheme_action" value="recreate_page" class="tt-set__btn tt-set__btn--primary" style="padding:6px 14px;font-size:0.85rem;" onclick="this.form.querySelector('input[name=role]').value='<?php echo esc_js($role); ?>';"><?php echo $trashed ? 'Terugzetten' : 'Aanmaken'; ?></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <input type="hidden" name="role" value="">

            <div class="tt-set__actions">
                <button type="submit" class="tt-set__btn tt-set__btn--primary">Wijzigingen opslaan</button>
            </div>
        </form>

        <?php
    }

    private static function tab_settings() {
        $paused = TaxiTheme_Installer::is_paused();

        // Update info
        $current_version = wp_get_theme()->get('Version');
        $latest_version  = null;
        $has_update      = false;
        if (class_exists('TaxiTheme_Updater')) {
            $latest_version = TaxiTheme_Updater::latest_version();
            $has_update     = TaxiTheme_Updater::has_update();
        }
        $force_check_url = admin_url('admin.php?page=' . TaxiTheme_Setup::WIZARD_SLUG . '&tab=settings&taxitheme_force_update_check=1');
        ?>
        <div class="tt-set__panel-head">
            <div>
                <h2>Instellingen</h2>
                <p>Beheer TaxiTheme z'n status op je site.</p>
            </div>
        </div>

        <!-- Update info -->
        <div class="tt-set__card">
            <div class="tt-set__card-head">
                <h3>Updates</h3>
                <p>TaxiTheme haalt updates automatisch op via GitHub. Beschikbare updates verschijnen in <em>Appearance → Themes</em> met een "Update Available" melding.</p>
            </div>
            <div style="display:grid;grid-template-columns:auto 1fr;gap:8px 20px;font-size:0.92rem;margin-bottom:16px;">
                <div style="color:#6b7280;">Geïnstalleerde versie</div>
                <div style="font-weight:700;"><?php echo esc_html($current_version); ?></div>

                <div style="color:#6b7280;">Laatste release</div>
                <div style="font-weight:700;">
                    <?php if ($latest_version) : ?>
                        <?php echo esc_html($latest_version); ?>
                        <?php if ($has_update) : ?>
                            <span style="margin-left:8px;background:#fef9c3;color:#78350f;padding:2px 8px;border-radius:4px;font-size:0.78rem;">Update beschikbaar</span>
                        <?php else : ?>
                            <span style="margin-left:8px;color:#059669;font-size:0.82rem;">✓ Up-to-date</span>
                        <?php endif; ?>
                    <?php else : ?>
                        <span style="color:#9ca3af;">Nog geen release gevonden (of nog niet gecheckt)</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="tt-set__actions" style="margin:0;padding:0;border:none;">
                <a href="<?php echo esc_url($force_check_url); ?>" class="tt-set__btn tt-set__btn--ghost">Check nu op updates</a>
                <?php if ($has_update) : ?>
                    <a href="<?php echo esc_url(admin_url('themes.php')); ?>" class="tt-set__btn tt-set__btn--primary" style="margin-left:8px;">Ga naar Themes om te updaten →</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Uitschakelen (reversible) -->
        <div class="tt-set__card">
            <div class="tt-set__card-head">
                <h3><?php echo $paused ? 'TaxiTheme is uitgeschakeld' : 'TaxiTheme uitschakelen'; ?></h3>
                <p>
                    <?php if ($paused) : ?>
                        De oude homepage is actief en TaxiTheme staat op pauze. Alle content is bewaard — klik hieronder om weer aan te zetten.
                    <?php else : ?>
                        Zet TaxiTheme tijdelijk uit — de oude homepage komt terug, alle pages en content blijven bewaard.
                    <?php endif; ?>
                </p>
            </div>
            <ul class="tt-set__bullets">
                <li>Oude homepage-instelling + primary menu worden hersteld</li>
                <li>TaxiTheme pages blijven gepubliceerd (via directe URL bereikbaar)</li>
                <li>Alle content, settings en bedrijfsgegevens blijven staan</li>
            </ul>

            <div class="tt-set__info" style="margin:0 0 16px;">
                <strong>Let op:</strong> TaxiTheme blijft het actieve thema, dus header en footer blijven van TaxiTheme.
                Wil je terug naar je oorspronkelijke look? Kies dan in <em>Appearance → Themes</em> je vorige thema.
                Dat kan altijd — al je TaxiTheme content blijft bewaard.
            </div>

            <form method="post" class="tt-set__actions">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
                <input type="hidden" name="taxitheme_action" value="toggle_pause">
                <button type="submit" class="tt-set__btn <?php echo $paused ? 'tt-set__btn--primary' : 'tt-set__btn--ghost'; ?>">
                    <?php echo $paused ? 'TaxiTheme weer aanzetten' : 'TaxiTheme uitschakelen'; ?>
                </button>
                <a href="<?php echo esc_url(admin_url('themes.php')); ?>" class="tt-set__btn tt-set__btn--ghost" style="margin-left:8px;">
                    Ga naar Themes →
                </a>
            </form>
        </div>

        <!-- Deïnstalleren (destructive) -->
        <div class="tt-set__card tt-set__card--danger">
            <div class="tt-set__card-head">
                <h3 style="color:#dc2626;">Deïnstalleer TaxiTheme</h3>
                <p>Zet je site terug naar de staat vóór TaxiTheme. Deze actie is minder makkelijk terug te draaien.</p>
            </div>
            <ul class="tt-set__danger-list">
                <li>Homepage-instelling → terug naar origineel</li>
                <li>TaxiTheme pages → naar prullenbak (later terug te halen)</li>
                <li>TaxiTheme menu → verwijderd</li>
                <li>Setup-flag + backup opties → verwijderd</li>
                <li><strong>Oude pages van klant → onaangeroerd</strong></li>
            </ul>

            <form method="post" onsubmit="return confirm('Weet je zeker dat je TaxiTheme wilt deïnstalleren? Pages gaan naar de prullenbak.');" class="tt-set__actions">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
                <input type="hidden" name="taxitheme_action" value="uninstall">
                <label class="tt-set__checkbox">
                    <input type="checkbox" name="delete_company" value="1">
                    Verwijder ook mijn bedrijfsgegevens
                </label>
                <button type="submit" class="tt-set__btn tt-set__btn--danger">Deïnstalleer TaxiTheme</button>
            </form>
        </div>
        <?php
    }
}
