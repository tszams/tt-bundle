<?php
/**
 * Steps — "Hoe werkt het" sectie met genummerde stappen (01, 02, 03) in een card.
 * Editorial, klant-uitleg-stijl. Vooral gebruikt door Premium preset maar universeel bruikbaar.
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['steps_enabled']) || empty($content['steps_items'])) return;

$items = array_values(array_filter($content['steps_items'], function ($s) {
    return !empty($s['title']) || !empty($s['text']);
}));
if (empty($items)) return;
?>
<section class="tt-section tt-steps">
    <div class="tt-container">
        <div class="tt-steps__card">
            <?php if (!empty($content['steps_title'])) : ?>
                <h2 class="tt-steps__title"><?php echo esc_html($content['steps_title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($content['steps_subtitle'])) : ?>
                <p class="tt-steps__subtitle"><?php echo esc_html($content['steps_subtitle']); ?></p>
            <?php endif; ?>
            <div class="tt-steps__grid">
                <?php foreach ($items as $i => $step) : ?>
                    <div class="tt-steps__item">
                        <span class="tt-steps__num"><?php printf('%02d', $i + 1); ?></span>
                        <?php if (!empty($step['title'])) : ?>
                            <h3 class="tt-steps__item-title"><?php echo esc_html($step['title']); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($step['text'])) : ?>
                            <p class="tt-steps__item-text"><?php echo esc_html($step['text']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
