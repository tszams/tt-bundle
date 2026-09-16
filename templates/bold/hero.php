<?php
/**
 * Hero — bold dispatcher.
 * Kiest tussen 'fullscreen' (grote display-typografie, geen form) en
 * 'form' (split: text links, booking-widget rechts op dark bold bg).
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$style   = $content['hero_bold_style'] ?? 'fullscreen';

if ($style === 'form') {
    get_template_part('templates/bold/hero-form');
} else {
    get_template_part('templates/bold/hero-fullscreen');
}
