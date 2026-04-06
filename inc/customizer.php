<?php
/**
 * Customizer - Configuracoes do tema
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function hotboys_customizer_register( $wp_customize ) {
    // Secao HotBoys
    $wp_customize->add_section( 'hotboys_settings', array(
        'title'    => 'HotBoys - Configurações',
        'priority' => 30,
    ) );

    // Titulo SEO da Homepage
    $wp_customize->add_setting( 'hotboys_seo_title', array(
        'default'           => 'HotBoys - Catálogo de Cenas Exclusivas',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'hotboys_seo_title', array(
        'label'   => 'Título H1 da Homepage',
        'section' => 'hotboys_settings',
        'type'    => 'text',
    ) );

    // Meta Description da Homepage
    $wp_customize->add_setting( 'hotboys_meta_description', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'hotboys_meta_description', array(
        'label'       => 'Meta Description da Homepage',
        'description' => 'Máximo 160 caracteres. Aparece nos resultados do Google.',
        'section'     => 'hotboys_settings',
        'type'        => 'textarea',
    ) );

    // Texto SEO da Homepage
    $wp_customize->add_setting( 'hotboys_seo_text', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
    ) );
    $wp_customize->add_control( 'hotboys_seo_text', array(
        'label'       => 'Texto SEO da Homepage',
        'description' => 'Texto exibido na parte inferior da homepage para SEO.',
        'section'     => 'hotboys_settings',
        'type'        => 'textarea',
    ) );

    // Secao Redes Sociais
    $wp_customize->add_section( 'hotboys_social', array(
        'title'    => 'HotBoys - Redes Sociais',
        'priority' => 31,
    ) );

    // Twitter URL
    $wp_customize->add_setting( 'hotboys_twitter', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'hotboys_twitter', array(
        'label'   => 'URL do Twitter / X',
        'section' => 'hotboys_social',
        'type'    => 'url',
    ) );

    // Twitter Handle
    $wp_customize->add_setting( 'hotboys_twitter_handle', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'hotboys_twitter_handle', array(
        'label'       => 'Twitter Handle',
        'description' => 'Ex: @hotboys (para Twitter Cards)',
        'section'     => 'hotboys_social',
        'type'        => 'text',
    ) );

    // Instagram URL
    $wp_customize->add_setting( 'hotboys_instagram', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'hotboys_instagram', array(
        'label'   => 'URL do Instagram',
        'section' => 'hotboys_social',
        'type'    => 'url',
    ) );
}
add_action( 'customize_register', 'hotboys_customizer_register' );
