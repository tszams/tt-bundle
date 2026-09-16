<?php
/**
 * Klassiek hero — variant "Stacked".
 * Layout: tekst gecentreerd boven, booking-form full-width eronder.
 */
if (!defined('ABSPATH')) exit;

$ctx = require __DIR__ . '/_context.php';
?>
<section class="tt-hero tt-hero--stacked">
    <div class="tt-container tt-hero__inner">
        <div class="tt-hero__text tt-hero__text--center">
            <?php $ctx['render_text_block'](); $ctx['render_phone_cta'](); ?>
        </div>
        <p class="tt-hero__form-heading tt-hero__form-heading--stacked">Boek uw taxi hieronder</p>
        <div class="tt-hero__form tt-hero__form--wide"><?php echo $ctx['booking_form_html']; ?></div>
    </div>
</section>
