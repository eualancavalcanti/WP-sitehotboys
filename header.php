<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#0a0a0a">
    <link rel="preconnect" href="<?php echo esc_url( home_url() ); ?>">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//www.googletagmanager.com">
    <link rel="preload" href="<?php echo esc_url( HOTBOYS_URI . '/assets/css/main.css' ); ?>" as="style">
    <style>
    /* Critical CSS — above-the-fold rendering */
    :root{--color-bg:#0a0a0a;--color-surface:#141414;--color-primary:#e50914;--color-text:#fff;--color-text-muted:#a0a0a0;--header-height:60px;--container-width:1200px}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{font-size:16px;-webkit-text-size-adjust:100%}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--color-bg);color:var(--color-text);line-height:1.6}
    .container{width:100%;max-width:var(--container-width);margin:0 auto;padding:0 1rem}
    .site-header{background:var(--color-surface);position:sticky;top:0;z-index:100;border-bottom:1px solid rgba(255,255,255,.05)}
    .header-inner{display:flex;align-items:center;justify-content:space-between;height:var(--header-height)}
    .site-title{color:var(--color-primary);font-size:1.25rem;font-weight:700;text-decoration:none}
    .promo-bar{background:linear-gradient(90deg,#e50914 0%,#ff6b35 100%);color:#fff;padding:.625rem 0;text-align:center;font-size:.875rem;position:relative;z-index:99}
    .promo-bar .container{display:flex;align-items:center;justify-content:center;gap:1rem}
    .promo-bar.is-hidden{display:none}
    .hero--home{padding:3rem 0 2.5rem;text-align:center;background:linear-gradient(180deg,var(--color-surface) 0%,var(--color-bg) 100%)}
    .hero__title{font-size:clamp(1.5rem,4vw,2.5rem);margin-bottom:.5rem}
    .hero__stats{display:flex;gap:2rem;justify-content:center;margin:2rem 0}
    .hero__stat-number{font-size:2rem;font-weight:800;color:#ff4444;line-height:1.2}
    .hero__stat-label{font-size:.75rem;color:#a0a0a0;text-transform:uppercase;letter-spacing:1px}
    .btn{display:inline-flex;padding:.75rem 1.5rem;border-radius:.5rem;text-decoration:none;font-weight:600;transition:.2s}
    .btn-primary{background:var(--color-primary);color:#fff}
    .btn-accent{background:linear-gradient(135deg,#e50914 0%,#ff6b35 100%);color:#fff}
    .screen-reader-text{clip:rect(1px,1px,1px,1px);clip-path:inset(50%);position:absolute;overflow:hidden;width:1px;height:1px;margin:-1px;padding:0;border:0;word-wrap:normal!important}
    </style>
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
