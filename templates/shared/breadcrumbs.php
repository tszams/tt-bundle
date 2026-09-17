<?php
/**
 * Breadcrumbs — dun wrapper rond TaxiTheme_SEO::render_breadcrumbs().
 * Include in sub-page templates net vóór de h1 title in de page-header.
 * Automatisch onderdrukt op home of als toggle uit staat.
 */
if (!defined('ABSPATH')) exit;
if (!class_exists('TaxiTheme_SEO')) return;
TaxiTheme_SEO::render_breadcrumbs();
