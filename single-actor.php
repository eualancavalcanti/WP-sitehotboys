<?php
/**
 * Single: Pagina Individual de Ator
 *
 * @package HotBoys
 */

get_header();

if ( have_posts() ) : the_post();

$age           = get_post_meta( get_the_ID(), '_actor_age', true );
$city          = get_post_meta( get_the_ID(), '_actor_city', true );
$instagram     = get_post_meta( get_the_ID(), '_actor_instagram', true );
$twitter       = get_post_meta( get_the_ID(), '_actor_twitter', true );
$external_url  = get_post_meta( get_the_ID(), '_actor_external_url', true );
$source_url    = get_post_meta( get_the_ID(), '_hotboys_source_url', true );
$scraped_count = get_post_meta( get_the_ID(), '_actor_scene_count', true );
$scraped_views = get_post_meta( get_the_ID(), '_actor_views', true );
$scene_count   = hotboys_get_actor_scene_count( get_the_ID() );
$paged         = max( 1, get_query_var( 'paged' ) );
$scenes_query  = hotboys_get_actor_scenes( get_the_ID(), $paged );

$actor_cta_url = $source_url ? $source_url : 'https://hotboys.com.br';

// Bio sanitizer — filter garbage scraped content
$raw_content = get_the_content();
$bio_clean   = '';
if ( $raw_content ) {
    $lines = preg_split( '/\r?\n/', wp_strip_all_tags( $raw_content ) );
    $valid_lines = array();
    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( empty( $line ) ) continue;
        if ( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $line ) ) continue;
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $line ) ) continue;
        if ( preg_match( '/^[\d,\.]+$/', $line ) ) continue;
        if ( preg_match( '/^(VIP|FREE|GRATIS|HD|4K)$/i', $line ) ) continue;
        if ( mb_strlen( $line ) < 30 ) continue;
        if ( preg_match( '/^(?:[A-Z][a-záéíóúãõâêîôûç\s]+,\s*){2,}/u', $line ) ) continue;
        $valid_lines[] = $line;
    }
    $bio_clean = implode( "\n\n", $valid_lines );
}

$display_count = $scene_count > 0 ? $scene_count : ( (int) $scraped_count > 0 ? (int) $scraped_count : 0 );
?>

<article class="single-actor" itemscope itemtype="https://schema.org/Person">
    <div class="container">
        <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

        <div class="single-actor__profile">
            <div class="single-actor__photo-wrapper">
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

                <?php if ( $display_count > 0 || $scraped_views ) : ?>
                <div class="actor-stats">
                    <?php if ( $display_count > 0 ) : ?>
                        <div class="actor-stat">
                            <span class="actor-stat__number"><?php echo esc_html( $display_count ); ?></span>
                            <span class="actor-stat__label">Cenas</span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $scraped_views ) : ?>
                        <div class="actor-stat">
                            <span class="actor-stat__number"><?php echo esc_html( number_format( (int) $scraped_views, 0, ',', '.' ) ); ?></span>
                            <span class="actor-stat__label">Views</span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="single-actor__info">
                <h1 class="single-actor__name" itemprop="name"><?php the_title(); ?></h1>

                <div class="single-actor__meta">
                    <?php if ( $age ) : ?>
                        <span class="meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <?php echo esc_html( $age ); ?> anos
                        </span>
                    <?php endif; ?>

                    <?php if ( $city ) : ?>
                        <span class="meta-item" itemprop="address">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?php echo esc_html( $city ); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ( $display_count > 0 ) : ?>
                        <span class="meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                            <?php echo esc_html( $display_count ); ?> cenas
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ( $bio_clean ) : ?>
                    <div class="single-actor__bio" itemprop="description">
                        <p><?php echo esc_html( $bio_clean ); ?></p>
                    </div>
                <?php endif; ?>

                <div class="single-actor__links">
                    <?php if ( $instagram ) : ?>
                        <a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener nofollow" class="social-btn social-btn--instagram" itemprop="sameAs">Instagram</a>
                    <?php endif; ?>

                    <?php if ( $twitter ) : ?>
                        <a href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener nofollow" class="social-btn social-btn--twitter" itemprop="sameAs">Twitter / X</a>
                    <?php endif; ?>

                    <?php if ( $external_url ) : ?>
                        <a href="<?php echo esc_url( $external_url ); ?>" target="_blank" rel="nofollow noopener" class="btn btn-primary">Ver Perfil Completo</a>
                    <?php endif; ?>
                </div>

                <div class="actor-profile-cta">
                    <span class="actor-profile-cta__icon">🔥</span>
                    <div class="actor-profile-cta__content">
                        <strong>Assista todas as cenas de <?php the_title(); ?></strong>
                        <span>Acesso ilimitado ao catálogo completo — teste por R$ 1,00</span>
                    </div>
                    <a href="<?php echo esc_url( $actor_cta_url ); ?>" target="_blank" rel="noopener" class="btn btn-accent btn-small">Assistir</a>
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
                <div class="filmography-cta">
                    <div class="filmography-cta__inner">
                        <p class="filmography-cta__text">🎬 Assista a <strong>filmografia completa</strong> de <?php the_title(); ?> e <strong>+5.000 atores</strong> no HotBoys</p>
                        <a href="<?php echo esc_url( $actor_cta_url ); ?>" target="_blank" rel="noopener" class="btn btn-accent">Assistir Cenas</a>
                    </div>
                </div>
            </section>
        <?php elseif ( $display_count > 0 ) : ?>
            <section class="actor-filmography">
                <div class="actor-no-scenes-cta">
                    <div class="actor-no-scenes-cta__icon">🎬</div>
                    <h2 class="actor-no-scenes-cta__title"><?php echo esc_html( $display_count ); ?> cenas de <?php the_title(); ?> disponíveis</h2>
                    <p class="actor-no-scenes-cta__text">Assista a filmografia completa no HotBoys com qualidade HD e 4K.</p>
                    <a href="<?php echo esc_url( $actor_cta_url ); ?>" target="_blank" rel="noopener" class="btn btn-accent btn-large">▶ Ver Cenas de <?php the_title(); ?></a>
                </div>
            </section>
        <?php endif; ?>
    </div>
</article>

<?php
endif;
get_footer();
