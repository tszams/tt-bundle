<?php
/**
 * Services / diensten sectie — icon-cards met titel, beschrijving en optionele link.
 * Werkt in alle presets (base styling in style.css, per-preset overrides in preset CSS).
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['services_enabled']) || empty($content['services_items'])) return;

$items = array_values(array_filter($content['services_items'], function ($s) {
    return !empty($s['title']) || !empty($s['text']);
}));
if (empty($items)) return;
?>
<section class="tt-section tt-services">
    <div class="tt-container">
        <div class="tt-section__header">
            <?php if (!empty($content['services_title'])) : ?>
                <h2><?php echo esc_html($content['services_title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($content['services_subtitle'])) : ?>
                <p class="tt-muted"><?php echo esc_html($content['services_subtitle']); ?></p>
            <?php endif; ?>
        </div>
        <div class="tt-services__grid">
            <?php foreach ($items as $svc) : ?>
                <div class="tt-services__card">
                    <?php if (!empty($svc['icon'])) : ?>
                        <div class="tt-services__icon"><?php echo TaxiTheme_Icons::svg($svc['icon'], 22); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($svc['title'])) : ?>
                        <h3 class="tt-services__title"><?php echo esc_html($svc['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($svc['text'])) : ?>
                        <p class="tt-services__text"><?php echo esc_html($svc['text']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($svc['link_url']) && !empty($svc['link_label'])) : ?>
                        <a href="<?php echo esc_url($svc['link_url']); ?>" class="tt-services__link">
                            <?php echo esc_html($svc['link_label']); ?>
                            <?php echo TaxiTheme_Icons::svg('arrow-right', 16); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
