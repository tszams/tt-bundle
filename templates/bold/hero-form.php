<?php
/**
 * Hero — bold variant "form".
 * Two-column split op de bold dark bg met accent-glow: display-title + subtitle
 * links, booking-widget rechts (transparent — geen wit card, past bij bold).
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$name    = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));
$phone   = TaxiTheme_Company_Info::get('phone');
$city    = TaxiTheme_Company_Info::get('city');

$boeken_id   = TaxiTheme_Installer::get_page_id('boeken');
$boeken_url  = $boeken_id ? get_permalink($boeken_id) : '#';
$phone_clean = $phone ? preg_replace('/[^0-9+]/', '', $phone) : '';

$eyebrow = '';
if (!empty($content['hero_eyebrow_enabled'])) {
    $eyebrow = !empty($content['hero_eyebrow'])
        ? $content['hero_eyebrow']
        : ($city ? 'Taxi in ' . $city : '');
}

$title    = !empty($content['hero_title'])    ? $content['hero_title']    : $name;
$subtitle = $content['hero_subtitle'] ?? '';

$wa_number = !empty($content['hero_whatsapp_number']) ? $content['hero_whatsapp_number'] : $phone;
$wa_clean  = $wa_number ? preg_replace('/[^0-9]/', '', $wa_number) : '';

$show_phone    = !empty($content['hero_phone_enabled']) && $phone;
$show_whatsapp = !empty($content['hero_whatsapp_enabled']) && $wa_clean;
?>
<section class="tt-hero-fs tt-hero-fs--form">
    <div class="tt-hero-fs__bg" aria-hidden="true"></div>
    <div class="tt-container tt-hero-fs__inner tt-hero-fs__inner--form">
        <div class="tt-hero-fs__text">
            <?php if ($eyebrow) : ?>
                <span class="tt-hero-fs__eyebrow"><?php echo esc_html($eyebrow); ?></span>
            <?php endif; ?>
            <h1 class="tt-hero-fs__title"><?php echo esc_html($title); ?></h1>
            <?php if ($subtitle) : ?>
                <p class="tt-hero-fs__sub"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>
            <div class="tt-hero-fs__actions">
                <a href="<?php echo esc_url($boeken_url); ?>" class="tt-hero-fs__cta tt-hero-fs__cta--primary">
                    Boek direct
                    <?php echo TaxiTheme_Icons::svg('arrow-right', 20); ?>
                </a>
                <?php if ($show_phone) : ?>
                    <a href="tel:<?php echo esc_attr($phone_clean); ?>" class="tt-hero-fs__cta tt-hero-fs__cta--ghost">
                        <?php echo TaxiTheme_Icons::svg('phone', 18); ?>
                        <?php echo esc_html($phone); ?>
                    </a>
                <?php endif; ?>
                <?php if ($show_whatsapp) : ?>
                    <a href="https://wa.me/<?php echo esc_attr($wa_clean); ?>" target="_blank" rel="noopener" class="tt-hero-fs__cta tt-hero-fs__cta--wa">
                        <?php echo TaxiTheme_Icons::svg('whatsapp', 18); ?>
                        WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="tt-hero-fs__form">
            <div class="taxibookingform" style="min-height:450px;"></div>
        </div>
    </div>
</section>
