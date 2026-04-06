<?php
/**
 * 404 - Pagina nao encontrada
 *
 * @package HotBoys
 */

get_header();
?>

<div class="container">
    <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

    <div class="error-404">
        <h1 class="error-404__title">404</h1>
        <h2 class="error-404__subtitle">Página não encontrada</h2>
        <p class="error-404__text">A página que você está procurando não existe ou foi movida.</p>

        <div class="error-404__search">
            <?php get_search_form(); ?>
        </div>

        <div class="error-404__links">
            <h3>Talvez você esteja procurando:</h3>
            <ul>
                <li><a href="<?php echo esc_url( get_post_type_archive_link( 'scene' ) ); ?>">Todas as Cenas</a></li>
                <li><a href="<?php echo esc_url( get_post_type_archive_link( 'actor' ) ); ?>">Nossos Atores</a></li>
                <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Página Inicial</a></li>
            </ul>
        </div>
    </div>
</div>

<?php
get_footer();
