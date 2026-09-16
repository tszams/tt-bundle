<?php
/**
 * Reviews — grid van klantreviews met sterren + optionele Google CTA.
 * Handmatig ingevuld via home-editor. Items zonder naam OF tekst worden overgeslagen.
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['reviews_enabled'])) return;

$items = array_values(array_filter($content['reviews_items'] ?? [], function ($r) {
    return !empty($r['name']) && !empty($r['text']);
}));
if (empty($items)) return;

$has_cta = !empty($content['reviews_google_url']) && !empty($content['reviews_cta_label']);
?>
<section class="tt-section tt-reviews">
    <div class="tt-container">
        <?php if (!empty($content['reviews_title']) || !empty($content['reviews_subtitle'])) : ?>
            <div class="tt-section__header">
                <?php if (!empty($content['reviews_title'])) : ?>
                    <h2><?php echo esc_html($content['reviews_title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($content['reviews_subtitle'])) : ?>
                    <p class="tt-muted"><?php echo esc_html($content['reviews_subtitle']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="tt-reviews__grid">
            <?php foreach ($items as $review) :
                $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
            ?>
                <article class="tt-reviews__card">
                    <div class="tt-reviews__stars" aria-label="<?php echo esc_attr($rating . ' van 5 sterren'); ?>">
                        <?php for ($i = 0; $i < 5; $i++) : ?>
                            <span class="tt-reviews__star <?php echo $i < $rating ? 'is-filled' : ''; ?>">
                                <?php echo TaxiTheme_Icons::svg('star', 16); ?>
                            </span>
                        <?php endfor; ?>
                    </div>
                    <p class="tt-reviews__text">&ldquo;<?php echo esc_html($review['text']); ?>&rdquo;</p>
                    <div class="tt-reviews__meta">
                        <span class="tt-reviews__name"><?php echo esc_html($review['name']); ?></span>
                        <?php if (!empty($review['date'])) : ?>
                            <span class="tt-reviews__date"><?php echo esc_html($review['date']); ?></span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($has_cta) : ?>
            <div class="tt-reviews__cta-wrap">
                <a href="<?php echo esc_url($content['reviews_google_url']); ?>" target="_blank" rel="noopener" class="tt-reviews__cta">
                    <?php echo esc_html($content['reviews_cta_label']); ?>
                    <?php echo TaxiTheme_Icons::svg('arrow-right', 14); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
