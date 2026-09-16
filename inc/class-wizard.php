<?php
if (!defined('ABSPATH')) {
    exit;
}

class TaxiTheme_Wizard {

    const STEPS = [
        1 => 'Welkom',
        2 => 'Bedrijfsgegevens',
        3 => 'Bevestigen',
        4 => 'Klaar',
    ];

    const NONCE_ACTION = 'taxitheme_wizard_save';
    const NONCE_FIELD  = 'taxitheme_wizard_nonce';

    private static $errors = [];

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_page']);
        add_action('admin_init', [__CLASS__, 'handle_submit']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_styles']);
    }

    public static function register_page() {
        add_menu_page(
            'TaxiTheme Setup',
            'TaxiTheme',
            'manage_options',
            TaxiTheme_Setup::WIZARD_SLUG,
            [__CLASS__, 'render'],
            'dashicons-car',
            60
        );
    }

    public static function enqueue_styles($hook) {
        if ($hook !== 'toplevel_page_' . TaxiTheme_Setup::WIZARD_SLUG) {
            return;
        }
        wp_enqueue_style(
            'taxitheme-wizard-font',
            'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
            [],
            null
        );
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

        $step = (int) ($_POST['taxitheme_step'] ?? 1);

        switch ($step) {
            case 2:
                $input = $_POST['company'] ?? [];
                $errors = TaxiTheme_Company_Info::validate($input);
                if (!empty($errors)) {
                    self::$errors = $errors;
                    return;
                }
                TaxiTheme_Company_Info::save($input);
                wp_safe_redirect(TaxiTheme_Setup::wizard_url(3));
                exit;

            case 3:
                TaxiTheme_Installer::install();
                wp_safe_redirect(TaxiTheme_Setup::wizard_url(4));
                exit;
        }
    }

    public static function render() {
        if (TaxiTheme_Setup::is_completed()) {
            TaxiTheme_Settings::render();
            return;
        }

        $current = isset($_GET['step']) ? max(1, min(4, (int) $_GET['step'])) : 1;
        ?>
        <?php self::render_inline_styles(); ?>
        <div class="tt-wiz">
            <div class="tt-wiz__brand">
                <span class="tt-wiz__logo"><?php echo TaxiTheme_Icons::svg('car', 20); ?></span>
                <span class="tt-wiz__brandtxt">TaxiTheme</span>
            </div>

            <?php self::render_stepper($current); ?>

            <div class="tt-wiz__card">
                <?php self::render_step($current); ?>
            </div>

            <p class="tt-wiz__footer">Duurt ongeveer 2 minuten · Je kunt later alles nog aanpassen</p>
        </div>
        <?php
    }

    private static function render_inline_styles() {
        ?>
        <style>
            #wpcontent, #wpbody-content { padding: 0 !important; }
            .auto-fold #wpcontent { margin-left: 36px; }
            @media (min-width: 961px) { .auto-fold #wpcontent { margin-left: 160px; } }
            #wpfooter, .update-nag, .notice { display: none !important; }

            .tt-wiz {
                font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
                max-width: 1100px;
                margin: 0 auto;
                padding: 40px 32px 60px;
                color: #14161f;
            }
            .tt-wiz * { box-sizing: border-box; }

            .tt-wiz__brand {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 32px;
            }
            .tt-wiz__logo {
                width: 36px; height: 36px;
                background: #f5b800;
                color: #14161f;
                border-radius: 8px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .tt-wiz__logo svg { display: block; }
            .tt-wiz__brandtxt {
                font-size: 1.3rem;
                font-weight: 800;
                letter-spacing: -0.02em;
            }

            /* Stepper */
            .tt-wiz__stepper {
                display: flex;
                align-items: center;
                margin-bottom: 32px;
                gap: 0;
            }
            .tt-wiz__step {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 0.9rem;
                font-weight: 600;
                color: #9ca3af;
            }
            .tt-wiz__step-num {
                width: 32px; height: 32px;
                border-radius: 50%;
                background: #e5e7eb;
                color: #6b7280;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 0.9rem;
                transition: all 0.3s;
            }
            .tt-wiz__step.is-done .tt-wiz__step-num { background: #14161f; color: #fff; }
            .tt-wiz__step.is-done .tt-wiz__step-num::before { content: "✓"; }
            .tt-wiz__step.is-done .tt-wiz__step-num > * { display: none; }
            .tt-wiz__step.is-active .tt-wiz__step-num {
                background: #f5b800;
                color: #14161f;
                box-shadow: 0 0 0 6px rgba(245, 184, 0, 0.15);
            }
            .tt-wiz__step.is-active,
            .tt-wiz__step.is-done { color: #14161f; }
            .tt-wiz__line {
                flex: 1;
                height: 2px;
                background: #e5e7eb;
                margin: 0 12px;
                border-radius: 1px;
            }
            .tt-wiz__line.is-done { background: #14161f; }

            /* Card */
            .tt-wiz__card {
                background: #fff;
                border: 1px solid #e6e2d5;
                border-radius: 16px;
                padding: 48px;
                box-shadow: 0 4px 20px -8px rgba(20, 22, 31, 0.08);
            }

            /* Typography inside wizard */
            .tt-wiz__card h2 {
                font-size: 1.8rem;
                font-weight: 800;
                letter-spacing: -0.025em;
                margin: 0 0 8px;
                color: #14161f;
            }
            .tt-wiz__card > .tt-wiz__lead {
                font-size: 1.05rem;
                color: #6b7280;
                margin: 0 0 32px;
            }

            /* Welcome */
            .tt-wiz__welcome {
                text-align: center;
            }
            .tt-wiz__hero-icon {
                width: 72px; height: 72px;
                background: #f5b800;
                color: #14161f;
                border-radius: 16px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 20px;
                box-shadow: 0 8px 20px -6px rgba(245, 184, 0, 0.5);
            }
            .tt-wiz__hero-icon svg { display: block; }
            .tt-wiz__welcome h2 { font-size: 2.2rem; }
            .tt-wiz__welcome .tt-wiz__lead { max-width: 520px; margin-left: auto; margin-right: auto; }

            .tt-wiz__info-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin: 40px 0;
                text-align: left;
            }
            .tt-wiz__info-card {
                background: #fdfcf7;
                border: 1px solid #e6e2d5;
                border-radius: 12px;
                padding: 28px;
            }
            .tt-wiz__info-card h3 {
                font-size: 1.1rem;
                font-weight: 700;
                margin: 0 0 6px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .tt-wiz__info-icon {
                width: 24px; height: 24px;
                border-radius: 6px;
                background: #f5b800;
                color: #14161f;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .tt-wiz__info-icon svg { display: block; }
            .tt-wiz__info-card p {
                color: #6b7280;
                margin: 0 0 14px;
                font-size: 0.95rem;
            }
            .tt-wiz__info-card ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .tt-wiz__info-card li {
                position: relative;
                padding-left: 22px;
                margin-bottom: 8px;
                font-size: 0.9rem;
                color: #14161f;
            }
            .tt-wiz__info-card li::before {
                content: "✓";
                position: absolute;
                left: 0; top: 0;
                color: #f5b800;
                font-weight: 800;
            }

            /* Form */
            .tt-wiz__form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px 24px;
                margin-top: 8px;
            }
            .tt-wiz__field { display: flex; flex-direction: column; }
            .tt-wiz__field--full { grid-column: 1 / -1; }
            .tt-wiz__field label {
                font-size: 0.9rem;
                font-weight: 600;
                margin-bottom: 6px;
                color: #14161f;
            }
            .tt-wiz__field label .req { color: #dc2626; margin-left: 3px; }
            .tt-wiz__field input {
                padding: 12px 14px;
                border: 1.5px solid #e6e2d5;
                border-radius: 8px;
                font-size: 0.95rem;
                font-family: inherit;
                background: #fff;
                color: #14161f;
                transition: border-color 0.15s, box-shadow 0.15s;
            }
            .tt-wiz__field input:focus {
                outline: none;
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }
            .tt-wiz__field .err {
                color: #dc2626;
                font-size: 0.85rem;
                margin-top: 6px;
            }

            /* Confirm */
            .tt-wiz__summary {
                display: grid;
                gap: 20px;
                margin: 24px 0;
            }
            .tt-wiz__summary-block {
                background: #fdfcf7;
                border: 1px solid #e6e2d5;
                border-radius: 12px;
                padding: 24px;
            }
            .tt-wiz__summary-block h3 {
                font-size: 1rem;
                font-weight: 700;
                margin: 0 0 12px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #6b7280;
            }
            .tt-wiz__summary-block ul { list-style: none; padding: 0; margin: 0; }
            .tt-wiz__summary-block li { padding: 4px 0; }
            .tt-wiz__summary-block strong { font-weight: 600; }
            .tt-wiz__info-box {
                background: #eff6ff;
                border-left: 4px solid #1e3a8a;
                padding: 14px 18px;
                border-radius: 6px;
                font-size: 0.9rem;
                color: #1e3a8a;
                margin: 20px 0;
            }

            /* Nav */
            .tt-wiz__nav {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 32px;
                padding-top: 24px;
                border-top: 1px solid #e6e2d5;
            }
            .tt-wiz__nav--center { justify-content: center; }
            .tt-wiz__btn {
                display: inline-block;
                padding: 12px 24px;
                border-radius: 8px;
                font-family: inherit;
                font-weight: 700;
                font-size: 0.95rem;
                cursor: pointer;
                border: 1.5px solid transparent;
                text-decoration: none;
                transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
            }
            .tt-wiz__btn--primary {
                background: #f5b800;
                color: #14161f;
                box-shadow: 0 4px 12px -3px rgba(245, 184, 0, 0.5);
            }
            .tt-wiz__btn--primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 18px -3px rgba(245, 184, 0, 0.6);
                background: #f5b800;
                color: #14161f;
            }
            .tt-wiz__btn--primary.tt-wiz__btn--lg {
                padding: 16px 40px;
                font-size: 1.05rem;
            }
            .tt-wiz__btn--ghost {
                background: transparent;
                color: #6b7280;
                border-color: #e6e2d5;
            }
            .tt-wiz__btn--ghost:hover { color: #14161f; border-color: #14161f; }

            /* Done */
            .tt-wiz__done { text-align: center; }
            .tt-wiz__done h2 { font-size: 2rem; }

            /* Footer */
            .tt-wiz__footer {
                text-align: center;
                margin-top: 24px;
                color: #9ca3af;
                font-size: 0.85rem;
            }

            /* Mobile */
            @media (max-width: 780px) {
                .tt-wiz { padding: 24px 16px 40px; }
                .tt-wiz__card { padding: 28px 20px; }
                .tt-wiz__info-grid { grid-template-columns: 1fr; }
                .tt-wiz__form-grid { grid-template-columns: 1fr; }
                .tt-wiz__step-label { display: none; }
                .tt-wiz__line { margin: 0 8px; }
            }
        </style>
        <?php
    }

    private static function render_stepper($current) {
        $steps = self::STEPS;
        $total = count($steps);
        $i = 0;
        ?>
        <div class="tt-wiz__stepper">
            <?php foreach ($steps as $num => $label) :
                $i++;
                $class = '';
                if ($num < $current) $class = 'is-done';
                elseif ($num === $current) $class = 'is-active';
            ?>
                <div class="tt-wiz__step <?php echo $class; ?>">
                    <span class="tt-wiz__step-num"><span><?php echo $num; ?></span></span>
                    <span class="tt-wiz__step-label"><?php echo esc_html($label); ?></span>
                </div>
                <?php if ($i < $total) : ?>
                    <div class="tt-wiz__line <?php echo $num < $current ? 'is-done' : ''; ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private static function render_step($step) {
        switch ($step) {
            case 1: self::step_welcome();  break;
            case 2: self::step_company();  break;
            case 3: self::step_confirm();  break;
            case 4: self::step_done();     break;
        }
    }

    private static function step_welcome() {
        ?>
        <div class="tt-wiz__welcome">
            <div class="tt-wiz__hero-icon"><?php echo TaxiTheme_Icons::svg('car', 36); ?></div>
            <h2>Welkom bij TaxiTheme</h2>
            <p class="tt-wiz__lead">
                In een paar stappen richten we een complete, professionele
                taxi-website voor je in — inclusief boekingsformulier.
            </p>

            <div class="tt-wiz__info-grid">
                <div class="tt-wiz__info-card">
                    <h3><span class="tt-wiz__info-icon"><?php echo TaxiTheme_Icons::svg('sparkles', 14); ?></span> Nieuwe website</h3>
                    <p>Perfect voor een lege WordPress-installatie of vervanging van een oude site.</p>
                    <ul>
                        <li>6 pagina's automatisch aangemaakt</li>
                        <li>Navigatiemenu direct ingericht</li>
                        <li>Homepage klaar voor gebruik</li>
                        <li>Boekingsformulier meteen actief</li>
                    </ul>
                </div>
                <div class="tt-wiz__info-card">
                    <h3><span class="tt-wiz__info-icon"><?php echo TaxiTheme_Icons::svg('refresh', 14); ?></span> Bestaande website</h3>
                    <p>Heb je al pagina's? Geen probleem — die blijven veilig staan.</p>
                    <ul>
                        <li>Oude pagina's blijven intact</li>
                        <li>TaxiTheme voegt nieuwe pagina's toe</li>
                        <li>Deïnstalleren zet alles terug</li>
                        <li>Alle content blijft bewaard</li>
                    </ul>
                </div>
            </div>

            <a href="<?php echo esc_url(TaxiTheme_Setup::wizard_url(2)); ?>" class="tt-wiz__btn tt-wiz__btn--primary tt-wiz__btn--lg">
                Start setup →
            </a>
        </div>
        <?php
    }

    private static function step_company() {
        $data = TaxiTheme_Company_Info::all();
        ?>
        <h2>Vertel ons over je bedrijf</h2>
        <p class="tt-wiz__lead">Deze gegevens verschijnen in je header, footer en SEO-schema.</p>

        <form method="post">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
            <input type="hidden" name="taxitheme_step" value="2">

            <div class="tt-wiz__form-grid">
                <?php foreach (TaxiTheme_Company_Info::FIELDS as $field => $meta) :
                    $value    = $data[$field] ?? '';
                    $error    = self::$errors[$field] ?? '';
                    $required = !empty($meta['required']);
                    $wide     = in_array($field, ['address'], true);
                ?>
                    <div class="tt-wiz__field <?php echo $wide ? 'tt-wiz__field--full' : ''; ?>">
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

            <div class="tt-wiz__nav">
                <a href="<?php echo esc_url(TaxiTheme_Setup::wizard_url(1)); ?>" class="tt-wiz__btn tt-wiz__btn--ghost">← Vorige</a>
                <button type="submit" class="tt-wiz__btn tt-wiz__btn--primary">Opslaan en verder →</button>
            </div>
        </form>
        <?php
    }

    private static function step_confirm() {
        $company = TaxiTheme_Company_Info::all();
        $roles   = TaxiTheme_Installer::roles();
        ?>
        <h2>Klaar om te installeren?</h2>
        <p class="tt-wiz__lead">Controleer je gegevens. Klik "Installeren" om je site in te richten.</p>

        <div class="tt-wiz__summary">
            <div class="tt-wiz__summary-block">
                <h3>Bedrijfsgegevens</h3>
                <ul>
                    <li><strong>Naam:</strong> <?php echo esc_html($company['name'] ?: '—'); ?></li>
                    <li><strong>Telefoon:</strong> <?php echo esc_html($company['phone'] ?: '—'); ?></li>
                    <?php if (!empty($company['email'])) : ?>
                        <li><strong>E-mail:</strong> <?php echo esc_html($company['email']); ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="tt-wiz__summary-block">
                <h3>Wat wordt er aangemaakt?</h3>
                <ul>
                    <?php foreach ($roles as $data) : ?>
                        <li>✓ <?php echo esc_html($data['label']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="tt-wiz__summary-block">
                <h3>Wat wordt er ingesteld?</h3>
                <ul>
                    <li>Navigatiemenu → wijst naar de nieuwe pagina's</li>
                    <li>Voorpagina → wordt de nieuwe Home</li>
                    <li>Homepage layout → hero + booking + secties</li>
                </ul>
            </div>
        </div>

        <div class="tt-wiz__info-box">
            <strong>Bestaande content blijft veilig:</strong>
            oude pagina's, menu's en instellingen worden nooit verwijderd of overschreven.
            Bij deïnstallatie zetten we alles terug naar de originele staat.
        </div>

        <form method="post" class="tt-wiz__nav">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
            <input type="hidden" name="taxitheme_step" value="3">
            <a href="<?php echo esc_url(TaxiTheme_Setup::wizard_url(2)); ?>" class="tt-wiz__btn tt-wiz__btn--ghost">← Vorige</a>
            <button type="submit" class="tt-wiz__btn tt-wiz__btn--primary">Installeren →</button>
        </form>
        <?php
    }

    private static function step_done() {
        ?>
        <div class="tt-wiz__done">
            <div class="tt-wiz__hero-icon"><?php echo TaxiTheme_Icons::svg('check-circle', 36); ?></div>
            <h2>Je site staat klaar!</h2>
            <p class="tt-wiz__lead">
                6 pagina's zijn aangemaakt, het menu is ingesteld en je homepage is live.
                Tijd om te bekijken.
            </p>
            <div style="margin-top:32px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="tt-wiz__btn tt-wiz__btn--primary tt-wiz__btn--lg">
                    Bekijk je site →
                </a>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>" class="tt-wiz__btn tt-wiz__btn--ghost">
                    Bekijk pagina's
                </a>
            </div>
        </div>
        <?php
    }
}
