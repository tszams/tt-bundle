<?php
/**
 * Tarieven-pagina — universeel template (Klassiek default, andere presets erven via CSS scoping).
 * Structuur: header + vaste routes + eigen page content + contact-CTA.
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
// Vaste routes — altijd tonen op de tarieven-pagina, ongeacht de homepage toggle.
$routes = array_values(array_filter($content['routes_items'] ?? [], function ($r) {
    return !empty($r['from']) && !empty($r['to']);
}));
if (!empty($routes)) : ?>
    <section class="tt-section tt-routes tt-routes--page">
        <div class="tt-container">
            <div class="tt-section__header">
                <h2><?php echo esc_html($content['routes_title'] ?: 'Vaste route prijzen'); ?></h2>
                <?php if (!empty($content['routes_subtitle'])) : ?>
                    <p class="tt-muted"><?php echo esc_html($content['routes_subtitle']); ?></p>
                <?php endif; ?>
            </div>
            <div class="tt-routes__grid">
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
                                echo $has_currency ? esc_html($price) : '€ ' . esc_html($price);
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="tt-routes__note">
                Alle prijzen zijn all-in en indicatief — definitieve prijs afhankelijk van route, tijdstip en aantal passagiers.
            </p>
        </div>
    </section>
<?php endif; ?>

<?php
// Extra info-blokken (uurtarief, betaalmethoden, extra kosten, etc)
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
?>

<?php get_footer(); ?>
