<?php
/**
 * Hero-context voor de "Klassiek" preset.
 *
 * Extract alle variables + herbruikbare render-closures die door de 3 hero
 * varianten (split, stacked, centered) worden gebruikt. Includeer via:
 *
 *   $ctx = require __DIR__ . '/_context.php';
 *
 * Zo blijft de setup op één plek en zijn de variant-files puur layout.
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

$hero_title    = !empty($content['hero_title'])    ? $content['hero_title']    : $name;
$hero_subtitle = $content['hero_subtitle'] ?? '';

$show_title    = !empty($content['hero_title_enabled']);
$show_subtitle = !empty($content['hero_subtitle_enabled']);
$show_phone    = !empty($content['hero_phone_enabled']) && $phone;
$wa_number     = !empty($content['hero_whatsapp_number']) ? $content['hero_whatsapp_number'] : $phone;
$wa_clean      = $wa_number ? preg_replace('/[^0-9]/', '', $wa_number) : '';
$show_whatsapp = !empty($content['hero_whatsapp_enabled']) && $wa_clean;
$bullets       = array_values(array_filter(array_map('trim', $content['hero_bullets'] ?? []), 'strlen'));
$show_bullets  = !empty($content['hero_bullets_enabled']) && !empty($bullets);
$tags          = array_values(array_filter(array_map('trim', $content['hero_tags'] ?? []), 'strlen'));
$show_tags     = !empty($content['hero_tags_enabled']) && !empty($tags);

$render_text_block = function () use ($eyebrow, $hero_title, $hero_subtitle, $show_title, $show_subtitle, $show_bullets, $bullets, $show_tags, $tags) { ?>
    <?php if ($eyebrow) : ?>
        <span class="tt-eyebrow"><?php echo esc_html($eyebrow); ?></span>
    <?php endif; ?>
    <?php if ($show_title) : ?>
        <h1 class="tt-hero__title"><?php echo esc_html($hero_title); ?></h1>
    <?php endif; ?>
    <?php if ($show_subtitle) : ?>
        <p class="tt-hero__tagline"><?php echo esc_html($hero_subtitle); ?></p>
    <?php endif; ?>
    <?php if ($show_bullets) : ?>
        <ul class="tt-hero__bullets tt-hero__bullets--list">
            <?php foreach ($bullets as $b) : ?>
                <li><?php echo esc_html($b); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($show_tags) : ?>
        <ul class="tt-hero__tags">
            <?php foreach ($tags as $t) : ?>
                <li><?php echo esc_html($t); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php };

$render_phone_cta = function () use ($show_phone, $show_whatsapp, $phone_clean, $wa_clean) {
    if (!$show_phone && !$show_whatsapp) return;
    ?>
    <div class="tt-hero__actions">
        <?php if ($show_phone) : ?>
            <a href="tel:<?php echo esc_attr($phone_clean); ?>" class="tt-cta tt-cta--ghost">Bel ons</a>
        <?php endif; ?>
        <?php if ($show_whatsapp) : ?>
            <a href="https://wa.me/<?php echo esc_attr($wa_clean); ?>" target="_blank" rel="noopener" class="tt-cta tt-cta--whatsapp">
                <?php echo TaxiTheme_Icons::svg('whatsapp', 18); ?>
                WhatsApp
            </a>
        <?php endif; ?>
    </div>
    <?php
};

$booking_form_html = '<div class="taxibookingform" style="min-height:450px;"></div>';

return [
    'content'           => $content,
    'boeken_url'        => $boeken_url,
    'phone_clean'       => $phone_clean,
    'wa_clean'          => $wa_clean,
    'show_phone'        => $show_phone,
    'show_whatsapp'     => $show_whatsapp,
    'render_text_block' => $render_text_block,
    'render_phone_cta'  => $render_phone_cta,
    'booking_form_html' => $booking_form_html,
];
