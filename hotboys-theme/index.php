<?php
/**
 * Template fallback principal
 *
 * @package HotBoys
 */

get_header();
?>

<div class="container">
    <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

    <h1 class="page-title"><?php bloginfo( 'name' ); ?></h1>

    <?php if ( have_posts() ) : ?>
        <div class="scenes-grid">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php get_template_part( 'template-parts/content-scene-card' ); ?>
            <?php endwhile; ?>
        </div>

        <?php hotboys_pagination(); ?>
    <?php else : ?>
        <div class="no-results">
            <p>Nenhum conteúdo encontrado.</p>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
