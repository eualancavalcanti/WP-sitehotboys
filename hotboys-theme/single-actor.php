<?php
/**
 * Single: Pagina Individual de Ator
 *
 * @package HotBoys
 */

get_header();

if ( have_posts() ) : the_post();

$age          = get_post_meta( get_the_ID(), '_actor_age', true );
$city         = get_post_meta( get_the_ID(), '_actor_city', true );
$instagram    = get_post_meta( get_the_ID(), '_actor_instagram', true );
$twitter      = get_post_meta( get_the_ID(), '_actor_twitter', true );
$external_url = get_post_meta( get_the_ID(), '_actor_external_url', true );
$scene_count  = hotboys_get_actor_scene_count( get_the_ID() );
$paged        = max( 1, get_query_var( 'paged' ) );
$scenes_query = hotboys_get_actor_scenes( get_the_ID(), $paged );
?>

<article class="single-actor" itemscope itemtype="https://schema.org/Person">
    <div class="container">
        <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

        <div class="single-actor__profile">
            <div class="single-actor__photo">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'actor-large', array(
                        'class'    => 'single-actor__img',
                        'alt'      => get_the_title(),
                        'itemprop' => 'image',
                    ) ); ?>
                <?php else : ?>
                    <div class="actor-card__placeholder actor-card__placeholder--large" aria-hidden="true"></div>
                <?php endif; ?>
            </div>

            <div class="single-actor__info">
                <h1 class="single-actor__name" itemprop="name"><?php the_title(); ?></h1>

                <div class="single-actor__meta">
                    <?php if ( $age ) : ?>
                        <span class="meta-item">
                            <strong>Idade:</strong> <?php echo esc_html( $age ); ?> anos
                        </span>
                    <?php endif; ?>

                    <?php if ( $city ) : ?>
                        <span class="meta-item" itemprop="address">
                            <strong>Cidade:</strong> <?php echo esc_html( $city ); ?>
                        </span>
                    <?php endif; ?>

                    <span class="meta-item">
                        <strong>Cenas:</strong> <?php echo esc_html( $scene_count ); ?>
                    </span>
                </div>

                <?php if ( get_the_content() ) : ?>
                    <div class="single-actor__bio" itemprop="description">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>

                <div class="single-actor__links">
                    <?php if ( $instagram ) : ?>
                        <a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener nofollow" class="social-btn social-btn--instagram" itemprop="sameAs">
                            Instagram
                        </a>
                    <?php endif; ?>

                    <?php if ( $twitter ) : ?>
                        <a href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener nofollow" class="social-btn social-btn--twitter" itemprop="sameAs">
                            Twitter / X
                        </a>
                    <?php endif; ?>

                    <?php if ( $external_url ) : ?>
                        <a href="<?php echo esc_url( $external_url ); ?>" target="_blank" rel="nofollow noopener" class="btn btn-primary">
                            Ver Perfil Completo
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ( $scenes_query->have_posts() ) : ?>
            <section class="actor-filmography">
                <h2 class="section-title">Filmografia de <?php the_title(); ?></h2>

                <div class="scenes-grid">
                    <?php while ( $scenes_query->have_posts() ) : $scenes_query->the_post(); ?>
                        <?php get_template_part( 'template-parts/content-scene-card' ); ?>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>

                <?php hotboys_pagination( $scenes_query ); ?>
            </section>
        <?php endif; ?>
    </div>
</article>

<?php
endif;
get_footer();
