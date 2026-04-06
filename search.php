<?php
/**
 * Search Results
 *
 * @package HotBoys
 */

get_header();

// Separar resultados por tipo
$scenes = array();
$actors = array();
$others = array();

if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        $type = get_post_type();
        if ( $type === 'scene' ) {
            $scenes[] = get_the_ID();
        } elseif ( $type === 'actor' ) {
            $actors[] = get_the_ID();
        } else {
            $others[] = get_the_ID();
        }
    }
    rewind_posts();
}
?>

<div class="container">
    <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

    <header class="archive-header">
        <h1 class="archive-title">Resultados para: &ldquo;<?php echo esc_html( get_search_query() ); ?>&rdquo;</h1>
        <p class="archive-description">
            <?php
            printf(
                '%d resultado(s) encontrado(s)',
                (int) $wp_query->found_posts
            );
            ?>
        </p>
    </header>

    <?php if ( have_posts() ) : ?>

        <?php if ( ! empty( $scenes ) ) : ?>
            <section class="search-section">
                <h2 class="section-title">Cenas</h2>
                <div class="scenes-grid">
                    <?php
                    while ( have_posts() ) : the_post();
                        if ( get_post_type() === 'scene' ) :
                            get_template_part( 'template-parts/content-scene-card' );
                        endif;
                    endwhile;
                    rewind_posts();
                    ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ( ! empty( $actors ) ) : ?>
            <section class="search-section">
                <h2 class="section-title">Atores</h2>
                <div class="actors-grid">
                    <?php
                    while ( have_posts() ) : the_post();
                        if ( get_post_type() === 'actor' ) :
                            get_template_part( 'template-parts/content-actor-card' );
                        endif;
                    endwhile;
                    rewind_posts();
                    ?>
                </div>
            </section>
        <?php endif; ?>

        <?php hotboys_pagination(); ?>

    <?php else : ?>
        <div class="no-results">
            <h2>Nenhum resultado encontrado</h2>
            <p>Tente buscar com outros termos.</p>
            <?php get_search_form(); ?>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
