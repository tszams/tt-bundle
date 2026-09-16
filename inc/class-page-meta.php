<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Per-page content helper. Voor sub-pages (tarieven, diensten, over-ons, contact)
 * die naast de shared homepage-data ook een eigen intro + extra info-blokken hebben.
 *
 * Opslag: post_meta op de betreffende page. Zo blijft data bij de page horen —
 * verwijder je de page, verdwijnt de data mee. Geen pollution van global options.
 */
class TaxiTheme_Page_Meta {

    const META_INTRO    = '_taxitheme_intro';
    const META_SECTIONS = '_taxitheme_sections';
    const META_CTA_TITLE    = '_taxitheme_cta_title';
    const META_CTA_SUBTITLE = '_taxitheme_cta_subtitle';
    const META_CTA1_LABEL   = '_taxitheme_cta1_label';
    const META_CTA1_URL     = '_taxitheme_cta1_url';
    const META_CTA2_LABEL   = '_taxitheme_cta2_label';
    const META_CTA2_URL     = '_taxitheme_cta2_url';
    const SECTIONS_MAX  = 3;

    // Over ons-specifieke uitbreiding:
    // Donkere hero (eyebrow + 2 CTA's) + 4 USP-kaartjes + donkere bottom CTA sectie
    const OVER_ONS_USPS_MAX = 4;
    const OVER_ONS_META_KEYS = [
        'eyebrow'      => '_taxitheme_over_ons_eyebrow',
        'cta1_label'   => '_taxitheme_over_ons_cta1_label',
        'cta1_url'     => '_taxitheme_over_ons_cta1_url',
        'cta2_label'   => '_taxitheme_over_ons_cta2_label',
        'cta2_url'     => '_taxitheme_over_ons_cta2_url',
        'usps_enabled' => '_taxitheme_over_ons_usps_enabled',
        'footer_title' => '_taxitheme_over_ons_footer_title',
        'footer_text'  => '_taxitheme_over_ons_footer_text',
        'footer_cta1_label' => '_taxitheme_over_ons_footer_cta1_label',
        'footer_cta1_url'   => '_taxitheme_over_ons_footer_cta1_url',
        'footer_cta2_label' => '_taxitheme_over_ons_footer_cta2_label',
        'footer_cta2_url'   => '_taxitheme_over_ons_footer_cta2_url',
    ];
    const META_OVER_ONS_USPS = '_taxitheme_over_ons_usps';

    /**
     * Intro-tekst voor de page. Losse regels = paragrafen (lege regels splitten).
     */
    public static function get_intro($post_id) {
        return (string) get_post_meta($post_id, self::META_INTRO, true);
    }

    /**
     * Array van SECTIONS_MAX blokken, elk met {title, text}.
     * Lege blokken worden bij render weggefilterd; hier altijd volle array voor de editor.
     */
    public static function get_sections($post_id) {
        $stored = get_post_meta($post_id, self::META_SECTIONS, true);
        if (!is_array($stored)) $stored = [];
        $out = [];
        for ($i = 0; $i < self::SECTIONS_MAX; $i++) {
            $s = $stored[$i] ?? [];
            $out[$i] = [
                'title'    => isset($s['title'])    ? (string) $s['title']    : '',
                'text'     => isset($s['text'])     ? (string) $s['text']     : '',
                'image_id' => isset($s['image_id']) ? (int)    $s['image_id'] : 0,
            ];
        }
        return $out;
    }

    /**
     * Alleen niet-lege sections, voor render op de frontend.
     */
    public static function get_visible_sections($post_id) {
        return array_values(array_filter(self::get_sections($post_id), function ($s) {
            return !empty($s['title']) || !empty($s['text']);
        }));
    }

    public static function save($post_id, array $input) {
        if (array_key_exists('intro', $input)) {
            $intro = sanitize_textarea_field($input['intro']);
            if ($intro === '') {
                delete_post_meta($post_id, self::META_INTRO);
            } else {
                update_post_meta($post_id, self::META_INTRO, $intro);
            }
        }

        if (isset($input['sections']) && is_array($input['sections'])) {
            $out = [];
            for ($i = 0; $i < self::SECTIONS_MAX; $i++) {
                $s = $input['sections'][$i] ?? [];
                $out[$i] = [
                    'title'    => sanitize_text_field($s['title'] ?? ''),
                    'text'     => sanitize_textarea_field($s['text'] ?? ''),
                    'image_id' => (int) ($s['image_id'] ?? 0),
                ];
            }
            update_post_meta($post_id, self::META_SECTIONS, $out);
        }

        // CTA-banner override (per-page). Leeg = delete → fallback naar home defaults.
        $cta_string_fields = [
            'cta_title'    => self::META_CTA_TITLE,
            'cta_subtitle' => self::META_CTA_SUBTITLE,
            'cta1_label'   => self::META_CTA1_LABEL,
            'cta2_label'   => self::META_CTA2_LABEL,
        ];
        foreach ($cta_string_fields as $key => $meta_key) {
            if (!array_key_exists($key, $input)) continue;
            $v = sanitize_text_field($input[$key]);
            if ($v === '') delete_post_meta($post_id, $meta_key);
            else update_post_meta($post_id, $meta_key, $v);
        }
        $cta_url_fields = [
            'cta1_url' => self::META_CTA1_URL,
            'cta2_url' => self::META_CTA2_URL,
        ];
        foreach ($cta_url_fields as $key => $meta_key) {
            if (!array_key_exists($key, $input)) continue;
            $v = esc_url_raw($input[$key]);
            if ($v === '') delete_post_meta($post_id, $meta_key);
            else update_post_meta($post_id, $meta_key, $v);
        }
    }

    /**
     * Per-page CTA-banner override. Return array met title/subtitle + optionele
     * custom button labels/URLs. Lege waardes → template valt terug op defaults.
     */
    public static function get_cta_override($post_id) {
        return [
            'title'      => (string) get_post_meta($post_id, self::META_CTA_TITLE,    true),
            'subtitle'   => (string) get_post_meta($post_id, self::META_CTA_SUBTITLE, true),
            'cta1_label' => (string) get_post_meta($post_id, self::META_CTA1_LABEL,   true),
            'cta1_url'   => (string) get_post_meta($post_id, self::META_CTA1_URL,     true),
            'cta2_label' => (string) get_post_meta($post_id, self::META_CTA2_LABEL,   true),
            'cta2_url'   => (string) get_post_meta($post_id, self::META_CTA2_URL,     true),
        ];
    }

    /**
     * Over ons hero + footer velden (eyebrow, CTA's, footer sectie).
     */
    public static function get_over_ons_extras($post_id) {
        $out = [];
        foreach (self::OVER_ONS_META_KEYS as $key => $meta_key) {
            $out[$key] = (string) get_post_meta($post_id, $meta_key, true);
        }
        return $out;
    }

    /**
     * 4 USP-slots voor de Over ons pagina — altijd volle array voor de editor.
     */
    public static function get_over_ons_usps($post_id) {
        $stored = get_post_meta($post_id, self::META_OVER_ONS_USPS, true);
        if (!is_array($stored)) $stored = [];
        $out = [];
        for ($i = 0; $i < self::OVER_ONS_USPS_MAX; $i++) {
            $u = $stored[$i] ?? [];
            $out[$i] = [
                'icon'  => isset($u['icon'])  ? sanitize_key($u['icon']) : '',
                'title' => isset($u['title']) ? (string) $u['title'] : '',
                'text'  => isset($u['text'])  ? (string) $u['text']  : '',
            ];
        }
        return $out;
    }

    public static function get_visible_over_ons_usps($post_id) {
        return array_values(array_filter(self::get_over_ons_usps($post_id), function ($u) {
            return !empty($u['title']) || !empty($u['text']);
        }));
    }

    /**
     * Defaults voor de Over ons pagina — dynamisch gegenereerd uit bedrijfsgegevens,
     * zodat er direct passende tekst in staat bij een nieuwe installatie.
     */
    public static function over_ons_defaults() {
        $company = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));
        $city    = TaxiTheme_Company_Info::get('city');
        $phone   = TaxiTheme_Company_Info::get('phone');
        $phone_clean = $phone ? preg_replace('/[^0-9+]/', '', $phone) : '';
        $tel_url = $phone_clean ? 'tel:' . $phone_clean : '';

        $boeken_url = '/boeken';
        if (class_exists('TaxiTheme_Installer')) {
            $bid = TaxiTheme_Installer::get_page_id('boeken');
            if ($bid) {
                $link = get_permalink($bid);
                if ($link) $boeken_url = $link;
            }
        }

        $eyebrow = trim($company . ($city ? ' · ' . $city . ' & omgeving' : ''));
        $intro = sprintf(
            "%s verzorgt gepland en vooraf gereserveerd taxivervoer%s. Duidelijke afspraken, rechtstreeks vervoer en persoonlijk contact staan daarbij centraal.\n\nOnze chauffeurs kennen de regio, zijn ervaren en zorgen dat u ontspannen op uw bestemming aankomt — op tijd, elke keer.",
            $company,
            $city ? ' vanuit ' . $city . ' en omgeving' : ''
        );

        return [
            'intro'        => $intro,
            'eyebrow'      => $eyebrow,
            'cta1_label'   => 'Reserveer uw taxi',
            'cta1_url'     => $boeken_url,
            'cta2_label'   => $phone ? 'Bel ' . $phone : 'Neem contact op',
            'cta2_url'     => $tel_url ?: $boeken_url,
            'usps_enabled' => 1,
            'footer_title' => 'Klaar om uw rit vooraf te regelen?',
            'footer_text'  => 'Reserveer uw vervoer online of neem rechtstreeks contact op wanneer u eerst een vraag heeft over uw rit, bagage of reisplanning.',
            'footer_cta1_label' => 'Reserveer uw taxi',
            'footer_cta1_url'   => $boeken_url,
            'footer_cta2_label' => $phone ? 'Bel ' . $phone : '',
            'footer_cta2_url'   => $tel_url,
            'sections' => [
                [
                    'title' => 'Onze aanpak',
                    'text'  => "Wij geloven in eenvoud. U reserveert vooraf, wij bevestigen de afspraak en op de dag zelf zorgen we dat alles klopt.\n\nGeen wachttijden, geen onduidelijkheden — gewoon een chauffeur die u opwacht en direct naar uw bestemming brengt.",
                ],
                [
                    'title' => 'Onze wagens',
                    'text'  => "Nette, comfortabele wagens die grondig worden onderhouden. Voldoende bagageruimte, airco en een rustige rit — zodat u ontspannen aankomt.",
                ],
                [
                    'title' => 'Voor wie wij rijden',
                    'text'  => "Zakelijk luchthavenvervoer, particulieren die een betrouwbare rit willen, en groepen die samen willen reizen. Ieder type rit met dezelfde aandacht.",
                ],
            ],
            'usps' => [
                [
                    'icon'  => 'check',
                    'title' => 'Duidelijkheid vooraf',
                    'text'  => 'Uw rit en afspraken worden vooraf vastgelegd — geen verrassingen achteraf.',
                ],
                [
                    'icon'  => 'car',
                    'title' => 'Ervaren chauffeurs',
                    'text'  => 'Vaste, betrouwbare chauffeurs die de regio als hun broekzak kennen.',
                ],
                [
                    'icon'  => 'clock',
                    'title' => 'Op tijd, elke keer',
                    'text'  => 'Vanaf het afgesproken adres direct naar uw bestemming — zonder omweg.',
                ],
                [
                    'icon'  => 'phone',
                    'title' => 'Persoonlijk contact',
                    'text'  => 'Rechtstreeks bereikbaar bij vragen, wijzigingen of last-minute planning.',
                ],
            ],
        ];
    }

    /**
     * Vul de Over ons pagina met defaults uit over_ons_defaults().
     * Alleen als er nog niet geseed is (idempotent via post_meta flag).
     * $force=true overschrijft alles opnieuw met de defaults.
     */
    public static function ensure_over_ons_seeded($post_id, $force = false) {
        if (!$force && get_post_meta($post_id, '_taxitheme_over_ons_seeded', true)) {
            return false;
        }
        $d = self::over_ons_defaults();

        // Intro (gedeeld veld met andere pages)
        if ($force || (string) get_post_meta($post_id, self::META_INTRO, true) === '') {
            update_post_meta($post_id, self::META_INTRO, $d['intro']);
        }

        // Over ons-specifieke velden
        foreach (self::OVER_ONS_META_KEYS as $key => $meta_key) {
            if (!$force && (string) get_post_meta($post_id, $meta_key, true) !== '') continue;
            $val = $d[$key] ?? '';
            if ($val === '') continue;
            update_post_meta($post_id, $meta_key, $val);
        }

        // USPs
        $existing_usps = get_post_meta($post_id, self::META_OVER_ONS_USPS, true);
        if ($force || !is_array($existing_usps) || empty(array_filter($existing_usps, function ($u) {
            return !empty($u['title']) || !empty($u['text']);
        }))) {
            update_post_meta($post_id, self::META_OVER_ONS_USPS, $d['usps']);
        }

        // Sections (gedeeld veld)
        $existing_sections = get_post_meta($post_id, self::META_SECTIONS, true);
        if ($force || !is_array($existing_sections) || empty(array_filter($existing_sections, function ($s) {
            return !empty($s['title']) || !empty($s['text']);
        }))) {
            update_post_meta($post_id, self::META_SECTIONS, $d['sections']);
        }

        update_post_meta($post_id, '_taxitheme_over_ons_seeded', 1);
        return true;
    }

    public static function save_over_ons_extras($post_id, array $input) {
        // Text velden + URL velden
        foreach (self::OVER_ONS_META_KEYS as $key => $meta_key) {
            // Bool-toggle apart afhandelen: checkbox = altijd 1 of 0 (afwezig = 0).
            if ($key === 'usps_enabled') {
                update_post_meta($post_id, $meta_key, !empty($input['usps_enabled']) ? '1' : '0');
                continue;
            }
            if (!array_key_exists($key, $input)) continue;
            $val = trim((string) $input[$key]);
            if ($val === '') {
                delete_post_meta($post_id, $meta_key);
                continue;
            }
            if (substr($key, -4) === '_url') {
                update_post_meta($post_id, $meta_key, esc_url_raw($val));
            } elseif ($key === 'footer_text') {
                update_post_meta($post_id, $meta_key, sanitize_textarea_field($val));
            } else {
                update_post_meta($post_id, $meta_key, sanitize_text_field($val));
            }
        }
        // USPs
        if (isset($input['usps']) && is_array($input['usps'])) {
            $out = [];
            for ($i = 0; $i < self::OVER_ONS_USPS_MAX; $i++) {
                $u = $input['usps'][$i] ?? [];
                $out[$i] = [
                    'icon'  => sanitize_key($u['icon'] ?? ''),
                    'title' => sanitize_text_field($u['title'] ?? ''),
                    'text'  => sanitize_textarea_field($u['text'] ?? ''),
                ];
            }
            update_post_meta($post_id, self::META_OVER_ONS_USPS, $out);
        }
    }

    /**
     * Render alle zichtbare sections in een simpele lijst — herbruikbaar door page-templates.
     */
    public static function render_sections($post_id, $wrapper_class = 'tt-page-sections') {
        $items = self::get_visible_sections($post_id);
        if (empty($items)) return;
        ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>">
            <?php foreach ($items as $s) :
                $image_url = !empty($s['image_id']) ? wp_get_attachment_image_url($s['image_id'], 'large') : '';
            ?>
                <div class="tt-page-section<?php echo $image_url ? ' has-image' : ''; ?>">
                    <div class="tt-page-section__head">
                        <?php if ($image_url) : ?>
                            <div class="tt-page-section__image">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($s['title']); ?>" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($s['title'])) : ?>
                            <h2 class="tt-page-section__title"><?php echo esc_html($s['title']); ?></h2>
                        <?php endif; ?>
                    </div>
                    <div class="tt-page-section__body">
                        <?php if (!empty($s['text'])) :
                            $paragraphs = preg_split('/\r?\n\r?\n/', trim($s['text']));
                            foreach ($paragraphs as $p) :
                                $p = trim($p);
                                if ($p !== '') : ?>
                                    <p class="tt-page-section__text"><?php echo nl2br(esc_html($p)); ?></p>
                                <?php endif;
                            endforeach;
                        endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
