<?php
/**
 * Spotlight — één prominente highlight-kaart.
 *
 * Twee stijlen (via spotlight_style):
 *   - subtle: rustige card voor dienst-highlight (icon + eyebrow + title + text + link)
 *   - promo:  accent-band met discount-display (bijv. "10%" korting-aanbieding)
 *
 * Universeel — elke preset stylet via .tt-preset-{slug} .tt-spotlight scope.
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['spotlight_enabled'])) return;
if (empty($content['spotlight_title']) && empty($content['spotlight_text'])) return;

$style       = ($content['spotlight_style'] ?? 'subtle') === 'promo' ? 'promo' : 'subtle';
$has_link    = !empty($content['spotlight_link_url']) && !empty($content['spotlight_link_label']);
$discount    = trim($content['spotlight_discount'] ?? '');
// Als klant alleen een getal typt (bijv. "10"), automatisch "%" erachter plakken.
if ($discount !== '' && preg_match('/^\d+([.,]\d+)?$/', $discount)) {
    $discount .= '%';
}
$show_disc   = ($style === 'promo') && $discount !== '';
?>
<section class="tt-section tt-spotlight tt-spotlight--<?php echo esc_attr($style); ?>">
    <div class="tt-container">
        <div class="tt-spotlight__card">
            <div class="tt-spotlight__icon">
                <?php echo TaxiTheme_Icons::svg($content['spotlight_icon'] ?: 'car', 22); ?>
            </div>
            <div class="tt-spotlight__body">
                <?php if (!empty($content['spotlight_eyebrow'])) : ?>
                    <span class="tt-spotlight__eyebrow"><?php echo esc_html(strtoupper($content['spotlight_eyebrow'])); ?></span>
                <?php endif; ?>
                <?php if (!empty($content['spotlight_title'])) : ?>
                    <h2 class="tt-spotlight__title"><?php echo esc_html($content['spotlight_title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($content['spotlight_text'])) : ?>
                    <p class="tt-spotlight__text"><?php echo esc_html($content['spotlight_text']); ?></p>
                <?php endif; ?>
                <?php if ($has_link) : ?>
                    <a href="<?php echo esc_url($content['spotlight_link_url']); ?>" class="tt-spotlight__link">
                        <?php echo esc_html($content['spotlight_link_label']); ?>
                        <?php echo TaxiTheme_Icons::svg('arrow-right', 16); ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($show_disc) : ?>
                <div class="tt-spotlight__discount" aria-hidden="false">
                    <span class="tt-spotlight__discount-value"><?php echo esc_html($discount); ?></span>
                    <span class="tt-spotlight__discount-label">korting</span>
                </div>
            <?php else : ?>
                <div class="tt-spotlight__decoration" aria-hidden="true">
                    <?php echo TaxiTheme_Icons::svg($content['spotlight_icon'] ?: 'car', 200); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
