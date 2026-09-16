<?php
/**
 * Hero — premium variant "form".
 * Two-column layout: tekst + CTAs links, booking-widget rechts in een subtle
 * premium white card met dunne border en soft shadow. Behoudt editorial
 * typografie en dark gradient bg.
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$name    = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));
$phone   = TaxiTheme_Company_Info::get('phone');
$city    = TaxiTheme_Company_Info::get('city');

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
?>
<section class="tt-premium-hero tt-premium-hero--form <?php echo $has_image ? 'has-image' : 'no-image'; ?>">
    <?php if ($has_image) : ?>
        <div class="tt-premium-hero__bg" style="background-image: url('<?php echo esc_url($image_url); ?>');" aria-hidden="true"></div>
        <div class="tt-premium-hero__overlay" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="tt-container tt-premium-hero__inner tt-premium-hero__inner--form">
        <div class="tt-premium-hero__text">
            <?php if ($eyebrow) : ?>
                <span class="tt-premium-hero__eyebrow"><?php echo esc_html($eyebrow); ?></span>
            <?php endif; ?>
            <h1 class="tt-premium-hero__title"><?php echo esc_html($title); ?></h1>
            <?php if ($subtitle) : ?>
                <p class="tt-premium-hero__sub"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>
            <?php if ($phone) : ?>
                <div class="tt-premium-hero__meta">
                    <?php echo TaxiTheme_Icons::svg('phone', 14); ?>
                    <span>Of bel direct: <a href="tel:<?php echo esc_attr($phone_clean); ?>"><?php echo esc_html($phone); ?></a></span>
                </div>
            <?php endif; ?>
        </div>
        <div class="tt-premium-hero__form">
            <div class="taxibookingform" style="min-height:450px;"></div>
        </div>
    </div>
</section>
