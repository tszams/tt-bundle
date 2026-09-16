<?php
/**
 * Sticky call/whatsapp knop rechtsonder (voor "Bold" preset).
 * Verschijnt alleen als er een telefoonnummer bekend is.
 */
if (!defined('ABSPATH')) exit;

$phone = TaxiTheme_Company_Info::get('phone');
if (!$phone) return;

$phone_clean = preg_replace('/[^0-9+]/', '', $phone);
?>
<a href="tel:<?php echo esc_attr($phone_clean); ?>" class="tt-sticky-call" aria-label="Bel <?php echo esc_attr($phone); ?>">
    <?php echo TaxiTheme_Icons::svg('phone', 22); ?>
    <span class="tt-sticky-call__text">Bel nu</span>
</a>
