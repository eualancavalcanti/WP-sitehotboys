<?php
/**
 * Archive: Listagem de Cenas (/cenas/)
 *
 * @package HotBoys
 */

get_header();
?>

<div class="container">
    <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

    <header class="archive-header">
        <h1 class="archive-title">Todas as Cenas</h1>
        <p class="archive-description">Explore nosso catálogo completo de cenas exclusivas HotBoys.</p>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="scenes-grid">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php get_template_part( 'template-parts/content-scene-card' ); ?>
            <?php endwhile; ?>
        </div>

        <?php hotboys_pagination(); ?>
    <?php else : ?>
        <div class="no-results">
            <p>Nenhuma cena encontrada.</p>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
