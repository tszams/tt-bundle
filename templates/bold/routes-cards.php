<?php
/**
 * Routes — grote gele prijskaarten (voor "Bold" preset).
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$routes  = array_values(array_filter($content['routes_items'] ?? [], function ($r) {
    return !empty($r['from']) && !empty($r['to']);
}));
if (empty($content['routes_enabled']) || empty($routes)) return;
?>
<section class="tt-section tt-routes-cards">
    <div class="tt-container">
        <div class="tt-routes-cards__head">
            <?php if (!empty($content['routes_title'])) : ?>
                <h2><?php echo esc_html($content['routes_title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($content['routes_subtitle'])) : ?>
                <p><?php echo esc_html($content['routes_subtitle']); ?></p>
            <?php endif; ?>
        </div>
        <div class="tt-routes-cards__grid">
            <?php foreach ($routes as $r) :
                $price = trim($r['price'] ?? '');
                $has_currency = preg_match('/[€$£]/', $price);
                $price_display = $price ? ($has_currency ? $price : '€ ' . $price) : '';
            ?>
                <div class="tt-routes-cards__card">
                    <div class="tt-routes-cards__price"><?php echo esc_html($price_display); ?></div>
                    <div class="tt-routes-cards__route">
                        <div class="tt-routes-cards__from"><?php echo esc_html($r['from']); ?></div>
                        <div class="tt-routes-cards__arrow">
                            <?php echo TaxiTheme_Icons::svg('arrow-right', 24); ?>
                        </div>
                        <div class="tt-routes-cards__to"><?php echo esc_html($r['to']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
