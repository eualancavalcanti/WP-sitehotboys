<?php
/**
 * Archive: Listagem de Atores (/atores/)
 *
 * @package HotBoys
 */

get_header();
?>

<div class="container">
    <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

    <header class="archive-header">
        <h1 class="archive-title">Nossos Atores</h1>
        <p class="archive-description">Conheça todos os atores exclusivos HotBoys.</p>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="actors-grid">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php get_template_part( 'template-parts/content-actor-card' ); ?>
            <?php endwhile; ?>
        </div>

        <?php hotboys_pagination(); ?>
    <?php else : ?>
        <div class="no-results">
            <p>Nenhum ator encontrado.</p>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
