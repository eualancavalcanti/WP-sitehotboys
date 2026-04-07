<?php
/**
 * Archive: Listagem de Cenas (/cenas/)
 *
 * @package HotBoys
 */

get_header();
?>

<div class="container">
    <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

    <!-- Banner topo -->
    <div class="archive-top-cta">
        <p>🔓 Acesse <strong>todas as cenas sem restrição</strong> — Conteúdo exclusivo em HD e 4K</p>
        <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="btn btn-accent btn-small">Assinar por R$ 1,00</a>
    </div>

    <header class="archive-header">
        <h1 class="archive-title">Todas as Cenas</h1>
        <p class="archive-description">Explore nosso catálogo completo de cenas exclusivas HotBoys.</p>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="scenes-grid">
            <?php
            $count = 0;
            while ( have_posts() ) : the_post();
                $count++;
                get_template_part( 'template-parts/content-scene-card' );

                // CTA inline a cada 8 cenas
                if ( $count % 8 === 0 ) : ?>
                    <div class="grid-inline-cta">
                        <div class="grid-inline-cta__inner">
                            <strong>🔥 Quer assistir sem limites?</strong>
                            <span>Teste o HotBoys por apenas R$ 1,00</span>
                            <a href="https://hotboys.com.br" target="_blank" rel="noopener" class="btn btn-accent btn-small">Assinar Agora</a>
                        </div>
                    </div>
                <?php endif;
            endwhile;
            ?>
        </div>

        <?php hotboys_pagination(); ?>
    <?php else : ?>
        <div class="no-results">
            <p>Nenhuma cena encontrada.</p>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
