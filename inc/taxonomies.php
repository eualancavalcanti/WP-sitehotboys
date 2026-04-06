<?php
/**
 * Taxonomias customizadas
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function hotboys_register_taxonomies() {
    // Categoria de Cena (hierarquica)
    register_taxonomy( 'scene_category', 'scene', array(
        'labels' => array(
            'name'          => 'Categorias',
            'singular_name' => 'Categoria',
            'search_items'  => 'Buscar Categorias',
            'all_items'     => 'Todas as Categorias',
            'parent_item'   => 'Categoria Pai',
            'edit_item'     => 'Editar Categoria',
            'add_new_item'  => 'Adicionar Categoria',
            'menu_name'     => 'Categorias',
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => array( 'slug' => 'categorias', 'with_front' => false ),
    ) );

    // Tags de Cena (nao hierarquica)
    register_taxonomy( 'scene_tag', 'scene', array(
        'labels' => array(
            'name'                       => 'Tags',
            'singular_name'              => 'Tag',
            'search_items'               => 'Buscar Tags',
            'popular_items'              => 'Tags Populares',
            'all_items'                  => 'Todas as Tags',
            'edit_item'                  => 'Editar Tag',
            'add_new_item'               => 'Adicionar Tag',
            'separate_items_with_commas' => 'Separe as tags com virgulas',
            'choose_from_most_used'      => 'Escolha entre as mais usadas',
            'menu_name'                  => 'Tags',
        ),
        'hierarchical'      => false,
        'public'            => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => array( 'slug' => 'tags', 'with_front' => false ),
    ) );
}
add_action( 'init', 'hotboys_register_taxonomies' );
