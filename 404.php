<?php
$tt_phone       = TaxiTheme_Company_Info::get('phone');
$tt_phone_clean = $tt_phone ? preg_replace('/[^0-9+]/', '', $tt_phone) : '';

$tt_quick_links = [];
foreach (['home', 'diensten', 'tarieven', 'contact', 'boeken'] as $tt_role) {
    $tt_pid = TaxiTheme_Installer::get_page_id($tt_role);
    if (!$tt_pid) continue;
    $tt_post = get_post($tt_pid);
    if (!$tt_post || $tt_post->post_status !== 'publish') continue;
    if ($tt_role === 'boeken' && !TaxiTheme_Booking::webapp_page_enabled()) continue;
    $tt_quick_links[] = [
        'label' => get_the_title($tt_pid),
        'url'   => get_permalink($tt_pid),
    ];
}

get_header();
?>

<main class="tt-main tt-404">
    <div class="tt-container tt-404__inner">
        <div class="tt-404__code" aria-hidden="true">404</div>
        <h1 class="tt-404__title">Deze pagina bestaat niet</h1>
        <p class="tt-404__lead">
            De pagina die je zoekt is verplaatst, verwijderd of nooit bestaan.
            <?php if ($tt_phone) : ?>
                Direct een taxi nodig? Bel ons gerust.
            <?php else : ?>
                Ga terug naar de homepage of gebruik één van de snelle links hieronder.
            <?php endif; ?>
        </p>

        <div class="tt-404__actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="tt-cta tt-404__cta">
                Terug naar home
            </a>
            <?php if ($tt_phone) : ?>
                <a href="tel:<?php echo esc_attr($tt_phone_clean); ?>" class="tt-cta tt-cta--ghost tt-404__cta">
                    Bel <?php echo esc_html($tt_phone); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($tt_quick_links)) : ?>
            <div class="tt-404__links">
                <span class="tt-404__links-label">Of ga direct naar:</span>
                <ul class="tt-404__links-list">
                    <?php foreach ($tt_quick_links as $tt_link) : ?>
                        <li>
                            <a href="<?php echo esc_url($tt_link['url']); ?>">
                                <?php echo esc_html($tt_link['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
