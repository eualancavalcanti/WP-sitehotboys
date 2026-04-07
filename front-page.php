<?php
/**
 * Front Page: Homepage
 *
 * @package HotBoys
 */

get_header();

// Cenas recentes
$recent_scenes = new WP_Query( array(
    'post_type'      => 'scene',
    'posts_per_page' => 12,
    'post_status'    => 'publish',
) );

// Atores em destaque
$featured_actors = new WP_Query( array(
    'post_type'      => 'actor',
    'posts_per_page' => 8,
    'post_status'    => 'publish',
    'orderby'        => 'rand',
) );

// Categorias populares
$popular_categories = get_terms( array(
    'taxonomy'   => 'scene_category',
    'orderby'    => 'count',
    'order'      => 'DESC',
    'number'     => 8,
    'hide_empty' => true,
) );

$seo_text = get_theme_mod( 'hotboys_seo_text', '' );
$seo_title = get_theme_mod( 'hotboys_seo_title', 'HotBoys - Catálogo de Cenas Exclusivas' );
?>

<div class="homepage">
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="hero__title"><?php echo esc_html( $seo_title ); ?></h1>
            <p class="hero__subtitle"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
            <div class="hero__actions">
                <a href="<?php echo esc_url( get_post_type_archive_link( 'scene' ) ); ?>" class="btn btn-primary btn-large">Ver Todas as Cenas</a>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'actor' ) ); ?>" class="btn btn-outline btn-large">Nossos Atores</a>
            </div>
        </div>
    </section>

    <!-- Cenas Recentes -->
    <?php if ( $recent_scenes->have_posts() ) : ?>
    <section class="section section-scenes">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Cenas Recentes</h2>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'scene' ) ); ?>" class="section-link">Ver todas &rarr;</a>
            </div>
            <div class="scenes-grid">
                <?php while ( $recent_scenes->have_posts() ) : $recent_scenes->the_post(); ?>
                    <?php get_template_part( 'template-parts/content-scene-card' ); ?>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Atores em Destaque -->
    <?php if ( $featured_actors->have_posts() ) : ?>
    <section class="section section-actors">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Atores em Destaque</h2>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'actor' ) ); ?>" class="section-link">Ver todos &rarr;</a>
            </div>
            <div class="actors-grid">
                <?php while ( $featured_actors->have_posts() ) : $featured_actors->the_post(); ?>
                    <?php get_template_part( 'template-parts/content-actor-card' ); ?>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Categorias Populares -->
    <?php if ( ! empty( $popular_categories ) && ! is_wp_error( $popular_categories ) ) : ?>
    <section class="section section-categories">
        <div class="container">
            <h2 class="section-title">Categorias Populares</h2>
            <div class="categories-grid">
                <?php foreach ( $popular_categories as $cat ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="category-card">
                        <span class="category-card__name"><?php echo esc_html( $cat->name ); ?></span>
                        <span class="category-card__count"><?php echo esc_html( $cat->count ); ?> cenas</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Bloco SEO -->
    <?php if ( $seo_text ) : ?>
    <section class="section section-seo">
        <div class="container">
            <div class="seo-content">
                <?php echo wp_kses_post( $seo_text ); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- FAQ Section (visivel + schema) -->
    <section class="section section-faq">
        <div class="container">
            <h2 class="section-title">Perguntas Frequentes</h2>
            <div class="faq-list">
                <details class="faq-item" open>
                    <summary class="faq-item__question">O que é o HotBoys?</summary>
                    <div class="faq-item__answer">
                        <p>HotBoys é uma das maiores produtoras de conteúdo adulto do Brasil, com um catálogo exclusivo de cenas e atores profissionais.</p>
                    </div>
                </details>
                <details class="faq-item">
                    <summary class="faq-item__question">Como assistir as cenas exclusivas?</summary>
                    <div class="faq-item__answer">
                        <p>Navegue pelo catálogo de cenas no site e clique em "Assistir Completo" para ser redirecionado ao conteúdo premium na plataforma oficial.</p>
                    </div>
                </details>
                <details class="faq-item">
                    <summary class="faq-item__question">Os vídeos estão disponíveis em HD e 4K?</summary>
                    <div class="faq-item__answer">
                        <p>Sim, a maioria das cenas está disponível em alta definição (HD) e muitas também em qualidade 4K para a melhor experiência.</p>
                    </div>
                </details>
                <details class="faq-item">
                    <summary class="faq-item__question">Como encontrar um ator específico?</summary>
                    <div class="faq-item__answer">
                        <p>Use a <a href="<?php echo esc_url( get_post_type_archive_link( 'actor' ) ); ?>">página de Atores</a> para explorar perfis completos com filmografia, ou utilize a busca no topo do site para encontrar atores por nome.</p>
                    </div>
                </details>
                <details class="faq-item">
                    <summary class="faq-item__question">O conteúdo é atualizado com frequência?</summary>
                    <div class="faq-item__answer">
                        <p>Sim, novas cenas são adicionadas regularmente ao <a href="<?php echo esc_url( get_post_type_archive_link( 'scene' ) ); ?>">catálogo</a> com atores exclusivos e produções de alta qualidade.</p>
                    </div>
                </details>
            </div>
        </div>
    </section>
</div>

<?php
get_footer();
