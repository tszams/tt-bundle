<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SEO — meta description, Open Graph, Twitter Cards + breadcrumbs.
 *
 * Alles auto-gegenereerd uit bestaande data (home-editor, page-meta, company-info).
 * Geen extra invoervelden voor de klant.
 *
 * Meta description / OG / Twitter worden UITGESCHAKELD als er een SEO plugin
 * actief is (Yoast, Rank Math, SEOPress) — die outputen dezelfde tags en we
 * willen geen duplicates. Onze schema.org output (class-schema.php) blijft
 * altijd staan omdat wij een specifiekere TaxiService schema hebben.
 *
 * Breadcrumbs (zichtbaar + BreadcrumbList schema) staan altijd aan tenzij
 * expliciet uitgezet in TaxiTheme → Instellingen.
 */
class TaxiTheme_SEO {

    const OPT_BREADCRUMBS_ENABLED = 'taxitheme_breadcrumbs_enabled';
    const DESC_MAX_LENGTH         = 155;

    public static function init() {
        add_action('wp_head', [__CLASS__, 'output_meta'], 3);
    }

    // ================================================================
    //  Meta description + Open Graph + Twitter
    // ================================================================

    public static function output_meta() {
        if (self::is_seo_plugin_active()) return;

        $desc      = self::get_description();
        $title     = self::get_meta_title();
        $url       = self::current_url();
        $og_image  = self::get_og_image_url();
        $site_name = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));

        echo "\n<!-- TaxiTheme SEO -->\n";

        if ($desc) {
            echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
        }

        // Open Graph (Facebook, LinkedIn, WhatsApp)
        echo '<meta property="og:type" content="' . (is_front_page() ? 'website' : 'article') . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        if ($desc) {
            echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
        }
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        echo '<meta property="og:locale" content="' . esc_attr(str_replace('-', '_', get_locale())) . '">' . "\n";
        if ($og_image) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
        }

        // Twitter Cards
        echo '<meta name="twitter:card" content="' . ($og_image ? 'summary_large_image' : 'summary') . '">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        if ($desc) {
            echo '<meta name="twitter:description" content="' . esc_attr($desc) . '">' . "\n";
        }
        if ($og_image) {
            echo '<meta name="twitter:image" content="' . esc_url($og_image) . '">' . "\n";
        }

        echo "<!-- /TaxiTheme SEO -->\n";
    }

    /**
     * Detecteer of een populaire SEO plugin actief is die zelf al meta description
     * en OG tags outputet. Als ja → wij houden ons in om duplicates te voorkomen.
     */
    public static function is_seo_plugin_active() {
        // Yoast
        if (defined('WPSEO_VERSION')) return true;
        // Rank Math
        if (class_exists('RankMath') || defined('RANK_MATH_VERSION')) return true;
        // SEOPress
        if (defined('SEOPRESS_VERSION')) return true;
        // All in One SEO
        if (defined('AIOSEO_VERSION')) return true;
        return false;
    }

    /**
     * Cascade voor meta description — probeert per pagina de meest specifieke
     * bron, valt terug op sensible defaults met bedrijfsdata.
     */
    public static function get_description() {
        $company_name = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));
        $tagline      = get_bloginfo('description');
        $city         = TaxiTheme_Company_Info::get('city');
        $phone        = TaxiTheme_Company_Info::get('phone');
        $email        = TaxiTheme_Company_Info::get('email');

        $raw = '';

        if (is_front_page()) {
            // Homepage: hero subtitle uit home-editor → fallback WP tagline
            if (class_exists('TaxiTheme_Home_Content')) {
                $content = TaxiTheme_Home_Content::all();
                $raw = trim($content['hero_subtitle'] ?? '');
            }
            if ($raw === '') $raw = $tagline;
        } elseif (is_page()) {
            $post_id = get_queried_object_id();
            $role    = get_post_meta($post_id, TaxiTheme_Installer::META_ROLE, true);

            // 1. Per-page intro (hebben over-ons, diensten, tarieven, contact, faq)
            if (class_exists('TaxiTheme_Page_Meta')) {
                $intro = TaxiTheme_Page_Meta::get_intro($post_id);
                if ($intro !== '') $raw = $intro;
            }

            // 2. Role-specifieke fallbacks
            if ($raw === '' && $role) {
                switch ($role) {
                    case 'diensten':
                        $raw = "Onze diensten — $company_name" . ($city ? " · $city en omgeving." : '.');
                        break;
                    case 'tarieven':
                        $raw = "Tarieven — $company_name" . ($tagline ? " · $tagline" : '.');
                        break;
                    case 'over-ons':
                        $raw = $tagline ? "$tagline · $company_name" : $company_name;
                        break;
                    case 'faq':
                        $raw = "Veelgestelde vragen over onze diensten, tarieven en werkwijze — $company_name.";
                        break;
                    case 'contact':
                        $parts = ["Neem contact op met $company_name"];
                        if ($phone) $parts[] = $phone;
                        if ($email) $parts[] = $email;
                        $raw = implode(' · ', $parts) . '.';
                        break;
                    case 'boeken':
                        $raw = "Boek uw taxi online bij $company_name — snel, veilig en professioneel.";
                        break;
                }
            }

            // 3. Legal pages: eerste stuk uit post_content
            if ($raw === '' && in_array($role, ['privacy', 'voorwaarden'], true)) {
                $post = get_post($post_id);
                if ($post) $raw = $post->post_content;
            }

            // 4. Custom WP page: WP excerpt → post_content
            if ($raw === '') {
                $post = get_post($post_id);
                if ($post) {
                    $raw = $post->post_excerpt ?: $post->post_content;
                }
            }

            // 5. Ultimate fallback
            if ($raw === '') {
                $raw = $tagline ?: $company_name;
            }
        } else {
            $raw = $tagline ?: $company_name;
        }

        return self::truncate_description($raw);
    }

    /**
     * Clean + truncate op woord-grens.
     */
    private static function truncate_description($text) {
        $text = wp_strip_all_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        if ($text === '') return '';

        if (mb_strlen($text) <= self::DESC_MAX_LENGTH) return $text;

        $cut = mb_substr($text, 0, self::DESC_MAX_LENGTH);
        $last_space = mb_strrpos($cut, ' ');
        if ($last_space !== false && $last_space > 100) {
            $cut = mb_substr($cut, 0, $last_space);
        }
        return rtrim($cut, " ,.;:-") . '…';
    }

    /**
     * Titel voor OG/Twitter — meestal WP z'n eigen title zonder site-suffix.
     */
    private static function get_meta_title() {
        if (is_front_page()) {
            $name    = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));
            $tagline = get_bloginfo('description');
            return $tagline ? "$name — $tagline" : $name;
        }
        return wp_get_document_title();
    }

    /**
     * OG image cascade:
     *   1. Featured image van de pagina (post_thumbnail)
     *   2. Homepage hero image (uit home-editor)
     *   3. Company logo
     *   4. Theme screenshot
     */
    public static function get_og_image_url() {
        // 1. Featured image
        if (is_singular() && has_post_thumbnail()) {
            $url = get_the_post_thumbnail_url(null, 'full');
            if ($url) return $url;
        }

        // 2. Homepage hero
        if (class_exists('TaxiTheme_Home_Content')) {
            $content = TaxiTheme_Home_Content::all();
            $hero_id = (int) ($content['hero_image_id'] ?? 0);
            if ($hero_id) {
                $url = wp_get_attachment_image_url($hero_id, 'full');
                if ($url) return $url;
            }
        }

        // 3. Company logo
        if (class_exists('TaxiTheme_Company_Info')) {
            $logo = TaxiTheme_Company_Info::logo_url();
            if ($logo) return $logo;
        }

        // 4. Theme screenshot
        $screenshot = get_template_directory_uri() . '/screenshot.png';
        return $screenshot;
    }

    private static function current_url() {
        if (is_singular()) return get_permalink();
        if (is_front_page()) return home_url('/');
        return home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? '/'));
    }

    // ================================================================
    //  Breadcrumbs
    // ================================================================

    public static function breadcrumbs_enabled() {
        return (bool) get_option(self::OPT_BREADCRUMBS_ENABLED, 1);
    }

    public static function set_breadcrumbs_enabled($enabled) {
        update_option(self::OPT_BREADCRUMBS_ENABLED, $enabled ? 1 : 0);
    }

    /**
     * Bouwt de breadcrumb-trail voor de huidige pagina.
     * Array van ['name' => string, 'url' => string].
     * Home is altijd eerst; laatste item is de huidige pagina.
     */
    public static function get_trail() {
        $trail = [
            ['name' => 'Home', 'url' => home_url('/')],
        ];

        if (is_front_page()) {
            return $trail; // enkel Home
        }

        if (is_singular()) {
            $post = get_queried_object();
            if (!$post) return $trail;

            // Ancestors (voor genest WP pages — TaxiTheme is flat, dus meestal geen)
            $ancestors = array_reverse(get_post_ancestors($post->ID));
            foreach ($ancestors as $anc_id) {
                $trail[] = [
                    'name' => get_the_title($anc_id),
                    'url'  => get_permalink($anc_id),
                ];
            }

            $trail[] = [
                'name' => get_the_title($post->ID),
                'url'  => get_permalink($post->ID),
            ];
        } elseif (is_archive()) {
            $trail[] = [
                'name' => get_the_archive_title(),
                'url'  => '',
            ];
        } elseif (is_search()) {
            $trail[] = [
                'name' => 'Zoekresultaten',
                'url'  => '',
            ];
        } elseif (is_404()) {
            $trail[] = [
                'name' => 'Pagina niet gevonden',
                'url'  => '',
            ];
        }

        return $trail;
    }

    /**
     * Render zichtbare breadcrumbs. Aangeroepen door templates/shared/breadcrumbs.php.
     * Geen output op home of als toggle uit staat.
     */
    public static function render_breadcrumbs() {
        if (!self::breadcrumbs_enabled()) return;
        if (is_front_page()) return;

        $trail = self::get_trail();
        if (count($trail) < 2) return;
        $last = count($trail) - 1;
        ?>
        <nav class="tt-breadcrumbs" aria-label="Broodkruimels">
            <ol class="tt-breadcrumbs__list">
                <?php foreach ($trail as $i => $crumb) : ?>
                    <li class="tt-breadcrumbs__item">
                        <?php if ($i === $last) : ?>
                            <span class="tt-breadcrumbs__current" aria-current="page"><?php echo esc_html($crumb['name']); ?></span>
                        <?php else : ?>
                            <a href="<?php echo esc_url($crumb['url']); ?>" class="tt-breadcrumbs__link"><?php echo esc_html($crumb['name']); ?></a>
                            <span class="tt-breadcrumbs__sep" aria-hidden="true">›</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php
    }

    /**
     * BreadcrumbList voor Schema.org JSON-LD.
     * Aangeroepen vanuit class-schema.php.
     * Return null als geen breadcrumbs of home page.
     */
    public static function breadcrumb_schema() {
        if (!self::breadcrumbs_enabled()) return null;
        if (is_front_page()) return null;

        $trail = self::get_trail();
        if (count($trail) < 2) return null;

        $items = [];
        foreach ($trail as $i => $crumb) {
            $item = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['name'],
            ];
            if (!empty($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }
            $items[] = $item;
        }

        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }
}
