<?php
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$items = array_values(array_filter($content['trust_items'] ?? [], function ($it) {
    return !empty($it['text']);
}));

if (empty($content['trust_enabled']) || empty($items)) return;
?>
<section class="tt-trust">
    <div class="tt-container tt-trust__inner">
        <?php foreach ($items as $item) : ?>
            <div class="tt-trust__item">
                <?php echo TaxiTheme_Icons::svg($item['icon'] ?: 'star', 18); ?>
                <span><?php echo esc_html($item['text']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
