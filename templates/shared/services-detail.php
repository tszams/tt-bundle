<?php
/**
 * Services — detailed variant voor de Diensten-pagina.
 *
 * Rendert elk item als grote alternerende image/text row met:
 *   - Icon + titel + prijs-label
 *   - Uitgebreide beschrijving (paragrafen)
 *   - Kenmerken als checkbullets
 *   - CTA link (optioneel)
 *
 * Data komt uit services_items (uit homepage-editor). Detail-velden zijn optioneel —
 * als een service ze niet heeft, valt 'ie terug op de "short" info.
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$items = array_values(array_filter($content['services_items'] ?? [], function ($s) {
    return !empty($s['title']) || !empty($s['text']);
}));
if (empty($items)) return;
?>
<section class="tt-section tt-services-detail">
    <div class="tt-container">
        <?php foreach ($items as $i => $svc) :
            $image_id  = (int) ($svc['image_id'] ?? 0);
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
            $flip      = ($i % 2 === 1);

            $features = array_values(array_filter(
                array_map('trim', preg_split('/\r?\n/', $svc['features'] ?? '')),
                'strlen'
            ));

            $long_text = trim($svc['long_text'] ?? '');
            $body_text = $long_text !== '' ? $long_text : ($svc['text'] ?? '');
        ?>
            <div class="tt-services-detail__row <?php echo $flip ? 'is-flipped' : ''; ?>">
                <div class="tt-services-detail__media <?php echo $image_url ? '' : 'is-icon-only'; ?>">
                    <?php if ($image_url) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($svc['title']); ?>" loading="lazy">
                    <?php else : ?>
                        <div class="tt-services-detail__icon-large">
                            <?php echo TaxiTheme_Icons::svg($svc['icon'] ?: 'car', 64); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tt-services-detail__body">
                    <?php if (!empty($svc['icon']) && $image_url) : ?>
                        <div class="tt-services-detail__icon-small">
                            <?php echo TaxiTheme_Icons::svg($svc['icon'], 22); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($svc['title'])) : ?>
                        <h2 class="tt-services-detail__title"><?php echo esc_html($svc['title']); ?></h2>
                    <?php endif; ?>

                    <?php if (!empty($svc['price'])) : ?>
                        <div class="tt-services-detail__price"><?php echo esc_html($svc['price']); ?></div>
                    <?php endif; ?>

                    <?php if ($body_text !== '') :
                        foreach (preg_split('/\r?\n\r?\n/', trim($body_text)) as $p) :
                            $p = trim($p);
                            if ($p !== '') : ?>
                                <p class="tt-services-detail__text"><?php echo nl2br(esc_html($p)); ?></p>
                            <?php endif;
                        endforeach;
                    endif; ?>

                    <?php if (!empty($features)) : ?>
                        <ul class="tt-services-detail__features">
                            <?php foreach ($features as $f) : ?>
                                <li><?php echo esc_html($f); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($svc['link_url']) && !empty($svc['link_label'])) : ?>
                        <a href="<?php echo esc_url($svc['link_url']); ?>" class="tt-services-detail__link">
                            <?php echo esc_html($svc['link_label']); ?>
                            <?php echo TaxiTheme_Icons::svg('arrow-right', 16); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
