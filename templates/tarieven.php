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
        <?php get_template_part('templates/shared/breadcrumbs'); ?>
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
// ==== Vervoerstypes (Personenauto, Busje, etc) ====
$vehicles = TaxiTheme_Page_Meta::get_visible_tarieven_vehicles($page_id);
if (!empty($vehicles)) : ?>
    <section class="tt-section tt-tv-vehicles">
        <div class="tt-container">
            <div class="tt-tv-vehicles__grid">
                <?php foreach ($vehicles as $veh) :
                    $img_url = $veh['image_id'] ? wp_get_attachment_image_url($veh['image_id'], 'large') : '';
                ?>
                    <article class="tt-tv-vehicle">
                        <?php if ($img_url) : ?>
                            <div class="tt-tv-vehicle__image">
                                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($veh['title']); ?>" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <div class="tt-tv-vehicle__body">
                            <h3 class="tt-tv-vehicle__title"><?php echo esc_html($veh['title']); ?></h3>
                            <?php if ($veh['description']) : ?>
                                <p class="tt-tv-vehicle__desc"><?php echo nl2br(esc_html($veh['description'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="tt-tv-vehicle__rates">
                            <?php $rate_rows = [
                                ['icon' => 'euro',  'label' => 'Starttarief',     'value' => $veh['starttarief']],
                                ['icon' => 'route', 'label' => 'Kilometertarief', 'value' => $veh['kilometertarief']],
                                ['icon' => 'clock', 'label' => 'Tijdstarief',     'value' => $veh['tijdstarief']],
                            ];
                            foreach ($rate_rows as $r) :
                                if ($r['value'] === '') continue;
                            ?>
                                <div class="tt-tv-rate">
                                    <span class="tt-tv-rate__icon"><?php echo TaxiTheme_Icons::svg($r['icon'], 18); ?></span>
                                    <div class="tt-tv-rate__body">
                                        <span class="tt-tv-rate__label"><?php echo esc_html($r['label']); ?></span>
                                        <span class="tt-tv-rate__value"><?php echo esc_html($r['value']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
// ==== Bestemmingen (bv. luchthavens) ====
$destinations = TaxiTheme_Page_Meta::get_tarieven_destinations($page_id);
$dest_items = array_values(array_filter($destinations['items'], function ($it) {
    return !empty($it['label']);
}));
if (!empty($destinations['title']) && !empty($dest_items)) :
    $dest_img = $destinations['image_id'] ? wp_get_attachment_image_url($destinations['image_id'], 'large') : '';
?>
    <section class="tt-section tt-tv-destinations">
        <div class="tt-container">
            <div class="tt-section__header tt-tv-destinations__head">
                <h2><?php echo esc_html($destinations['title']); ?></h2>
                <?php if ($destinations['description']) : ?>
                    <p class="tt-muted"><?php echo nl2br(esc_html($destinations['description'])); ?></p>
                <?php endif; ?>
            </div>
            <article class="tt-tv-destinations__card">
                <?php if ($dest_img) : ?>
                    <div class="tt-tv-destinations__image">
                        <img src="<?php echo esc_url($dest_img); ?>" alt="<?php echo esc_attr($destinations['title']); ?>" loading="lazy">
                    </div>
                <?php endif; ?>
                <ul class="tt-tv-destinations__list">
                    <?php foreach ($dest_items as $it) : ?>
                        <li class="tt-tv-dest-item">
                            <span class="tt-tv-dest-item__icon"><?php echo TaxiTheme_Icons::svg('plane', 18); ?></span>
                            <div class="tt-tv-dest-item__body">
                                <span class="tt-tv-dest-item__label"><?php echo esc_html($it['label']); ?></span>
                                <?php if ($it['price']) : ?>
                                    <span class="tt-tv-dest-item__price"><?php echo esc_html($it['price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>
        </div>
    </section>
<?php endif; ?>

<?php
// ==== Regionale zones (gegroepeerde lokale bestemmingen) ====
$zones = TaxiTheme_Page_Meta::get_tarieven_zones($page_id);
$zone_groups = array_values(array_filter($zones['groups'], function ($g) {
    if (empty($g['title'])) return false;
    return count(array_filter($g['rows'], fn($r) => !empty($r['label']))) > 0;
}));
if (!empty($zones['title']) && !empty($zone_groups)) : ?>
    <section class="tt-section tt-tv-zones">
        <div class="tt-container">
            <div class="tt-section__header">
                <h2><?php echo esc_html($zones['title']); ?></h2>
                <?php if ($zones['subtitle']) : ?>
                    <p class="tt-muted"><?php echo esc_html($zones['subtitle']); ?></p>
                <?php endif; ?>
            </div>
            <div class="tt-tv-zones__grid">
                <?php foreach ($zone_groups as $g) :
                    $group_rows = array_values(array_filter($g['rows'], fn($r) => !empty($r['label'])));
                ?>
                    <div class="tt-tv-zone">
                        <div class="tt-tv-zone__head">
                            <?php if ($g['icon']) : ?>
                                <span class="tt-tv-zone__icon"><?php echo TaxiTheme_Icons::svg($g['icon'], 22); ?></span>
                            <?php endif; ?>
                            <h3 class="tt-tv-zone__title"><?php echo esc_html($g['title']); ?></h3>
                        </div>
                        <ul class="tt-tv-zone__list">
                            <?php foreach ($group_rows as $r) : ?>
                                <li class="tt-tv-zone__row">
                                    <span class="tt-tv-zone__label"><?php echo esc_html($r['label']); ?></span>
                                    <?php if ($r['price']) : ?>
                                        <span class="tt-tv-zone__price"><?php echo esc_html($r['price']); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
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
