<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Booking integratie.
 *
 * Widget-CDN staat vast (verwerkt in preset-hero's).
 * Webapp-CDN staat vast (voor de aparte /boeken page).
 *
 * Enige klant-instelling: of de aparte boekpagina met webapp actief is.
 */
class TaxiTheme_Booking {

    const WIDGET_URL     = 'https://cdn.taxibookingform.nl/assets/app.js';
    const WEBAPP_JS_URL  = 'https://taxiwebapp.pages.dev/booking.js';
    const WEBAPP_CSS_URL = 'https://taxiwebapp.pages.dev/booking.css';

    const OPT_WEBAPP_PAGE_ENABLED = 'taxitheme_booking_webapp_page_enabled';

    public static function widget_url() {
        return self::WIDGET_URL;
    }

    public static function webapp_url() {
        return self::WEBAPP_JS_URL;
    }

    public static function webapp_css_url() {
        return self::WEBAPP_CSS_URL;
    }

    /**
     * Is de aparte boekpagina met webapp ingeschakeld?
     * Default aan — klant kan uitzetten in Boeken-tab.
     */
    public static function webapp_page_enabled() {
        return (bool) get_option(self::OPT_WEBAPP_PAGE_ENABLED, 1);
    }

    public static function save($input) {
        update_option(self::OPT_WEBAPP_PAGE_ENABLED, !empty($input['webapp_page_enabled']) ? 1 : 0);
    }
}
