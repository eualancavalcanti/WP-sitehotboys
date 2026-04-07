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
                    <div class="scene-thumbnail scene-thumbnail--cta">
                        <?php the_post_thumbnail( 'scene-large', array(
                            'class'    => 'single-scene__img',
                            'alt'      => get_the_title(),
                            'itemprop' => 'thumbnailUrl',
                        ) ); ?>
                        <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="scene-thumbnail__play" aria-label="Assistir cena completa">
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none"><circle cx="40" cy="40" r="38" stroke="#fff" stroke-width="3" opacity=".9"/><polygon points="33,24 58,40 33,56" fill="#fff"/></svg>
                            <span class="scene-thumbnail__label">Assistir</span>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- CTA Principal — sempre visível -->
                <div class="scene-cta-block">
                    <div class="scene-cta-block__inner">
                        <div class="scene-cta-block__text">
                            <span class="scene-cta-block__fire">🔥</span>
                            <div>
                                <strong class="scene-cta-block__title">Assista essa cena completa</strong>
                                <p class="scene-cta-block__sub">Acesso ilimitado a todo o catálogo HotBoys</p>
                            </div>
                        </div>
                        <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="btn btn-accent btn-large scene-cta-block__btn">
                            ▶ Assistir Agora
                        </a>
                    </div>
                    <div class="scene-cta-block__badges">
                        <span class="cta-badge">✓ Qualidade HD/4K</span>
                        <span class="cta-badge">✓ Conteúdo Exclusivo</span>
                        <span class="cta-badge">✓ Cancele quando quiser</span>
                        <span class="cta-badge cta-badge--price">Teste por R$ 1,00</span>
                    </div>
                </div>
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
        // Mid-Page CTA — converter quem scrollou até aqui
        $actor_names_cta = hotboys_get_actor_names();
        ?>
        <section class="scene-midcta" id="sceneMidCta">
            <div class="scene-midcta__inner">
                <div class="scene-midcta__icon">🎬</div>
                <h2 class="scene-midcta__title">
                    <?php if ( $actor_names_cta ) : ?>
                        Quer mais de <?php echo esc_html( $actor_names_cta ); ?>?
                    <?php else : ?>
                        Gostou dessa cena?
                    <?php endif; ?>
                </h2>
                <p class="scene-midcta__text">Assine o HotBoys e tenha acesso ilimitado a <strong>+600 cenas exclusivas</strong> em qualidade HD e 4K.</p>
                <div class="scene-midcta__actions">
                    <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="btn btn-accent btn-large">Assinar por R$ 1,00</a>
                    <span class="scene-midcta__trial">Teste sem compromisso — cancele quando quiser</span>
                </div>
            </div>
        </section>

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

        <!-- Sticky Bottom CTA (mobile) -->
        <div class="sticky-cta" id="stickyCta" aria-hidden="true">
            <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="sticky-cta__btn">
                ▶ Assistir Agora <span class="sticky-cta__price">R$ 1,00</span>
            </a>
        </div>
    </div>
</article>

<?php
endif;
get_footer();
