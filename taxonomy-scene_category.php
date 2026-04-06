<?php
/**
 * Taxonomy: Categoria de Cena (/categorias/{slug}/)
 *
 * @package HotBoys
 */

get_header();

$term = get_queried_object();
?>

<div class="container">
    <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

    <header class="archive-header">
        <h1 class="archive-title"><?php echo esc_html( $term->name ); ?></h1>
        <?php if ( $term->description ) : ?>
            <p class="archive-description"><?php echo esc_html( $term->description ); ?></p>
        <?php else : ?>
            <p class="archive-description">
                <?php printf( 'Cenas de %s no HotBoys. %d vídeos disponíveis.', esc_html( $term->name ), $term->count ); ?>
            </p>
        <?php endif; ?>
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
            <p>Nenhuma cena encontrada nesta categoria.</p>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
