<?php
/**
 * Archive: Listagem de Atores (/atores/)
 *
 * @package HotBoys
 */

get_header();
?>

<div class="container">
    <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

    <!-- Banner topo -->
    <div class="archive-top-cta">
        <p>⭐ Conheça <strong>todos os atores exclusivos</strong> e assista seus melhores momentos</p>
        <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="btn btn-accent btn-small">Assinar por R$ 1,00</a>
    </div>

    <header class="archive-header">
        <h1 class="archive-title">Nossos Atores</h1>
        <p class="archive-description">Conheça todos os atores exclusivos HotBoys.</p>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="actors-grid">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php get_template_part( 'template-parts/content-actor-card' ); ?>
            <?php endwhile; ?>
        </div>

        <!-- CTA bottom antes da paginação -->
        <div class="archive-bottom-cta">
            <div class="archive-bottom-cta__inner">
                <div class="archive-bottom-cta__text">
                    <strong>🎬 Assista a todas as cenas completas</strong>
                    <span>+600 cenas exclusivas em qualidade HD e 4K. Cancele quando quiser.</span>
                </div>
                <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="btn btn-accent btn-large">Teste por R$ 1,00</a>
            </div>
        </div>

        <?php hotboys_pagination(); ?>
    <?php else : ?>
        <div class="no-results">
            <p>Nenhum ator encontrado.</p>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
