<?php
/**
 * FAQ — homepage variant (simple accordion, eerste N vragen).
 *
 * Args:
 *   - limit: aantal te tonen items (override op faq_home_limit setting)
 *   - all:   bool, toon alle items zonder "Alle vragen" link (voor FAQ-pagina)
 *   - show_header: bool, toon title/subtitle (default true)
 *
 * Gebruikt native <details>/<summary> — geen JS, keyboard-toegankelijk.
 */
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['faq_enabled']) || empty($content['faq_items'])) return;

$show_all    = !empty($args['all']);
$show_header = !isset($args['show_header']) || $args['show_header'];
$limit       = $args['limit'] ?? (int) ($content['faq_home_limit'] ?? 5);

// Home items (max 5). Op de FAQ-pagina worden hier ook de faq_page_items achteraan geplakt.
$home_items = array_values(array_filter($content['faq_items'], function ($f) {
    return !empty($f['question']) && !empty($f['answer']);
}));
$page_items = [];
if ($show_all) {
    $page_items = array_values(array_filter($content['faq_page_items'] ?? [], function ($f) {
        return !empty($f['question']) && !empty($f['answer']);
    }));
}
$items = $show_all ? array_merge($home_items, $page_items) : $home_items;
if (empty($items)) return;

$total = count($items);

if (!$show_all) {
    $items = array_slice($items, 0, $limit);
}

$faq_page_id  = function_exists('TaxiTheme_Installer::get_page_id') || class_exists('TaxiTheme_Installer')
    ? TaxiTheme_Installer::get_page_id('faq')
    : 0;
$faq_page_url = $faq_page_id ? get_permalink($faq_page_id) : '';
// Op home: toon "Alle vragen" link als er meer items zijn dan wat we tonen,
// OF als er faq_page_items zijn (die alleen op de FAQ-pagina zichtbaar zijn).
$page_extras_count = 0;
if (!$show_all) {
    $page_extras_count = count(array_filter($content['faq_page_items'] ?? [], function ($f) {
        return !empty($f['question']) && !empty($f['answer']);
    }));
}
$has_more = !$show_all && $faq_page_url && ($total > count($items) || $page_extras_count > 0);
?>
<section class="tt-section tt-faq">
    <div class="tt-container">
        <?php if ($show_header) : ?>
            <div class="tt-section__header">
                <?php if (!empty($content['faq_title'])) : ?>
                    <h2><?php echo esc_html($content['faq_title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($content['faq_subtitle'])) : ?>
                    <p class="tt-muted"><?php echo esc_html($content['faq_subtitle']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="tt-faq__list">
            <?php foreach ($items as $i => $faq) : ?>
                <details class="tt-faq__item"<?php echo $i === 0 && $show_all ? ' open' : ''; ?>>
                    <summary class="tt-faq__question">
                        <span class="tt-faq__q-text"><?php echo esc_html($faq['question']); ?></span>
                        <span class="tt-faq__icon" aria-hidden="true"></span>
                    </summary>
                    <div class="tt-faq__answer">
                        <?php echo nl2br(esc_html($faq['answer'])); ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>

        <?php if ($has_more) : ?>
            <div class="tt-faq__more">
                <a href="<?php echo esc_url($faq_page_url); ?>" class="tt-faq__more-link">
                    Alle veelgestelde vragen
                    <?php echo TaxiTheme_Icons::svg('arrow-right', 14); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
