<?php
/**
 * HotBoys Health Check — WP-CLI audit command
 *
 * Usage: wp hotboys-health run
 *        wp hotboys-health fix_bios
 *        wp hotboys-health fix_links
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HotBoys_Health_Command {

    /**
     * Run a full site health audit.
     *
     * @subcommand run
     */
    public function run( $args, $assoc_args ) {
        WP_CLI::log( '=== HOTBOYS HEALTH CHECK ===' );
        WP_CLI::log( '' );

        $actors = wp_count_posts( 'actor' );
        $scenes = wp_count_posts( 'scene' );
        WP_CLI::log( sprintf( 'Atores: %d publicados, %d rascunhos', $actors->publish, $actors->draft ) );
        WP_CLI::log( sprintf( 'Cenas: %d publicadas, %d rascunhos', $scenes->publish, $scenes->draft ) );

        // Actors without photo
        $no_img_actors = new WP_Query( array(
            'post_type'      => 'actor',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array( array( 'key' => '_thumbnail_id', 'compare' => 'NOT EXISTS' ) ),
        ) );
        WP_CLI::log( sprintf( 'Atores sem foto: %d', $no_img_actors->found_posts ) );

        // Scenes without thumbnail
        $no_img_scenes = new WP_Query( array(
            'post_type'      => 'scene',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array( array( 'key' => '_thumbnail_id', 'compare' => 'NOT EXISTS' ) ),
        ) );
        WP_CLI::log( sprintf( 'Cenas sem thumbnail: %d', $no_img_scenes->found_posts ) );

        // Garbage bios
        $garbage_bios = 0;
        $actors_q = new WP_Query( array(
            'post_type'      => 'actor',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ) );
        while ( $actors_q->have_posts() ) {
            $actors_q->the_post();
            $content = get_the_content();
            if ( $content && (
                preg_match( '/^\d{2}\/\d{2}\/\d{4}$/m', $content ) ||
                preg_match( '/^VIP$/mi', $content ) ||
                preg_match( '/^[\d,\.]+$/m', $content )
            ) ) {
                $garbage_bios++;
            }
        }
        wp_reset_postdata();
        WP_CLI::log( sprintf( 'Atores com bio contaminada: %d', $garbage_bios ) );

        // Orphan scenes (no actors linked)
        $orphan_scenes = 0;
        $scenes_q = new WP_Query( array(
            'post_type'      => 'scene',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );
        foreach ( $scenes_q->posts as $sid ) {
            $actor_ids = get_post_meta( $sid, '_scene_actors', true );
            if ( empty( $actor_ids ) || ! is_array( $actor_ids ) ) {
                $orphan_scenes++;
            }
        }
        WP_CLI::log( sprintf( 'Cenas sem atores vinculados: %d', $orphan_scenes ) );

        // Actors without source URL
        $no_source = new WP_Query( array(
            'post_type'      => 'actor',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array( array( 'key' => '_hotboys_source_url', 'compare' => 'NOT EXISTS' ) ),
        ) );
        WP_CLI::log( sprintf( 'Atores sem source_url (CTA generico): %d', $no_source->found_posts ) );

        // Scenes without source URL
        $no_source_scenes = new WP_Query( array(
            'post_type'      => 'scene',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array( array( 'key' => '_hotboys_source_url', 'compare' => 'NOT EXISTS' ) ),
        ) );
        WP_CLI::log( sprintf( 'Cenas sem source_url (CTA generico): %d', $no_source_scenes->found_posts ) );

        // Duplicate source URLs
        global $wpdb;
        $dupes = $wpdb->get_var(
            "SELECT COUNT(*) FROM (
                SELECT meta_value, COUNT(*) as cnt FROM {$wpdb->postmeta}
                WHERE meta_key = '_hotboys_source_url' AND meta_value != ''
                GROUP BY meta_value HAVING cnt > 1
            ) as dupes"
        );
        WP_CLI::log( sprintf( 'URLs duplicadas: %d', (int) $dupes ) );

        WP_CLI::log( '' );
        WP_CLI::success( 'Auditoria completa.' );
    }

    /**
     * Clean garbage bio content from actor posts.
     *
     * [--dry-run]
     * : Show what would be cleaned without making changes.
     *
     * @subcommand fix_bios
     */
    public function fix_bios( $args, $assoc_args ) {
        $dry_run = isset( $assoc_args['dry-run'] );
        WP_CLI::log( '=== LIMPEZA DE BIOS CONTAMINADAS ===' );

        $actors_q = new WP_Query( array(
            'post_type'      => 'actor',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ) );
        $cleaned       = 0;
        $already_clean = 0;

        while ( $actors_q->have_posts() ) {
            $actors_q->the_post();
            $content = get_the_content();
            if ( empty( $content ) ) {
                $already_clean++;
                continue;
            }

            $has_garbage = preg_match( '/^\d{2}\/\d{2}\/\d{4}$/m', $content )
                || preg_match( '/^VIP$/mi', $content )
                || preg_match( '/^FREE$/mi', $content )
                || preg_match( '/^[\d,\.]{4,}$/m', $content );

            if ( ! $has_garbage ) {
                $already_clean++;
                continue;
            }

            $lines = preg_split( '/\r?\n/', wp_strip_all_tags( $content ) );
            $valid = array();
            foreach ( $lines as $line ) {
                $line = trim( $line );
                if ( empty( $line ) ) continue;
                if ( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $line ) ) continue;
                if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $line ) ) continue;
                if ( preg_match( '/^[\d,\.]+$/', $line ) ) continue;
                if ( preg_match( '/^(VIP|FREE|GRATIS|HD|4K)$/i', $line ) ) continue;
                if ( mb_strlen( $line ) < 30 ) continue;
                if ( preg_match( '/^(?:[A-Z][a-záéíóúãõâêîôûç\s]+,\s*){2,}/u', $line ) ) continue;
                $valid[] = $line;
            }

            $new_content = implode( "\n\n", $valid );

            if ( $dry_run ) {
                WP_CLI::log( sprintf(
                    '  [DRY] %s (ID %d): %d chars -> %d chars',
                    get_the_title(), get_the_ID(),
                    mb_strlen( $content ), mb_strlen( $new_content )
                ) );
            } else {
                wp_update_post( array(
                    'ID'           => get_the_ID(),
                    'post_content' => $new_content,
                ) );
            }
            $cleaned++;
        }
        wp_reset_postdata();

        WP_CLI::success( sprintf(
            '%s %d bios limpas, %d ja estavam OK.',
            $dry_run ? '[DRY RUN]' : 'Concluido:',
            $cleaned, $already_clean
        ) );
    }

    /**
     * Rebuild actor-scene linkages for scenes missing _scene_actors meta.
     *
     * [--dry-run]
     * : Show what would be fixed without making changes.
     *
     * @subcommand fix_links
     */
    public function fix_links( $args, $assoc_args ) {
        $dry_run = isset( $assoc_args['dry-run'] );
        WP_CLI::log( '=== REBUILD ACTOR-SCENE LINKS ===' );

        // Build actor map: slug/source-slug => actor ID
        $actor_map = array();
        $actors_q  = get_posts( array(
            'post_type'      => 'actor',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );
        foreach ( $actors_q as $aid ) {
            $post = get_post( $aid );
            if ( $post ) {
                $actor_map[ $post->post_name ] = $aid;
            }
            $src = get_post_meta( $aid, '_hotboys_source_url', true );
            if ( $src && preg_match( '/\/ator\/([^\/\?]+)/', $src, $m ) ) {
                $actor_map[ $m[1] ] = $aid;
            }
        }
        WP_CLI::log( sprintf( 'Actor map: %d atores indexados.', count( $actor_map ) ) );

        $scenes    = get_posts( array(
            'post_type'      => 'scene',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ) );
        $fixed      = 0;
        $already_ok = 0;

        foreach ( $scenes as $scene ) {
            $existing = get_post_meta( $scene->ID, '_scene_actors', true );
            if ( is_array( $existing ) && ! empty( $existing ) ) {
                // Validate existing IDs still exist
                $valid_ids = array();
                foreach ( $existing as $id ) {
                    if ( get_post_status( $id ) ) {
                        $valid_ids[] = (int) $id;
                    }
                }
                if ( count( $valid_ids ) === count( $existing ) ) {
                    $already_ok++;
                    continue;
                }
                if ( ! $dry_run ) {
                    update_post_meta( $scene->ID, '_scene_actors', $valid_ids );
                }
                $fixed++;
                continue;
            }

            // Try to find actors by name match in scene content/title
            $found_ids = array();
            foreach ( $actor_map as $slug => $aid ) {
                $actor_name = get_the_title( $aid );
                if ( $actor_name && (
                    stripos( $scene->post_title, $actor_name ) !== false ||
                    stripos( $scene->post_content, $actor_name ) !== false
                ) ) {
                    $found_ids[] = $aid;
                }
            }

            if ( ! empty( $found_ids ) ) {
                $found_ids = array_unique( $found_ids );
                if ( $dry_run ) {
                    $names = array_map( 'get_the_title', $found_ids );
                    WP_CLI::log( sprintf(
                        '  [DRY] Scene "%s" (ID %d) -> %s',
                        $scene->post_title, $scene->ID, implode( ', ', $names )
                    ) );
                } else {
                    update_post_meta( $scene->ID, '_scene_actors', $found_ids );
                }
                $fixed++;
            }
        }

        WP_CLI::success( sprintf(
            '%s %d cenas corrigidas, %d ja estavam OK.',
            $dry_run ? '[DRY RUN]' : 'Concluido:',
            $fixed, $already_ok
        ) );
    }
}

WP_CLI::add_command( 'hotboys-health', 'HotBoys_Health_Command' );
