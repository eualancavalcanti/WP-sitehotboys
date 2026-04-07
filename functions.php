<?php
/**
 * HotBoys Theme Functions
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HOTBOYS_VERSION', '1.0.0' );
define( 'HOTBOYS_DIR', get_template_directory() );
define( 'HOTBOYS_URI', get_template_directory_uri() );

/**
 * Theme setup
 */
function hotboys_setup() {
    load_theme_textdomain( 'hotboys', HOTBOYS_DIR . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array(
        'search-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );
    add_theme_support( 'custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    register_nav_menus( array(
        'primary' => __( 'Menu Principal', 'hotboys' ),
        'footer'  => __( 'Menu Rodape', 'hotboys' ),
    ) );

    // Tamanhos de imagem customizados
    add_image_size( 'scene-thumb', 400, 225, true );   // 16:9
    add_image_size( 'scene-large', 800, 450, true );   // 16:9
    add_image_size( 'actor-thumb', 300, 400, true );   // Retrato
    add_image_size( 'actor-large', 600, 800, true );   // Retrato
}
add_action( 'after_setup_theme', 'hotboys_setup' );

/**
 * Includes
 */
require_once HOTBOYS_DIR . '/inc/enqueue.php';
require_once HOTBOYS_DIR . '/inc/custom-post-types.php';
require_once HOTBOYS_DIR . '/inc/taxonomies.php';
require_once HOTBOYS_DIR . '/inc/template-tags.php';
require_once HOTBOYS_DIR . '/inc/schema-markup.php';
require_once HOTBOYS_DIR . '/inc/seo-helpers.php';
require_once HOTBOYS_DIR . '/inc/customizer.php';
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once HOTBOYS_DIR . '/inc/import-hotboys.php';
}

/**
 * Flush rewrite rules on theme activation
 */
function hotboys_activate() {
    hotboys_register_scene_cpt();
    hotboys_register_actor_cpt();
    hotboys_register_taxonomies();
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'hotboys_activate' );

/**
 * Modify main query for custom post types
 */
function hotboys_pre_get_posts( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Busca inclui scenes e actors
    if ( $query->is_search() ) {
        $query->set( 'post_type', array( 'scene', 'actor', 'post', 'page' ) );
    }

    // Archives de scenes: 12 por pagina
    if ( $query->is_post_type_archive( 'scene' ) || $query->is_tax( 'scene_category' ) || $query->is_tax( 'scene_tag' ) ) {
        $query->set( 'posts_per_page', 12 );
    }

    // Archives de actors: 20 por pagina, ordem alfabetica
    if ( $query->is_post_type_archive( 'actor' ) ) {
        $query->set( 'posts_per_page', 20 );
        $query->set( 'orderby', 'title' );
        $query->set( 'order', 'ASC' );
    }
}
add_action( 'pre_get_posts', 'hotboys_pre_get_posts' );

/**
 * Customizar robots.txt
 */
function hotboys_robots_txt( $output, $public ) {
    if ( '0' === $public ) {
        return $output;
    }

    $output  = "User-agent: *\n";
    $output .= "Disallow: /wp-admin/\n";
    $output .= "Allow: /wp-admin/admin-ajax.php\n";
    $output .= "Disallow: /wp-json/\n";
    $output .= "Disallow: /?s=\n";
    $output .= "Disallow: /search/\n\n";
    $output .= "# Sitemaps\n";
    $output .= "Sitemap: " . home_url( '/sitemap.xml' ) . "\n";
    $output .= "Sitemap: " . home_url( '/sitemap.rss' ) . "\n";

    return $output;
}
add_filter( 'robots_txt', 'hotboys_robots_txt', 10, 2 );

/**
 * Redirecionar URLs antigas do Elementor para novas rotas do tema
 */
function hotboys_redirects() {
    if ( is_admin() ) {
        return;
    }

    $redirects = array(
        '/pagina-inicial/' => '/',
        '/cenas-gratis/'   => '/cenas/',
    );

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
    $path = wp_parse_url( $request_uri, PHP_URL_PATH );

    if ( $path && isset( $redirects[ $path ] ) ) {
        wp_safe_redirect( home_url( $redirects[ $path ] ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'hotboys_redirects' );

/**
 * Remover tamanhos de imagem desnecessarios do WP
 */
function hotboys_remove_image_sizes() {
    remove_image_size( 'medium_large' );
    remove_image_size( '1536x1536' );
    remove_image_size( '2048x2048' );
}
add_action( 'init', 'hotboys_remove_image_sizes' );

/**
 * Adicionar atributos de performance aos scripts
 */
function hotboys_script_loader_tag( $tag, $handle, $src ) {
    if ( 'hotboys-script' === $handle ) {
        return str_replace( ' src', ' defer src', $tag );
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'hotboys_script_loader_tag', 10, 3 );

/**
 * Desabilitar tamanhos de imagem desnecessarios do WP
 */
function hotboys_disable_extra_image_sizes() {
    remove_image_size( 'medium_large' );
    remove_image_size( '1536x1536' );
    remove_image_size( '2048x2048' );
}
add_action( 'init', 'hotboys_disable_extra_image_sizes' );
