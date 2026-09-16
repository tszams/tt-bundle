<?php
if (!defined('ABSPATH')) exit;
?>
<?php while (have_posts()) : the_post();
    $content = get_the_content();
    if (trim(strip_tags($content))) : ?>
        <section class="tt-section tt-extra">
            <div class="tt-container">
                <div class="tt-prose"><?php the_content(); ?></div>
            </div>
        </section>
    <?php endif;
endwhile;
