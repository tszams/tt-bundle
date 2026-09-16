<?php
/**
 * Hero — premium dispatcher.
 * Kiest tussen 'image' (cinematic bg image met centered CTA's) en 'form'
 * (two-column: tekst links, booking-widget rechts in premium white panel).
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$style   = $content['hero_premium_style'] ?? 'image';

if ($style === 'form') {
    get_template_part('templates/premium/hero-form');
} else {
    get_template_part('templates/premium/hero-image');
}
