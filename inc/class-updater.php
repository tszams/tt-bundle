<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * GitHub-based theme updater.
 *
 * Werkt met de normale WordPress "Update Available" UI in Dashboard → Themes.
 * Poll GitHub Releases API, cache 6h, injecteer de zipball_url in WP z'n theme
 * update transient. Bij een update-klik downloadt WP het zip en installeert 'em
 * over de bestaande theme folder heen (met upgrader_source_selection hook die
 * de folder-naam corrigeert — GitHub's zipball geeft `{owner}-{repo}-{sha}`).
 *
 * ============ CONFIG (invullen wanneer je repo klaar staat) ============
 *  - GITHUB_OWNER: je GitHub username of org (bv. "taxi-amsterdam")
 *  - GITHUB_REPO:  de repo-naam (bv. "taxitheme")
 *  - GITHUB_TOKEN: leeg voor publieke repo. Voor privé: een fine-grained
 *    Personal Access Token met "Contents: Read" rechten op de repo. Zet 'm
 *    in wp-config.php als: define('TAXITHEME_GITHUB_TOKEN', 'ghp_xxx');
 *    Zo staat de token niet in de theme code (belangrijk voor privé).
 *
 * ============ Release-workflow ============
 *  1. Bump Version in style.css (bv. 0.1.0 → 0.1.1)
 *  2. Commit + push naar main
 *  3. GitHub → Releases → Draft a new release
 *  4. Tag: v0.1.1  (met "v" prefix — de updater strip 'em)
 *  5. Publish
 *  → Klanten zien binnen 6h "Update Available" in Themes. Update = 1 klik.
 */
class TaxiTheme_Updater {

    const GITHUB_OWNER = 'tszams';
    const GITHUB_REPO  = 'tt-bundle';

    const TRANSIENT_RELEASE = 'taxitheme_gh_release';
    const CACHE_TTL         = 6 * HOUR_IN_SECONDS;
    const CACHE_TTL_FAIL    = 15 * MINUTE_IN_SECONDS;

    public static function init() {
        add_filter('pre_set_site_transient_update_themes', [__CLASS__, 'inject_update']);
        add_filter('themes_api',                            [__CLASS__, 'theme_information'], 10, 3);
        add_filter('upgrader_source_selection',             [__CLASS__, 'fix_source_dir'],   10, 4);
        add_filter('http_request_args',                     [__CLASS__, 'authorize_github_request'], 10, 2);
        add_action('upgrader_process_complete',             [__CLASS__, 'clear_cache_after_update'], 10, 2);
        add_action('admin_init',                            [__CLASS__, 'maybe_force_check']);
    }

    /**
     * Voegt Authorization: Bearer <PAT> toe aan elke HTTP request naar onze
     * repo op api.github.com. Nodig voor privé repos — de zipball download
     * die WP zelf triggert (via download_url) heeft ook auth nodig, en die
     * gaat niet door onze eigen wp_remote_get maar door de WP Upgrader.
     *
     * Voor publieke repos is dit een no-op (token is leeg).
     */
    public static function authorize_github_request($args, $url) {
        $prefix = 'https://api.github.com/repos/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO . '/';
        if (strpos($url, $prefix) !== 0) return $args;

        $token = self::get_token();
        if (!$token) return $args;

        if (!isset($args['headers']) || !is_array($args['headers'])) {
            $args['headers'] = [];
        }
        $args['headers']['Authorization'] = 'Bearer ' . $token;
        $args['headers']['Accept']        = 'application/vnd.github+json';
        return $args;
    }

    /**
     * Voegt de update toe aan het theme update transient wanneer GitHub
     * een nieuwere versie heeft dan wat lokaal geïnstalleerd is.
     */
    public static function inject_update($transient) {
        if (empty($transient) || !is_object($transient)) return $transient;

        $release = self::get_latest_release();
        if (!$release || empty($release->tag_name)) return $transient;

        $slug    = get_stylesheet();
        $current = wp_get_theme()->get('Version');
        $latest  = ltrim($release->tag_name, 'vV');

        if (version_compare($latest, $current, '>')) {
            $package_url = self::download_url_from_release($release);
            if (!$package_url) return $transient;

            $transient->response[$slug] = [
                'theme'        => $slug,
                'new_version'  => $latest,
                'url'          => 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO,
                'package'      => $package_url,
                'requires'     => '5.8',
                'requires_php' => '7.4',
            ];
        }
        return $transient;
    }

    /**
     * Populate de "View version details" popup in Dashboard → Themes.
     */
    public static function theme_information($result, $action, $args) {
        if ($action !== 'theme_information') return $result;
        if (empty($args->slug) || $args->slug !== get_stylesheet()) return $result;

        $release = self::get_latest_release();
        if (!$release) return $result;

        $theme = wp_get_theme();
        $body  = !empty($release->body) ? $release->body : 'Release ' . $release->tag_name;

        return (object) [
            'name'          => $theme->get('Name'),
            'slug'          => get_stylesheet(),
            'version'       => ltrim($release->tag_name, 'vV'),
            'author'        => $theme->get('Author'),
            'homepage'      => 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO,
            'download_link' => self::download_url_from_release($release),
            'sections'      => [
                'description' => wpautop(esc_html($body)),
                'changelog'   => wpautop(esc_html($body)),
            ],
        ];
    }

    /**
     * GitHub's zipball geeft folder naam `{owner}-{repo}-{sha7}` — WP installeert
     * dat als apart thema (of gooit error). Rename de extracted folder naar de
     * theme slug zodat WP 'em over de bestaande installatie schrijft.
     */
    public static function fix_source_dir($source, $remote_source, $upgrader, $hook_extra = []) {
        global $wp_filesystem;

        $slug = get_stylesheet();

        // Alleen actief voor ons theme
        $is_our_theme = false;
        if (!empty($hook_extra['theme']) && $hook_extra['theme'] === $slug) $is_our_theme = true;
        if (!empty($hook_extra['themes']) && is_array($hook_extra['themes']) && in_array($slug, $hook_extra['themes'], true)) $is_our_theme = true;
        // Fallback: als de map begint met {owner}-{repo}-, aanname is ons
        $basename = basename(untrailingslashit($source));
        $prefix   = self::GITHUB_OWNER . '-' . self::GITHUB_REPO . '-';
        if (stripos($basename, $prefix) === 0) $is_our_theme = true;

        if (!$is_our_theme) return $source;

        $expected = trailingslashit($remote_source) . $slug . '/';
        if ($source === $expected) return $source;

        if ($wp_filesystem && $wp_filesystem->move(untrailingslashit($source), untrailingslashit($expected))) {
            return $expected;
        }
        return $source;
    }

    /**
     * Cache legen na een succesvolle theme-update zodat de UI direct de nieuwe
     * versie ziet en niet nog 6h denkt dat er nog een update beschikbaar is.
     */
    public static function clear_cache_after_update($upgrader, $hook_extra) {
        if (empty($hook_extra['type']) || $hook_extra['type'] !== 'theme') return;
        delete_transient(self::TRANSIENT_RELEASE);
    }

    /**
     * Publiek: haal de laatst-bekende release info op (uit cache of vers).
     * Return: {tag_name, html_url, published_at, body, ...} of null.
     * Voor UI-doeleinden (Instellingen tab).
     */
    public static function latest_release() {
        return self::get_latest_release();
    }

    /**
     * Publiek: alleen versie-string van de laatste release (zonder "v" prefix).
     */
    public static function latest_version() {
        $release = self::get_latest_release();
        if (!$release || empty($release->tag_name)) return null;
        return ltrim($release->tag_name, 'vV');
    }

    /**
     * Publiek: is er een nieuwere versie beschikbaar dan de geïnstalleerde?
     */
    public static function has_update() {
        $latest = self::latest_version();
        if (!$latest) return false;
        return version_compare($latest, wp_get_theme()->get('Version'), '>');
    }

    /**
     * Fetch de latest release van GitHub. Gecached voor 6h (of 15min bij fail).
     */
    private static function get_latest_release() {
        $cached = get_transient(self::TRANSIENT_RELEASE);
        if ($cached !== false) {
            return $cached === 'FAIL' ? null : $cached;
        }

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            self::GITHUB_OWNER,
            self::GITHUB_REPO
        );

        $args = [
            'timeout' => 10,
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'TaxiTheme-Updater',
            ],
        ];
        $token = self::get_token();
        if ($token) {
            $args['headers']['Authorization'] = 'Bearer ' . $token;
        }

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            set_transient(self::TRANSIENT_RELEASE, 'FAIL', self::CACHE_TTL_FAIL);
            return null;
        }

        $release = json_decode(wp_remote_retrieve_body($response));
        if (!$release || empty($release->tag_name)) {
            set_transient(self::TRANSIENT_RELEASE, 'FAIL', self::CACHE_TTL_FAIL);
            return null;
        }

        set_transient(self::TRANSIENT_RELEASE, $release, self::CACHE_TTL);
        return $release;
    }

    /**
     * Bepaal de download URL uit een release.
     *
     * PUBLIEKE repo: prefer een .zip asset uit de release (clean packaged zip
     * zonder de {owner}-{repo}-{sha} folder-prefix). Fallback: zipball_url.
     *
     * PRIVÉ repo: altijd zipball_url — dat is de API-endpoint vorm
     * (api.github.com/repos/.../zipball/...) die onze http_request_args
     * filter kan herkennen en met Authorization header kan authoriseren.
     * De browser_download_url gaat via github.com en heeft een aparte
     * auth-flow (Accept: application/octet-stream + asset-endpoint) die
     * lastiger is om via WP Upgrader te routeren.
     */
    private static function download_url_from_release($release) {
        if (!$release) return '';

        $is_private = (bool) self::get_token();

        // Publiek + release-asset .zip beschikbaar → gebruik die (clean).
        if (!$is_private && !empty($release->assets) && is_array($release->assets)) {
            foreach ($release->assets as $asset) {
                if (isset($asset->name) && preg_match('/\.zip$/i', $asset->name)) {
                    return $asset->browser_download_url;
                }
            }
        }

        // Fallback (en de standaard voor privé): automatische zipball van de tag.
        return isset($release->zipball_url) ? $release->zipball_url : '';
    }

    /**
     * Personal Access Token uit wp-config.php constant. Optioneel — alleen
     * nodig voor privé repos.
     */
    private static function get_token() {
        if (defined('TAXITHEME_GITHUB_TOKEN') && TAXITHEME_GITHUB_TOKEN) {
            return (string) TAXITHEME_GITHUB_TOKEN;
        }
        return '';
    }

    /**
     * Debug helper — forceer een cache-clear via admin URL:
     * /wp-admin/?taxitheme_force_update_check=1
     */
    public static function maybe_force_check() {
        if (!is_admin() || !current_user_can('update_themes')) return;
        if (empty($_GET['taxitheme_force_update_check'])) return;
        delete_transient(self::TRANSIENT_RELEASE);
        delete_site_transient('update_themes');
    }
}
