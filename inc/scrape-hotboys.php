<?php
/**
 * HotBoys Web Scraper — WP-CLI Command
 *
 * Importa cenas e atores a partir do site público hotboys.com.br via sitemap.
 * Alternativa ao import-hotboys.php quando não se tem acesso ao banco de dados.
 *
 * Uso:
 *   wp hotboys-scrape scrape_actors [--dry-run] [--limit=N] [--with-images]
 *   wp hotboys-scrape scrape_scenes [--dry-run] [--limit=N] [--with-images]
 *   wp hotboys-scrape scrape_all    [--dry-run] [--limit=N] [--with-images]
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class HotBoys_Scrape_Command {

    /**
     * Base URL of the HotBoys site.
     */
    const BASE_URL = 'https://www.hotboys.com.br';

    /**
     * Sitemap URLs.
     */
    const SITEMAP_ACTORS = 'https://www.hotboys.com.br/sitemap-atores.xml';
    const SITEMAP_SCENES = 'https://www.hotboys.com.br/sitemap-cenas.xml';

    /**
     * Actor slug => WP post ID map (built during scrape_actors or from existing).
     *
     * @var array
     */
    private $actor_map = array();

    /**
     * Fetch a URL with SSL verification disabled (hotboys.com.br cert issue).
     *
     * @param string $url URL to fetch.
     * @return string|false HTML body or false on error.
     */
    private function fetch_url( $url ) {
        $response = wp_remote_get( $url, array(
            'timeout'   => 30,
            'sslverify' => false,
            'headers'   => array(
                'User-Agent' => 'Mozilla/5.0 (compatible; HotBoys WP Importer/1.0)',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            WP_CLI::warning( "Erro ao buscar {$url}: " . $response->get_error_message() );
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            WP_CLI::warning( "HTTP {$code} ao buscar {$url}" );
            return false;
        }

        return wp_remote_retrieve_body( $response );
    }

    /**
     * Parse sitemap XML and return array of URLs.
     *
     * @param string $sitemap_url Sitemap URL.
     * @return array URLs found.
     */
    private function parse_sitemap( $sitemap_url ) {
        $body = $this->fetch_url( $sitemap_url );
        if ( ! $body ) {
            return array();
        }

        $urls = array();
        // Use regex to extract <loc> tags (more robust than XML parsing for encoding issues).
        if ( preg_match_all( '/<loc>\s*(.*?)\s*<\/loc>/si', $body, $matches ) ) {
            $urls = $matches[1];
        }

        return $urls;
    }

    /**
     * Extract scene ID from URL pattern /cena/{id}/{slug}.
     *
     * @param string $url Scene URL.
     * @return string Scene ID or empty string.
     */
    private function extract_scene_id( $url ) {
        if ( preg_match( '/\/cena\/(\d+)/', $url, $m ) ) {
            return $m[1];
        }
        return '';
    }

    /**
     * Extract actor slug from URL pattern /ator/{slug}.
     *
     * @param string $url Actor URL.
     * @return string Actor slug or empty string.
     */
    private function extract_actor_slug( $url ) {
        if ( preg_match( '/\/ator\/([^\/\?]+)/', $url, $m ) ) {
            return $m[1];
        }
        return '';
    }

    /**
     * Parse an actor page and return structured data.
     *
     * @param string $html Raw HTML.
     * @param string $url  Source URL.
     * @return array|false Parsed data or false.
     */
    private function parse_actor_page( $html, $url ) {
        $data = array(
            'slug'        => $this->extract_actor_slug( $url ),
            'source_url'  => $url,
            'name'        => '',
            'bio'         => '',
            'photo_url'   => '',
            'scene_count' => 0,
            'views'       => 0,
        );

        // Name from <title>: "Mlk Edu - Ator Pornô Gay Brasileiro | 52 Vídeos..."
        if ( preg_match( '/<title>\s*(.+?)\s*-\s*Ator\b/iu', $html, $m ) ) {
            $data['name'] = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
        }

        // OG image for photo.
        if ( preg_match( '/property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $html, $m ) ) {
            $data['photo_url'] = $m[1];
        }

        // Stats: scene count and views from hb-stat-number spans.
        if ( preg_match_all( '/<span\s+class=["\']hb-stat-number["\']>\s*([\d,\.]+)\s*<\/span>/i', $html, $m ) ) {
            if ( isset( $m[1][0] ) ) {
                $data['scene_count'] = (int) str_replace( array( ',', '.' ), '', $m[1][0] );
            }
            if ( isset( $m[1][1] ) ) {
                $data['views'] = (int) str_replace( array( ',', '.' ), '', $m[1][1] );
            }
        }

        // Bio from itemprop="description" section.
        if ( preg_match( '/itemprop=["\']description["\'][^>]*>.*?<\/div>\s*<div[^>]*>\s*(.*?)\s*<p\s+style/si', $html, $m ) ) {
            $bio_raw = strip_tags( $m[1] );
            $data['bio'] = trim( html_entity_decode( $bio_raw, ENT_QUOTES, 'UTF-8' ) );
        }

        // Fallback bio: look for the text block after "Biografia de"
        if ( empty( $data['bio'] ) && preg_match( '/Biografia\s+de\b.*?<\/h2>.*?<div[^>]*>\s*(.*?)<\/div>/si', $html, $m ) ) {
            $bio_raw = strip_tags( $m[1] );
            $bio_raw = trim( html_entity_decode( $bio_raw, ENT_QUOTES, 'UTF-8' ) );
            // Remove the generic SEO footer text.
            $bio_raw = preg_replace( '/\s*é um dos atores pornô gay mais populares.*/si', '', $bio_raw );
            $data['bio'] = trim( $bio_raw );
        }

        // Fallback name from OG title.
        if ( empty( $data['name'] ) && preg_match( '/property=["\']og:title["\']\s+content=["\']([^"\']+)["\']/i', $html, $m ) ) {
            $name = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
            $name = preg_replace( '/\s*[-|].*$/', '', $name );
            $data['name'] = trim( $name );
        }

        // Fallback name from slug.
        if ( empty( $data['name'] ) && $data['slug'] ) {
            $data['name'] = ucwords( str_replace( '-', ' ', $data['slug'] ) );
        }

        return $data;
    }

    /**
     * Parse a scene page and return structured data.
     *
     * @param string $html Raw HTML.
     * @param string $url  Source URL.
     * @return array|false Parsed data or false.
     */
    private function parse_scene_page( $html, $url ) {
        $scene_id = $this->extract_scene_id( $url );

        $data = array(
            'remote_id'    => $scene_id,
            'source_url'   => $url,
            'title'        => '',
            'description'  => '',
            'duration'     => '',
            'thumbnail'    => '',
            'upload_date'  => '',
            'actors'       => array(), // array of slugs
            'actor_names'  => array(), // array of display names
            'tags'         => array(), // array of tag names
        );

        // JSON-LD VideoObject.
        if ( preg_match( '/<script\s+type=["\']application\/ld\+json["\']>\s*(\{[^<]*"@type"\s*:\s*"VideoObject"[^<]*\})\s*<\/script>/si', $html, $m ) ) {
            $json = json_decode( $m[1], true );
            if ( $json ) {
                if ( ! empty( $json['name'] ) ) {
                    $data['title'] = html_entity_decode( $json['name'], ENT_QUOTES, 'UTF-8' );
                }
                if ( ! empty( $json['thumbnailUrl'] ) ) {
                    $data['thumbnail'] = $json['thumbnailUrl'];
                }
                if ( ! empty( $json['uploadDate'] ) ) {
                    $data['upload_date'] = $json['uploadDate'];
                }
            }
        }

        // Title fallback from h1.
        if ( empty( $data['title'] ) && preg_match( '/<h1\s+class=["\']hb-scene-title["\']>\s*(.*?)\s*<\/h1>/si', $html, $m ) ) {
            $data['title'] = html_entity_decode( trim( strip_tags( $m[1] ) ), ENT_QUOTES, 'UTF-8' );
        }

        // Title fallback from <title> tag.
        if ( empty( $data['title'] ) && preg_match( '/<title>\s*(.+?)\s*[-|]/u', $html, $m ) ) {
            $data['title'] = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
        }

        // Description from data-full-text attribute (most complete).
        if ( preg_match( '/data-full-text=["\']([^"\']+)["\']/i', $html, $m ) ) {
            $data['description'] = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
        }

        // Description fallback from meta description.
        if ( empty( $data['description'] ) && preg_match( '/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $html, $m ) ) {
            $data['description'] = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
        }

        // Duration from hb-scene-meta__value (the first one after clock icon).
        if ( preg_match( '/hb-scene-meta__icon["\']><\/i>\s*<span\s+class=["\']hb-scene-meta__value["\']>\s*([\d:]+)\s*<\/span>/i', $html, $m ) ) {
            $data['duration'] = trim( $m[1] );
        }

        // Actors: extract from elenco links.
        if ( preg_match_all( '/href=["\']https?:\/\/(?:www\.)?hotboys\.com\.br\/ator\/([^"\'\/]+)["\'][^>]*class=["\']hb-elenco-link["\']/i', $html, $m ) ) {
            $data['actors'] = $m[1];
        }

        // Actor names from hb-elenco-nome.
        if ( preg_match_all( '/<h4\s+class=["\']hb-elenco-nome["\']>\s*(.*?)\s*<\/h4>/si', $html, $m ) ) {
            $data['actor_names'] = array_map( function( $n ) {
                return html_entity_decode( trim( strip_tags( $n ) ), ENT_QUOTES, 'UTF-8' );
            }, $m[1] );
        }

        // Tags from tag links.
        if ( preg_match_all( '/href=["\']https?:\/\/(?:www\.)?hotboys\.com\.br\/tag\/([^"\'\/]+)\/?["\'][^>]*class=["\']hb-tag-item["\']/i', $html, $m ) ) {
            $data['tags'] = array_map( function( $slug ) {
                return ucfirst( str_replace( '-', ' ', $slug ) );
            }, $m[1] );
        }

        return $data;
    }

    /**
     * Build actor slug => WP post ID map from existing actors.
     */
    private function build_actor_map() {
        $actors = get_posts( array(
            'post_type'      => 'actor',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ) );

        foreach ( $actors as $actor_id ) {
            $source = get_post_meta( $actor_id, '_hotboys_source_url', true );
            if ( $source ) {
                $slug = $this->extract_actor_slug( $source );
                if ( $slug ) {
                    $this->actor_map[ $slug ] = $actor_id;
                    continue;
                }
            }
            // Fallback to post_name.
            $post = get_post( $actor_id );
            if ( $post ) {
                $this->actor_map[ $post->post_name ] = $actor_id;
            }
        }

        WP_CLI::log( sprintf( 'Actor map: %d atores já existem no WP.', count( $this->actor_map ) ) );
    }

    /**
     * Scrape and import actors from hotboys.com.br sitemap.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Show what would be imported without creating posts.
     *
     * [--limit=<num>]
     * : Max number of actors to import.
     *
     * [--with-images]
     * : Download and set featured images.
     *
     * [--url=<url>]
     * : Scrape a single actor URL instead of the sitemap.
     *
     * ## EXAMPLES
     *
     *   wp hotboys-scrape scrape_actors --dry-run --limit=5
     *   wp hotboys-scrape scrape_actors --with-images
     *   wp hotboys-scrape scrape_actors --url=https://www.hotboys.com.br/ator/mlkedu
     *
     * @subcommand scrape_actors
     */
    public function scrape_actors( $args, $assoc_args ) {
        $dry_run     = isset( $assoc_args['dry-run'] );
        $with_images = isset( $assoc_args['with-images'] );
        $limit       = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 0;
        $single_url  = isset( $assoc_args['url'] ) ? $assoc_args['url'] : '';

        WP_CLI::log( '=== SCRAPER DE ATORES ===' );

        if ( $single_url ) {
            $urls = array( $single_url );
        } else {
            WP_CLI::log( 'Buscando sitemap de atores...' );
            $urls = $this->parse_sitemap( self::SITEMAP_ACTORS );
        }

        if ( empty( $urls ) ) {
            WP_CLI::error( 'Nenhuma URL de ator encontrada no sitemap.' );
            return;
        }

        $total = count( $urls );
        WP_CLI::log( "Encontradas {$total} URLs de atores." );

        if ( $limit > 0 && $total > $limit ) {
            $urls  = array_slice( $urls, 0, $limit );
            $total = $limit;
            WP_CLI::log( "Limitando a {$limit} atores." );
        }

        if ( $dry_run ) {
            WP_CLI::log( '[DRY RUN] Nenhum dado será criado.' );
        }

        $progress = \WP_CLI\Utils\make_progress_bar( 'Scraping atores', $total );
        $imported = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ( $urls as $url ) {
            $slug = $this->extract_actor_slug( $url );

            // Check if already imported.
            $existing = get_posts( array(
                'post_type'   => 'actor',
                'meta_key'    => '_hotboys_source_url',
                'meta_value'  => $url,
                'numberposts' => 1,
                'post_status' => 'any',
            ) );

            if ( ! empty( $existing ) ) {
                $this->actor_map[ $slug ] = $existing[0]->ID;
                $skipped++;
                $progress->tick();
                continue;
            }

            // Also check by slug.
            $existing_by_slug = get_posts( array(
                'post_type'   => 'actor',
                'name'        => $slug,
                'numberposts' => 1,
                'post_status' => 'any',
            ) );

            if ( ! empty( $existing_by_slug ) ) {
                $this->actor_map[ $slug ] = $existing_by_slug[0]->ID;
                // Store source URL for future dedup.
                update_post_meta( $existing_by_slug[0]->ID, '_hotboys_source_url', $url );
                $skipped++;
                $progress->tick();
                continue;
            }

            // Fetch and parse page.
            $html = $this->fetch_url( $url );
            if ( ! $html ) {
                $errors++;
                $progress->tick();
                continue;
            }

            $data = $this->parse_actor_page( $html, $url );
            if ( ! $data || empty( $data['name'] ) ) {
                WP_CLI::warning( "Não consegui extrair dados de {$url}" );
                $errors++;
                $progress->tick();
                continue;
            }

            if ( $dry_run ) {
                WP_CLI::log( sprintf(
                    '  [DRY] %s — %s (%d cenas, %s views, foto: %s)',
                    $data['slug'],
                    $data['name'],
                    $data['scene_count'],
                    number_format( $data['views'] ),
                    $data['photo_url'] ? 'sim' : 'não'
                ) );
                $progress->tick();
                continue;
            }

            // Create actor post.
            $post_id = wp_insert_post( array(
                'post_type'    => 'actor',
                'post_title'   => $data['name'],
                'post_name'    => $data['slug'],
                'post_content' => $data['bio'],
                'post_status'  => 'publish',
            ), true );

            if ( is_wp_error( $post_id ) ) {
                WP_CLI::warning( "Erro ao criar ator {$data['name']}: " . $post_id->get_error_message() );
                $errors++;
                $progress->tick();
                continue;
            }

            // Meta fields.
            update_post_meta( $post_id, '_hotboys_source_url', $url );
            if ( $data['scene_count'] ) {
                update_post_meta( $post_id, '_actor_scene_count', $data['scene_count'] );
            }
            if ( $data['views'] ) {
                update_post_meta( $post_id, '_actor_views', $data['views'] );
            }

            // Featured image.
            if ( $with_images && $data['photo_url'] ) {
                $this->set_featured_image( $post_id, $data['photo_url'], $data['name'] );
            }

            $this->actor_map[ $data['slug'] ] = $post_id;
            $imported++;
            $progress->tick();

            // Rate limit: avoid hammering the server.
            usleep( 500000 ); // 0.5s
        }

        $progress->finish();
        WP_CLI::success( sprintf(
            'Atores: %d importados, %d já existiam, %d erros (total: %d)',
            $imported, $skipped, $errors, $total
        ) );
    }

    /**
     * Scrape and import scenes from hotboys.com.br sitemap.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Show what would be imported without creating posts.
     *
     * [--limit=<num>]
     * : Max number of scenes to import.
     *
     * [--with-images]
     * : Download and set featured images.
     *
     * [--url=<url>]
     * : Scrape a single scene URL instead of the sitemap.
     *
     * [--skip-actors]
     * : Skip building actor map (faster if actors not imported yet).
     *
     * ## EXAMPLES
     *
     *   wp hotboys-scrape scrape_scenes --dry-run --limit=5
     *   wp hotboys-scrape scrape_scenes --with-images
     *   wp hotboys-scrape scrape_scenes --url=https://www.hotboys.com.br/cena/869/voc-acabou-com-a-minha-pscoa
     *
     * @subcommand scrape_scenes
     */
    public function scrape_scenes( $args, $assoc_args ) {
        $dry_run      = isset( $assoc_args['dry-run'] );
        $with_images  = isset( $assoc_args['with-images'] );
        $limit        = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 0;
        $single_url   = isset( $assoc_args['url'] ) ? $assoc_args['url'] : '';
        $skip_actors  = isset( $assoc_args['skip-actors'] );

        WP_CLI::log( '=== SCRAPER DE CENAS ===' );

        // Build actor map for linking.
        if ( ! $skip_actors ) {
            $this->build_actor_map();
        }

        if ( $single_url ) {
            $urls = array( $single_url );
        } else {
            WP_CLI::log( 'Buscando sitemap de cenas...' );
            $urls = $this->parse_sitemap( self::SITEMAP_SCENES );
        }

        if ( empty( $urls ) ) {
            WP_CLI::error( 'Nenhuma URL de cena encontrada no sitemap.' );
            return;
        }

        $total = count( $urls );
        WP_CLI::log( "Encontradas {$total} URLs de cenas." );

        if ( $limit > 0 && $total > $limit ) {
            $urls  = array_slice( $urls, 0, $limit );
            $total = $limit;
            WP_CLI::log( "Limitando a {$limit} cenas." );
        }

        if ( $dry_run ) {
            WP_CLI::log( '[DRY RUN] Nenhum dado será criado.' );
        }

        $progress = \WP_CLI\Utils\make_progress_bar( 'Scraping cenas', $total );
        $imported = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ( $urls as $url ) {
            $scene_id = $this->extract_scene_id( $url );

            // Check if already imported by source URL.
            $existing = get_posts( array(
                'post_type'   => 'scene',
                'meta_key'    => '_hotboys_source_url',
                'meta_value'  => $url,
                'numberposts' => 1,
                'post_status' => 'any',
            ) );

            if ( ! empty( $existing ) ) {
                $skipped++;
                $progress->tick();
                continue;
            }

            // Also check by remote ID.
            if ( $scene_id ) {
                $existing_by_id = get_posts( array(
                    'post_type'   => 'scene',
                    'meta_key'    => '_hotboys_remote_id',
                    'meta_value'  => $scene_id,
                    'numberposts' => 1,
                    'post_status' => 'any',
                ) );

                if ( ! empty( $existing_by_id ) ) {
                    $skipped++;
                    $progress->tick();
                    continue;
                }
            }

            // Fetch and parse page.
            $html = $this->fetch_url( $url );
            if ( ! $html ) {
                $errors++;
                $progress->tick();
                continue;
            }

            $data = $this->parse_scene_page( $html, $url );
            if ( ! $data || empty( $data['title'] ) ) {
                WP_CLI::warning( "Não consegui extrair dados de {$url}" );
                $errors++;
                $progress->tick();
                continue;
            }

            if ( $dry_run ) {
                WP_CLI::log( sprintf(
                    '  [DRY] #%s — %s (%s) | Atores: %s | Tags: %s | Thumb: %s',
                    $data['remote_id'],
                    $data['title'],
                    $data['duration'] ?: 'sem duração',
                    ! empty( $data['actor_names'] ) ? implode( ', ', $data['actor_names'] ) : implode( ', ', $data['actors'] ),
                    implode( ', ', $data['tags'] ),
                    $data['thumbnail'] ? 'sim' : 'não'
                ) );
                $progress->tick();
                continue;
            }

            // Parse date.
            $post_date = '';
            if ( $data['upload_date'] ) {
                $timestamp = strtotime( $data['upload_date'] );
                if ( $timestamp ) {
                    $post_date = gmdate( 'Y-m-d H:i:s', $timestamp );
                }
            }

            // Create scene post.
            $post_data = array(
                'post_type'    => 'scene',
                'post_title'   => $data['title'],
                'post_name'    => sanitize_title( $data['title'] ),
                'post_content' => $data['description'],
                'post_status'  => 'publish',
            );

            if ( $post_date ) {
                $post_data['post_date']     = $post_date;
                $post_data['post_date_gmt'] = $post_date;
            }

            $post_id = wp_insert_post( $post_data, true );

            if ( is_wp_error( $post_id ) ) {
                WP_CLI::warning( "Erro ao criar cena #{$data['remote_id']} ({$data['title']}): " . $post_id->get_error_message() );
                $errors++;
                $progress->tick();
                continue;
            }

            // Meta fields.
            update_post_meta( $post_id, '_hotboys_source_url', $url );

            if ( $scene_id ) {
                update_post_meta( $post_id, '_hotboys_remote_id', $scene_id );
            }

            if ( $data['duration'] ) {
                update_post_meta( $post_id, '_scene_duration', sanitize_text_field( $data['duration'] ) );
            }

            update_post_meta( $post_id, '_scene_quality', 'HD' );

            if ( $data['upload_date'] ) {
                $release = gmdate( 'Y-m-d', strtotime( $data['upload_date'] ) );
                update_post_meta( $post_id, '_scene_release_date', $release );
            }

            // Link actors by slug.
            if ( ! empty( $data['actors'] ) ) {
                $wp_actor_ids = array();
                foreach ( $data['actors'] as $idx => $actor_slug ) {
                    if ( isset( $this->actor_map[ $actor_slug ] ) ) {
                        $wp_actor_ids[] = $this->actor_map[ $actor_slug ];
                    } else {
                        // Actor not imported yet — create a draft placeholder.
                        $actor_name = isset( $data['actor_names'][ $idx ] ) ? $data['actor_names'][ $idx ] : ucwords( str_replace( '-', ' ', $actor_slug ) );
                        $placeholder_id = wp_insert_post( array(
                            'post_type'   => 'actor',
                            'post_title'  => $actor_name,
                            'post_name'   => $actor_slug,
                            'post_status' => 'draft',
                        ), true );

                        if ( ! is_wp_error( $placeholder_id ) ) {
                            update_post_meta( $placeholder_id, '_hotboys_source_url', self::BASE_URL . '/ator/' . $actor_slug );
                            $this->actor_map[ $actor_slug ] = $placeholder_id;
                            $wp_actor_ids[] = $placeholder_id;
                        }
                    }
                }
                if ( ! empty( $wp_actor_ids ) ) {
                    update_post_meta( $post_id, '_scene_actors', $wp_actor_ids );
                }
            }

            // Set tags.
            if ( ! empty( $data['tags'] ) ) {
                wp_set_object_terms( $post_id, $data['tags'], 'scene_tag' );
            }

            // Featured image.
            if ( $with_images && $data['thumbnail'] ) {
                $this->set_featured_image( $post_id, $data['thumbnail'], $data['title'] );
            }

            $imported++;
            $progress->tick();

            // Rate limit.
            usleep( 500000 ); // 0.5s
        }

        $progress->finish();
        WP_CLI::success( sprintf(
            'Cenas: %d importadas, %d já existiam, %d erros (total: %d)',
            $imported, $skipped, $errors, $total
        ) );
    }

    /**
     * Scrape both actors and scenes.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Show what would be imported.
     *
     * [--with-images]
     * : Download and set featured images.
     *
     * [--limit=<num>]
     * : Max per type.
     *
     * ## EXAMPLES
     *
     *   wp hotboys-scrape scrape_all --dry-run --limit=3
     *   wp hotboys-scrape scrape_all --with-images
     *
     * @subcommand scrape_all
     */
    public function scrape_all( $args, $assoc_args ) {
        WP_CLI::log( '=== SCRAPING COMPLETO ===' );
        WP_CLI::log( '' );
        WP_CLI::log( '--- ATORES ---' );
        $this->scrape_actors( $args, $assoc_args );
        WP_CLI::log( '' );
        WP_CLI::log( '--- CENAS ---' );
        $this->scrape_scenes( $args, $assoc_args );
        WP_CLI::log( '' );
        WP_CLI::success( 'Scraping completo!' );
    }

    /**
     * Download an image and set as featured.
     *
     * @param int    $post_id WP post ID.
     * @param string $url     Image URL.
     * @param string $title   Image title/alt text.
     */
    private function set_featured_image( $post_id, $url, $title ) {
        if ( strpos( $url, 'http' ) !== 0 ) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Override SSL verify for download_url.
        add_filter( 'http_request_args', array( $this, 'disable_ssl_verify' ), 10, 2 );

        $tmp = download_url( $url, 30 );

        remove_filter( 'http_request_args', array( $this, 'disable_ssl_verify' ), 10 );

        if ( is_wp_error( $tmp ) ) {
            WP_CLI::warning( "Erro ao baixar imagem {$url}: " . $tmp->get_error_message() );
            return;
        }

        $file_array = array(
            'name'     => sanitize_file_name( basename( wp_parse_url( $url, PHP_URL_PATH ) ) ),
            'tmp_name' => $tmp,
        );

        $attachment_id = media_handle_sideload( $file_array, $post_id, $title );

        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            WP_CLI::warning( "Erro ao salvar imagem para post #{$post_id}: " . $attachment_id->get_error_message() );
            return;
        }

        set_post_thumbnail( $post_id, $attachment_id );
    }

    /**
     * Disable SSL verification for image downloads from hotboys servers.
     *
     * @param array  $args HTTP request args.
     * @param string $url  Request URL.
     * @return array Modified args.
     */
    public function disable_ssl_verify( $args, $url ) {
        if ( strpos( $url, 'hotboys.com.br' ) !== false ) {
            $args['sslverify'] = false;
        }
        return $args;
    }
}

WP_CLI::add_command( 'hotboys-scrape', 'HotBoys_Scrape_Command' );
