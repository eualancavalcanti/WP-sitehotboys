<?php
/**
 * Template: Pagina padrao
 *
 * @package HotBoys
 */

get_header();
?>

<div class="container">
    <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        <article class="page-content">
            <h1 class="page-title"><?php the_title(); ?></h1>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </article>
    <?php endwhile; endif; ?>
</div>

<?php
get_footer();
