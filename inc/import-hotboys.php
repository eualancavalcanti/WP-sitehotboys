<?php
/**
 * HotBoys Data Importer — WP-CLI Command
 *
 * Importa cenas e atores do banco MySQL do HotBoys para o WordPress.
 *
 * Uso:
 *   wp hotboys import_actors --db-host=HOST --db-name=DB --db-user=USER --db-pass=PASS
 *   wp hotboys import_scenes --db-host=HOST --db-name=DB --db-user=USER --db-pass=PASS
 *   wp hotboys import_all    --db-host=HOST --db-name=DB --db-user=USER --db-pass=PASS
 *   wp hotboys stats         --db-host=HOST --db-name=DB --db-user=USER --db-pass=PASS
 *
 * Ou defina no wp-config.php:
 *   define( 'HOTBOYS_DB_HOST', 'host' );
 *   define( 'HOTBOYS_DB_NAME', 'database' );
 *   define( 'HOTBOYS_DB_USER', 'user' );
 *   define( 'HOTBOYS_DB_PASS', 'password' );
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class HotBoys_Import_Command {

    /**
     * Remote DB connection.
     *
     * @var mysqli|null
     */
    private $remote_db = null;

    /**
     * Remote table prefix (will be auto-detected).
     *
     * @var string
     */
    private $remote_prefix = '';

    /**
     * Mapping of remote actor IDs to local WP post IDs.
     *
     * @var array
     */
    private $actor_map = array();

    /**
     * Establish remote database connection.
     *
     * @param array $assoc_args CLI associative args.
     * @return bool
     */
    private function connect( $assoc_args ) {
        $host = $this->get_param( $assoc_args, 'db-host', 'HOTBOYS_DB_HOST' );
        $name = $this->get_param( $assoc_args, 'db-name', 'HOTBOYS_DB_NAME' );
        $user = $this->get_param( $assoc_args, 'db-user', 'HOTBOYS_DB_USER' );
        $pass = $this->get_param( $assoc_args, 'db-pass', 'HOTBOYS_DB_PASS' );
        $port = isset( $assoc_args['db-port'] ) ? (int) $assoc_args['db-port'] : 3306;

        if ( ! $host || ! $name || ! $user ) {
            WP_CLI::error( 'Credenciais do banco obrigatórias. Use --db-host, --db-name, --db-user, --db-pass ou defina HOTBOYS_DB_* no wp-config.php' );
            return false;
        }

        $this->remote_db = new mysqli( $host, $user, $pass, $name, $port );

        if ( $this->remote_db->connect_error ) {
            WP_CLI::error( 'Falha ao conectar: ' . $this->remote_db->connect_error );
            return false;
        }

        $this->remote_db->set_charset( 'utf8mb4' );

        // Auto-detect table prefix and structure.
        $this->detect_structure();

        WP_CLI::success( "Conectado ao banco '{$name}' em {$host}" );
        return true;
    }

    /**
     * Get a parameter from CLI args or wp-config constant.
     */
    private function get_param( $assoc_args, $cli_key, $const_name ) {
        if ( ! empty( $assoc_args[ $cli_key ] ) ) {
            return $assoc_args[ $cli_key ];
        }
        if ( defined( $const_name ) ) {
            return constant( $const_name );
        }
        return '';
    }

    /**
     * Detect remote database structure (tables and columns).
     */
    private function detect_structure() {
        $tables = array();
        $result = $this->remote_db->query( 'SHOW TABLES' );
        while ( $row = $result->fetch_row() ) {
            $tables[] = $row[0];
        }
        $result->free();

        $this->detected_tables = $tables;

        // Try to find actors and scenes tables.
        $this->actors_table   = $this->find_table( $tables, array( 'atores', 'actors', 'ator', 'actor', 'performers', 'models' ) );
        $this->scenes_table   = $this->find_table( $tables, array( 'cenas', 'scenes', 'cena', 'scene', 'videos', 'video' ) );
        $this->tags_table     = $this->find_table( $tables, array( 'tags', 'tag', 'etiquetas' ) );
        $this->cats_table     = $this->find_table( $tables, array( 'categorias', 'categories', 'categoria', 'category' ) );
        $this->scene_tags_table = $this->find_table( $tables, array( 'cena_tags', 'scene_tags', 'cenas_tags', 'video_tags' ) );
        $this->scene_actors_table = $this->find_table( $tables, array( 'cena_atores', 'scene_actors', 'cenas_atores', 'video_actors', 'elenco', 'cast' ) );
    }

    /**
     * Find a table by possible names.
     */
    private function find_table( $tables, $candidates ) {
        foreach ( $candidates as $name ) {
            foreach ( $tables as $table ) {
                if ( preg_match( '/(^|_)' . preg_quote( $name, '/' ) . '$/i', $table ) ) {
                    return $table;
                }
            }
        }
        return '';
    }

    /**
     * Get columns of a table.
     */
    private function get_columns( $table ) {
        $columns = array();
        $result = $this->remote_db->query( "SHOW COLUMNS FROM `{$table}`" );
        if ( $result ) {
            while ( $row = $result->fetch_assoc() ) {
                $columns[] = $row['Field'];
            }
            $result->free();
        }
        return $columns;
    }

    /**
     * Find a column by possible names.
     */
    private function find_column( $columns, $candidates ) {
        foreach ( $candidates as $name ) {
            foreach ( $columns as $col ) {
                if ( strtolower( $col ) === strtolower( $name ) ) {
                    return $col;
                }
            }
        }
        // Partial match.
        foreach ( $candidates as $name ) {
            foreach ( $columns as $col ) {
                if ( false !== stripos( $col, $name ) ) {
                    return $col;
                }
            }
        }
        return '';
    }

    /**
     * Show remote database stats and structure.
     *
     * ## OPTIONS
     *
     * [--db-host=<host>]
     * : Remote MySQL host.
     *
     * [--db-name=<name>]
     * : Remote database name.
     *
     * [--db-user=<user>]
     * : Remote MySQL user.
     *
     * [--db-pass=<pass>]
     * : Remote MySQL password.
     *
     * [--db-port=<port>]
     * : Remote MySQL port (default 3306).
     *
     * @subcommand stats
     */
    public function stats( $args, $assoc_args ) {
        if ( ! $this->connect( $assoc_args ) ) {
            return;
        }

        WP_CLI::log( '' );
        WP_CLI::log( '=== TABELAS ENCONTRADAS ===' );
        foreach ( $this->detected_tables as $t ) {
            $count_result = $this->remote_db->query( "SELECT COUNT(*) as c FROM `{$t}`" );
            $count = $count_result ? $count_result->fetch_assoc()['c'] : '?';
            if ( $count_result ) $count_result->free();
            WP_CLI::log( sprintf( '  %-40s %s registros', $t, number_format_i18n( $count ) ) );
        }

        WP_CLI::log( '' );
        WP_CLI::log( '=== MAPEAMENTO DETECTADO ===' );
        $map = array(
            'Atores'        => $this->actors_table,
            'Cenas'         => $this->scenes_table,
            'Tags'          => $this->tags_table,
            'Categorias'    => $this->cats_table,
            'Cena↔Tags'     => $this->scene_tags_table,
            'Cena↔Atores'   => $this->scene_actors_table,
        );
        foreach ( $map as $label => $table ) {
            $status = $table ? $table : '❌ NÃO ENCONTRADA';
            WP_CLI::log( sprintf( '  %-20s → %s', $label, $status ) );
        }

        // Show columns for detected tables.
        $detected = array_filter( array(
            $this->actors_table,
            $this->scenes_table,
            $this->tags_table,
            $this->scene_tags_table,
            $this->scene_actors_table,
        ) );
        foreach ( $detected as $table ) {
            WP_CLI::log( '' );
            $cols = $this->get_columns( $table );
            WP_CLI::log( "Colunas de '{$table}': " . implode( ', ', $cols ) );
        }

        WP_CLI::log( '' );
        WP_CLI::log( '=== PRÓXIMOS PASSOS ===' );
        WP_CLI::log( 'Se o mapeamento está correto, rode:' );
        WP_CLI::log( '  wp hotboys import_actors [mesmos flags de DB]' );
        WP_CLI::log( '  wp hotboys import_scenes [mesmos flags de DB]' );
        WP_CLI::log( 'Ou ambos de uma vez:' );
        WP_CLI::log( '  wp hotboys import_all [mesmos flags de DB]' );

        $this->remote_db->close();
    }

    /**
     * Import actors from remote database.
     *
     * ## OPTIONS
     *
     * [--db-host=<host>]
     * : Remote MySQL host.
     *
     * [--db-name=<name>]
     * : Remote database name.
     *
     * [--db-user=<user>]
     * : Remote MySQL user.
     *
     * [--db-pass=<pass>]
     * : Remote MySQL password.
     *
     * [--db-port=<port>]
     * : Remote MySQL port (default 3306).
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
     * [--actors-table=<table>]
     * : Override auto-detected actors table name.
     *
     * @subcommand import_actors
     */
    public function import_actors( $args, $assoc_args ) {
        if ( ! $this->connect( $assoc_args ) ) {
            return;
        }

        $dry_run     = isset( $assoc_args['dry-run'] );
        $with_images = isset( $assoc_args['with-images'] );
        $limit       = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 0;
        $table       = ! empty( $assoc_args['actors-table'] ) ? $assoc_args['actors-table'] : $this->actors_table;

        if ( ! $table ) {
            WP_CLI::error( 'Tabela de atores não encontrada. Use --actors-table=NOME ou rode wp hotboys stats para ver as tabelas.' );
            return;
        }

        $columns = $this->get_columns( $table );
        WP_CLI::log( "Tabela: {$table}" );
        WP_CLI::log( "Colunas: " . implode( ', ', $columns ) );

        // Auto-detect column names.
        $col_id    = $this->find_column( $columns, array( 'id', 'ator_id', 'actor_id', 'cod', 'codigo' ) );
        $col_name  = $this->find_column( $columns, array( 'nome', 'name', 'titulo', 'title', 'nome_artistico' ) );
        $col_slug  = $this->find_column( $columns, array( 'slug', 'apelido', 'username', 'url', 'user' ) );
        $col_bio   = $this->find_column( $columns, array( 'bio', 'biografia', 'descricao', 'description', 'sobre', 'about' ) );
        $col_photo = $this->find_column( $columns, array( 'foto', 'photo', 'imagem', 'image', 'thumb', 'avatar', 'img' ) );

        if ( ! $col_id || ! $col_name ) {
            WP_CLI::error( "Não consegui detectar colunas de ID e Nome na tabela '{$table}'. Colunas: " . implode( ', ', $columns ) );
            return;
        }

        WP_CLI::log( "Mapeamento: ID={$col_id}, Nome={$col_name}, Slug={$col_slug}, Bio={$col_bio}, Foto={$col_photo}" );

        // Fetch actors.
        $query = "SELECT * FROM `{$table}` ORDER BY `{$col_id}` ASC";
        if ( $limit > 0 ) {
            $query .= " LIMIT {$limit}";
        }

        $result = $this->remote_db->query( $query );
        if ( ! $result ) {
            WP_CLI::error( 'Query falhou: ' . $this->remote_db->error );
            return;
        }

        $total = $result->num_rows;
        WP_CLI::log( "Encontrados {$total} atores para importar." );

        if ( $dry_run ) {
            WP_CLI::log( '[DRY RUN] Nenhum dado será criado.' );
        }

        $progress = \WP_CLI\Utils\make_progress_bar( 'Importando atores', $total );
        $imported = 0;
        $skipped  = 0;

        while ( $row = $result->fetch_assoc() ) {
            $remote_id = $row[ $col_id ];
            $name      = trim( $row[ $col_name ] );
            $slug      = $col_slug ? sanitize_title( $row[ $col_slug ] ) : sanitize_title( $name );
            $bio       = $col_bio ? trim( $row[ $col_bio ] ) : '';
            $photo_url = $col_photo ? trim( $row[ $col_photo ] ) : '';

            // Check if already imported.
            $existing = get_posts( array(
                'post_type'   => 'actor',
                'meta_key'    => '_hotboys_remote_id',
                'meta_value'  => $remote_id,
                'numberposts' => 1,
                'post_status' => 'any',
            ) );

            if ( ! empty( $existing ) ) {
                $this->actor_map[ $remote_id ] = $existing[0]->ID;
                $skipped++;
                $progress->tick();
                continue;
            }

            if ( $dry_run ) {
                WP_CLI::log( "  [DRY] #{$remote_id} — {$name} ({$slug})" );
                $progress->tick();
                continue;
            }

            $post_id = wp_insert_post( array(
                'post_type'    => 'actor',
                'post_title'   => $name,
                'post_name'    => $slug,
                'post_content' => $bio,
                'post_status'  => 'publish',
            ), true );

            if ( is_wp_error( $post_id ) ) {
                WP_CLI::warning( "Erro ao importar ator #{$remote_id} ({$name}): " . $post_id->get_error_message() );
                $progress->tick();
                continue;
            }

            // Store remote ID for future syncs.
            update_post_meta( $post_id, '_hotboys_remote_id', $remote_id );

            // Download featured image.
            if ( $with_images && $photo_url ) {
                $this->set_featured_image( $post_id, $photo_url, $name );
            }

            $this->actor_map[ $remote_id ] = $post_id;
            $imported++;
            $progress->tick();
        }

        $progress->finish();
        $result->free();

        WP_CLI::success( "Atores: {$imported} importados, {$skipped} já existiam (total: {$total})" );
    }

    /**
     * Import scenes from remote database.
     *
     * ## OPTIONS
     *
     * [--db-host=<host>]
     * : Remote MySQL host.
     *
     * [--db-name=<name>]
     * : Remote database name.
     *
     * [--db-user=<user>]
     * : Remote MySQL user.
     *
     * [--db-pass=<pass>]
     * : Remote MySQL password.
     *
     * [--db-port=<port>]
     * : Remote MySQL port (default 3306).
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
     * [--scenes-table=<table>]
     * : Override auto-detected scenes table name.
     *
     * [--actors-join-table=<table>]
     * : Override auto-detected scene↔actors join table.
     *
     * [--tags-join-table=<table>]
     * : Override auto-detected scene↔tags join table.
     *
     * @subcommand import_scenes
     */
    public function import_scenes( $args, $assoc_args ) {
        if ( ! $this->connect( $assoc_args ) ) {
            return;
        }

        $dry_run     = isset( $assoc_args['dry-run'] );
        $with_images = isset( $assoc_args['with-images'] );
        $limit       = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 0;
        $table       = ! empty( $assoc_args['scenes-table'] ) ? $assoc_args['scenes-table'] : $this->scenes_table;

        if ( ! $table ) {
            WP_CLI::error( 'Tabela de cenas não encontrada. Use --scenes-table=NOME ou rode wp hotboys stats.' );
            return;
        }

        // Build actor map from existing imported actors.
        $this->build_actor_map();

        $columns = $this->get_columns( $table );
        WP_CLI::log( "Tabela: {$table}" );
        WP_CLI::log( "Colunas: " . implode( ', ', $columns ) );

        $col_id       = $this->find_column( $columns, array( 'id', 'cena_id', 'scene_id', 'video_id', 'cod', 'codigo' ) );
        $col_title    = $this->find_column( $columns, array( 'titulo', 'title', 'nome', 'name' ) );
        $col_slug     = $this->find_column( $columns, array( 'slug', 'url', 'url_amigavel', 'permalink' ) );
        $col_desc     = $this->find_column( $columns, array( 'descricao', 'description', 'sinopse', 'synopsis', 'texto', 'content' ) );
        $col_duration = $this->find_column( $columns, array( 'duracao', 'duration', 'tempo', 'time', 'length' ) );
        $col_quality  = $this->find_column( $columns, array( 'qualidade', 'quality', 'resolucao', 'resolution' ) );
        $col_date     = $this->find_column( $columns, array( 'data_lancamento', 'release_date', 'data', 'date', 'published_at', 'created_at', 'data_publicacao' ) );
        $col_thumb    = $this->find_column( $columns, array( 'thumb', 'thumbnail', 'capa', 'cover', 'imagem', 'image', 'foto', 'img' ) );
        $col_views    = $this->find_column( $columns, array( 'views', 'visualizacoes', 'acessos', 'view_count' ) );

        if ( ! $col_id || ! $col_title ) {
            WP_CLI::error( "Não consegui detectar colunas de ID e Título. Colunas: " . implode( ', ', $columns ) );
            return;
        }

        WP_CLI::log( "Mapeamento: ID={$col_id}, Título={$col_title}, Slug={$col_slug}, Desc={$col_desc}, Duração={$col_duration}, Qualidade={$col_quality}, Data={$col_date}, Thumb={$col_thumb}" );

        // Tags and actors join tables.
        $tags_join   = ! empty( $assoc_args['tags-join-table'] ) ? $assoc_args['tags-join-table'] : $this->scene_tags_table;
        $actors_join = ! empty( $assoc_args['actors-join-table'] ) ? $assoc_args['actors-join-table'] : $this->scene_actors_table;

        // Import tags/categories to WP taxonomies first.
        $tag_map = $this->import_tags( $assoc_args, $dry_run );

        // Fetch scenes.
        $query = "SELECT * FROM `{$table}` ORDER BY `{$col_id}` ASC";
        if ( $limit > 0 ) {
            $query .= " LIMIT {$limit}";
        }

        $result = $this->remote_db->query( $query );
        if ( ! $result ) {
            WP_CLI::error( 'Query falhou: ' . $this->remote_db->error );
            return;
        }

        $total = $result->num_rows;
        WP_CLI::log( "Encontradas {$total} cenas para importar." );

        if ( $dry_run ) {
            WP_CLI::log( '[DRY RUN] Nenhum dado será criado.' );
        }

        $progress = \WP_CLI\Utils\make_progress_bar( 'Importando cenas', $total );
        $imported = 0;
        $skipped  = 0;

        while ( $row = $result->fetch_assoc() ) {
            $remote_id = $row[ $col_id ];
            $title     = trim( $row[ $col_title ] );
            $slug      = $col_slug ? sanitize_title( $row[ $col_slug ] ) : sanitize_title( $title );
            $desc      = $col_desc ? trim( $row[ $col_desc ] ) : '';
            $duration  = $col_duration ? trim( $row[ $col_duration ] ) : '';
            $quality   = $col_quality ? trim( $row[ $col_quality ] ) : 'HD';
            $date      = $col_date ? trim( $row[ $col_date ] ) : '';
            $thumb_url = $col_thumb ? trim( $row[ $col_thumb ] ) : '';

            // Check if already imported.
            $existing = get_posts( array(
                'post_type'   => 'scene',
                'meta_key'    => '_hotboys_remote_id',
                'meta_value'  => $remote_id,
                'numberposts' => 1,
                'post_status' => 'any',
            ) );

            if ( ! empty( $existing ) ) {
                $skipped++;
                $progress->tick();
                continue;
            }

            if ( $dry_run ) {
                WP_CLI::log( "  [DRY] #{$remote_id} — {$title} ({$duration})" );
                $progress->tick();
                continue;
            }

            // Parse date.
            $post_date = '';
            if ( $date ) {
                $timestamp = strtotime( $date );
                if ( $timestamp ) {
                    $post_date = date( 'Y-m-d H:i:s', $timestamp );
                }
            }

            $post_data = array(
                'post_type'    => 'scene',
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_content' => $desc,
                'post_status'  => 'publish',
            );

            if ( $post_date ) {
                $post_data['post_date'] = $post_date;
            }

            $post_id = wp_insert_post( $post_data, true );

            if ( is_wp_error( $post_id ) ) {
                WP_CLI::warning( "Erro ao importar cena #{$remote_id} ({$title}): " . $post_id->get_error_message() );
                $progress->tick();
                continue;
            }

            // Store remote ID.
            update_post_meta( $post_id, '_hotboys_remote_id', $remote_id );

            // Scene meta fields.
            if ( $duration ) {
                update_post_meta( $post_id, '_scene_duration', sanitize_text_field( $duration ) );
            }
            if ( $quality ) {
                update_post_meta( $post_id, '_scene_quality', sanitize_text_field( $quality ) );
            }
            if ( $date ) {
                $release = date( 'Y-m-d', strtotime( $date ) );
                update_post_meta( $post_id, '_scene_release_date', $release );
            }

            // Link actors.
            if ( $actors_join ) {
                $actor_ids = $this->get_scene_actor_ids( $actors_join, $remote_id );
                if ( ! empty( $actor_ids ) ) {
                    $wp_actor_ids = array();
                    foreach ( $actor_ids as $remote_actor_id ) {
                        if ( isset( $this->actor_map[ $remote_actor_id ] ) ) {
                            $wp_actor_ids[] = $this->actor_map[ $remote_actor_id ];
                        }
                    }
                    if ( ! empty( $wp_actor_ids ) ) {
                        update_post_meta( $post_id, '_scene_actors', $wp_actor_ids );
                    }
                }
            }

            // Link tags.
            if ( $tags_join && ! empty( $tag_map ) ) {
                $scene_tag_ids = $this->get_scene_tag_ids( $tags_join, $remote_id );
                $wp_tag_names = array();
                foreach ( $scene_tag_ids as $remote_tag_id ) {
                    if ( isset( $tag_map[ $remote_tag_id ] ) ) {
                        $wp_tag_names[] = $tag_map[ $remote_tag_id ];
                    }
                }
                if ( ! empty( $wp_tag_names ) ) {
                    wp_set_object_terms( $post_id, $wp_tag_names, 'scene_tag' );
                }
            }

            // Featured image.
            if ( $with_images && $thumb_url ) {
                $this->set_featured_image( $post_id, $thumb_url, $title );
            }

            $imported++;
            $progress->tick();
        }

        $progress->finish();
        $result->free();

        WP_CLI::success( "Cenas: {$imported} importadas, {$skipped} já existiam (total: {$total})" );
    }

    /**
     * Import both actors and scenes.
     *
     * ## OPTIONS
     *
     * [--db-host=<host>]
     * : Remote MySQL host.
     *
     * [--db-name=<name>]
     * : Remote database name.
     *
     * [--db-user=<user>]
     * : Remote MySQL user.
     *
     * [--db-pass=<pass>]
     * : Remote MySQL password.
     *
     * [--db-port=<port>]
     * : Remote MySQL port (default 3306).
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
     * @subcommand import_all
     */
    public function import_all( $args, $assoc_args ) {
        WP_CLI::log( '=== IMPORTAÇÃO COMPLETA ===' );
        WP_CLI::log( '' );
        WP_CLI::log( '--- ATORES ---' );
        $this->import_actors( $args, $assoc_args );
        WP_CLI::log( '' );
        WP_CLI::log( '--- CENAS ---' );
        $this->import_scenes( $args, $assoc_args );
        WP_CLI::log( '' );
        WP_CLI::success( 'Importação completa!' );
    }

    /**
     * Import tags from remote DB into scene_tag taxonomy.
     *
     * @return array Map of remote tag ID => WP term name.
     */
    private function import_tags( $assoc_args, $dry_run ) {
        $table = $this->tags_table;
        if ( ! $table ) {
            WP_CLI::log( 'Tabela de tags não encontrada, pulando importação de tags.' );
            return array();
        }

        $columns = $this->get_columns( $table );
        $col_id  = $this->find_column( $columns, array( 'id', 'tag_id', 'cod' ) );
        $col_name = $this->find_column( $columns, array( 'nome', 'name', 'titulo', 'title', 'tag' ) );
        $col_slug = $this->find_column( $columns, array( 'slug', 'url' ) );

        if ( ! $col_id || ! $col_name ) {
            WP_CLI::warning( 'Colunas de tags não detectadas.' );
            return array();
        }

        $result = $this->remote_db->query( "SELECT * FROM `{$table}`" );
        if ( ! $result ) {
            return array();
        }

        $tag_map = array();
        while ( $row = $result->fetch_assoc() ) {
            $remote_id = $row[ $col_id ];
            $name = trim( $row[ $col_name ] );
            $slug = $col_slug ? sanitize_title( $row[ $col_slug ] ) : sanitize_title( $name );

            if ( ! $dry_run ) {
                $term = term_exists( $name, 'scene_tag' );
                if ( ! $term ) {
                    $term = wp_insert_term( $name, 'scene_tag', array( 'slug' => $slug ) );
                }
            }

            $tag_map[ $remote_id ] = $name;
        }
        $result->free();

        WP_CLI::log( sprintf( 'Tags: %d importadas da tabela %s', count( $tag_map ), $table ) );
        return $tag_map;
    }

    /**
     * Get actor IDs for a scene from the join table.
     */
    private function get_scene_actor_ids( $join_table, $remote_scene_id ) {
        $columns = $this->get_columns( $join_table );
        $col_scene = $this->find_column( $columns, array( 'cena_id', 'scene_id', 'video_id', 'id_cena', 'id_scene' ) );
        $col_actor = $this->find_column( $columns, array( 'ator_id', 'actor_id', 'id_ator', 'id_actor', 'performer_id', 'model_id' ) );

        if ( ! $col_scene || ! $col_actor ) {
            return array();
        }

        $stmt = $this->remote_db->prepare( "SELECT `{$col_actor}` FROM `{$join_table}` WHERE `{$col_scene}` = ?" );
        $stmt->bind_param( 'i', $remote_scene_id );
        $stmt->execute();
        $result = $stmt->get_result();

        $ids = array();
        while ( $row = $result->fetch_row() ) {
            $ids[] = (int) $row[0];
        }
        $result->free();
        $stmt->close();

        return $ids;
    }

    /**
     * Get tag IDs for a scene from the join table.
     */
    private function get_scene_tag_ids( $join_table, $remote_scene_id ) {
        $columns = $this->get_columns( $join_table );
        $col_scene = $this->find_column( $columns, array( 'cena_id', 'scene_id', 'video_id', 'id_cena', 'id_scene' ) );
        $col_tag   = $this->find_column( $columns, array( 'tag_id', 'id_tag' ) );

        if ( ! $col_scene || ! $col_tag ) {
            return array();
        }

        $stmt = $this->remote_db->prepare( "SELECT `{$col_tag}` FROM `{$join_table}` WHERE `{$col_scene}` = ?" );
        $stmt->bind_param( 'i', $remote_scene_id );
        $stmt->execute();
        $result = $stmt->get_result();

        $ids = array();
        while ( $row = $result->fetch_row() ) {
            $ids[] = (int) $row[0];
        }
        $result->free();
        $stmt->close();

        return $ids;
    }

    /**
     * Build actor map from already-imported actors.
     */
    private function build_actor_map() {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_hotboys_remote_id' AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'actor')"
        );
        foreach ( $results as $row ) {
            $this->actor_map[ $row->meta_value ] = (int) $row->post_id;
        }
        WP_CLI::log( sprintf( 'Actor map: %d atores já importados no WP.', count( $this->actor_map ) ) );
    }

    /**
     * Download an image and set as featured.
     */
    private function set_featured_image( $post_id, $url, $title ) {
        // Ensure URL is absolute.
        if ( strpos( $url, 'http' ) !== 0 ) {
            // Try common HotBoys image base URLs.
            $bases = array(
                'https://server2.hotboys.com.br/arquivos/',
                'https://www.hotboys.com.br/imagens/',
                'https://hotboys.com.br/',
            );
            foreach ( $bases as $base ) {
                $test_url = $base . ltrim( $url, '/' );
                $response = wp_remote_head( $test_url, array( 'timeout' => 5 ) );
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $url = $test_url;
                    break;
                }
            }
        }

        if ( strpos( $url, 'http' ) !== 0 ) {
            return; // Cannot resolve URL.
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url( $url, 15 );
        if ( is_wp_error( $tmp ) ) {
            return;
        }

        $file_array = array(
            'name'     => sanitize_file_name( basename( wp_parse_url( $url, PHP_URL_PATH ) ) ),
            'tmp_name' => $tmp,
        );

        $attachment_id = media_handle_sideload( $file_array, $post_id, $title );

        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            return;
        }

        set_post_thumbnail( $post_id, $attachment_id );
    }
}

WP_CLI::add_command( 'hotboys', 'HotBoys_Import_Command' );
