<?php
$tt_name     = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));
$tt_tagline  = get_bloginfo('description'); // WP's Site Tagline (Settings > Algemeen)
$tt_phone    = TaxiTheme_Company_Info::get('phone');
$tt_email    = TaxiTheme_Company_Info::get('email');
$tt_address  = TaxiTheme_Company_Info::get('address');
$tt_postcode = TaxiTheme_Company_Info::get('postcode');
$tt_city     = TaxiTheme_Company_Info::get('city');
$tt_kvk      = TaxiTheme_Company_Info::get('kvk');

$tt_phone_clean = $tt_phone ? preg_replace('/[^0-9+]/', '', $tt_phone) : '';
$tt_hours_lines = TaxiTheme_Company_Info::formatted_hours();
$tt_logo_url    = TaxiTheme_Company_Info::logo_url();

$tt_footer_links = [
    'home'     => 'Home',
    'diensten' => 'Diensten',
    'tarieven' => 'Tarieven',
    'over-ons' => 'Over ons',
    'boeken'   => 'Boeken',
    'contact'  => 'Contact',
];
?>
<footer class="tt-footer">
    <div class="tt-container">
        <div class="tt-footer__grid">

            <!-- Brand -->
            <div class="tt-footer__col tt-footer__col--brand">
                <?php if ($tt_logo_url) : ?>
                    <img src="<?php echo esc_url($tt_logo_url); ?>" alt="<?php echo esc_attr($tt_name); ?>" class="tt-footer__logo">
                <?php else : ?>
                    <div class="tt-footer__brand"><?php echo esc_html($tt_name); ?></div>
                <?php endif; ?>
                <?php if ($tt_tagline) : ?>
                    <p class="tt-footer__tagline"><?php echo esc_html($tt_tagline); ?></p>
                <?php endif; ?>
            </div>

            <!-- Contact -->
            <?php if ($tt_phone || $tt_email || $tt_address) : ?>
                <div class="tt-footer__col">
                    <h4 class="tt-footer__heading">Contact</h4>
                    <ul class="tt-footer__list">
                        <?php if ($tt_phone) : ?>
                            <li>
                                <?php echo TaxiTheme_Icons::svg('phone', 16); ?>
                                <a href="tel:<?php echo esc_attr($tt_phone_clean); ?>"><?php echo esc_html($tt_phone); ?></a>
                            </li>
                        <?php endif; ?>
                        <?php if ($tt_email) : ?>
                            <li>
                                <?php echo TaxiTheme_Icons::svg('mail', 16); ?>
                                <a href="mailto:<?php echo esc_attr($tt_email); ?>"><?php echo esc_html($tt_email); ?></a>
                            </li>
                        <?php endif; ?>
                        <?php if ($tt_address || $tt_city) : ?>
                            <li class="tt-footer__list-addr">
                                <?php echo TaxiTheme_Icons::svg('map-pin', 16); ?>
                                <span>
                                    <?php if ($tt_address) : ?>
                                        <?php echo esc_html($tt_address); ?><br>
                                    <?php endif; ?>
                                    <?php echo esc_html(trim($tt_postcode . ' ' . $tt_city)); ?>
                                </span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Snelle links -->
            <div class="tt-footer__col">
                <h4 class="tt-footer__heading">Snelle links</h4>
                <ul class="tt-footer__list">
                    <?php foreach ($tt_footer_links as $role => $label) :
                        $pid = TaxiTheme_Installer::get_page_id($role);
                        if (!$pid) continue;
                        $post = get_post($pid);
                        if (!$post || $post->post_status !== 'publish') continue;
                    ?>
                        <li>
                            <a href="<?php echo esc_url(get_permalink($pid)); ?>"><?php echo esc_html($label); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Openingstijden -->
            <?php if (!empty($tt_hours_lines)) : ?>
                <div class="tt-footer__col">
                    <h4 class="tt-footer__heading">Beschikbaarheid</h4>
                    <ul class="tt-footer__list">
                        <li>
                            <?php echo TaxiTheme_Icons::svg('clock', 16); ?>
                            <span>
                                <?php foreach ($tt_hours_lines as $i => $tt_line) : ?>
                                    <?php if ($i > 0) echo '<br>'; ?>
                                    <?php echo esc_html($tt_line); ?>
                                <?php endforeach; ?>
                            </span>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

        </div>

        <div class="tt-footer__bottom">
            <div>&copy; <?php echo date('Y'); ?> <?php echo esc_html($tt_name); ?>. Alle rechten voorbehouden.</div>
            <?php
            // Juridische links (privacybeleid + algemene voorwaarden) — alleen als de
            // pagina bestaat en gepubliceerd is. Menu_default=false, dus staan niet
            // automatisch in de hoofdnav; hier expliciet.
            $tt_legal_links = ['privacy' => 'Privacybeleid', 'voorwaarden' => 'Voorwaarden'];
            $tt_legal_html = [];
            foreach ($tt_legal_links as $tt_legal_role => $tt_legal_label) {
                $tt_legal_pid = TaxiTheme_Installer::get_page_id($tt_legal_role);
                if (!$tt_legal_pid) continue;
                $tt_legal_post = get_post($tt_legal_pid);
                if (!$tt_legal_post || $tt_legal_post->post_status !== 'publish') continue;
                $tt_legal_html[] = '<a href="' . esc_url(get_permalink($tt_legal_pid)) . '">' . esc_html($tt_legal_label) . '</a>';
            }
            if (!empty($tt_legal_html)) : ?>
                <div class="tt-footer__legal"><?php echo implode(' <span class="tt-footer__legal-sep">·</span> ', $tt_legal_html); ?></div>
            <?php endif; ?>
            <?php if ($tt_kvk) : ?>
                <div>KvK: <?php echo esc_html($tt_kvk); ?></div>
            <?php endif; ?>
        </div>
    </div>
</footer>
<?php if (class_exists('TaxiTheme_Preset') && TaxiTheme_Preset::current() === 'onepage') : ?>
<script>
(function () {
    var bar = document.querySelector('.tt-onepage-progress__bar');
    if (!bar) return;
    function update() {
        var total = document.documentElement.scrollHeight - window.innerHeight;
        var pct = total > 0 ? (window.scrollY / total) * 100 : 0;
        bar.style.width = pct + '%';
    }
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
})();
</script>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
