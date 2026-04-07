<?php
/**
 * Front Page: Homepage — Layout de conversão
 *
 * @package HotBoys
 */

get_header();

// Cenas mais recentes ("Saiu do Forno")
$latest_scenes = new WP_Query( array(
    'post_type'      => 'scene',
    'posts_per_page' => 8,
    'post_status'    => 'publish',
) );

// Cenas em alta (mais visualizadas / comentadas)
$trending_scenes = new WP_Query( array(
    'post_type'      => 'scene',
    'posts_per_page' => 8,
    'post_status'    => 'publish',
    'orderby'        => 'comment_count',
    'order'          => 'DESC',
) );

// Atores do momento
$spotlight_actors = new WP_Query( array(
    'post_type'      => 'actor',
    'posts_per_page' => 10,
    'post_status'    => 'publish',
    'orderby'        => 'rand',
) );

// Categorias populares
$popular_categories = get_terms( array(
    'taxonomy'   => 'scene_category',
    'orderby'    => 'count',
    'order'      => 'DESC',
    'number'     => 8,
    'hide_empty' => false,
) );

// Contadores para social proof
$total_scenes = wp_count_posts( 'scene' );
$total_actors = wp_count_posts( 'actor' );
$scene_count  = isset( $total_scenes->publish ) ? (int) $total_scenes->publish : 690;
$actor_count  = isset( $total_actors->publish ) ? (int) $total_actors->publish : 217;

$seo_text  = get_theme_mod( 'hotboys_seo_text', '' );
$seo_title = get_theme_mod( 'hotboys_seo_title', 'HotBoys — Cenas Exclusivas em HD e 4K' );
?>

<!-- Promo Bar -->
<div class="promo-bar" id="promoBar">
    <div class="container">
        <p class="promo-bar__text">
            <span class="promo-bar__badge">OFERTA</span>
            Teste por apenas <strong>R$&nbsp;1,00</strong> durante 2 dias &mdash;
            <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="promo-bar__link">Assinar agora &rarr;</a>
        </p>
        <button class="promo-bar__close" aria-label="Fechar" id="promoClose">&times;</button>
    </div>
</div>

<div class="homepage">

    <!-- Hero -->
    <section class="hero hero--home">
        <div class="container">
            <div class="hero__inner">
                <h1 class="hero__title"><?php echo esc_html( $seo_title ); ?></h1>
                <p class="hero__subtitle">A maior produtora de conteúdo adulto gay do Brasil. Cenas exclusivas, atores profissionais, qualidade 4K.</p>

                <div class="hero__stats">
                    <div class="hero__stat">
                        <span class="hero__stat-number"><?php echo esc_html( number_format_i18n( $scene_count ) ); ?>+</span>
                        <span class="hero__stat-label">Cenas</span>
                    </div>
                    <div class="hero__stat">
                        <span class="hero__stat-number"><?php echo esc_html( number_format_i18n( $actor_count ) ); ?>+</span>
                        <span class="hero__stat-label">Atores</span>
                    </div>
                    <div class="hero__stat">
                        <span class="hero__stat-number">4K</span>
                        <span class="hero__stat-label">Qualidade</span>
                    </div>
                </div>

                <div class="hero__actions">
                    <a href="<?php echo esc_url( get_post_type_archive_link( 'scene' ) ); ?>" class="btn btn-primary btn-large">Explorar Cenas</a>
                    <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="btn btn-accent btn-large">Assinar &mdash; R$&nbsp;1 Trial</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Categorias rápidas -->
    <?php if ( ! empty( $popular_categories ) && ! is_wp_error( $popular_categories ) ) : ?>
    <section class="section section-quickcats">
        <div class="container">
            <div class="quickcats-scroll">
                <?php
                $cat_icons = array(
                    'novinhos'     => '🔥',
                    'dotados'      => '💪',
                    'com história' => '🎬',
                    'amador'       => '📹',
                    'bareback'     => '⚡',
                    'exclusivos'   => '⭐',
                    'musculosos'   => '🏋️',
                    'interracial'  => '🌍',
                );
                foreach ( $popular_categories as $cat ) :
                    $slug  = sanitize_title( $cat->name );
                    $icon  = '';
                    foreach ( $cat_icons as $key => $emoji ) {
                        if ( false !== strpos( strtolower( $cat->name ), $key ) ) {
                            $icon = $emoji . ' ';
                            break;
                        }
                    }
                ?>
                    <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="quickcat-pill">
                        <?php echo $icon; ?><?php echo esc_html( $cat->name ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Saiu do Forno (Últimas cenas) -->
    <?php if ( $latest_scenes->have_posts() ) : ?>
    <section class="section section-latest">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><span class="section-icon">🔥</span> Saiu do Forno</h2>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'scene' ) ); ?>" class="section-link">Ver todas &rarr;</a>
            </div>
            <div class="scroll-row" data-scroll-row>
                <?php while ( $latest_scenes->have_posts() ) : $latest_scenes->the_post(); ?>
                    <div class="scroll-row__item">
                        <?php get_template_part( 'template-parts/content-scene-card' ); ?>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Assinatura -->
    <section class="section section-cta">
        <div class="container">
            <div class="cta-box">
                <div class="cta-box__content">
                    <h2 class="cta-box__title">Acesse Todo o Catálogo</h2>
                    <p class="cta-box__text">Cenas completas em HD e 4K. Novos vídeos toda semana. Cancele quando quiser.</p>
                    <div class="cta-box__badges">
                        <span class="trust-badge">🔒 Site Seguro</span>
                        <span class="trust-badge">🤫 Discreto na Fatura</span>
                        <span class="trust-badge">❌ Sem Fidelidade</span>
                    </div>
                </div>
                <div class="cta-box__plans">
                    <div class="plan-card">
                        <span class="plan-card__name">Trial</span>
                        <span class="plan-card__price">R$&nbsp;1<small>,00</small></span>
                        <span class="plan-card__period">por 2 dias</span>
                        <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="btn btn-primary btn-small">Testar</a>
                    </div>
                    <div class="plan-card plan-card--featured">
                        <span class="plan-card__tag">Mais Popular</span>
                        <span class="plan-card__name">Mensal</span>
                        <span class="plan-card__price">R$&nbsp;38<small>,90</small></span>
                        <span class="plan-card__period">por mês</span>
                        <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="btn btn-primary">Assinar</a>
                    </div>
                    <div class="plan-card">
                        <span class="plan-card__tag plan-card__tag--save">Economia de 47%</span>
                        <span class="plan-card__name">Anual</span>
                        <span class="plan-card__price">R$&nbsp;249<small>,90</small></span>
                        <span class="plan-card__period">por ano</span>
                        <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="btn btn-primary btn-small">Assinar</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Atores do Momento -->
    <?php if ( $spotlight_actors->have_posts() ) : ?>
    <section class="section section-actors-spotlight">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><span class="section-icon">⭐</span> Atores do Momento</h2>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'actor' ) ); ?>" class="section-link">Ver todos &rarr;</a>
            </div>
            <div class="scroll-row" data-scroll-row>
                <?php while ( $spotlight_actors->have_posts() ) : $spotlight_actors->the_post(); ?>
                    <div class="scroll-row__item scroll-row__item--actor">
                        <?php get_template_part( 'template-parts/content-actor-card' ); ?>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Em Alta Esta Semana -->
    <?php if ( $trending_scenes->have_posts() ) : ?>
    <section class="section section-trending">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><span class="section-icon">📈</span> Em Alta</h2>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'scene' ) ); ?>" class="section-link">Ver todas &rarr;</a>
            </div>
            <div class="scenes-grid">
                <?php while ( $trending_scenes->have_posts() ) : $trending_scenes->the_post(); ?>
                    <?php get_template_part( 'template-parts/content-scene-card' ); ?>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Categorias Populares -->
    <?php if ( ! empty( $popular_categories ) && ! is_wp_error( $popular_categories ) ) : ?>
    <section class="section section-categories">
        <div class="container">
            <h2 class="section-title">Explore por Categoria</h2>
            <div class="categories-grid">
                <?php foreach ( $popular_categories as $cat ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="category-card">
                        <span class="category-card__name"><?php echo esc_html( $cat->name ); ?></span>
                        <span class="category-card__count"><?php echo esc_html( $cat->count ); ?> cenas</span>
                        <span class="category-card__arrow">&rarr;</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Social Proof / Trust -->
    <section class="section section-trust">
        <div class="container">
            <div class="trust-grid">
                <div class="trust-item">
                    <span class="trust-item__icon">🔒</span>
                    <span class="trust-item__title">Pagamento Seguro</span>
                    <span class="trust-item__text">SSL + criptografia 256-bit</span>
                </div>
                <div class="trust-item">
                    <span class="trust-item__icon">🤫</span>
                    <span class="trust-item__title">Discreto na Fatura</span>
                    <span class="trust-item__text">Não aparece o nome do site</span>
                </div>
                <div class="trust-item">
                    <span class="trust-item__icon">❌</span>
                    <span class="trust-item__title">Sem Fidelidade</span>
                    <span class="trust-item__text">Cancele quando quiser</span>
                </div>
                <div class="trust-item">
                    <span class="trust-item__icon">📱</span>
                    <span class="trust-item__title">Multi-dispositivo</span>
                    <span class="trust-item__text">Celular, tablet e PC</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="section section-newsletter">
        <div class="container">
            <div class="newsletter-box">
                <h2 class="newsletter-box__title">Fique por Dentro</h2>
                <p class="newsletter-box__text">Receba notificações de novas cenas, atores e promoções exclusivas.</p>
                <form class="newsletter-form" action="#" method="post" data-newsletter-form>
                    <input type="email" name="email" class="newsletter-form__input" placeholder="Seu melhor e-mail" required autocomplete="email">
                    <button type="submit" class="btn btn-primary newsletter-form__btn">Quero Receber</button>
                </form>
                <p class="newsletter-box__disclaimer">Não enviamos spam. Você pode cancelar a qualquer momento.</p>
            </div>
        </div>
    </section>

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
                        <p>HotBoys é uma das maiores produtoras de conteúdo adulto gay do Brasil, com mais de <?php echo esc_html( number_format_i18n( $scene_count ) ); ?> cenas exclusivas e <?php echo esc_html( number_format_i18n( $actor_count ) ); ?>+ atores profissionais. Todo o conteúdo é produzido com qualidade HD e 4K.</p>
                    </div>
                </details>
                <details class="faq-item">
                    <summary class="faq-item__question">Quanto custa a assinatura?</summary>
                    <div class="faq-item__answer">
                        <p>Oferecemos planos flexíveis: Trial de 2 dias por R$&nbsp;1,00, Mensal por R$&nbsp;38,90, ou Anual por R$&nbsp;249,90 (economia de 47%). Todos incluem acesso ilimitado ao catálogo completo.</p>
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
                    <summary class="faq-item__question">A cobrança é discreta?</summary>
                    <div class="faq-item__answer">
                        <p>Sim! Na fatura do cartão aparece um nome genérico, sem referência ao site. Sua privacidade é nossa prioridade.</p>
                    </div>
                </details>
                <details class="faq-item">
                    <summary class="faq-item__question">Posso cancelar a qualquer momento?</summary>
                    <div class="faq-item__answer">
                        <p>Sim, não existe fidelidade. Você pode cancelar sua assinatura quando quiser, sem burocracia.</p>
                    </div>
                </details>
                <details class="faq-item">
                    <summary class="faq-item__question">Como encontrar um ator específico?</summary>
                    <div class="faq-item__answer">
                        <p>Use a <a href="<?php echo esc_url( get_post_type_archive_link( 'actor' ) ); ?>">página de Atores</a> para explorar perfis completos com filmografia, ou utilize a busca no topo do site para encontrar atores por nome.</p>
                    </div>
                </details>
            </div>
        </div>
    </section>
</div>

<?php
get_footer();
