<?php
/**
 * Klassiek hero — variant "Centered".
 * Layout: alleen tekst + knoppen (geen booking-form). Voor sites die het form
 * pas verderop willen tonen of enkel op /boeken.
 */
if (!defined('ABSPATH')) exit;

$ctx = require __DIR__ . '/_context.php';
?>
<section class="tt-hero tt-hero--centered">
    <div class="tt-container tt-hero__inner">
        <div class="tt-hero__text tt-hero__text--center">
            <?php $ctx['render_text_block'](); ?>
            <div class="tt-hero__actions tt-hero__actions--center">
                <a href="<?php echo esc_url($ctx['boeken_url']); ?>" class="tt-cta">Boek direct →</a>
                <?php if ($ctx['show_phone']) : ?>
                    <a href="tel:<?php echo esc_attr($ctx['phone_clean']); ?>" class="tt-cta tt-cta--ghost">Bel ons</a>
                <?php endif; ?>
                <?php if ($ctx['show_whatsapp']) : ?>
                    <a href="https://wa.me/<?php echo esc_attr($ctx['wa_clean']); ?>" target="_blank" rel="noopener" class="tt-cta tt-cta--whatsapp">
                        <?php echo TaxiTheme_Icons::svg('whatsapp', 18); ?>
                        WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
