<?php
/**
 * Single: Pagina Individual de Cena
 * Template mais importante para SEO
 *
 * @package HotBoys
 */

get_header();

if ( have_posts() ) : the_post();

$duration      = get_post_meta( get_the_ID(), '_scene_duration', true );
$release_date  = get_post_meta( get_the_ID(), '_scene_release_date', true );
$external_url  = get_post_meta( get_the_ID(), '_scene_external_url', true );
$trailer_url   = get_post_meta( get_the_ID(), '_scene_trailer_url', true );
$quality       = get_post_meta( get_the_ID(), '_scene_quality', true );
$actors        = hotboys_get_scene_actors();
$categories    = get_the_terms( get_the_ID(), 'scene_category' );
$tags          = get_the_terms( get_the_ID(), 'scene_tag' );
?>

<article class="single-scene" itemscope itemtype="https://schema.org/VideoObject">
    <div class="container">
        <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

        <header class="single-scene__header">
            <h1 class="single-scene__title" itemprop="name"><?php the_title(); ?></h1>

            <div class="single-scene__meta">
                <?php if ( $duration ) : ?>
                    <span class="meta-item meta-duration">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span itemprop="duration" content="<?php echo esc_attr( hotboys_duration_to_iso8601( $duration ) ); ?>"><?php echo esc_html( $duration ); ?></span>
                    </span>
                <?php endif; ?>

                <?php if ( $quality ) : ?>
                    <span class="meta-item meta-quality"><?php echo esc_html( $quality ); ?></span>
                <?php endif; ?>

                <?php if ( $release_date ) : ?>
                    <time class="meta-item meta-date" datetime="<?php echo esc_attr( $release_date ); ?>" itemprop="uploadDate">
                        <?php echo esc_html( date_i18n( 'd \d\e F \d\e Y', strtotime( $release_date ) ) ); ?>
                    </time>
                <?php endif; ?>
            </div>
        </header>

        <div class="single-scene__content">
            <div class="single-scene__media">
                <?php if ( $trailer_url ) : ?>
                    <div class="scene-player">
                        <iframe src="<?php echo esc_url( $trailer_url ); ?>" allowfullscreen loading="lazy" title="Trailer - <?php the_title_attribute(); ?>"></iframe>
                    </div>
                <?php elseif ( has_post_thumbnail() ) : ?>
                    <div class="scene-thumbnail">
                        <?php the_post_thumbnail( 'scene-large', array(
                            'class'    => 'single-scene__img',
                            'alt'      => get_the_title(),
                            'itemprop' => 'thumbnailUrl',
                        ) ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $external_url ) : ?>
                    <div class="scene-cta">
                        <a href="<?php echo esc_url( $external_url ); ?>" class="btn btn-primary btn-large" target="_blank" rel="nofollow noopener">
                            Assistir Completo
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="single-scene__details">
                <?php if ( get_the_content() ) : ?>
                    <div class="scene-description" itemprop="description">
                        <?php the_content(); ?>
                    </div>
                <?php elseif ( has_excerpt() ) : ?>
                    <div class="scene-description" itemprop="description">
                        <?php the_excerpt(); ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $actors ) ) : ?>
                    <div class="scene-actors">
                        <h2 class="section-subtitle">Atores</h2>
                        <div class="scene-actors__list">
                            <?php foreach ( $actors as $actor ) : ?>
                                <a href="<?php echo esc_url( get_permalink( $actor->ID ) ); ?>" class="scene-actor-chip" title="Ver perfil de <?php echo esc_attr( $actor->post_title ); ?>">
                                    <?php if ( has_post_thumbnail( $actor->ID ) ) : ?>
                                        <?php echo get_the_post_thumbnail( $actor->ID, array( 40, 40 ), array(
                                            'class'   => 'scene-actor-chip__img',
                                            'loading' => 'lazy',
                                            'alt'     => $actor->post_title,
                                        ) ); ?>
                                    <?php endif; ?>
                                    <span itemprop="actor" itemscope itemtype="https://schema.org/Person">
                                        <span itemprop="name"><?php echo esc_html( $actor->post_title ); ?></span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                    <div class="scene-categories">
                        <h2 class="section-subtitle">Categorias</h2>
                        <div class="tags-list">
                            <?php foreach ( $categories as $cat ) : ?>
                                <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="tag-link">
                                    <?php echo esc_html( $cat->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
                    <div class="scene-tags">
                        <h2 class="section-subtitle">Tags</h2>
                        <div class="tags-list">
                            <?php foreach ( $tags as $tag ) : ?>
                                <a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="tag-link">
                                    <?php echo esc_html( $tag->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Cenas relacionadas
        $related = hotboys_get_related_scenes( get_the_ID(), 4 );
        if ( ! empty( $related ) ) :
        ?>
            <section class="related-scenes">
                <h2 class="section-title">Cenas Relacionadas</h2>
                <div class="scenes-grid scenes-grid--small">
                    <?php foreach ( $related as $post ) : setup_postdata( $post ); ?>
                        <?php get_template_part( 'template-parts/content-scene-card' ); ?>
                    <?php endforeach; wp_reset_postdata(); ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</article>

<?php
endif;
get_footer();
