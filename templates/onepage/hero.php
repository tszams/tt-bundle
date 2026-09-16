<?php
/**
 * Hero — one-page variant (voor "One-page" preset).
 * Centered, pulserend badge, gele highlight in titel, tags rij.
 * Boekingsformulier zit direct hieronder in aparte sectie (anker #boek).
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$name    = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));
$city    = TaxiTheme_Company_Info::get('city');

$eyebrow = '';
if (!empty($content['hero_eyebrow_enabled'])) {
    $eyebrow = !empty($content['hero_eyebrow'])
        ? $content['hero_eyebrow']
        : ($city ? 'Taxi in ' . $city : '24/7 beschikbaar');
} elseif ($city) {
    $eyebrow = 'Taxi in ' . $city;
}

$title    = !empty($content['hero_title'])    ? $content['hero_title']    : $name;
$subtitle = $content['hero_subtitle'] ?? '';

$tags = array_values(array_filter(array_map('trim', $content['hero_tags'] ?? []), 'strlen'));
if (empty($tags)) {
    $tags = array_values(array_filter(array_map('trim', $content['hero_bullets'] ?? []), 'strlen'));
}
?>
<section class="tt-onepage-hero">
    <div class="tt-container tt-onepage-hero__inner">
        <?php if ($eyebrow) : ?>
            <div class="tt-onepage-hero__eyebrow">
                <span class="tt-onepage-hero__dot" aria-hidden="true"></span>
                <?php echo esc_html($eyebrow); ?>
            </div>
        <?php endif; ?>
        <h1 class="tt-onepage-hero__title"><?php echo esc_html($title); ?></h1>
        <?php if ($subtitle) : ?>
            <p class="tt-onepage-hero__sub"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>
        <?php if (!empty($tags)) : ?>
            <div class="tt-onepage-hero__tags">
                <?php foreach ($tags as $tag) : ?>
                    <span class="tt-onepage-hero__tag">
                        <?php echo TaxiTheme_Icons::svg('check', 14); ?>
                        <?php echo esc_html($tag); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section id="boek" class="tt-onepage-booking">
    <div class="tt-container">
        <div class="taxibookingform"></div>
    </div>
</section>
