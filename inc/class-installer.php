<?php
if (!defined('ABSPATH')) {
    exit;
}

class TaxiTheme_Installer {

    const META_FLAG = '_taxitheme_page';
    const META_ROLE = '_taxitheme_role';
    const MENU_NAME = 'TaxiTheme Primary';

    public static function roles() {
        return [
            'home' => [
                'label'    => 'Home',
                'title'    => 'Home',
                'slug'     => 'home',
                'template' => '',
                'content'  => "",
            ],
            'diensten' => [
                'label'    => 'Diensten',
                'title'    => 'Diensten',
                'slug'     => 'diensten',
                'template' => '',
                'content'  => "<!-- wp:heading {\"level\":1} --><h1>Onze diensten</h1><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Luchthavenvervoer, zakelijk vervoer, ziekenvervoer en meer.</p><!-- /wp:paragraph -->",
            ],
            'tarieven' => [
                'label'    => 'Tarieven',
                'title'    => 'Tarieven',
                'slug'     => 'tarieven',
                'template' => '',
                'content'  => "<!-- wp:heading {\"level\":1} --><h1>Onze tarieven</h1><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Transparante prijzen, geen verrassingen achteraf.</p><!-- /wp:paragraph -->",
            ],
            'over-ons' => [
                'label'    => 'Over ons',
                'title'    => 'Over ons',
                'slug'     => 'over-ons',
                'template' => '',
                'content'  => "<!-- wp:heading {\"level\":1} --><h1>Over ons</h1><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Al jaren uw vertrouwde partner voor taxivervoer in de regio.</p><!-- /wp:paragraph -->",
            ],
            'faq' => [
                'label'    => 'FAQ',
                'title'    => 'Veelgestelde vragen',
                'slug'     => 'faq',
                'template' => '',
                'content'  => "",
            ],
            'contact' => [
                'label'    => 'Contact',
                'title'    => 'Contact',
                'slug'     => 'contact',
                'template' => '',
                'content'  => "<!-- wp:heading {\"level\":1} --><h1>Contact</h1><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Bel of mail ons voor vragen of een offerte op maat.</p><!-- /wp:paragraph -->",
            ],
            'boeken' => [
                'label'    => 'Boeken',
                'title'    => 'Boeken',
                'slug'     => 'boeken',
                'template' => 'page-boeken.php',
                'content'  => "<!-- wp:paragraph --><p>Vul hieronder uw gegevens in om direct een taxi te boeken.</p><!-- /wp:paragraph -->",
            ],
            'privacy' => [
                'label'         => 'Privacybeleid',
                'title'         => 'Privacybeleid',
                'slug'          => 'privacybeleid',
                'template'      => '',
                'menu_default'  => false, // niet in het hoofdmenu, alleen footer
                'content'       => "<!-- wp:paragraph --><p>Wij hechten belang aan uw privacy. Dit privacybeleid legt uit welke gegevens wij verzamelen wanneer u gebruikmaakt van onze taxi-diensten en hoe wij daarmee omgaan.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Welke gegevens verzamelen wij?</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Wanneer u een taxi bij ons boekt, verzamelen wij: naam, telefoonnummer, e-mailadres, ophaal- en bestemmingsadres, en rit-datum/tijd. Deze gegevens zijn nodig om de rit uit te voeren en met u te communiceren.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Waarom verzamelen wij deze gegevens?</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Uw gegevens gebruiken wij uitsluitend om uw taxirit te plannen en uit te voeren, u te bereiken bij vragen over uw rit, en om te voldoen aan wettelijke administratieve verplichtingen.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Hoe lang bewaren wij uw gegevens?</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Ritgegevens bewaren wij zolang dat wettelijk verplicht is voor de belastingdienst (7 jaar). Contactgegevens uit boekingen verwijderen wij na afronding van de rit, tenzij u ons expliciet toestemming heeft gegeven om u op de hoogte te houden.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Delen wij gegevens met derden?</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Wij delen uw gegevens uitsluitend met partijen die noodzakelijk zijn voor de uitvoering van uw rit (bv. onze chauffeur) of met derde partijen wanneer een wettelijke verplichting daartoe bestaat.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Cookies</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Onze website gebruikt functionele cookies die noodzakelijk zijn voor de werking van het boekingsformulier. Wij plaatsen geen tracking- of marketing-cookies zonder uw toestemming.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Uw rechten</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>U heeft het recht om uw gegevens in te zien, te corrigeren, te laten verwijderen of over te dragen. Neem hiervoor contact met ons op via de contactpagina.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Contact</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Vragen over ons privacybeleid? Neem contact op via de contactpagina. Wij reageren binnen 30 dagen op uw verzoek.</p><!-- /wp:paragraph -->",
            ],
            'voorwaarden' => [
                'label'         => 'Voorwaarden',
                'title'         => 'Algemene voorwaarden',
                'slug'          => 'algemene-voorwaarden',
                'template'      => '',
                'menu_default'  => false, // niet in het hoofdmenu, alleen footer
                'content'       => "<!-- wp:paragraph --><p>Deze algemene voorwaarden zijn van toepassing op alle taxi-ritten die u bij ons boekt. Door een rit te reserveren gaat u akkoord met deze voorwaarden.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Boekingen en bevestiging</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Een rit is pas definitief geboekt na onze schriftelijke bevestiging (per e-mail of sms). Voor deze bevestiging is er geen rechtsgeldige overeenkomst. Wijzigingen doorgeven kan tot 2 uur voor vertrek.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Prijzen en betaling</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Alle prijzen zijn vaste all-in prijzen, inclusief BTW en excl. eventuele parkeerkosten of tolgeld. Betaling geschiedt contant, per pin of op factuur (voor zakelijke klanten na goedkeuring).</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Annulering</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Annuleren is kosteloos tot 24 uur voor de rit. Bij annulering binnen 24 uur brengen wij 50% van de ritprijs in rekening. Bij no-show op de ophaaltijd geldt het volledige tarief.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Wachttijd</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Wij hanteren 15 minuten kosteloze wachttijd bij vluchtaankomsten en 5 minuten bij standaard-ophaladressen. Extra wachttijd wordt gefactureerd tegen het geldende uurtarief.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Bagage en huisdieren</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Standaardbagage (koffers, handbagage) is inbegrepen. Voor extra bagage, huisdieren of speciale wensen graag vooraf melden zodat wij passend vervoer kunnen inzetten.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Aansprakelijkheid</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Wij zijn verzekerd voor schade aan passagiers en bagage tijdens vervoer. Onze aansprakelijkheid is beperkt tot het bedrag dat onze verzekering uitkeert. Verlies of schade aan waardevolle voorwerpen dient u zelf te verzekeren.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Overmacht</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Bij overmacht (extreme weer, verkeersdrukte buiten onze invloedssfeer, technische storingen) doen wij ons best om alternatieven te bieden, maar aanvaarden wij geen aansprakelijkheid voor eventuele gevolgschade.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Klachten</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Klachten kunt u binnen 14 dagen na de rit melden via onze contactpagina. Wij behandelen elke klacht binnen 14 werkdagen en zoeken samen naar een passende oplossing.</p><!-- /wp:paragraph -->\n\n<!-- wp:heading --><h2>Toepasselijk recht</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Op alle overeenkomsten is Nederlands recht van toepassing. Geschillen worden voorgelegd aan de bevoegde rechter in ons vestigingsdistrict.</p><!-- /wp:paragraph -->",
            ],
        ];
    }

    public static function page_option_key($role) {
        return 'taxitheme_page_' . str_replace('-', '_', $role);
    }

    public static function get_page_id($role) {
        return (int) get_option(self::page_option_key($role), 0);
    }

    public static function page_visible_key($role) {
        return 'taxitheme_page_visible_' . str_replace('-', '_', $role);
    }

    /**
     * Zichtbaarheid in het menu. Default true, tenzij de rol menu_default=false
     * heeft (bv. privacy/voorwaarden — die horen alleen in de footer thuis).
     */
    public static function is_page_visible($role) {
        $roles = self::roles();
        $default = isset($roles[$role]['menu_default']) && $roles[$role]['menu_default'] === false ? 0 : 1;
        return (bool) get_option(self::page_visible_key($role), $default);
    }

    public static function set_page_visible($role, $visible) {
        update_option(self::page_visible_key($role), $visible ? 1 : 0);
    }

    public static function page_active_key($role) {
        return 'taxitheme_page_active_' . str_replace('-', '_', $role);
    }

    /**
     * Of een TaxiTheme-page actief hoort te zijn. Home blijft altijd actief.
     */
    public static function is_page_active($role) {
        if ($role === 'home') return true;
        return (bool) get_option(self::page_active_key($role), 1);
    }

    /**
     * Deactiveer een page zonder data te verwijderen.
     * De page gaat naar concept en verdwijnt uit het menu.
     */
    public static function deactivate_page($role) {
        $roles = self::roles();
        if ($role === 'home' || !isset($roles[$role])) return false;

        $page_id = self::get_page_id($role);
        $post    = $page_id ? get_post($page_id) : null;
        if (!$post || $post->post_status === 'trash') return false;

        $result = wp_update_post([
            'ID'          => $page_id,
            'post_status' => 'draft',
        ], true);
        if (is_wp_error($result)) return false;

        update_option(self::page_active_key($role), 0);
        self::set_page_visible($role, false);
        return true;
    }

    /**
     * Activeer een eerder gedeactiveerde page opnieuw.
     */
    public static function activate_page($role) {
        $roles = self::roles();
        if ($role === 'home' || !isset($roles[$role])) return false;

        $page_id = self::get_page_id($role);
        $post    = $page_id ? get_post($page_id) : null;
        if (!$post || $post->post_status === 'trash') return false;

        $result = wp_update_post([
            'ID'          => $page_id,
            'post_status' => 'publish',
        ], true);
        if (is_wp_error($result)) return false;

        update_option(self::page_active_key($role), 1);
        self::set_page_visible($role, true);
        return true;
    }

    /**
     * Trash de page van een rol. Optie/meta blijft — page kan later gerecreated worden.
     */
    public static function trash_page($role) {
        $page_id = self::get_page_id($role);
        if (!$page_id) return false;
        return (bool) wp_trash_post($page_id);
    }

    /**
     * (Her)maak de page voor een rol als 'ie niet bestaat of getrashed is.
     * Herbouwt daarna het menu zodat 'ie erin komt.
     */
    public static function recreate_page($role) {
        $roles = self::roles();
        if (!isset($roles[$role])) return 0;

        // Bestaat de page nog en is 'ie niet getrashed? Niks doen.
        $existing = self::get_page_id($role);
        if ($existing) {
            $post = get_post($existing);
            if ($post && $post->post_status !== 'trash') {
                return $existing;
            }
            // Getrashed: haal terug
            if ($post && $post->post_status === 'trash') {
                wp_untrash_post($existing);
                wp_update_post(['ID' => $existing, 'post_status' => 'publish']);
                update_option(self::page_active_key($role), 1);
                $default_visible = !(isset($roles[$role]['menu_default']) && $roles[$role]['menu_default'] === false);
                self::set_page_visible($role, $default_visible);
                self::rebuild_menu();
                return $existing;
            }
        }

        // Nieuw aanmaken
        $data = $roles[$role];
        $post_id = wp_insert_post([
            'post_title'   => $data['title'],
            'post_name'    => self::unique_slug($data['slug']),
            'post_content' => $data['content'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
        if (is_wp_error($post_id) || !$post_id) return 0;

        update_post_meta($post_id, self::META_FLAG, 1);
        update_post_meta($post_id, self::META_ROLE, $role);
        if (!empty($data['template'])) {
            update_post_meta($post_id, '_wp_page_template', $data['template']);
        }
        update_option(self::page_option_key($role), $post_id);
        update_option(self::page_active_key($role), 1);
        // Menu-zichtbaarheid volgt de rol-config (default true, tenzij menu_default=false)
        $default_visible = !(isset($data['menu_default']) && $data['menu_default'] === false);
        self::set_page_visible($role, $default_visible);

        // Role-specifieke default content seeden (idem als create_pages)
        if ($role === 'over-ons' && class_exists('TaxiTheme_Page_Meta')) {
            TaxiTheme_Page_Meta::ensure_over_ons_seeded($post_id);
        }

        self::rebuild_menu();
        return $post_id;
    }

    /**
     * Herbouw het menu met de huidige pages die zichtbaar zijn.
     */
    public static function rebuild_menu() {
        $ids = [];
        foreach (array_keys(self::roles()) as $role) {
            $ids[$role] = self::get_page_id($role);
        }
        self::create_menu($ids);
    }

    /**
     * Full install: backup current settings, create pages, tag them, build menu,
     * set homepage, mark completed. Returns array of created role => page_id.
     */
    public static function install() {
        self::backup_previous_settings();
        $ids = self::create_pages();
        self::set_homepage($ids['home'] ?? 0);
        self::create_menu($ids);
        TaxiTheme_Setup::mark_completed();
        return $ids;
    }

    const OPTION_PAUSED = 'taxitheme_paused';

    public static function is_paused() {
        return (bool) get_option(self::OPTION_PAUSED, false);
    }

    /**
     * Pause TaxiTheme: oude homepage + primary menu terug, pages blijven staan.
     * Reversible via unpause() — geen data-verlies.
     */
    public static function pause() {
        self::restore_previous_settings();

        // Primary menu-locatie terug naar wat het was.
        $prev_menu = (int) get_option('taxitheme_prev_menu_id', 0);
        $locations = get_theme_mod('nav_menu_locations', []);
        if ($prev_menu > 0) {
            $locations['primary'] = $prev_menu;
        } else {
            unset($locations['primary']);
        }
        set_theme_mod('nav_menu_locations', $locations);

        update_option(self::OPTION_PAUSED, 1);
    }

    /**
     * Reactiveer TaxiTheme (na pause). Homepage + menu terug in bedrijf.
     */
    public static function unpause() {
        delete_option(self::OPTION_PAUSED);
        self::reapply();
    }

    /**
     * Re-apply our config without recreating pages (used on theme reactivation).
     */
    public static function reapply() {
        self::unhide_pages();

        $home_id = self::get_page_id('home');
        if ($home_id) {
            self::set_homepage($home_id);
        }
        $ids = [];
        foreach (array_keys(self::roles()) as $role) {
            $ids[$role] = self::get_page_id($role);
        }
        self::create_menu($ids);
    }

    /**
     * Zet onze published pages op draft — zodat ze niet lekken in fallback-menu's
     * van andere thema's als TaxiTheme is gedeactiveerd. Trashed pages blijven trashed.
     */
    public static function hide_pages() {
        foreach (array_keys(self::roles()) as $role) {
            $page_id = self::get_page_id($role);
            if (!$page_id) continue;
            $post = get_post($page_id);
            if ($post && $post->post_status === 'publish') {
                wp_update_post([
                    'ID'          => $page_id,
                    'post_status' => 'draft',
                ]);
            }
        }
    }

    /**
     * Zet onze draft pages terug op publish (bij reactivatie).
     */
    public static function unhide_pages() {
        foreach (array_keys(self::roles()) as $role) {
            $page_id = self::get_page_id($role);
            if (!$page_id) continue;
            $post = get_post($page_id);
            if ($post && $post->post_status === 'draft' && self::is_page_active($role)) {
                wp_update_post([
                    'ID'          => $page_id,
                    'post_status' => 'publish',
                ]);
            }
        }
    }

    /**
     * Restore the settings we overwrote during install (used on theme deactivation).
     * Defensive: als de opgeslagen page niet meer bestaat, fallback naar 'posts'
     * zodat je geen 404 op de voorpagina krijgt.
     */
    public static function restore_previous_settings() {
        $prev_page = get_option('taxitheme_prev_page_on_front', null);
        $prev_show = get_option('taxitheme_prev_show_on_front', null);

        $prev_page_id = (int) $prev_page;
        $page_exists = false;
        if ($prev_page_id > 0) {
            $post = get_post($prev_page_id);
            $page_exists = $post && !in_array($post->post_status, ['trash', 'auto-draft'], true);
        }

        if ($prev_show === 'page' && $page_exists) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $prev_page_id);
        } else {
            update_option('show_on_front', 'posts');
            update_option('page_on_front', 0);
        }
    }

    private static function backup_previous_settings() {
        if (get_option('taxitheme_prev_page_on_front', null) === null) {
            update_option('taxitheme_prev_page_on_front', (int) get_option('page_on_front', 0));
        }
        if (get_option('taxitheme_prev_show_on_front', null) === null) {
            update_option('taxitheme_prev_show_on_front', get_option('show_on_front', 'posts'));
        }
        if (get_option('taxitheme_prev_menu_id', null) === null) {
            $locations = get_theme_mod('nav_menu_locations', []);
            update_option('taxitheme_prev_menu_id', (int) ($locations['primary'] ?? 0));
        }
    }

    /**
     * Full uninstall: restore original site state.
     *   - Restore homepage + show_on_front
     *   - Trash TaxiTheme pages (recoverable, klant kan restoren)
     *   - Delete TaxiTheme menu
     *   - Delete all taxitheme_page_* + setup + backup options
     *   - Company info: alleen verwijderen als $delete_company = true
     */
    public static function uninstall($delete_company = false) {
        self::restore_previous_settings();

        foreach (array_keys(self::roles()) as $role) {
            $page_id = self::get_page_id($role);
            if ($page_id) {
                wp_trash_post($page_id);
            }
            delete_option(self::page_option_key($role));
            delete_option(self::page_visible_key($role));
            delete_option(self::page_active_key($role));
        }

        $menu = wp_get_nav_menu_object(self::MENU_NAME);
        if ($menu) {
            wp_delete_nav_menu($menu->term_id);
        }

        delete_option('taxitheme_setup_completed');
        delete_option('taxitheme_prev_page_on_front');
        delete_option('taxitheme_prev_show_on_front');
        delete_option('taxitheme_prev_menu_id');
        delete_option(self::OPTION_PAUSED);

        if ($delete_company) {
            foreach (array_keys(TaxiTheme_Company_Info::FIELDS) as $field) {
                delete_option(TaxiTheme_Company_Info::option_key($field));
            }
            delete_option(TaxiTheme_Company_Info::option_key('logo_id'));
        }
    }

    private static function create_pages() {
        $ids = [];
        foreach (self::roles() as $role => $data) {
            $post_id = wp_insert_post([
                'post_title'   => $data['title'],
                'post_name'    => self::unique_slug($data['slug']),
                'post_content' => $data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);

            if (is_wp_error($post_id) || !$post_id) {
                continue;
            }

            update_post_meta($post_id, self::META_FLAG, 1);
            update_post_meta($post_id, self::META_ROLE, $role);

            if (!empty($data['template'])) {
                update_post_meta($post_id, '_wp_page_template', $data['template']);
            }

            update_option(self::page_option_key($role), $post_id);
            update_option(self::page_active_key($role), 1);
            $default_visible = !(isset($data['menu_default']) && $data['menu_default'] === false);
            self::set_page_visible($role, $default_visible);
            $ids[$role] = $post_id;

            // Role-specifieke default content seeden
            if ($role === 'over-ons' && class_exists('TaxiTheme_Page_Meta')) {
                TaxiTheme_Page_Meta::ensure_over_ons_seeded($post_id);
            }
        }
        return $ids;
    }

    private static function unique_slug($base) {
        $slug = $base;
        $i = 2;
        while (get_page_by_path($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private static function set_homepage($home_id) {
        if (!$home_id) return;
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_id);
    }

    private static function create_menu($ids) {
        $menu = wp_get_nav_menu_object(self::MENU_NAME);
        if ($menu) {
            $items = wp_get_nav_menu_items($menu->term_id);
            if ($items) {
                foreach ($items as $item) {
                    wp_delete_post($item->ID, true);
                }
            }
            $menu_id = $menu->term_id;
        } else {
            $menu_id = wp_create_nav_menu(self::MENU_NAME);
        }

        $order = ['home', 'diensten', 'tarieven', 'over-ons', 'faq', 'contact', 'boeken'];
        foreach ($order as $role) {
            if (empty($ids[$role])) continue;
            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title'     => get_the_title($ids[$role]),
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $ids[$role],
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
            ]);
        }

        $locations = get_theme_mod('nav_menu_locations', []);
        $locations['primary'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }
}
