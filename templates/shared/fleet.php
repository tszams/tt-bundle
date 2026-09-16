<?php
/**
 * Fleet — "Ons wagenpark" mozaïek-grid.
 * 5 vaste slots: 1 tall (a), 1 wide (b), 2 square (c,d), 1 feature onder (e).
 * Op mobile stackt alles naar full-width.
 * Item kan alleen 'tag' hebben (kleine pill) of title+subtitle (feature overlay).
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['fleet_enabled'])) return;

$items = $content['fleet_items'] ?? [];
// Alleen renderen als er tenminste 1 image is
$has_any = false;
foreach ($items as $it) {
    if (!empty($it['image_id'])) { $has_any = true; break; }
}
if (!$has_any) return;

$positions = ['a', 'b', 'c', 'd', 'e'];
?>
<section class="tt-section tt-fleet">
    <div class="tt-container">
        <?php if (!empty($content['fleet_title']) || !empty($content['fleet_subtitle'])) : ?>
            <div class="tt-section__header">
                <?php if (!empty($content['fleet_title'])) : ?>
                    <h2><?php echo esc_html($content['fleet_title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($content['fleet_subtitle'])) : ?>
                    <p class="tt-muted"><?php echo esc_html($content['fleet_subtitle']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="tt-fleet__grid">
            <?php foreach ($items as $i => $item) :
                if (empty($item['image_id'])) continue;
                $img_url  = wp_get_attachment_image_url((int) $item['image_id'], 'large');
                if (!$img_url) continue;
                $pos       = $positions[$i] ?? 'c';
                $has_feature = !empty($item['title']);
                $alt = $item['title'] ?: ($item['tag'] ?: '');
            ?>
                <figure class="tt-fleet__card tt-fleet__card--<?php echo esc_attr($pos); ?> <?php echo $has_feature ? 'tt-fleet__card--feature' : ''; ?>">
                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy">
                    <div class="tt-fleet__overlay">
                        <?php if ($has_feature) : ?>
                            <h3 class="tt-fleet__title"><?php echo esc_html($item['title']); ?></h3>
                            <?php if (!empty($item['subtitle'])) : ?>
                                <p class="tt-fleet__subtitle"><?php echo esc_html($item['subtitle']); ?></p>
                            <?php endif; ?>
                        <?php elseif (!empty($item['tag'])) : ?>
                            <span class="tt-fleet__tag"><?php echo esc_html($item['tag']); ?></span>
                        <?php endif; ?>
                    </div>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
