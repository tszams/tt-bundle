<?php
if (!defined('ABSPATH')) {
    exit;
}

class TaxiTheme_Home_Content {

    const OPTION_KEY = 'taxitheme_home_content';

    const HERO_STYLES = ['split', 'stacked', 'centered'];

    // Reorderable secties tussen hero (top) en contact-cta (bottom).
    // Elke key komt overeen met een template_part in templates/shared/ of preset-specifiek.
    const REORDERABLE_SECTIONS = [
        'trust'        => 'Trust bar',
        'usps'         => 'USPs',
        'services'     => 'Diensten',
        'features'     => 'Features',
        'spotlight'    => 'Spotlight',
        'steps'        => 'Hoe werkt het',
        'about'        => 'Over ons',
        'fleet'        => 'Wagenpark',
        'waarom'       => 'Waarom rijden met ons',
        'routes'       => 'Vaste routes',
        'service-area' => 'Regio',
        'faq'          => 'FAQ',
        'reviews'      => 'Reviews',
        'post-content' => 'Eigen tekst',
    ];

    public static function defaults() {
        return [
            // Standaard sectie-volgorde (tussen hero en contact-cta). Klant kan
            // dit per-preset overriden via de home-editor met up/down knoppen.
            'section_order'         => array_keys(self::REORDERABLE_SECTIONS),
            'section_backgrounds'   => array_fill_keys(
                ['klassiek', 'bold', 'onepage', 'premium', 'simpel'],
                array_fill_keys(array_keys(self::REORDERABLE_SECTIONS), 'auto')
            ),

            'hero_style'            => 'split',
            'hero_accent_enabled'   => 0,
            'hero_eyebrow_enabled'  => 1,
            'hero_eyebrow'          => '',
            'hero_title_enabled'    => 1,
            'hero_title'            => '', // leeg = fallback naar bedrijfsnaam
            'hero_subtitle_enabled' => 1,
            'hero_subtitle'         => '', // leeg = geen ondertitel tonen
            'hero_phone_enabled'    => 1,
            'hero_whatsapp_enabled' => 0,
            'hero_whatsapp_number'  => '', // leeg = fallback naar bedrijfs-telefoonnummer
            'hero_image_id'         => 0,

            // Simpel-preset specifiek
            'hero_simpel_style'     => 'image',  // 'image' | 'form'
            'hero_simpel_image_id'  => 0,

            // Premium-preset specifiek
            'hero_premium_style'    => 'image',  // 'image' | 'form'

            // Bold-preset specifiek
            'hero_bold_style'       => 'fullscreen',  // 'fullscreen' | 'form'

            'hero_bullets_enabled'  => 0,
            'hero_bullets'          => [
                '24/7 beschikbaar',
                'Vaste, transparante prijzen',
                'Ervaren chauffeurs',
                '',
                '',
            ],

            'hero_tags_enabled'     => 0,
            'hero_tags'             => [
                '★ 4.9 · 1.200+ ritten',
                '24/7 beschikbaar',
                'Vluchttracking',
                '',
                '',
            ],

            'trust_enabled'     => 0,
            'trust_items'       => [
                ['icon' => 'star',    'text' => '4.9 op Google'],
                ['icon' => 'shield',  'text' => 'KvK geregistreerd · TX-keurmerk'],
                ['icon' => 'check',   'text' => 'Veilig betalen · iDEAL & creditcard'],
                ['icon' => 'users',   'text' => '1.200+ ritten per jaar'],
            ],

            'usps_enabled'      => 1,
            'usps' => [
                ['icon' => 'clock', 'title' => 'Altijd op tijd',
                 'text' => 'Wij plannen ruim en zijn er wanneer wij het beloofd hebben — geen gedoe, geen wachten.'],
                ['icon' => 'euro',  'title' => 'Vaste prijzen',
                 'text' => 'Weet vooraf wat je betaalt. Transparante tarieven, geen verrassingen achteraf.'],
                ['icon' => 'star',  'title' => 'Vriendelijke chauffeurs',
                 'text' => 'Nette, ervaren chauffeurs die de weg kennen en jou veilig op bestemming brengen.'],
            ],

            'about_enabled'     => 0,
            'about_title'       => 'Over ons',
            'about_subtitle'    => '',
            'about_text'        => "Al meer dan 20 jaar rijden wij passagiers door de Randstad. Wat begon als één auto is inmiddels een team van professionele chauffeurs die de weg kennen als hun broekzak.\n\nWij geloven in vaste prijzen, nette wagens en op tijd komen. Geen verrassingen achteraf, geen gedoe — gewoon een betrouwbare rit van A naar B.",
            'about_image_id'    => 0,
            'about_cta_url'     => '',
            'about_cta_label'   => '',

            'spotlight_enabled'    => 0,
            'spotlight_style'      => 'subtle',  // 'subtle' | 'promo'
            'spotlight_eyebrow'    => 'Voor reizigers',
            'spotlight_icon'       => 'car',
            'spotlight_title'      => 'Schiphol Airport Taxi',
            'spotlight_text'       => 'Boek een taxi van of naar Schiphol Airport met een vaste prijs vooraf. Direct online boeken — je kiest zelf datum en tijd.',
            'spotlight_discount'   => '',   // bijv. "10%" of "€5 korting" — alleen bij promo variant
            'spotlight_link_url'   => '',
            'spotlight_link_label' => 'Meer over Taxi Schiphol Airport',

            'steps_enabled'     => 0,
            'steps_title'       => 'Hoe werkt het',
            'steps_subtitle'    => '',
            'steps_items'       => [
                ['title' => 'Kies je rit',        'text' => 'Voer je adres en bestemming in en kies de gewenste auto.'],
                ['title' => 'Vul in en boek',      'text' => 'Vul passagiersgegevens in, kies extra opties en betaal.'],
                ['title' => 'Ontmoet je chauffeur','text' => 'Je ontvangt de chauffeur-gegevens ruim vóór pickup, met naambord bij ontvangst.'],
            ],

            'features_enabled'  => 0,
            'features_items'    => [
                ['eyebrow' => 'Snelheid', 'title' => 'Altijd een taxi in de buurt', 'text' => 'Onze taxi\'s rijden door heel Amsterdam. Binnen enkele minuten staat jouw taxi voor de deur, klaar om je snel en veilig te vervoeren.', 'image_id' => 0, 'link_url' => '', 'link_label' => ''],
                ['eyebrow' => 'Comfort',  'title' => 'Comfortabele taxi\'s en professionele chauffeurs', 'text' => 'Stap in een nette, comfortabele taxi met een ervaren chauffeur. Wij zorgen voor een aangename rit van begin tot eind.', 'image_id' => 0, 'link_url' => '', 'link_label' => ''],
                ['eyebrow' => 'Betrouwbaar', 'title' => 'Op tijd, elke rit', 'text' => 'Onze chauffeurs zorgen voor een tijdige en comfortabele rit — elke keer weer.', 'image_id' => 0, 'link_url' => '', 'link_label' => ''],
            ],

            'services_enabled'      => 0,
            'services_title'        => 'Onze diensten',
            'services_subtitle'     => 'Comfortabel vervoer voor elk type rit.',
            'services_link_enabled' => 1, // "Lees meer." knop op Simpel-cards (naar Diensten-pagina)
            'services_items'    => [
                ['icon' => 'car',     'title' => 'Schiphol Airport Taxi',
                 'text' => 'Stress-vrij van en naar Schiphol. Wij volgen uw vlucht, staan klaar bij aankomst en helpen met uw bagage.',
                 'link_url' => '', 'link_label' => 'Meer over Schiphol-taxi',
                 'home_image_id' => 0,
                 'image_id' => 0, 'long_text' => '', 'features' => '', 'price' => ''],
                ['icon' => 'map-pin', 'title' => 'Taxi in de stad',
                 'text' => 'Ritten door de hele stad. Van Centraal Station tot alle wijken.',
                 'link_url' => '', 'link_label' => 'Meer over stadstaxi',
                 'home_image_id' => 0,
                 'image_id' => 0, 'long_text' => '', 'features' => '', 'price' => ''],
                ['icon' => 'target',  'title' => 'Ritten in de regio',
                 'text' => 'Wij rijden naar omliggende gemeenten en steden. Snel, betrouwbaar, vaste prijzen.',
                 'link_url' => '', 'link_label' => '',
                 'home_image_id' => 0,
                 'image_id' => 0, 'long_text' => '', 'features' => '', 'price' => ''],
            ],

            'waarom_enabled'    => 1,
            'waarom_title'      => 'Waarom rijden met ons?',
            'waarom_subtitle'   => 'Al jaren de eerste keuze voor particulieren en bedrijven.',
            'waarom_image_id'   => 0,
            'waarom_items'      => [
                'Vaste, transparante tarieven',
                'Ervaren chauffeurs met kennis van de regio',
                'Nette, comfortabele wagens',
                'Betrouwbaar 24/7 bereikbaar',
                'Zakelijk, luchthaven- en ziekenvervoer',
                'Direct online boeken, geen wachttijd aan de telefoon',
            ],

            'service_area_enabled'  => 0,
            'service_area_title'    => 'Ons service gebied',
            'service_area_subtitle' => 'Wij rijden in en tussen deze plaatsen.',
            'service_area_items'    => [
                'Amsterdam', 'Amstelveen', 'Haarlem', 'Schiphol',
                'Utrecht', 'Almere', '', '',
                '', '', '', '',
                '', '', '', '',
            ],

            'routes_enabled'    => 0,
            'routes_title'      => 'Vaste route prijzen',
            'routes_subtitle'   => 'Transparante tarieven voor populaire ritten.',
            'routes_items'      => [
                ['from' => 'Amsterdam',         'to' => 'Schiphol', 'price' => '55'],
                ['from' => 'Amsterdam Centrum', 'to' => 'Schiphol', 'price' => '50'],
                ['from' => 'Amsterdam Zuid',    'to' => 'Schiphol', 'price' => '45'],
                ['from' => 'Amstelveen',        'to' => 'Schiphol', 'price' => '40'],
                ['from' => '',                  'to' => '',         'price' => ''],
                ['from' => '',                  'to' => '',         'price' => ''],
                ['from' => '',                  'to' => '',         'price' => ''],
                ['from' => '',                  'to' => '',         'price' => ''],
                ['from' => '',                  'to' => '',         'price' => ''],
                ['from' => '',                  'to' => '',         'price' => ''],
                ['from' => '',                  'to' => '',         'price' => ''],
                ['from' => '',                  'to' => '',         'price' => ''],
            ],

            'faq_enabled'       => 0,
            'faq_title'         => 'Veelgestelde vragen',
            'faq_subtitle'      => '',
            'faq_home_limit'    => 5,
            'faq_items'         => [
                ['question' => 'Hoe kan ik een taxi boeken?', 'answer' => 'Vul het boekingsformulier in of bel ons direct. Je krijgt binnen een minuut bevestiging.'],
                ['question' => 'Rijden jullie ook naar Schiphol?', 'answer' => 'Ja, wij rijden 24/7 naar en van Schiphol tegen een vaste, transparante prijs.'],
                ['question' => 'Hoe kan ik betalen?', 'answer' => 'Contant, pin of via factuur (voor zakelijke klanten). Betalen in de auto of vooraf online is beide mogelijk.'],
                ['question' => 'Krijg ik een bewijs van de rit?', 'answer' => 'Ja, je ontvangt automatisch een bevestigingsmail met alle details en na afloop een factuur indien gewenst.'],
                ['question' => 'Wat als ik de rit wil annuleren?', 'answer' => 'Annuleren is gratis tot 24 uur voor de rit. Binnen 24 uur kunnen kosten van toepassing zijn.'],
            ],
            // Extra vragen die alleen op de aparte FAQ-pagina verschijnen —
            // dus NIET op de homepage FAQ preview. Beheerd via FAQ-page editor.
            // 15 slots → samen met de 5 home-items geeft dat max 20 op de FAQ-pagina.
            'faq_page_items'    => array_fill(0, 15, ['question' => '', 'answer' => '']),

            'contact_enabled'   => 1,
            'contact_title'     => 'Direct contact?',
            'contact_subtitle'  => 'Bel of mail ons voor vragen of een offerte op maat.',

            'fleet_enabled'     => 0,
            'fleet_title'       => 'Ons Wagenpark',
            'fleet_subtitle'    => 'Reizen in stijl en comfort met onze vloot.',
            'fleet_items'       => [
                ['image_id' => 0, 'tag' => 'Mercedes E-Klasse',     'title' => '',                    'subtitle' => ''],
                ['image_id' => 0, 'tag' => 'Mercedes V-Klasse Bus', 'title' => '',                    'subtitle' => ''],
                ['image_id' => 0, 'tag' => 'Luxe interieur',        'title' => '',                    'subtitle' => ''],
                ['image_id' => 0, 'tag' => 'Premium comfort',       'title' => '',                    'subtitle' => ''],
                ['image_id' => 0, 'tag' => '',                      'title' => 'Mercedes-Benz vloot', 'subtitle' => 'Comfort, veiligheid en luxe voor elke rit'],
            ],

            'reviews_enabled'    => 0,
            'reviews_title'      => 'Wat onze klanten zeggen',
            'reviews_subtitle'   => 'Reviews van passagiers die vaker met ons meerijden.',
            'reviews_google_url' => '',
            'reviews_cta_label'  => 'Bekijk alle reviews op Google',
            'reviews_items'      => [
                ['name' => 'Sander K.',     'rating' => 5, 'text' => 'Op tijd, nette auto en vriendelijke chauffeur. Al mijn Schiphol-ritten boek ik hier.', 'date' => ''],
                ['name' => 'Fatima B.',     'rating' => 5, 'text' => 'Vaste prijs vooraf en betrouwbaar. Precies wat je wil bij een taxirit met kinderen.',   'date' => ''],
                ['name' => 'Marco de Vries','rating' => 5, 'text' => 'Zakelijk gebruik ik ze al jaren. Nooit gedoe, altijd goed geregeld.',                 'date' => ''],
                ['name' => '',              'rating' => 5, 'text' => '', 'date' => ''],
                ['name' => '',              'rating' => 5, 'text' => '', 'date' => ''],
                ['name' => '',              'rating' => 5, 'text' => '', 'date' => ''],
            ],
        ];
    }

    public static function all() {
        $stored = get_option(self::OPTION_KEY, []);
        if (!is_array($stored)) $stored = [];
        return array_replace_recursive(self::defaults(), $stored);
    }

    public static function save(array $input) {
        update_option(self::OPTION_KEY, self::sanitize($input));
    }

    public static function reset() {
        delete_option(self::OPTION_KEY);
    }

    private static function sanitize(array $input) {
        $defaults = self::defaults();
        $out = [];

        // Section order: alleen bekende keys behouden, dedupe, ontbrekende
        // keys achteraan toevoegen zodat er niets stil verdwijnt.
        $valid_keys  = array_keys(self::REORDERABLE_SECTIONS);
        $order_in    = isset($input['section_order']) && is_array($input['section_order'])
            ? array_map('sanitize_key', $input['section_order'])
            : [];
        $order_clean = array_values(array_unique(array_intersect($order_in, $valid_keys)));
        foreach ($valid_keys as $k) {
            if (!in_array($k, $order_clean, true)) $order_clean[] = $k;
        }
        $out['section_order'] = $order_clean;

        // Achtergrondstijl per homepagecomponent en preset. Alleen semantische
        // keys opslaan, zodat kleuren later veilig via preset-CSS kunnen wisselen.
        $background_choices = [
            'klassiek' => ['auto', 'base', 'surface', 'dark', 'accent'],
            'bold'     => ['auto', 'base', 'alternate', 'light', 'white', 'accent'],
            'onepage'  => ['auto', 'base', 'surface', 'accent'],
            'premium'  => ['auto', 'base', 'surface', 'dark', 'accent'],
            'simpel'   => ['auto', 'base', 'surface', 'contrast', 'accent'],
        ];
        $out['section_backgrounds'] = [];
        foreach ($background_choices as $preset_key => $allowed_choices) {
            $background_input = isset($input['section_backgrounds'][$preset_key]) && is_array($input['section_backgrounds'][$preset_key])
                ? $input['section_backgrounds'][$preset_key]
                : [];
            $out['section_backgrounds'][$preset_key] = [];
            foreach ($valid_keys as $key) {
                $choice = sanitize_key($background_input[$key] ?? 'auto');
                $out['section_backgrounds'][$preset_key][$key] = in_array($choice, $allowed_choices, true)
                    ? $choice
                    : 'auto';
            }
        }

        $hero_style = sanitize_text_field($input['hero_style'] ?? '');
        $out['hero_style']            = in_array($hero_style, self::HERO_STYLES, true) ? $hero_style : 'split';
        $out['hero_accent_enabled']   = !empty($input['hero_accent_enabled']) ? 1 : 0;
        $out['hero_eyebrow_enabled']  = !empty($input['hero_eyebrow_enabled']) ? 1 : 0;
        $out['hero_eyebrow']          = isset($input['hero_eyebrow']) ? sanitize_text_field($input['hero_eyebrow']) : '';
        $out['hero_title_enabled']    = !empty($input['hero_title_enabled']) ? 1 : 0;
        $out['hero_title']            = isset($input['hero_title']) ? sanitize_text_field($input['hero_title']) : '';
        $out['hero_subtitle_enabled'] = !empty($input['hero_subtitle_enabled']) ? 1 : 0;
        $out['hero_subtitle']         = isset($input['hero_subtitle']) ? sanitize_text_field($input['hero_subtitle']) : '';
        $out['hero_phone_enabled']    = !empty($input['hero_phone_enabled']) ? 1 : 0;
        $out['hero_whatsapp_enabled'] = !empty($input['hero_whatsapp_enabled']) ? 1 : 0;
        $out['hero_whatsapp_number']  = isset($input['hero_whatsapp_number']) ? sanitize_text_field($input['hero_whatsapp_number']) : '';
        $out['hero_image_id']         = (int) ($input['hero_image_id'] ?? 0);

        $hero_simpel_style            = sanitize_text_field($input['hero_simpel_style'] ?? '');
        $out['hero_simpel_style']     = in_array($hero_simpel_style, ['image', 'form'], true) ? $hero_simpel_style : 'image';
        $out['hero_simpel_image_id']  = (int) ($input['hero_simpel_image_id'] ?? 0);

        $hero_premium_style           = sanitize_text_field($input['hero_premium_style'] ?? '');
        $out['hero_premium_style']    = in_array($hero_premium_style, ['image', 'form'], true) ? $hero_premium_style : 'image';

        $hero_bold_style              = sanitize_text_field($input['hero_bold_style'] ?? '');
        $out['hero_bold_style']       = in_array($hero_bold_style, ['fullscreen', 'form'], true) ? $hero_bold_style : 'fullscreen';

        $out['hero_bullets_enabled']  = !empty($input['hero_bullets_enabled']) ? 1 : 0;
        $out['hero_bullets']          = [];
        $bullets = $input['hero_bullets'] ?? [];
        for ($i = 0; $i < 5; $i++) {
            $val = isset($bullets[$i]) ? trim(sanitize_text_field($bullets[$i])) : '';
            $out['hero_bullets'][$i] = $val;
        }

        $out['hero_tags_enabled']     = !empty($input['hero_tags_enabled']) ? 1 : 0;
        $out['hero_tags']             = [];
        $tags = $input['hero_tags'] ?? [];
        for ($i = 0; $i < 5; $i++) {
            $val = isset($tags[$i]) ? trim(sanitize_text_field($tags[$i])) : '';
            $out['hero_tags'][$i] = $val;
        }

        $out['trust_enabled'] = !empty($input['trust_enabled']) ? 1 : 0;
        $out['trust_items']   = [];
        $trust_valid_icons    = TaxiTheme_Icons::usp_choices();
        $trust_in             = $input['trust_items'] ?? [];
        for ($i = 0; $i < 4; $i++) {
            $t    = $trust_in[$i] ?? [];
            $icon = sanitize_text_field($t['icon'] ?? '');
            if (!in_array($icon, $trust_valid_icons, true)) {
                $icon = $defaults['trust_items'][$i]['icon'] ?? 'star';
            }
            $out['trust_items'][$i] = [
                'icon' => $icon,
                'text' => sanitize_text_field($t['text'] ?? ''),
            ];
        }

        $out['usps_enabled']     = !empty($input['usps_enabled']) ? 1 : 0;
        $out['usps'] = [];
        $valid_icons = TaxiTheme_Icons::usp_choices();
        for ($i = 0; $i < 3; $i++) {
            $u = $input['usps'][$i] ?? [];
            $icon = sanitize_text_field($u['icon'] ?? '');
            if (!in_array($icon, $valid_icons, true)) {
                $icon = $defaults['usps'][$i]['icon'];
            }
            $out['usps'][$i] = [
                'icon'  => $icon,
                'title' => sanitize_text_field($u['title'] ?? ''),
                'text'  => sanitize_textarea_field($u['text']  ?? ''),
            ];
        }

        $out['about_enabled']    = !empty($input['about_enabled']) ? 1 : 0;
        $out['about_title']      = sanitize_text_field($input['about_title']       ?? '');
        $out['about_subtitle']   = sanitize_text_field($input['about_subtitle']    ?? '');
        $out['about_text']       = sanitize_textarea_field($input['about_text']    ?? '');
        $out['about_image_id']   = (int) ($input['about_image_id']                 ?? 0);
        $out['about_cta_url']    = esc_url_raw($input['about_cta_url']             ?? '');
        $out['about_cta_label']  = sanitize_text_field($input['about_cta_label']   ?? '');

        $out['spotlight_enabled']    = !empty($input['spotlight_enabled']) ? 1 : 0;
        $spot_style                  = sanitize_text_field($input['spotlight_style']    ?? '');
        $out['spotlight_style']      = in_array($spot_style, ['subtle', 'promo'], true) ? $spot_style : 'subtle';
        $out['spotlight_eyebrow']    = sanitize_text_field($input['spotlight_eyebrow']  ?? '');
        $spot_icon                   = sanitize_text_field($input['spotlight_icon']     ?? '');
        $out['spotlight_icon']       = in_array($spot_icon, TaxiTheme_Icons::usp_choices(), true) ? $spot_icon : 'car';
        $out['spotlight_title']      = sanitize_text_field($input['spotlight_title']    ?? '');
        $out['spotlight_text']       = sanitize_textarea_field($input['spotlight_text'] ?? '');
        $out['spotlight_discount']   = sanitize_text_field($input['spotlight_discount'] ?? '');
        $out['spotlight_link_url']   = esc_url_raw($input['spotlight_link_url']         ?? '');
        $out['spotlight_link_label'] = sanitize_text_field($input['spotlight_link_label'] ?? '');

        $out['steps_enabled']  = !empty($input['steps_enabled']) ? 1 : 0;
        $out['steps_title']    = sanitize_text_field($input['steps_title']    ?? '');
        $out['steps_subtitle'] = sanitize_text_field($input['steps_subtitle'] ?? '');
        $out['steps_items']    = [];
        $steps_in = $input['steps_items'] ?? [];
        for ($i = 0; $i < 3; $i++) {
            $s = $steps_in[$i] ?? [];
            $out['steps_items'][$i] = [
                'title' => sanitize_text_field($s['title']    ?? ''),
                'text'  => sanitize_textarea_field($s['text'] ?? ''),
            ];
        }

        $out['features_enabled']  = !empty($input['features_enabled']) ? 1 : 0;
        $out['features_items']    = [];
        $feat_in = $input['features_items'] ?? [];
        for ($i = 0; $i < 3; $i++) {
            $f = $feat_in[$i] ?? [];
            $out['features_items'][$i] = [
                'eyebrow'    => sanitize_text_field($f['eyebrow']    ?? ''),
                'title'      => sanitize_text_field($f['title']      ?? ''),
                'text'       => sanitize_textarea_field($f['text']   ?? ''),
                'image_id'   => (int) ($f['image_id']                ?? 0),
                'link_url'   => esc_url_raw($f['link_url']           ?? ''),
                'link_label' => sanitize_text_field($f['link_label'] ?? ''),
            ];
        }

        $out['services_enabled']       = !empty($input['services_enabled']) ? 1 : 0;
        $out['services_title']         = sanitize_text_field($input['services_title']    ?? '');
        $out['services_subtitle']      = sanitize_text_field($input['services_subtitle'] ?? '');
        $out['services_link_enabled']  = !empty($input['services_link_enabled']) ? 1 : 0;
        $out['services_items']    = [];
        $svc_in = $input['services_items'] ?? [];
        for ($i = 0; $i < 6; $i++) {
            $s = $svc_in[$i] ?? [];
            $icon = sanitize_text_field($s['icon'] ?? '');
            if (!in_array($icon, $valid_icons, true)) {
                // Voor slots > default array-lengte: fallback op eerste default icon
                $icon = $defaults['services_items'][$i]['icon'] ?? ($defaults['services_items'][0]['icon'] ?? 'car');
            }
            $out['services_items'][$i] = [
                'icon'          => $icon,
                'title'         => sanitize_text_field($s['title']      ?? ''),
                'text'          => sanitize_textarea_field($s['text']   ?? ''),
                'link_url'      => esc_url_raw($s['link_url']           ?? ''),
                'link_label'    => sanitize_text_field($s['link_label'] ?? ''),
                // Afbeelding voor Simpel-homepage cards (aparte upload op homepage-editor)
                'home_image_id' => (int) ($s['home_image_id']           ?? 0),
                // Detail-velden — alleen zichtbaar op de Diensten-pagina
                'image_id'      => (int) ($s['image_id']                ?? 0),
                'long_text'     => sanitize_textarea_field($s['long_text'] ?? ''),
                'features'      => sanitize_textarea_field($s['features']  ?? ''),
                'price'         => sanitize_text_field($s['price']      ?? ''),
            ];
        }

        $out['waarom_enabled']   = !empty($input['waarom_enabled']) ? 1 : 0;
        $out['waarom_title']     = sanitize_text_field($input['waarom_title']    ?? '');
        $out['waarom_subtitle']  = sanitize_text_field($input['waarom_subtitle'] ?? '');
        $out['waarom_image_id']  = (int) ($input['waarom_image_id'] ?? 0);
        $out['waarom_items']     = [];
        $raw = $input['waarom_items_raw'] ?? '';
        if (is_string($raw) && $raw !== '') {
            foreach (preg_split('/\r?\n/', $raw) as $line) {
                $line = trim(sanitize_text_field($line));
                if ($line !== '') $out['waarom_items'][] = $line;
            }
        }

        $out['service_area_enabled']  = !empty($input['service_area_enabled']) ? 1 : 0;
        $out['service_area_title']    = sanitize_text_field($input['service_area_title'] ?? '');
        $out['service_area_subtitle'] = sanitize_text_field($input['service_area_subtitle'] ?? '');
        $out['service_area_items']    = [];
        $sa_items = $input['service_area_items'] ?? [];
        for ($i = 0; $i < 16; $i++) {
            $val = isset($sa_items[$i]) ? trim(sanitize_text_field($sa_items[$i])) : '';
            $out['service_area_items'][$i] = $val;
        }

        $out['routes_enabled']  = !empty($input['routes_enabled']) ? 1 : 0;
        $out['routes_title']    = sanitize_text_field($input['routes_title']    ?? '');
        $out['routes_subtitle'] = sanitize_text_field($input['routes_subtitle'] ?? '');
        $out['routes_items']    = [];
        $routes_in = $input['routes_items'] ?? [];
        for ($i = 0; $i < 12; $i++) {
            $r = $routes_in[$i] ?? [];
            $out['routes_items'][$i] = [
                'from'  => sanitize_text_field($r['from']  ?? ''),
                'to'    => sanitize_text_field($r['to']    ?? ''),
                'price' => sanitize_text_field($r['price'] ?? ''),
            ];
        }

        $out['faq_enabled']     = !empty($input['faq_enabled']) ? 1 : 0;
        $out['faq_title']       = sanitize_text_field($input['faq_title']    ?? '');
        $out['faq_subtitle']    = sanitize_text_field($input['faq_subtitle'] ?? '');
        $out['faq_home_limit']  = max(1, min(10, (int) ($input['faq_home_limit'] ?? 5)));
        $out['faq_items']       = [];
        $faq_in = $input['faq_items'] ?? [];
        for ($i = 0; $i < 5; $i++) {
            $f = $faq_in[$i] ?? [];
            $out['faq_items'][$i] = [
                'question' => sanitize_text_field($f['question']   ?? ''),
                'answer'   => sanitize_textarea_field($f['answer'] ?? ''),
            ];
        }
        // Extra FAQ items voor de aparte FAQ-pagina (niet op home) — 15 slots
        $out['faq_page_items']  = [];
        $faq_page_in = $input['faq_page_items'] ?? [];
        for ($i = 0; $i < 15; $i++) {
            $f = $faq_page_in[$i] ?? [];
            $out['faq_page_items'][$i] = [
                'question' => sanitize_text_field($f['question']   ?? ''),
                'answer'   => sanitize_textarea_field($f['answer'] ?? ''),
            ];
        }

        $out['contact_enabled']  = !empty($input['contact_enabled']) ? 1 : 0;
        $out['contact_title']    = sanitize_text_field($input['contact_title']    ?? '');
        $out['contact_subtitle'] = sanitize_text_field($input['contact_subtitle'] ?? '');

        $out['fleet_enabled']  = !empty($input['fleet_enabled']) ? 1 : 0;
        $out['fleet_title']    = sanitize_text_field($input['fleet_title']    ?? '');
        $out['fleet_subtitle'] = sanitize_text_field($input['fleet_subtitle'] ?? '');
        $out['fleet_items']    = [];
        $fleet_in = $input['fleet_items'] ?? [];
        for ($i = 0; $i < 5; $i++) {
            $f = $fleet_in[$i] ?? [];
            $out['fleet_items'][$i] = [
                'image_id' => (int) ($f['image_id'] ?? 0),
                'tag'      => sanitize_text_field($f['tag']      ?? ''),
                'title'    => sanitize_text_field($f['title']    ?? ''),
                'subtitle' => sanitize_text_field($f['subtitle'] ?? ''),
            ];
        }

        $out['reviews_enabled']    = !empty($input['reviews_enabled']) ? 1 : 0;
        $out['reviews_title']      = sanitize_text_field($input['reviews_title']      ?? '');
        $out['reviews_subtitle']   = sanitize_text_field($input['reviews_subtitle']   ?? '');
        $out['reviews_google_url'] = esc_url_raw($input['reviews_google_url']         ?? '');
        $out['reviews_cta_label']  = sanitize_text_field($input['reviews_cta_label']  ?? '');
        $out['reviews_items']      = [];
        $reviews_in = $input['reviews_items'] ?? [];
        for ($i = 0; $i < 6; $i++) {
            $r = $reviews_in[$i] ?? [];
            $rating = (int) ($r['rating'] ?? 5);
            if ($rating < 1) $rating = 1;
            if ($rating > 5) $rating = 5;
            $out['reviews_items'][$i] = [
                'name'   => sanitize_text_field($r['name']   ?? ''),
                'rating' => $rating,
                'text'   => sanitize_textarea_field($r['text'] ?? ''),
                'date'   => sanitize_text_field($r['date']   ?? ''),
            ];
        }

        return $out;
    }
}
