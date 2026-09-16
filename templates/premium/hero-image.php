<?php
/**
 * Hero — premium variant "image".
 * Grote foto als achtergrond (optioneel — met gradient fallback), donkere overlay,
 * centered titel + subtitle, primary CTA. Editorial/Apple-esthetiek.
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

$image_id  = (int) ($content['hero_image_id'] ?? 0);
$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : '';
$has_image = (bool) $image_url;

$show_phone    = !empty($content['hero_phone_enabled']) && $phone;
$wa_number     = !empty($content['hero_whatsapp_number']) ? $content['hero_whatsapp_number'] : $phone;
$wa_clean      = $wa_number ? preg_replace('/[^0-9]/', '', $wa_number) : '';
$show_whatsapp = !empty($content['hero_whatsapp_enabled']) && $wa_clean;
?>
<section class="tt-premium-hero <?php echo $has_image ? 'has-image' : 'no-image'; ?>">
    <?php if ($has_image) : ?>
        <div class="tt-premium-hero__bg" style="background-image: url('<?php echo esc_url($image_url); ?>');" aria-hidden="true"></div>
        <div class="tt-premium-hero__overlay" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="tt-container tt-premium-hero__inner">
        <?php if ($eyebrow) : ?>
            <span class="tt-premium-hero__eyebrow"><?php echo esc_html($eyebrow); ?></span>
        <?php endif; ?>
        <h1 class="tt-premium-hero__title"><?php echo esc_html($title); ?></h1>
        <?php if ($subtitle) : ?>
            <p class="tt-premium-hero__sub"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>
        <div class="tt-premium-hero__actions">
            <a href="<?php echo esc_url($boeken_url); ?>" class="tt-premium-hero__cta tt-premium-hero__cta--primary">
                Boek je taxi
                <?php echo TaxiTheme_Icons::svg('arrow-right', 16); ?>
            </a>
            <?php if ($show_phone) : ?>
                <a href="tel:<?php echo esc_attr($phone_clean); ?>" class="tt-premium-hero__cta tt-premium-hero__cta--ghost">
                    <?php echo TaxiTheme_Icons::svg('phone', 15); ?>
                    <?php echo esc_html($phone); ?>
                </a>
            <?php endif; ?>
            <?php if ($show_whatsapp) : ?>
                <a href="https://wa.me/<?php echo esc_attr($wa_clean); ?>" target="_blank" rel="noopener" class="tt-premium-hero__cta tt-premium-hero__cta--whatsapp">
                    <?php echo TaxiTheme_Icons::svg('whatsapp', 16); ?>
                    WhatsApp
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
