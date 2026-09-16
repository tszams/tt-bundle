<?php
/**
 * Services — Simpel-variant.
 * Flat cards met groot icon centraal, titel, tekst en optionele "Lees meer." knop
 * die naar de Diensten-pagina linkt. Staxi/Taxi365-stijl.
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['services_enabled']) || empty($content['services_items'])) return;

$items = array_values(array_filter($content['services_items'], function ($s) {
    return !empty($s['title']) || !empty($s['text']);
}));
if (empty($items)) return;

$show_link       = !empty($content['services_link_enabled']);
$diensten_id     = class_exists('TaxiTheme_Installer') ? TaxiTheme_Installer::get_page_id('diensten') : 0;
$diensten_url    = $diensten_id ? get_permalink($diensten_id) : '';
$default_link_lbl = 'Lees meer.';
?>
<section class="tt-section tt-services-simpel">
    <div class="tt-container">
        <?php if (!empty($content['services_title']) || !empty($content['services_subtitle'])) : ?>
            <div class="tt-services-simpel__header">
                <?php if (!empty($content['services_title'])) : ?>
                    <h2 class="tt-services-simpel__title"><?php echo esc_html($content['services_title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($content['services_subtitle'])) : ?>
                    <p class="tt-services-simpel__sub"><?php echo esc_html($content['services_subtitle']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="tt-services-simpel__grid">
            <?php foreach ($items as $svc) :
                // Link-logica: per-service override > diensten-page fallback > geen link
                $custom_url   = !empty($svc['link_url']) ? $svc['link_url'] : '';
                $custom_label = !empty($svc['link_label']) ? $svc['link_label'] : '';

                if ($custom_url && $custom_label) {
                    $link_url   = $custom_url;
                    $link_label = $custom_label;
                    $render_link = true;
                } elseif ($show_link && $diensten_url) {
                    $link_url   = $diensten_url;
                    $link_label = $default_link_lbl;
                    $render_link = true;
                } else {
                    $render_link = false;
                }
            ?>
                <?php
                // Simpel-homepage gebruikt eigen home_image_id (los van Diensten-page detail image_id)
                $image_id  = (int) ($svc['home_image_id'] ?? 0);
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
                ?>
                <div class="tt-services-simpel__card">
                    <?php if ($image_url) : ?>
                        <div class="tt-services-simpel__image">
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($svc['title']); ?>" loading="lazy">
                        </div>
                    <?php elseif (!empty($svc['icon'])) : ?>
                        <div class="tt-services-simpel__icon">
                            <?php echo TaxiTheme_Icons::svg($svc['icon'], 48); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($svc['title'])) : ?>
                        <h3 class="tt-services-simpel__name"><?php echo esc_html($svc['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($svc['text'])) : ?>
                        <p class="tt-services-simpel__text"><?php echo esc_html($svc['text']); ?></p>
                    <?php endif; ?>
                    <?php if ($render_link) : ?>
                        <a href="<?php echo esc_url($link_url); ?>" class="tt-services-simpel__button">
                            <?php echo esc_html($link_label); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
