<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schema.org JSON-LD output voor Google rich results.
 *
 * Site-wide: TaxiService (met contact, adres, area served, openingstijden, sameAs)
 * Per-page:  WebPage + evt. FAQPage als de home-editor FAQ heeft aanstaan
 *
 * Alles in één <script type="application/ld+json"> in de <head>.
 * SEO-plugins (Yoast/RankMath) hebben hun eigen generieke Organization schema —
 * gebruikers kunnen die uitzetten, of onze output is complementair (TaxiService
 * type is specifieker).
 */
class TaxiTheme_Schema {

    public static function init() {
        add_action('wp_head', [__CLASS__, 'output'], 5);
    }

    public static function output() {
        $graph = [];

        $taxi = self::taxi_service();
        if ($taxi) $graph[] = $taxi;

        $webpage = self::webpage();
        if ($webpage) $graph[] = $webpage;

        $faq = self::faq_page();
        if ($faq) $graph[] = $faq;

        if (class_exists('TaxiTheme_SEO')) {
            $breadcrumb = TaxiTheme_SEO::breadcrumb_schema();
            if ($breadcrumb) $graph[] = $breadcrumb;
        }

        if (empty($graph)) return;

        $ld = [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];

        echo "\n<script type=\"application/ld+json\">\n";
        // JSON_UNESCAPED_SLASHES: URLs blijven leesbaar. JSON_UNESCAPED_UNICODE: accenten OK.
        echo wp_json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }

    private static function base_url() {
        return trailingslashit(home_url('/'));
    }

    private static function taxi_service() {
        $name = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));
        if (empty($name)) return null;

        $phone    = TaxiTheme_Company_Info::get('phone');
        $email    = TaxiTheme_Company_Info::get('email');
        $address  = TaxiTheme_Company_Info::get('address');
        $postcode = TaxiTheme_Company_Info::get('postcode');
        $city     = TaxiTheme_Company_Info::get('city');
        $kvk      = TaxiTheme_Company_Info::get('kvk');
        $tagline  = get_bloginfo('description'); // WP's ingebouwde site-tagline

        $base = self::base_url();

        $data = [
            '@type' => 'TaxiService',
            '@id'   => $base . '#taxiservice',
            'name'  => $name,
            'url'   => $base,
        ];

        if ($tagline) {
            $data['description'] = $tagline;
        }
        if ($phone) {
            $data['telephone'] = $phone;
        }
        if ($email) {
            $data['email'] = $email;
        }

        // Address
        if ($address || $city || $postcode) {
            $addr = ['@type' => 'PostalAddress', 'addressCountry' => 'NL'];
            if ($address)  $addr['streetAddress']   = $address;
            if ($postcode) $addr['postalCode']      = $postcode;
            if ($city)     $addr['addressLocality'] = $city;
            $data['address'] = $addr;
        }

        // KvK als identifier
        if ($kvk) {
            $data['identifier'] = [
                '@type' => 'PropertyValue',
                'name'  => 'KvK',
                'value' => $kvk,
            ];
        }

        // Area served — uit home-content service_area_items
        $content = class_exists('TaxiTheme_Home_Content') ? TaxiTheme_Home_Content::all() : [];
        $areas   = array_values(array_filter(array_map('trim', $content['service_area_items'] ?? []), 'strlen'));
        if (!empty($areas)) {
            $data['areaServed'] = array_map(function ($area) {
                return ['@type' => 'City', 'name' => $area];
            }, $areas);
        }

        // Openingstijden
        $data['openingHoursSpecification'] = self::opening_hours();

        // Defaults die Google fijn vindt
        $data['priceRange']         = '€€';
        $data['paymentAccepted']    = 'Cash, Credit Card, Debit Card, Mobile Payment';
        $data['currenciesAccepted'] = 'EUR';

        // Social URLs
        $socials = array_filter(TaxiTheme_Company_Info::socials());
        if (!empty($socials)) {
            $data['sameAs'] = array_values($socials);
        }

        return $data;
    }

    private static function opening_hours() {
        $day_map = [
            'monday'    => 'Monday',
            'tuesday'   => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday'  => 'Thursday',
            'friday'    => 'Friday',
            'saturday'  => 'Saturday',
            'sunday'    => 'Sunday',
        ];

        if (TaxiTheme_Company_Info::is_247()) {
            return [[
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => array_values($day_map),
                'opens'     => '00:00',
                'closes'    => '23:59',
            ]];
        }

        $hours = TaxiTheme_Company_Info::hours();
        $out   = [];
        foreach ($hours as $day => $spec) {
            if (!empty($spec['closed'])) continue;
            $out[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => $day_map[$day],
                'opens'     => $spec['open'],
                'closes'    => $spec['close'],
            ];
        }
        return $out;
    }

    private static function webpage() {
        if (!is_singular() && !is_front_page()) return null;

        $url   = is_singular() ? get_permalink() : self::base_url();
        $title = wp_get_document_title();

        return [
            '@type'    => 'WebPage',
            '@id'      => $url . '#webpage',
            'url'      => $url,
            'name'     => $title,
            'isPartOf' => ['@id' => self::base_url() . '#website'],
            'about'    => ['@id' => self::base_url() . '#taxiservice'],
        ];
    }

    private static function faq_page() {
        if (!is_singular()) return null;
        if (!class_exists('TaxiTheme_Home_Content')) return null;

        $content = TaxiTheme_Home_Content::all();

        // Alleen FAQPage schema als FAQ ergens getoond wordt op deze URL.
        // Meest voorspelbaar: homepage (als faq_enabled) + de dedicated /faq page.
        $is_faq_visible = false;
        if (!empty($content['faq_enabled'])) {
            if (is_front_page()) $is_faq_visible = true;
            $role = get_post_meta(get_queried_object_id(), TaxiTheme_Installer::META_ROLE, true);
            if ($role === 'faq') $is_faq_visible = true;
        }
        if (!$is_faq_visible) return null;

        $items = array_values(array_filter($content['faq_items'] ?? [], function ($f) {
            return !empty($f['question']) && !empty($f['answer']);
        }));
        if (empty($items)) return null;

        $entities = array_map(function ($f) {
            return [
                '@type' => 'Question',
                'name'  => $f['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $f['answer'],
                ],
            ];
        }, $items);

        return [
            '@type'      => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }
}
