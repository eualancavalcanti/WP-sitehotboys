<?php
/**
 * Schema.org JSON-LD Structured Data
 * Markup estruturado para Google Rich Results
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function hotboys_output_schema() {
    $schemas = array();

    if ( is_front_page() ) {
        $schemas[] = hotboys_schema_organization();
        $schemas[] = hotboys_schema_website();
    } elseif ( is_singular( 'scene' ) ) {
        $schemas[] = hotboys_schema_video_object();
    } elseif ( is_singular( 'actor' ) ) {
        $schemas[] = hotboys_schema_person();
    } elseif ( is_post_type_archive( 'scene' ) || is_tax( 'scene_category' ) || is_tax( 'scene_tag' ) ) {
        $schemas[] = hotboys_schema_item_list( 'scene' );
    } elseif ( is_post_type_archive( 'actor' ) ) {
        $schemas[] = hotboys_schema_item_list( 'actor' );
    }

    // Breadcrumbs em todas as paginas (exceto home)
    if ( ! is_front_page() ) {
        $schemas[] = hotboys_schema_breadcrumbs();
    }

    foreach ( $schemas as $schema ) {
        if ( ! empty( $schema ) ) {
            printf(
                '<script type="application/ld+json">%s</script>' . "\n",
                wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT )
            );
        }
    }
}
add_action( 'wp_head', 'hotboys_output_schema', 5 );

/**
 * Schema: Organization
 */
function hotboys_schema_organization() {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => get_bloginfo( 'name' ),
        'url'      => home_url( '/' ),
    );

    $logo_id = get_theme_mod( 'custom_logo' );
    if ( $logo_id ) {
        $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
        if ( $logo_url ) {
            $schema['logo'] = $logo_url;
        }
    }

    $social = array();
    $twitter = get_theme_mod( 'hotboys_twitter', '' );
    $instagram = get_theme_mod( 'hotboys_instagram', '' );

    if ( $twitter ) $social[] = $twitter;
    if ( $instagram ) $social[] = $instagram;

    if ( ! empty( $social ) ) {
        $schema['sameAs'] = $social;
    }

    return $schema;
}

/**
 * Schema: WebSite com SearchAction
 */
function hotboys_schema_website() {
    return array(
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        'name'            => get_bloginfo( 'name' ),
        'url'             => home_url( '/' ),
        'potentialAction' => array(
            '@type'       => 'SearchAction',
            'target'      => array(
                '@type'        => 'EntryPoint',
                'urlTemplate'  => home_url( '/?s={search_term_string}' ),
            ),
            'query-input' => 'required name=search_term_string',
        ),
    );
}

/**
 * Schema: VideoObject (para pagina individual de cena)
 */
function hotboys_schema_video_object() {
    $post_id      = get_the_ID();
    $duration     = get_post_meta( $post_id, '_scene_duration', true );
    $release_date = get_post_meta( $post_id, '_scene_release_date', true );
    $trailer_url  = get_post_meta( $post_id, '_scene_trailer_url', true );
    $actors       = hotboys_get_scene_actors( $post_id );

    $schema = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'VideoObject',
        'name'        => get_the_title(),
        'description' => hotboys_get_seo_description( $post_id ),
        'url'         => get_permalink(),
    );

    // Thumbnail
    if ( has_post_thumbnail() ) {
        $schema['thumbnailUrl'] = get_the_post_thumbnail_url( $post_id, 'scene-large' );
    }

    // Data de upload
    if ( $release_date ) {
        $schema['uploadDate'] = $release_date;
    } else {
        $schema['uploadDate'] = get_the_date( 'Y-m-d' );
    }

    // Duracao ISO 8601
    if ( $duration ) {
        $iso = hotboys_duration_to_iso8601( $duration );
        if ( $iso ) {
            $schema['duration'] = $iso;
        }
    }

    // Content URL (trailer)
    if ( $trailer_url ) {
        $schema['contentUrl'] = $trailer_url;
        $schema['embedUrl']   = $trailer_url;
    }

    // Atores
    if ( ! empty( $actors ) ) {
        $schema['actor'] = array();
        foreach ( $actors as $actor ) {
            $actor_schema = array(
                '@type' => 'Person',
                'name'  => $actor->post_title,
                'url'   => get_permalink( $actor->ID ),
            );
            if ( has_post_thumbnail( $actor->ID ) ) {
                $actor_schema['image'] = get_the_post_thumbnail_url( $actor->ID, 'actor-thumb' );
            }
            $schema['actor'][] = $actor_schema;
        }
    }

    // Publisher
    $schema['publisher'] = array(
        '@type' => 'Organization',
        'name'  => get_bloginfo( 'name' ),
        'url'   => home_url( '/' ),
    );

    return $schema;
}

/**
 * Schema: Person (para pagina individual de ator)
 */
function hotboys_schema_person() {
    $post_id   = get_the_ID();
    $instagram = get_post_meta( $post_id, '_actor_instagram', true );
    $twitter   = get_post_meta( $post_id, '_actor_twitter', true );

    $schema = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'Person',
        'name'        => get_the_title(),
        'url'         => get_permalink(),
        'description' => hotboys_get_seo_description( $post_id ),
        'jobTitle'    => 'Ator',
    );

    if ( has_post_thumbnail() ) {
        $schema['image'] = get_the_post_thumbnail_url( $post_id, 'actor-large' );
    }

    $same_as = array();
    if ( $instagram ) $same_as[] = $instagram;
    if ( $twitter ) $same_as[] = $twitter;

    if ( ! empty( $same_as ) ) {
        $schema['sameAs'] = $same_as;
    }

    return $schema;
}

/**
 * Schema: ItemList (para archives e taxonomias)
 */
function hotboys_schema_item_list( $type = 'scene' ) {
    global $wp_query;

    if ( ! $wp_query->have_posts() ) {
        return array();
    }

    $items = array();
    $position = 1;

    foreach ( $wp_query->posts as $post ) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position,
            'url'      => get_permalink( $post->ID ),
            'name'     => get_the_title( $post->ID ),
        );
        $position++;
    }

    return array(
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'itemListElement' => $items,
    );
}

/**
 * Schema: BreadcrumbList
 */
function hotboys_schema_breadcrumbs() {
    $items = array();
    $position = 1;

    // Home sempre primeiro
    $items[] = array(
        '@type'    => 'ListItem',
        'position' => $position++,
        'name'     => 'Home',
        'item'     => home_url( '/' ),
    );

    if ( is_singular( 'scene' ) ) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => 'Cenas',
            'item'     => get_post_type_archive_link( 'scene' ),
        );
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => get_the_title(),
            'item'     => get_permalink(),
        );
    } elseif ( is_singular( 'actor' ) ) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => 'Atores',
            'item'     => get_post_type_archive_link( 'actor' ),
        );
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => get_the_title(),
            'item'     => get_permalink(),
        );
    } elseif ( is_post_type_archive( 'scene' ) ) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => 'Cenas',
            'item'     => get_post_type_archive_link( 'scene' ),
        );
    } elseif ( is_post_type_archive( 'actor' ) ) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => 'Atores',
            'item'     => get_post_type_archive_link( 'actor' ),
        );
    } elseif ( is_tax( 'scene_category' ) || is_tax( 'scene_tag' ) ) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => 'Cenas',
            'item'     => get_post_type_archive_link( 'scene' ),
        );
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => single_term_title( '', false ),
            'item'     => get_term_link( get_queried_object() ),
        );
    } elseif ( is_search() ) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => 'Busca',
            'item'     => get_search_link(),
        );
    }

    if ( count( $items ) < 2 ) {
        return array();
    }

    return array(
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    );
}

/**
 * Helper: gerar descricao SEO para um post
 */
function hotboys_get_seo_description( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        return '';
    }

    // Se tem excerpt, usar
    if ( ! empty( $post->post_excerpt ) ) {
        return wp_strip_all_tags( $post->post_excerpt );
    }

    // Senao, gerar do conteudo
    if ( ! empty( $post->post_content ) ) {
        $content = wp_strip_all_tags( $post->post_content );
        return wp_trim_words( $content, 25, '...' );
    }

    return '';
}
