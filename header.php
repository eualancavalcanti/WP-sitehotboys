<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="<?php echo esc_url( home_url() ); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header" role="banner">
    <div class="container">
        <div class="header-inner">
            <div class="site-branding">
                <?php if ( has_custom_logo() ) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-title" rel="home">
                        <?php bloginfo( 'name' ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <button class="menu-toggle" aria-label="Menu" aria-expanded="false">
                <span class="hamburger"></span>
            </button>

            <nav class="main-nav" role="navigation" aria-label="Menu principal">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'nav-menu',
                    'fallback_cb'    => 'hotboys_fallback_menu',
                ) );
                ?>
            </nav>

            <div class="header-search">
                <?php get_search_form(); ?>
            </div>
        </div>
    </div>
</header>

<main id="main" class="site-main" role="main">
