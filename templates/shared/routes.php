<?php
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$routes = array_values(array_filter($content['routes_items'] ?? [], function ($r) {
    return !empty($r['from']) && !empty($r['to']);
}));
if (empty($content['routes_enabled']) || empty($routes)) return;

// 6+ routes → expliciet 2-koloms grid zodat de rijen naast elkaar lopen
// i.p.v. één lange lijst. Onder de 6: auto-fit blijft (1-3 cols afh. van breedte).
$two_col = count($routes) >= 6;
?>
<section class="tt-section tt-routes">
    <div class="tt-container">
        <div class="tt-section__header">
            <?php if (!empty($content['routes_title'])) : ?>
                <h2><?php echo esc_html($content['routes_title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($content['routes_subtitle'])) : ?>
                <p class="tt-muted"><?php echo esc_html($content['routes_subtitle']); ?></p>
            <?php endif; ?>
        </div>
        <div class="tt-routes__grid <?php echo $two_col ? 'tt-routes__grid--two-col' : ''; ?>">
            <?php foreach ($routes as $r) : ?>
                <div class="tt-routes__card">
                    <div class="tt-routes__route">
                        <span class="tt-routes__from"><?php echo esc_html($r['from']); ?></span>
                        <?php echo TaxiTheme_Icons::svg('arrow-right', 16); ?>
                        <span class="tt-routes__to"><?php echo esc_html($r['to']); ?></span>
                    </div>
                    <?php if (!empty($r['price'])) : ?>
                        <div class="tt-routes__price">
                            <?php
                            $price = trim($r['price']);
                            $has_currency = preg_match('/[€$£]/', $price);
                            ?>
                            <?php echo $has_currency ? esc_html($price) : '€ ' . esc_html($price); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
