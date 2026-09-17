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
    const META_COMPONENT_ORDER   = '_taxitheme_component_order';
    const META_COMPONENT_ENABLED = '_taxitheme_component_enabled';
    const SECTIONS_MAX  = 3;

    /** Korte, stabiele namen voor de onderdelen in de pagina-editor. */
    const COMPONENTS = [
        'tarieven'    => ['intro' => 'Intro', 'vehicles' => 'Vervoerstypes', 'destinations' => 'Bestemmingen', 'zones' => 'Regio’s', 'cta' => 'CTA-banner'],
        'diensten'    => ['intro' => 'Intro', 'services' => 'Dienstdetails', 'cta' => 'CTA-banner'],
        'over-ons'    => ['intro' => 'Intro', 'usps' => 'USP-kaartjes', 'cta' => 'CTA-banner'],
        'faq'         => ['intro' => 'Intro', 'faq' => 'Veelgestelde vragen', 'cta' => 'CTA-banner'],
        'contact'     => ['intro' => 'Intro', 'cta' => 'CTA-banner'],
        'privacy'     => ['title' => 'Paginatitel', 'content' => 'Inhoud'],
        'voorwaarden' => ['title' => 'Paginatitel', 'content' => 'Inhoud'],
    ];

    public static function init() {
        add_action('wp_head', [__CLASS__, 'render_component_visibility_css'], 99);
        add_action('wp_footer', [__CLASS__, 'render_component_order_script'], 99);
    }

    public static function get_component_definitions($role) {
        return self::COMPONENTS[$role] ?? [];
    }

    public static function get_component_config($post_id, $role = '') {
        if ($role === '') {
            $role = get_post_meta($post_id, TaxiTheme_Installer::META_ROLE, true);
        }
        $definitions = self::get_component_definitions($role);
        $keys = array_keys($definitions);

        $stored_order = get_post_meta($post_id, self::META_COMPONENT_ORDER, true);
        $order = is_array($stored_order)
            ? array_values(array_unique(array_intersect(array_map('sanitize_key', $stored_order), $keys)))
            : [];
        foreach ($keys as $key) {
            if (!in_array($key, $order, true)) $order[] = $key;
        }

        $stored_enabled = get_post_meta($post_id, self::META_COMPONENT_ENABLED, true);
        $enabled = [];
        foreach ($keys as $key) {
            $enabled[$key] = !is_array($stored_enabled) || !array_key_exists($key, $stored_enabled)
                ? true
                : !empty($stored_enabled[$key]);
        }

        return ['definitions' => $definitions, 'order' => $order, 'enabled' => $enabled];
    }

    public static function save_component_config($post_id, array $input) {
        $role = get_post_meta($post_id, TaxiTheme_Installer::META_ROLE, true);
        $definitions = self::get_component_definitions($role);
        if (empty($definitions) || empty($input['component_config_present'])) return;

        $keys = array_keys($definitions);
        $incoming_order = isset($input['component_order']) && is_array($input['component_order'])
            ? array_map('sanitize_key', $input['component_order'])
            : [];
        $order = array_values(array_unique(array_intersect($incoming_order, $keys)));
        foreach ($keys as $key) {
            if (!in_array($key, $order, true)) $order[] = $key;
        }

        $incoming_enabled = isset($input['component_enabled']) && is_array($input['component_enabled'])
            ? $input['component_enabled']
            : [];
        $enabled = [];
        foreach ($keys as $key) $enabled[$key] = !empty($incoming_enabled[$key]) ? 1 : 0;

        update_post_meta($post_id, self::META_COMPONENT_ORDER, $order);
        update_post_meta($post_id, self::META_COMPONENT_ENABLED, $enabled);
    }

    private static function frontend_component_context() {
        if (is_admin() || !is_page()) return null;
        $post_id = get_queried_object_id();
        $role = get_post_meta($post_id, TaxiTheme_Installer::META_ROLE, true);
        if (!isset(self::COMPONENTS[$role])) return null;

        $selectors = [
            'intro'        => '.tt-page-header__intro',
            'title'        => '.tt-page-header',
            'content'      => '.tt-legal-content',
            'vehicles'     => '.tt-tv-vehicles',
            'destinations' => '.tt-tv-destinations',
            'zones'        => '.tt-tv-zones',
            'services'     => '.tt-services-detail',
            'usps'         => '.tt-over-usps, .tt-page-header__usps-spacer',
            'faq'          => '.tt-faq',
            'cta'          => '.tt-contact-cta',
        ];
        return [$role, self::get_component_config($post_id, $role), $selectors];
    }

    public static function render_component_visibility_css() {
        $context = self::frontend_component_context();
        if (!$context) return;
        [, $config, $selectors] = $context;
        $hidden = [];
        foreach ($config['enabled'] as $key => $enabled) {
            if (!$enabled && isset($selectors[$key])) $hidden[] = $selectors[$key];
        }
        if ($hidden) echo '<style id="taxitheme-component-visibility">' . implode(',', array_map('esc_html', $hidden)) . '{display:none!important}</style>';
    }

    public static function render_component_order_script() {
        $context = self::frontend_component_context();
        if (!$context) return;
        [, $config, $selectors] = $context;
        $ordered_selectors = [];
        foreach ($config['order'] as $key) {
            if (!empty($config['enabled'][$key]) && isset($selectors[$key])) $ordered_selectors[] = $selectors[$key];
        }
        if (count($ordered_selectors) < 2) return;
        ?>
        <script id="taxitheme-component-order">
        (function () {
            var selectors = <?php echo wp_json_encode($ordered_selectors); ?>;
            var groups = new Map();
            selectors.forEach(function (selector) {
                var node = document.querySelector(selector);
                if (!node) return;
                if (selector === '.tt-legal-content') node = node.closest('section') || node;
                var parent = node.parentNode;
                if (!parent) return;
                if (!groups.has(parent)) groups.set(parent, []);
                groups.get(parent).push(node);
            });
            groups.forEach(function (nodes, parent) {
                if (nodes.length < 2) return;
                var firstNode = nodes.reduce(function (first, node) {
                    return first.compareDocumentPosition(node) & Node.DOCUMENT_POSITION_PRECEDING ? node : first;
                }, nodes[0]);
                var marker = document.createComment('taxitheme-component-order');
                parent.insertBefore(marker, firstNode);
                var cursor = marker;
                nodes.forEach(function (node) {
                    parent.insertBefore(node, cursor.nextSibling);
                    cursor = node;
                });
                marker.remove();
            });
        })();
        </script>
        <?php
    }

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

    // Tarieven-pagina uitbreiding — rijke prijs-blokken.
    // Zie template/tarieven.php voor render, en class-page-editor.php voor UI.
    const META_TARIEVEN_VEHICLES     = '_taxitheme_tarieven_vehicles';
    const META_TARIEVEN_DESTINATIONS = '_taxitheme_tarieven_destinations';
    const META_TARIEVEN_ZONES        = '_taxitheme_tarieven_zones';
    const TARIEVEN_VEHICLES_MAX      = 4;
    const TARIEVEN_DEST_ITEMS_MAX    = 10;
    const TARIEVEN_ZONES_GROUPS_MAX  = 4;
    const TARIEVEN_ZONES_ROWS_MAX    = 8;

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
        self::save_component_config($post_id, $input);

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

        // Tarieven — vervoerstypes (4 slots)
        if (isset($input['tarieven_vehicles']) && is_array($input['tarieven_vehicles'])) {
            $out = [];
            for ($i = 0; $i < self::TARIEVEN_VEHICLES_MAX; $i++) {
                $v = $input['tarieven_vehicles'][$i] ?? [];
                $out[$i] = [
                    'title'         => sanitize_text_field($v['title'] ?? ''),
                    'description'   => sanitize_textarea_field($v['description'] ?? ''),
                    'image_id'      => (int) ($v['image_id'] ?? 0),
                    'starttarief'   => sanitize_text_field($v['starttarief'] ?? ''),
                    'kilometertarief' => sanitize_text_field($v['kilometertarief'] ?? ''),
                    'tijdstarief'   => sanitize_text_field($v['tijdstarief'] ?? ''),
                ];
            }
            update_post_meta($post_id, self::META_TARIEVEN_VEHICLES, $out);
        }

        // Tarieven — bestemmingen (1 blok, dynamische items)
        if (isset($input['tarieven_destinations']) && is_array($input['tarieven_destinations'])) {
            $d = $input['tarieven_destinations'];
            $items_in = isset($d['items']) && is_array($d['items']) ? $d['items'] : [];
            $items = [];
            for ($i = 0; $i < self::TARIEVEN_DEST_ITEMS_MAX; $i++) {
                $it = $items_in[$i] ?? [];
                $items[$i] = [
                    'label' => sanitize_text_field($it['label'] ?? ''),
                    'price' => sanitize_text_field($it['price'] ?? ''),
                ];
            }
            update_post_meta($post_id, self::META_TARIEVEN_DESTINATIONS, [
                'title'       => sanitize_text_field($d['title'] ?? ''),
                'description' => sanitize_textarea_field($d['description'] ?? ''),
                'image_id'    => (int) ($d['image_id'] ?? 0),
                'items'       => $items,
            ]);
        }

        // Tarieven — regionale zones (1 blok, groepen met rows)
        if (isset($input['tarieven_zones']) && is_array($input['tarieven_zones'])) {
            $z = $input['tarieven_zones'];
            $groups_in = isset($z['groups']) && is_array($z['groups']) ? $z['groups'] : [];
            $groups = [];
            for ($i = 0; $i < self::TARIEVEN_ZONES_GROUPS_MAX; $i++) {
                $g = $groups_in[$i] ?? [];
                $rows_in = isset($g['rows']) && is_array($g['rows']) ? $g['rows'] : [];
                $rows = [];
                for ($j = 0; $j < self::TARIEVEN_ZONES_ROWS_MAX; $j++) {
                    $r = $rows_in[$j] ?? [];
                    $rows[$j] = [
                        'label' => sanitize_text_field($r['label'] ?? ''),
                        'price' => sanitize_text_field($r['price'] ?? ''),
                    ];
                }
                $groups[$i] = [
                    'icon'  => sanitize_key($g['icon'] ?? ''),
                    'title' => sanitize_text_field($g['title'] ?? ''),
                    'rows'  => $rows,
                ];
            }
            update_post_meta($post_id, self::META_TARIEVEN_ZONES, [
                'title'    => sanitize_text_field($z['title'] ?? ''),
                'subtitle' => sanitize_textarea_field($z['subtitle'] ?? ''),
                'groups'   => $groups,
            ]);
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

    // ================================================================
    //  Tarieven-pagina — vervoerstypes, bestemmingen, regionale zones
    // ================================================================

    /**
     * 4 vervoerstype-slots. Elk: title + description + image + starttarief/km/tijd.
     */
    public static function get_tarieven_vehicles($post_id) {
        $stored = get_post_meta($post_id, self::META_TARIEVEN_VEHICLES, true);
        if (!is_array($stored)) $stored = [];
        $out = [];
        for ($i = 0; $i < self::TARIEVEN_VEHICLES_MAX; $i++) {
            $v = $stored[$i] ?? [];
            $out[$i] = [
                'title'         => isset($v['title'])         ? (string) $v['title']         : '',
                'description'   => isset($v['description'])   ? (string) $v['description']   : '',
                'image_id'      => isset($v['image_id'])      ? (int)    $v['image_id']      : 0,
                'starttarief'   => isset($v['starttarief'])   ? (string) $v['starttarief']   : '',
                'kilometertarief' => isset($v['kilometertarief']) ? (string) $v['kilometertarief'] : '',
                'tijdstarief'   => isset($v['tijdstarief'])   ? (string) $v['tijdstarief']   : '',
            ];
        }
        return $out;
    }

    public static function get_visible_tarieven_vehicles($post_id) {
        return array_values(array_filter(self::get_tarieven_vehicles($post_id), function ($v) {
            return !empty($v['title']);
        }));
    }

    /**
     * Bestemmingen-blok: 1 blok met sectie-titel/desc/foto + max 10 items (label/prijs).
     */
    public static function get_tarieven_destinations($post_id) {
        $stored = get_post_meta($post_id, self::META_TARIEVEN_DESTINATIONS, true);
        if (!is_array($stored)) $stored = [];
        $items_stored = isset($stored['items']) && is_array($stored['items']) ? $stored['items'] : [];
        $items = [];
        for ($i = 0; $i < self::TARIEVEN_DEST_ITEMS_MAX; $i++) {
            $it = $items_stored[$i] ?? [];
            $items[$i] = [
                'label' => isset($it['label']) ? (string) $it['label'] : '',
                'price' => isset($it['price']) ? (string) $it['price'] : '',
            ];
        }
        return [
            'title'       => isset($stored['title'])       ? (string) $stored['title']       : '',
            'description' => isset($stored['description']) ? (string) $stored['description'] : '',
            'image_id'    => isset($stored['image_id'])    ? (int)    $stored['image_id']    : 0,
            'items'       => $items,
        ];
    }

    /**
     * Defaults voor de Tarieven-pagina — realistische voorbeelddata zodat de
     * klant direct ziet hoe alles er uit ziet, en het als startpunt kan editen.
     */
    public static function tarieven_defaults() {
        $city = TaxiTheme_Company_Info::get('city') ?: 'uw regio';
        return [
            'vehicles' => [
                [
                    'title'           => 'Personenauto',
                    'description'     => 'Comfortabele wagen voor maximaal 4 personen. Ideaal voor stadsritten, luchthavenvervoer en zakelijke afspraken.',
                    'starttarief'     => '€ 4,15',
                    'kilometertarief' => '€ 3,05',
                    'tijdstarief'     => '€ 0,50 / min',
                ],
                [
                    'title'           => 'Busje',
                    'description'     => 'Ruime bus voor 5 tot 8 personen. Extra bagageruimte en comfort voor groepen.',
                    'starttarief'     => '€ 8,44',
                    'kilometertarief' => '€ 3,85',
                    'tijdstarief'     => '€ 0,57 / min',
                ],
            ],
            'destinations' => [
                'title'       => 'Luchthaven vervoer',
                'description' => 'Vaste tarieven vanaf ' . $city . ' naar de belangrijkste luchthavens.',
                'items' => [
                    ['label' => 'Schiphol (Amsterdam)', 'price' => 'Vanaf €250'],
                    ['label' => 'Rotterdam The Hague',  'price' => 'Vanaf €220'],
                    ['label' => 'Eindhoven',            'price' => 'Vanaf €240'],
                    ['label' => 'Brussel (Zaventem)',   'price' => 'Vanaf €240'],
                    ['label' => 'Antwerpen',            'price' => 'Vanaf €180'],
                    ['label' => 'Charleroi',            'price' => 'Vanaf €290'],
                ],
            ],
            'zones' => [
                'title'    => 'Lokale ritten en richtprijzen vanuit ' . $city,
                'subtitle' => 'Plan uw rit eenvoudig met onze richtprijzen. Voor een exacte prijs kunt u altijd contact opnemen.',
                'groups' => [
                    [
                        'icon'  => 'car',
                        'title' => 'Korte ritten naar nabijgelegen dorpen',
                        'rows' => [
                            ['label' => 'Koudekerke / Oost-Souburg', 'price' => '€25'],
                            ['label' => 'Arnemuiden / Sint Laurens', 'price' => '€30'],
                            ['label' => 'Grijpskerke, Kleverskerke', 'price' => '€35'],
                            ['label' => 'Ritthem',                   'price' => '€35'],
                            ['label' => 'Serooskerke of Veere',      'price' => '€35'],
                            ['label' => 'Vlissingen',                'price' => '€30'],
                        ],
                    ],
                    [
                        'icon'  => 'map-pin',
                        'title' => 'Ritten naar populaire badplaatsen',
                        'rows' => [
                            ['label' => 'Biggekerke / Dishoek',        'price' => '€40'],
                            ['label' => 'Gapinge',                     'price' => '€40'],
                            ['label' => 'Oostkapelle / Zoutelande',    'price' => '€45'],
                            ['label' => 'Aagtekerke',                  'price' => '€45'],
                            ['label' => 'Vrouwenpolder',               'price' => '€50'],
                            ['label' => 'Domburg',                     'price' => '€55'],
                            ['label' => 'Westkapelle',                 'price' => '€65'],
                        ],
                    ],
                    [
                        'icon'  => 'briefcase',
                        'title' => 'Ritten naar andere Zeeuwse steden',
                        'rows' => [
                            ['label' => 'Kamperland',        'price' => '€70'],
                            ['label' => 'Goes / Kortgene',   'price' => '€85'],
                            ['label' => 'Colijnsplaat',      'price' => '€95'],
                        ],
                    ],
                    [
                        'icon'  => 'users',
                        'title' => 'Busvervoer (5-8 personen)',
                        'rows' => [
                            ['label' => 'Toeslag boven standaardtarief', 'price' => 'op aanvraag'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Vul de tarieven-pagina met defaults uit tarieven_defaults().
     * Idempotent via post_meta flag.
     */
    public static function ensure_tarieven_seeded($post_id, $force = false) {
        if (!$force && get_post_meta($post_id, '_taxitheme_tarieven_seeded', true)) {
            return false;
        }
        $defaults = self::tarieven_defaults();

        // Vervoerstypes
        $vehicles = [];
        for ($i = 0; $i < self::TARIEVEN_VEHICLES_MAX; $i++) {
            $v = $defaults['vehicles'][$i] ?? [];
            $vehicles[$i] = [
                'title'           => $v['title']           ?? '',
                'description'     => $v['description']     ?? '',
                'image_id'        => 0,
                'starttarief'     => $v['starttarief']     ?? '',
                'kilometertarief' => $v['kilometertarief'] ?? '',
                'tijdstarief'     => $v['tijdstarief']     ?? '',
            ];
        }
        update_post_meta($post_id, self::META_TARIEVEN_VEHICLES, $vehicles);

        // Bestemmingen
        $dest_items = [];
        for ($i = 0; $i < self::TARIEVEN_DEST_ITEMS_MAX; $i++) {
            $it = $defaults['destinations']['items'][$i] ?? [];
            $dest_items[$i] = [
                'label' => $it['label'] ?? '',
                'price' => $it['price'] ?? '',
            ];
        }
        update_post_meta($post_id, self::META_TARIEVEN_DESTINATIONS, [
            'title'       => $defaults['destinations']['title'],
            'description' => $defaults['destinations']['description'],
            'image_id'    => 0,
            'items'       => $dest_items,
        ]);

        // Regionale zones
        $zone_groups = [];
        for ($i = 0; $i < self::TARIEVEN_ZONES_GROUPS_MAX; $i++) {
            $g = $defaults['zones']['groups'][$i] ?? [];
            $rows = [];
            for ($j = 0; $j < self::TARIEVEN_ZONES_ROWS_MAX; $j++) {
                $r = $g['rows'][$j] ?? [];
                $rows[$j] = [
                    'label' => $r['label'] ?? '',
                    'price' => $r['price'] ?? '',
                ];
            }
            $zone_groups[$i] = [
                'icon'  => $g['icon']  ?? '',
                'title' => $g['title'] ?? '',
                'rows'  => $rows,
            ];
        }
        update_post_meta($post_id, self::META_TARIEVEN_ZONES, [
            'title'    => $defaults['zones']['title'],
            'subtitle' => $defaults['zones']['subtitle'],
            'groups'   => $zone_groups,
        ]);

        update_post_meta($post_id, '_taxitheme_tarieven_seeded', 1);
        return true;
    }

    /**
     * Regionale zones — sectie-titel + subtitle + max 5 groepen, per groep icon + title + max 8 rows.
     */
    public static function get_tarieven_zones($post_id) {
        $stored = get_post_meta($post_id, self::META_TARIEVEN_ZONES, true);
        if (!is_array($stored)) $stored = [];
        $groups_stored = isset($stored['groups']) && is_array($stored['groups']) ? $stored['groups'] : [];
        $groups = [];
        for ($i = 0; $i < self::TARIEVEN_ZONES_GROUPS_MAX; $i++) {
            $g = $groups_stored[$i] ?? [];
            $rows_stored = isset($g['rows']) && is_array($g['rows']) ? $g['rows'] : [];
            $rows = [];
            for ($j = 0; $j < self::TARIEVEN_ZONES_ROWS_MAX; $j++) {
                $r = $rows_stored[$j] ?? [];
                $rows[$j] = [
                    'label' => isset($r['label']) ? (string) $r['label'] : '',
                    'price' => isset($r['price']) ? (string) $r['price'] : '',
                ];
            }
            $groups[$i] = [
                'icon'  => isset($g['icon'])  ? sanitize_key($g['icon']) : '',
                'title' => isset($g['title']) ? (string) $g['title'] : '',
                'rows'  => $rows,
            ];
        }
        return [
            'title'    => isset($stored['title'])    ? (string) $stored['title']    : '',
            'subtitle' => isset($stored['subtitle']) ? (string) $stored['subtitle'] : '',
            'groups'   => $groups,
        ];
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
