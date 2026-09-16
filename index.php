<?php get_header(); ?>

<main class="tt-main">
    <div class="tt-container">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article style="margin-bottom:40px;">
                <h1><?php the_title(); ?></h1>
                <div><?php the_content(); ?></div>
            </article>
        <?php endwhile; else : ?>
            <p>Geen content gevonden.</p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
