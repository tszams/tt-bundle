<?php
/**
 * Features — alternating image/text rows (Premium preset signature).
 * Elke row: even index = image links / tekst rechts, oneven = tekst links / image rechts.
 * Items zonder titel EN tekst worden overgeslagen.
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['features_enabled']) || empty($content['features_items'])) return;

$items = array_values(array_filter($content['features_items'], function ($f) {
    return !empty($f['title']) || !empty($f['text']);
}));
if (empty($items)) return;
?>
<section class="tt-section tt-features">
    <div class="tt-container">
        <?php foreach ($items as $i => $feat) :
            $image_id  = (int) ($feat['image_id'] ?? 0);
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
            $flip      = ($i % 2 === 1);
        ?>
            <div class="tt-features__row <?php echo $flip ? 'is-flipped' : ''; ?>">
                <div class="tt-features__image <?php echo $image_url ? '' : 'is-empty'; ?>">
                    <?php if ($image_url) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($feat['title']); ?>" loading="lazy">
                    <?php else : ?>
                        <div class="tt-features__placeholder" aria-hidden="true">
                            <?php echo TaxiTheme_Icons::svg('image', 48); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="tt-features__text">
                    <?php if (!empty($feat['eyebrow'])) : ?>
                        <span class="tt-features__eyebrow">
                            <?php printf('%02d', $i + 1); ?> · <?php echo esc_html(strtoupper($feat['eyebrow'])); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($feat['title'])) : ?>
                        <h2 class="tt-features__title"><?php echo esc_html($feat['title']); ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($feat['text'])) : ?>
                        <p class="tt-features__body"><?php echo esc_html($feat['text']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($feat['link_url']) && !empty($feat['link_label'])) : ?>
                        <a href="<?php echo esc_url($feat['link_url']); ?>" class="tt-features__link">
                            <?php echo esc_html($feat['link_label']); ?>
                            <?php echo TaxiTheme_Icons::svg('arrow-right', 16); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
