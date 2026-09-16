<?php
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$items = array_values(array_filter(array_map('trim', $content['service_area_items'] ?? []), 'strlen'));
if (empty($content['service_area_enabled']) || empty($items)) return;
?>
<section class="tt-section tt-service-area">
    <div class="tt-container">
        <div class="tt-section__header">
            <?php if (!empty($content['service_area_title'])) : ?>
                <h2><?php echo esc_html($content['service_area_title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($content['service_area_subtitle'])) : ?>
                <p class="tt-muted"><?php echo esc_html($content['service_area_subtitle']); ?></p>
            <?php endif; ?>
        </div>
        <div class="tt-service-area__grid">
            <?php foreach ($items as $city) : ?>
                <div class="tt-service-area__item">
                    <?php echo TaxiTheme_Icons::svg('map-pin', 18); ?>
                    <span><?php echo esc_html($city); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
