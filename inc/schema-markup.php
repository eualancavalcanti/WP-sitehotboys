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
        $schemas[] = hotboys_schema_faq();
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

    // SiteNavigationElement em todas as paginas
    $schemas[] = hotboys_schema_navigation();

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
    $quality      = get_post_meta( $post_id, '_scene_quality', true );
    $actors       = hotboys_get_scene_actors( $post_id );
    $categories   = get_the_terms( $post_id, 'scene_category' );
    $tags         = get_the_terms( $post_id, 'scene_tag' );

    $schema = array(
        '@context'         => 'https://schema.org',
        '@type'            => 'VideoObject',
        'name'             => get_the_title(),
        'description'      => hotboys_get_seo_description( $post_id ),
        'url'              => get_permalink(),
        'isFamilyFriendly' => false,
        'inLanguage'       => 'pt-BR',
    );

    // Thumbnail como ImageObject com dimensoes
    if ( has_post_thumbnail() ) {
        $thumb_id  = get_post_thumbnail_id( $post_id );
        $thumb_url = get_the_post_thumbnail_url( $post_id, 'scene-large' );
        $schema['thumbnailUrl'] = $thumb_url;
        $schema['image'] = array(
            '@type'  => 'ImageObject',
            'url'    => $thumb_url,
            'width'  => 800,
            'height' => 450,
        );
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

    // Qualidade do video
    if ( $quality ) {
        $schema['videoQuality'] = $quality;
    }

    // Keywords geradas de categorias e tags
    $keywords = array();
    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
        foreach ( $categories as $cat ) {
            $keywords[] = $cat->name;
        }
        // Genre extraido das categorias
        $schema['genre'] = wp_list_pluck( $categories, 'name' );
    }
    if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
        foreach ( $tags as $tag ) {
            $keywords[] = $tag->name;
        }
    }
    if ( ! empty( $keywords ) ) {
        $schema['keywords'] = implode( ', ', $keywords );
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

    $logo_id = get_theme_mod( 'custom_logo' );
    if ( $logo_id ) {
        $schema['publisher']['logo'] = array(
            '@type' => 'ImageObject',
            'url'   => wp_get_attachment_image_url( $logo_id, 'full' ),
        );
    }

    return $schema;
}

/**
 * Schema: Person (para pagina individual de ator)
 */
function hotboys_schema_person() {
    $post_id   = get_the_ID();
    $instagram = get_post_meta( $post_id, '_actor_instagram', true );
    $twitter   = get_post_meta( $post_id, '_actor_twitter', true );
    $city      = get_post_meta( $post_id, '_actor_city', true );
    $age       = get_post_meta( $post_id, '_actor_age', true );

    $schema = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'Person',
        'name'        => get_the_title(),
        'url'         => get_permalink(),
        'description' => hotboys_get_seo_description( $post_id ),
        'jobTitle'    => 'Ator',
        'nationality' => array(
            '@type' => 'Country',
            'name'  => 'Brasil',
        ),
    );

    if ( has_post_thumbnail() ) {
        $schema['image'] = array(
            '@type'  => 'ImageObject',
            'url'    => get_the_post_thumbnail_url( $post_id, 'actor-large' ),
            'width'  => 600,
            'height' => 800,
        );
    }

    if ( $city ) {
        $schema['homeLocation'] = array(
            '@type' => 'Place',
            'name'  => $city,
        );
    }

    // Cenas em que participou (performerIn)
    $scenes = hotboys_get_actor_scenes( $post_id, 1, 100 );
    if ( $scenes->have_posts() ) {
        $schema['performerIn'] = array();
        foreach ( $scenes->posts as $scene ) {
            $schema['performerIn'][] = array(
                '@type' => 'VideoObject',
                'name'  => $scene->post_title,
                'url'   => get_permalink( $scene->ID ),
            );
        }
        wp_reset_postdata();
    }

    // Categorias frequentes (knowsAbout)
    $cat_counts = array();
    if ( $scenes->have_posts() ) {
        foreach ( $scenes->posts as $scene ) {
            $cats = get_the_terms( $scene->ID, 'scene_category' );
            if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
                foreach ( $cats as $cat ) {
                    if ( ! isset( $cat_counts[ $cat->name ] ) ) {
                        $cat_counts[ $cat->name ] = 0;
                    }
                    $cat_counts[ $cat->name ]++;
                }
            }
        }
    }
    if ( ! empty( $cat_counts ) ) {
        arsort( $cat_counts );
        $schema['knowsAbout'] = array_keys( array_slice( $cat_counts, 0, 5 ) );
    }

    $same_as = array();
    if ( $instagram ) $same_as[] = $instagram;
    if ( $twitter ) $same_as[] = $twitter;

    if ( ! empty( $same_as ) ) {
        $schema['sameAs'] = $same_as;
    }

    // Afiliado a organização
    $schema['memberOf'] = array(
        '@type' => 'Organization',
        'name'  => get_bloginfo( 'name' ),
        'url'   => home_url( '/' ),
    );

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
        // Categoria principal no breadcrumb schema
        $scene_cats = get_the_terms( get_the_ID(), 'scene_category' );
        if ( ! empty( $scene_cats ) && ! is_wp_error( $scene_cats ) ) {
            $primary_cat = $scene_cats[0];
            $cat_link = get_term_link( $primary_cat );
            if ( ! is_wp_error( $cat_link ) ) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => $primary_cat->name,
                    'item'     => $cat_link,
                );
            }
        }
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

/**
 * Schema: SiteNavigationElement
 */
function hotboys_schema_navigation() {
    $nav_items = array();

    $nav_items[] = array(
        '@type' => 'SiteNavigationElement',
        'name'  => 'Home',
        'url'   => home_url( '/' ),
    );

    $scenes_url = get_post_type_archive_link( 'scene' );
    if ( $scenes_url ) {
        $nav_items[] = array(
            '@type' => 'SiteNavigationElement',
            'name'  => 'Cenas',
            'url'   => $scenes_url,
        );
    }

    $actors_url = get_post_type_archive_link( 'actor' );
    if ( $actors_url ) {
        $nav_items[] = array(
            '@type' => 'SiteNavigationElement',
            'name'  => 'Atores',
            'url'   => $actors_url,
        );
    }

    // Categorias populares na navegação
    $categories = get_terms( array(
        'taxonomy'   => 'scene_category',
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 5,
        'hide_empty' => true,
    ) );

    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
        foreach ( $categories as $cat ) {
            $nav_items[] = array(
                '@type' => 'SiteNavigationElement',
                'name'  => $cat->name,
                'url'   => get_term_link( $cat ),
            );
        }
    }

    if ( empty( $nav_items ) ) {
        return array();
    }

    return array(
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'itemListElement' => $nav_items,
    );
}

/**
 * Schema: FAQPage para front-page
 */
function hotboys_schema_faq() {
    $faqs = array(
        array(
            'q' => 'O que é o HotBoys?',
            'a' => 'HotBoys é uma das maiores produtoras de conteúdo adulto do Brasil, com um catálogo exclusivo de cenas e atores profissionais.',
        ),
        array(
            'q' => 'Como assistir as cenas exclusivas?',
            'a' => 'Navegue pelo catálogo de cenas no site e clique em "Assistir Completo" para ser redirecionado ao conteúdo premium na plataforma oficial.',
        ),
        array(
            'q' => 'Os vídeos estão disponíveis em HD e 4K?',
            'a' => 'Sim, a maioria das cenas está disponível em alta definição (HD) e muitas também em qualidade 4K para a melhor experiência.',
        ),
        array(
            'q' => 'Como encontrar um ator específico?',
            'a' => 'Use a página de Atores para explorar perfis completos com filmografia, ou utilize a busca no topo do site para encontrar atores por nome.',
        ),
        array(
            'q' => 'O conteúdo é atualizado com frequência?',
            'a' => 'Sim, novas cenas são adicionadas regularmente ao catálogo com atores exclusivos e produções de alta qualidade.',
        ),
    );

    $faq_items = array();
    foreach ( $faqs as $faq ) {
        $faq_items[] = array(
            '@type'          => 'Question',
            'name'           => $faq['q'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $faq['a'],
            ),
        );
    }

    return array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $faq_items,
    );
}
