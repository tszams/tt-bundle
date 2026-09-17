<?php
/**
 * Services — detailed variant voor de Diensten-pagina.
 *
 * Verticale lijst met ruime dienstblokken. Elk blok heeft:
 *   - Optionele foto naast de tekst
 *   - Titel + optionele prijs-pill
 *   - Beschrijving (short of long_text)
 *   - Kenmerken als checkbullets (optioneel)
 *   - CTA link (optioneel)
 *
 * Zonder foto krijgt de tekst de volledige breedte. Op mobile staat de foto bovenaan.
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$items = array_values(array_filter($content['services_items'] ?? [], function ($s) {
    return !empty($s['title']) || !empty($s['text']);
}));
if (empty($items)) return;
?>
<section class="tt-section tt-services-detail">
    <div class="tt-container tt-services-detail__container">
        <div class="tt-services-detail__list">
            <?php foreach ($items as $svc) :
                $image_id  = (int) ($svc['image_id'] ?? 0);
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';

                $features = array_values(array_filter(
                    array_map('trim', preg_split('/\r?\n/', $svc['features'] ?? '')),
                    'strlen'
                ));

                $long_text = trim($svc['long_text'] ?? '');
                $body_text = $long_text !== '' ? $long_text : ($svc['text'] ?? '');
            ?>
                <article class="tt-services-detail__row <?php echo $image_url ? 'has-image' : 'no-image'; ?>">
                    <?php if ($image_url) : ?>
                        <figure class="tt-services-detail__media">
                            <?php echo wp_get_attachment_image($image_id, 'large', false, [
                                'alt'     => $svc['title'] ?? '',
                                'loading' => 'lazy',
                            ]); ?>
                        </figure>
                    <?php endif; ?>

                    <div class="tt-services-detail__body">
                        <div class="tt-services-detail__head">
                            <?php if (!empty($svc['icon'])) : ?>
                                <div class="tt-services-detail__icon-small">
                                    <?php echo TaxiTheme_Icons::svg($svc['icon'], 20); ?>
                                </div>
                            <?php endif; ?>
                            <div class="tt-services-detail__heading">
                                <?php if (!empty($svc['title'])) : ?>
                                    <h2 class="tt-services-detail__title"><?php echo esc_html($svc['title']); ?></h2>
                                <?php endif; ?>

                                <?php if (!empty($svc['price'])) : ?>
                                    <div class="tt-services-detail__price"><?php echo esc_html($svc['price']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="tt-services-detail__content">
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
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
