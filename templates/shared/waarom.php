<?php
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['waarom_enabled']) || empty($content['waarom_items'])) return;

$image_id  = (int) ($content['waarom_image_id'] ?? 0);
$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
$has_image = (bool) $image_url;
?>
<section class="tt-section tt-waarom <?php echo $has_image ? 'has-image' : ''; ?>">
    <div class="tt-container tt-waarom__inner">
        <?php if ($has_image) : ?>
            <div class="tt-waarom__image">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($content['waarom_title'] ?? ''); ?>" loading="lazy">
            </div>
        <?php endif; ?>
        <div class="tt-waarom__body">
            <?php if (!empty($content['waarom_title'])) : ?>
                <h2><?php echo esc_html($content['waarom_title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($content['waarom_subtitle'])) : ?>
                <p class="tt-muted"><?php echo esc_html($content['waarom_subtitle']); ?></p>
            <?php endif; ?>
            <ul class="tt-check">
                <?php foreach ($content['waarom_items'] as $item) : ?>
                    <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
