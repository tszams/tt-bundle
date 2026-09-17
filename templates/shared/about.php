<?php
/**
 * About / Over ons — verhaal-blok met optionele image + CTA.
 * Universeel bruikbaar in alle presets. Layout:
 *   - Met image: 2-col (tekst + visueel kader)
 *   - Zonder image: editorial kaart met kop en verhaal naast elkaar
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['about_enabled'])) return;
if (empty($content['about_title']) && empty($content['about_text'])) return;

$image_id  = (int) ($content['about_image_id'] ?? 0);
$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
$has_image = (bool) $image_url;

$has_cta = !empty($content['about_cta_url']) && !empty($content['about_cta_label']);

$paragraphs = [];
$raw_text = trim($content['about_text'] ?? '');
if ($raw_text !== '') {
    foreach (preg_split('/\r?\n\r?\n/', $raw_text) as $chunk) {
        $chunk = trim($chunk);
        if ($chunk !== '') $paragraphs[] = $chunk;
    }
}
?>
<section class="tt-section tt-about <?php echo $has_image ? 'has-image' : 'no-image'; ?>">
    <div class="tt-container tt-about__inner">
        <div class="tt-about__body">
            <div class="tt-about__heading">
                <?php if (!empty($content['about_subtitle'])) : ?>
                    <span class="tt-about__eyebrow"><?php echo esc_html($content['about_subtitle']); ?></span>
                <?php endif; ?>
                <?php if (!empty($content['about_title'])) : ?>
                    <h2 class="tt-about__title"><?php echo esc_html($content['about_title']); ?></h2>
                <?php endif; ?>
            </div>
            <div class="tt-about__copy">
                <?php foreach ($paragraphs as $p) : ?>
                    <p class="tt-about__text"><?php echo nl2br(esc_html($p)); ?></p>
                <?php endforeach; ?>
                <?php if ($has_cta) : ?>
                    <a href="<?php echo esc_url($content['about_cta_url']); ?>" class="tt-about__cta tt-cta">
                        <span><?php echo esc_html($content['about_cta_label']); ?></span>
                        <span class="tt-about__cta-arrow" aria-hidden="true">&rarr;</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($has_image) : ?>
            <figure class="tt-about__image">
                <?php echo wp_get_attachment_image($image_id, 'large', false, [
                    'alt'     => $content['about_title'] ?? '',
                    'loading' => 'lazy',
                ]); ?>
            </figure>
        <?php endif; ?>
    </div>
</section>
