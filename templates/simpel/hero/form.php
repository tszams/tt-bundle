<?php
/**
 * Hero — Simpel preset, variant "met formulier".
 * Split: tekst + CTA-knoppen links, boekingsformulier rechts. Bg volgt palette
 * (donker bij dark palette, wit bij licht).
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$name    = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));
$phone   = TaxiTheme_Company_Info::get('phone');
$city    = TaxiTheme_Company_Info::get('city');

$phone_clean = $phone ? preg_replace('/[^0-9+]/', '', $phone) : '';

$title    = !empty($content['hero_title'])    ? $content['hero_title']    : $name;
$subtitle = $content['hero_subtitle'] ?? '';

$wa_number = !empty($content['hero_whatsapp_number']) ? $content['hero_whatsapp_number'] : $phone;
$wa_clean  = $wa_number ? preg_replace('/[^0-9]/', '', $wa_number) : '';
?>
<section class="tt-simpel-hero tt-simpel-hero--form">
    <div class="tt-container tt-simpel-hero__inner">
        <div class="tt-simpel-hero__body">
            <h1 class="tt-simpel-hero__title"><?php echo esc_html($title); ?></h1>
            <?php if ($subtitle) : ?>
                <p class="tt-simpel-hero__sub"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>
            <div class="tt-simpel-hero__actions">
                <?php if ($phone) : ?>
                    <a href="tel:<?php echo esc_attr($phone_clean); ?>" class="tt-simpel-cta tt-simpel-cta--primary">
                        <?php echo TaxiTheme_Icons::svg('phone', 16); ?>
                        Bellen
                    </a>
                <?php endif; ?>
                <?php if ($wa_clean) : ?>
                    <a href="https://wa.me/<?php echo esc_attr($wa_clean); ?>" target="_blank" rel="noopener" class="tt-simpel-cta tt-simpel-cta--ghost">
                        <?php echo TaxiTheme_Icons::svg('whatsapp', 16); ?>
                        WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="tt-simpel-hero__media tt-simpel-hero__media--form">
            <div class="taxibookingform" style="min-height:450px;"></div>
        </div>
    </div>
</section>
