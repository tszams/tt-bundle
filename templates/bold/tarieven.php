<?php
/**
 * Tarieven-pagina — Bold preset.
 * Zelfde structuur maar gebruikt routes-cards (grote gele prijskaarten) ipv de standaard grid,
 * en voegt sticky-call toe.
 */
get_header();

$content = TaxiTheme_Home_Content::all();
$city    = TaxiTheme_Company_Info::get('city');
?>

<?php
$page_id = get_queried_object_id();
$intro   = TaxiTheme_Page_Meta::get_intro($page_id);
?>
<section class="tt-page-header">
    <div class="tt-container">
        <span class="tt-page-header__eyebrow">Tarieven<?php echo $city ? ' · ' . esc_html($city) : ''; ?></span>
        <h1 class="tt-page-header__title"><?php the_title(); ?></h1>
        <?php if ($intro !== '') : ?>
            <div class="tt-page-header__intro">
                <?php foreach (preg_split('/\r?\n\r?\n/', trim($intro)) as $p) :
                    $p = trim($p);
                    if ($p !== '') : ?>
                        <p><?php echo nl2br(esc_html($p)); ?></p>
                    <?php endif;
                endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Bold-signature: routes als grote gele prijskaarten. Altijd tonen op de tarieven-pagina.
$routes = array_values(array_filter($content['routes_items'] ?? [], function ($r) {
    return !empty($r['from']) && !empty($r['to']);
}));
if (!empty($routes)) : ?>
    <section class="tt-section tt-routes-cards">
        <div class="tt-container">
            <div class="tt-routes-cards__head">
                <h2><?php echo esc_html($content['routes_title'] ?: 'Vaste route prijzen'); ?></h2>
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
<?php endif; ?>

<?php
// Extra info-blokken uit page-meta
$sections = TaxiTheme_Page_Meta::get_visible_sections($page_id);
if (!empty($sections)) : ?>
    <section class="tt-section tt-page-sections-wrap">
        <div class="tt-container">
            <?php TaxiTheme_Page_Meta::render_sections($page_id); ?>
        </div>
    </section>
<?php endif; ?>

<?php
get_template_part('templates/shared/contact-cta');
get_template_part('templates/bold/sticky-call');
?>

<?php get_footer(); ?>
