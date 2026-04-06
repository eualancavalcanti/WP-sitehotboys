<?php
/**
 * Template Tags - Funcoes helper para templates
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retorna os atores de uma cena
 */
function hotboys_get_scene_actors( $scene_id = null ) {
    if ( ! $scene_id ) {
        $scene_id = get_the_ID();
    }

    $actor_ids = get_post_meta( $scene_id, '_scene_actors', true );

    if ( ! is_array( $actor_ids ) || empty( $actor_ids ) ) {
        return array();
    }

    return get_posts( array(
        'post_type'      => 'actor',
        'post__in'       => $actor_ids,
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ) );
}

/**
 * Retorna as cenas de um ator (query reversa)
 */
function hotboys_get_actor_scenes( $actor_id = null, $paged = 1, $per_page = 12 ) {
    if ( ! $actor_id ) {
        $actor_id = get_the_ID();
    }

    return new WP_Query( array(
        'post_type'      => 'scene',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_scene_actors',
                'value'   => sprintf( '"%d"', $actor_id ),
                'compare' => 'LIKE',
            ),
        ),
    ) );
}

/**
 * Contagem de cenas do ator (com cache transient)
 */
function hotboys_get_actor_scene_count( $actor_id = null ) {
    if ( ! $actor_id ) {
        $actor_id = get_the_ID();
    }

    $cache_key = 'hotboys_actor_scene_count_' . $actor_id;
    $count = get_transient( $cache_key );

    if ( false === $count ) {
        $query = new WP_Query( array(
            'post_type'      => 'scene',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_scene_actors',
                    'value'   => sprintf( '"%d"', $actor_id ),
                    'compare' => 'LIKE',
                ),
            ),
        ) );
        $count = $query->found_posts;
        set_transient( $cache_key, $count, HOUR_IN_SECONDS );
    }

    return (int) $count;
}

/**
 * Cenas relacionadas (por categorias/tags/atores em comum)
 */
function hotboys_get_related_scenes( $scene_id = null, $count = 4 ) {
    if ( ! $scene_id ) {
        $scene_id = get_the_ID();
    }

    $related = array();

    // Buscar por categorias em comum
    $categories = wp_get_post_terms( $scene_id, 'scene_category', array( 'fields' => 'ids' ) );
    $tags       = wp_get_post_terms( $scene_id, 'scene_tag', array( 'fields' => 'ids' ) );

    $tax_query = array( 'relation' => 'OR' );

    if ( ! empty( $categories ) ) {
        $tax_query[] = array(
            'taxonomy' => 'scene_category',
            'field'    => 'term_id',
            'terms'    => $categories,
        );
    }

    if ( ! empty( $tags ) ) {
        $tax_query[] = array(
            'taxonomy' => 'scene_tag',
            'field'    => 'term_id',
            'terms'    => $tags,
        );
    }

    if ( count( $tax_query ) > 1 ) {
        $query = new WP_Query( array(
            'post_type'      => 'scene',
            'posts_per_page' => $count,
            'post__not_in'   => array( $scene_id ),
            'post_status'    => 'publish',
            'tax_query'      => $tax_query,
            'orderby'        => 'rand',
        ) );

        if ( $query->have_posts() ) {
            return $query->posts;
        }
    }

    // Fallback: cenas recentes
    $query = new WP_Query( array(
        'post_type'      => 'scene',
        'posts_per_page' => $count,
        'post__not_in'   => array( $scene_id ),
        'post_status'    => 'publish',
    ) );

    return $query->posts;
}

/**
 * Formatar duracao para ISO 8601 (para schema)
 * Converte "25:30" para "PT25M30S"
 */
function hotboys_duration_to_iso8601( $duration ) {
    if ( empty( $duration ) ) {
        return '';
    }

    $parts = explode( ':', $duration );

    if ( count( $parts ) === 2 ) {
        return sprintf( 'PT%dM%dS', (int) $parts[0], (int) $parts[1] );
    }

    if ( count( $parts ) === 3 ) {
        return sprintf( 'PT%dH%dM%dS', (int) $parts[0], (int) $parts[1], (int) $parts[2] );
    }

    return '';
}

/**
 * Exibir lista de atores como links
 */
function hotboys_display_actor_links( $scene_id = null, $separator = ', ' ) {
    $actors = hotboys_get_scene_actors( $scene_id );

    if ( empty( $actors ) ) {
        return '';
    }

    $links = array();
    foreach ( $actors as $actor ) {
        $links[] = sprintf(
            '<a href="%s" title="%s">%s</a>',
            esc_url( get_permalink( $actor->ID ) ),
            esc_attr( sprintf( 'Ver perfil de %s', $actor->post_title ) ),
            esc_html( $actor->post_title )
        );
    }

    return implode( $separator, $links );
}

/**
 * Nomes dos atores como texto (para meta descriptions)
 */
function hotboys_get_actor_names( $scene_id = null, $separator = ', ' ) {
    $actors = hotboys_get_scene_actors( $scene_id );

    if ( empty( $actors ) ) {
        return '';
    }

    return implode( $separator, wp_list_pluck( $actors, 'post_title' ) );
}

/**
 * Paginacao numerada
 */
function hotboys_pagination( $query = null ) {
    if ( ! $query ) {
        global $wp_query;
        $query = $wp_query;
    }

    if ( $query->max_num_pages <= 1 ) {
        return;
    }

    $paged = max( 1, get_query_var( 'paged' ) );

    echo '<nav class="pagination" aria-label="Paginação">';
    echo paginate_links( array(
        'total'     => $query->max_num_pages,
        'current'   => $paged,
        'prev_text' => '&laquo; Anterior',
        'next_text' => 'Próxima &raquo;',
        'type'      => 'list',
    ) );
    echo '</nav>';
}
