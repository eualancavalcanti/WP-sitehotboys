<?php
/**
 * SEO Helpers - Open Graph, Twitter Cards, Canonical, Meta Descriptions, Robots
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Verificar se plugin de SEO esta ativo (Yoast, RankMath ou AIOSEO)
 */
function hotboys_seo_plugin_active() {
    return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'AIOSEO_VERSION' );
}

/**
 * Meta robots tags — controla indexacao por tipo de pagina
 */
function hotboys_meta_robots() {
    if ( hotboys_seo_plugin_active() ) {
        return;
    }

    $robots = array();

    if ( is_404() ) {
        $robots[] = 'noindex';
        $robots[] = 'follow';
    } elseif ( is_search() ) {
        $robots[] = 'noindex';
        $robots[] = 'follow';
    } elseif ( is_singular( 'scene' ) || is_singular( 'actor' ) ) {
        $robots[] = 'index';
        $robots[] = 'follow';
        $robots[] = 'max-image-preview:large';
        $robots[] = 'max-snippet:-1';
        $robots[] = 'max-video-preview:-1';
    } elseif ( is_post_type_archive( 'scene' ) || is_post_type_archive( 'actor' ) ) {
        $robots[] = 'index';
        $robots[] = 'follow';
        $robots[] = 'max-image-preview:large';
    } elseif ( is_tax( 'scene_category' ) || is_tax( 'scene_tag' ) ) {
        $term = get_queried_object();
        if ( $term && $term->count > 0 ) {
            $robots[] = 'index';
            $robots[] = 'follow';
            $robots[] = 'max-image-preview:large';
        } else {
            $robots[] = 'noindex';
            $robots[] = 'follow';
        }
    } elseif ( is_front_page() ) {
        $robots[] = 'index';
        $robots[] = 'follow';
        $robots[] = 'max-image-preview:large';
        $robots[] = 'max-snippet:-1';
        $robots[] = 'max-video-preview:-1';
    } elseif ( is_page() ) {
        $robots[] = 'index';
        $robots[] = 'follow';
    } else {
        $robots[] = 'index';
        $robots[] = 'follow';
    }

    if ( ! empty( $robots ) ) {
        printf( '<meta name="robots" content="%s">' . "\n", esc_attr( implode( ', ', $robots ) ) );
    }
}
add_action( 'wp_head', 'hotboys_meta_robots', 1 );

/**
 * Customiza title tag por tipo de pagina
 */
function hotboys_document_title_parts( $title ) {
    if ( hotboys_seo_plugin_active() ) {
        return $title;
    }

    $site_name = get_bloginfo( 'name' );

    if ( is_singular( 'scene' ) ) {
        $quality = get_post_meta( get_the_ID(), '_scene_quality', true );
        $suffix = $quality ? " {$quality}" : '';
        $title['title'] = get_the_title() . $suffix . ' | Cena Exclusiva';
        $title['site'] = $site_name;
    } elseif ( is_singular( 'actor' ) ) {
        $title['title'] = get_the_title() . ' - Perfil e Filmografia';
        $title['site'] = $site_name;
    } elseif ( is_post_type_archive( 'scene' ) ) {
        $paged = get_query_var( 'paged' );
        $title['title'] = 'Catálogo de Cenas Exclusivas';
        if ( $paged > 1 ) {
            $title['title'] .= " - Página {$paged}";
        }
        $title['site'] = $site_name;
    } elseif ( is_post_type_archive( 'actor' ) ) {
        $paged = get_query_var( 'paged' );
        $title['title'] = 'Atores Exclusivos';
        if ( $paged > 1 ) {
            $title['title'] .= " - Página {$paged}";
        }
        $title['site'] = $site_name;
    } elseif ( is_tax( 'scene_category' ) ) {
        $term = get_queried_object();
        $title['title'] = $term->name . ' - Cenas Exclusivas';
        $title['site'] = $site_name;
    } elseif ( is_tax( 'scene_tag' ) ) {
        $term = get_queried_object();
        $title['title'] = $term->name . ' - Vídeos';
        $title['site'] = $site_name;
    } elseif ( is_front_page() ) {
        $custom_title = get_theme_mod( 'hotboys_seo_title', '' );
        if ( $custom_title ) {
            $title['title'] = $custom_title;
        }
    }

    return $title;
}
add_filter( 'document_title_parts', 'hotboys_document_title_parts' );
add_filter( 'document_title_separator', function() { return '|'; } );

/**
 * Open Graph + Twitter Card meta tags
 */
function hotboys_og_tags() {
    // Se Yoast/RankMath ativo, nao gerar para evitar duplicacao
    if ( hotboys_seo_plugin_active() ) {
        return;
    }

    $og = array();
    $og['og:site_name'] = get_bloginfo( 'name' );
    $og['og:locale']    = 'pt_BR';

    // Twitter Card base
    $twitter = array();
    $twitter['twitter:card'] = 'summary_large_image';

    $twitter_handle = get_theme_mod( 'hotboys_twitter_handle', '' );
    if ( $twitter_handle ) {
        $twitter['twitter:site'] = $twitter_handle;
    }

    if ( is_front_page() ) {
        $og['og:type']        = 'website';
        $og['og:title']       = get_bloginfo( 'name' );
        $og['og:description'] = get_bloginfo( 'description' );
        $og['og:url']         = home_url( '/' );

        $logo_id = get_theme_mod( 'custom_logo' );
        if ( $logo_id ) {
            $og['og:image'] = wp_get_attachment_image_url( $logo_id, 'full' );
        }

    } elseif ( is_singular( 'scene' ) ) {
        $og['og:type']        = 'video.other';
        $og['og:title']       = get_the_title() . ' - ' . get_bloginfo( 'name' );
        $og['og:description'] = hotboys_generate_meta_description();
        $og['og:url']         = get_permalink();

        if ( has_post_thumbnail() ) {
            $thumb_id = get_post_thumbnail_id();
            $og['og:image'] = get_the_post_thumbnail_url( get_the_ID(), 'scene-large' );
            $image_meta = wp_get_attachment_metadata( $thumb_id );
            if ( $image_meta ) {
                $og['og:image:width']  = 800;
                $og['og:image:height'] = 450;
            }
            $og['og:image:type'] = get_post_mime_type( $thumb_id );
        }

        $duration = get_post_meta( get_the_ID(), '_scene_duration', true );
        if ( $duration ) {
            $parts = explode( ':', $duration );
            if ( count( $parts ) === 2 ) {
                $og['video:duration'] = ( (int) $parts[0] * 60 ) + (int) $parts[1];
            }
        }

    } elseif ( is_singular( 'actor' ) ) {
        $og['og:type']        = 'profile';
        $og['og:title']       = get_the_title() . ' - Perfil e Filmografia | HotBoys';
        $og['og:description'] = hotboys_generate_meta_description();
        $og['og:url']         = get_permalink();

        if ( has_post_thumbnail() ) {
            $thumb_id = get_post_thumbnail_id();
            $og['og:image'] = get_the_post_thumbnail_url( get_the_ID(), 'actor-large' );
            $og['og:image:width']  = 600;
            $og['og:image:height'] = 800;
            $og['og:image:type'] = get_post_mime_type( $thumb_id );
        }

        $og['profile:username'] = sanitize_title( get_the_title() );

    } elseif ( is_post_type_archive( 'scene' ) ) {
        $og['og:type']        = 'website';
        $og['og:title']       = 'Todas as Cenas - ' . get_bloginfo( 'name' );
        $og['og:description'] = 'Explore nosso catálogo completo de cenas exclusivas HotBoys.';
        $og['og:url']         = get_post_type_archive_link( 'scene' );

    } elseif ( is_post_type_archive( 'actor' ) ) {
        $og['og:type']        = 'website';
        $og['og:title']       = 'Nossos Atores - ' . get_bloginfo( 'name' );
        $og['og:description'] = 'Conheça todos os atores exclusivos HotBoys.';
        $og['og:url']         = get_post_type_archive_link( 'actor' );

    } elseif ( is_tax( 'scene_category' ) || is_tax( 'scene_tag' ) ) {
        $term = get_queried_object();
        $og['og:type']        = 'website';
        $og['og:title']       = $term->name . ' - ' . get_bloginfo( 'name' );
        $og['og:description'] = hotboys_generate_meta_description();
        $og['og:url']         = get_term_link( $term );

    } elseif ( is_search() ) {
        $og['og:type']        = 'website';
        $og['og:title']       = 'Busca: ' . get_search_query() . ' - ' . get_bloginfo( 'name' );
        $og['og:url']         = get_search_link();

    } else {
        $og['og:type']  = 'website';
        $og['og:title'] = wp_get_document_title();
        $og['og:url']   = home_url( add_query_arg( array() ) );
    }

    // Output OG tags
    foreach ( $og as $property => $content ) {
        if ( ! empty( $content ) ) {
            printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
        }
    }

    // Twitter tags (herda do OG quando possivel)
    $twitter['twitter:title']       = isset( $og['og:title'] ) ? $og['og:title'] : '';
    $twitter['twitter:description'] = isset( $og['og:description'] ) ? $og['og:description'] : '';
    $twitter['twitter:image']       = isset( $og['og:image'] ) ? $og['og:image'] : '';

    foreach ( $twitter as $name => $content ) {
        if ( ! empty( $content ) ) {
            printf( '<meta name="%s" content="%s">' . "\n", esc_attr( $name ), esc_attr( $content ) );
        }
    }
}
add_action( 'wp_head', 'hotboys_og_tags', 3 );

/**
 * Canonical URL
 */
function hotboys_canonical_url() {
    if ( hotboys_seo_plugin_active() ) {
        return;
    }

    $canonical = '';

    if ( is_front_page() ) {
        $canonical = home_url( '/' );
    } elseif ( is_singular() ) {
        $canonical = get_permalink();
    } elseif ( is_post_type_archive() ) {
        $canonical = get_post_type_archive_link( get_post_type() );
    } elseif ( is_tax() ) {
        $canonical = get_term_link( get_queried_object() );
    } elseif ( is_search() ) {
        $canonical = get_search_link();
    }

    // Paginacao: canonical aponta para si mesma
    $paged = get_query_var( 'paged' );
    if ( $paged > 1 && $canonical ) {
        $canonical = trailingslashit( $canonical ) . 'page/' . $paged . '/';
    }

    if ( $canonical && ! is_wp_error( $canonical ) ) {
        printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
    }
}
add_action( 'wp_head', 'hotboys_canonical_url', 2 );

/**
 * Meta Description auto-gerada
 */
function hotboys_meta_description() {
    if ( hotboys_seo_plugin_active() ) {
        return;
    }

    $description = hotboys_generate_meta_description();

    if ( ! empty( $description ) ) {
        printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
    }
}
add_action( 'wp_head', 'hotboys_meta_description', 3 );

/**
 * Gerar meta description baseado no contexto
 */
function hotboys_generate_meta_description() {
    if ( is_front_page() ) {
        $custom = get_theme_mod( 'hotboys_meta_description', '' );
        if ( $custom ) {
            return $custom;
        }
        return get_bloginfo( 'description' );
    }

    if ( is_singular( 'scene' ) ) {
        $title    = get_the_title();
        $actors   = hotboys_get_actor_names();
        $duration = get_post_meta( get_the_ID(), '_scene_duration', true );
        $quality  = get_post_meta( get_the_ID(), '_scene_quality', true );
        $excerpt  = get_the_excerpt();
        $categories = get_the_terms( get_the_ID(), 'scene_category' );

        $desc = sprintf( 'Assista %s', $title );
        if ( $quality ) {
            $desc .= sprintf( ' em %s', $quality );
        }
        if ( $actors ) {
            $desc .= sprintf( ' com %s', $actors );
        }
        $desc .= ' - cena exclusiva HotBoys.';
        if ( $duration ) {
            $desc .= sprintf( ' %s min.', $duration );
        }
        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            $cat_names = wp_list_pluck( array_slice( $categories, 0, 3 ), 'name' );
            $desc .= ' ' . implode( ', ', $cat_names ) . '.';
        }

        return mb_substr( $desc, 0, 160 );
    }

    if ( is_singular( 'actor' ) ) {
        $name        = get_the_title();
        $scene_count = hotboys_get_actor_scene_count();
        $bio         = get_the_excerpt();
        $city        = get_post_meta( get_the_ID(), '_actor_city', true );

        $desc = sprintf( '%s - Ator exclusivo HotBoys', $name );
        if ( $city ) {
            $desc .= sprintf( ' de %s', $city );
        }
        $desc .= sprintf( '. %d cenas disponíveis.', $scene_count );
        if ( $bio ) {
            $desc .= ' ' . wp_trim_words( $bio, 12, '...' );
        }

        return mb_substr( $desc, 0, 160 );
    }

    if ( is_tax( 'scene_category' ) || is_tax( 'scene_tag' ) ) {
        $term = get_queried_object();
        if ( $term->description ) {
            return mb_substr( $term->description, 0, 160 );
        }
        $type_label = is_tax( 'scene_category' ) ? 'Cenas' : 'Vídeos';
        return sprintf(
            '%s de %s no HotBoys. %d vídeos exclusivos em alta qualidade para assistir agora.',
            $type_label,
            $term->name,
            $term->count
        );
    }

    if ( is_post_type_archive( 'scene' ) ) {
        return 'Catálogo completo de cenas exclusivas HotBoys. Vídeos em HD e 4K com os melhores atores do Brasil. Atualizado diariamente.';
    }

    if ( is_post_type_archive( 'actor' ) ) {
        return 'Conheça todos os atores exclusivos HotBoys. Perfis completos com filmografia, fotos e redes sociais.';
    }

    if ( is_search() ) {
        return sprintf( 'Resultados de busca para "%s" no HotBoys.', get_search_query() );
    }

    return '';
}

/**
 * Rel prev/next para paginacao
 */
function hotboys_rel_prev_next() {
    global $wp_query;

    if ( ! is_singular() && $wp_query->max_num_pages > 1 ) {
        $paged = max( 1, get_query_var( 'paged' ) );

        if ( $paged > 1 ) {
            $prev = get_previous_posts_page_link();
            if ( $prev ) {
                printf( '<link rel="prev" href="%s">' . "\n", esc_url( $prev ) );
            }
        }

        if ( $paged < $wp_query->max_num_pages ) {
            $next = get_next_posts_page_link();
            if ( $next ) {
                printf( '<link rel="next" href="%s">' . "\n", esc_url( $next ) );
            }
        }
    }
}
add_action( 'wp_head', 'hotboys_rel_prev_next', 4 );

/**
 * Fallback menu (quando nenhum menu esta configurado)
 */
function hotboys_fallback_menu() {
    echo '<ul class="nav-menu">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li>';
    echo '<li><a href="' . esc_url( get_post_type_archive_link( 'scene' ) ) . '">Cenas</a></li>';
    echo '<li><a href="' . esc_url( get_post_type_archive_link( 'actor' ) ) . '">Atores</a></li>';
    echo '</ul>';
}
