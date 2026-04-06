<?php
/**
 * Enqueue scripts and styles
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function hotboys_enqueue_assets() {
    // CSS principal
    wp_enqueue_style(
        'hotboys-style',
        HOTBOYS_URI . '/assets/css/main.css',
        array(),
        HOTBOYS_VERSION
    );

    // JS principal (no footer)
    wp_enqueue_script(
        'hotboys-script',
        HOTBOYS_URI . '/assets/js/main.js',
        array(),
        HOTBOYS_VERSION,
        true
    );

    // Desregistrar jQuery no frontend (performance)
    if ( ! is_admin() ) {
        wp_deregister_script( 'jquery' );
    }
}
add_action( 'wp_enqueue_scripts', 'hotboys_enqueue_assets' );
