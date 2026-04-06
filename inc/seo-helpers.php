<?php
/**
 * SEO Helpers - Open Graph, Twitter Cards, Canonical, Meta Descriptions
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Verificar se plugin de SEO esta ativo (Yoast ou RankMath)
 */
function hotboys_seo_plugin_active() {
    return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' );
}

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
            $og['og:image'] = get_the_post_thumbnail_url( get_the_ID(), 'scene-large' );
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
        $og['og:title']       = get_the_title() . ' - Ator HotBoys';
        $og['og:description'] = hotboys_generate_meta_description();
        $og['og:url']         = get_permalink();

        if ( has_post_thumbnail() ) {
            $og['og:image'] = get_the_post_thumbnail_url( get_the_ID(), 'actor-large' );
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
        $excerpt  = get_the_excerpt();

        $desc = sprintf( 'Assista %s', $title );
        if ( $actors ) {
            $desc .= sprintf( ' com %s', $actors );
        }
        $desc .= '.';
        if ( $excerpt ) {
            $desc .= ' ' . wp_trim_words( $excerpt, 15, '...' );
        }
        if ( $duration ) {
            $desc .= sprintf( ' Duração: %s.', $duration );
        }

        return mb_substr( $desc, 0, 160 );
    }

    if ( is_singular( 'actor' ) ) {
        $name        = get_the_title();
        $scene_count = hotboys_get_actor_scene_count();
        $bio         = get_the_excerpt();

        $desc = sprintf( '%s - Ator HotBoys.', $name );
        if ( $bio ) {
            $desc .= ' ' . wp_trim_words( $bio, 15, '...' );
        }
        $desc .= sprintf( ' %d cenas.', $scene_count );

        return mb_substr( $desc, 0, 160 );
    }

    if ( is_tax( 'scene_category' ) || is_tax( 'scene_tag' ) ) {
        $term = get_queried_object();
        if ( $term->description ) {
            return mb_substr( $term->description, 0, 160 );
        }
        return sprintf(
            'Cenas de %s no HotBoys. %d vídeos disponíveis.',
            $term->name,
            $term->count
        );
    }

    if ( is_post_type_archive( 'scene' ) ) {
        return 'Explore nosso catálogo completo de cenas exclusivas HotBoys. Centenas de vídeos em alta qualidade.';
    }

    if ( is_post_type_archive( 'actor' ) ) {
        return 'Conheça todos os atores exclusivos HotBoys. Perfis completos com filmografia.';
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
