</main><!-- .site-main -->

<footer class="site-footer" role="contentinfo">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-nav">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'footer',
                    'container'      => false,
                    'menu_class'     => 'footer-menu',
                    'depth'          => 1,
                    'fallback_cb'    => false,
                ) );
                ?>
            </div>

            <div class="footer-social">
                <?php
                $social_links = array(
                    'twitter'   => get_theme_mod( 'hotboys_twitter', '' ),
                    'instagram' => get_theme_mod( 'hotboys_instagram', '' ),
                );
                foreach ( $social_links as $network => $url ) :
                    if ( ! empty( $url ) ) :
                ?>
                    <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( ucfirst( $network ) ); ?>" class="social-link social-<?php echo esc_attr( $network ); ?>">
                        <?php echo esc_html( ucfirst( $network ) ); ?>
                    </a>
                <?php
                    endif;
                endforeach;
                ?>
            </div>

            <div class="footer-disclaimer">
                <p>Este site contém conteúdo destinado exclusivamente para maiores de 18 anos.</p>
            </div>

            <div class="footer-copy">
                <p>&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
