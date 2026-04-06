<?php
/**
 * Custom Post Types: Scene e Actor
 *
 * @package HotBoys
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registrar CPT Scene
 */
function hotboys_register_scene_cpt() {
    $labels = array(
        'name'               => 'Cenas',
        'singular_name'      => 'Cena',
        'menu_name'          => 'Cenas',
        'add_new'            => 'Adicionar Cena',
        'add_new_item'       => 'Adicionar Nova Cena',
        'edit_item'          => 'Editar Cena',
        'new_item'           => 'Nova Cena',
        'view_item'          => 'Ver Cena',
        'search_items'       => 'Buscar Cenas',
        'not_found'          => 'Nenhuma cena encontrada',
        'not_found_in_trash' => 'Nenhuma cena na lixeira',
        'all_items'          => 'Todas as Cenas',
    );

    register_post_type( 'scene', array(
        'labels'       => $labels,
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => array( 'slug' => 'cenas', 'with_front' => false ),
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-video-alt3',
        'menu_position' => 5,
    ) );

    // Registrar meta fields
    $scene_meta = array(
        '_scene_duration'     => 'string',
        '_scene_release_date' => 'string',
        '_scene_external_url' => 'string',
        '_scene_trailer_url'  => 'string',
        '_scene_quality'      => 'string',
    );

    foreach ( $scene_meta as $key => $type ) {
        register_post_meta( 'scene', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
        ) );
    }
}
add_action( 'init', 'hotboys_register_scene_cpt' );

/**
 * Registrar CPT Actor
 */
function hotboys_register_actor_cpt() {
    $labels = array(
        'name'               => 'Atores',
        'singular_name'      => 'Ator',
        'menu_name'          => 'Atores',
        'add_new'            => 'Adicionar Ator',
        'add_new_item'       => 'Adicionar Novo Ator',
        'edit_item'          => 'Editar Ator',
        'new_item'           => 'Novo Ator',
        'view_item'          => 'Ver Ator',
        'search_items'       => 'Buscar Atores',
        'not_found'          => 'Nenhum ator encontrado',
        'not_found_in_trash' => 'Nenhum ator na lixeira',
        'all_items'          => 'Todos os Atores',
    );

    register_post_type( 'actor', array(
        'labels'       => $labels,
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => array( 'slug' => 'atores', 'with_front' => false ),
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-admin-users',
        'menu_position' => 6,
    ) );

    $actor_meta = array(
        '_actor_age'          => 'string',
        '_actor_city'         => 'string',
        '_actor_instagram'    => 'string',
        '_actor_twitter'      => 'string',
        '_actor_external_url' => 'string',
    );

    foreach ( $actor_meta as $key => $type ) {
        register_post_meta( 'actor', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
        ) );
    }
}
add_action( 'init', 'hotboys_register_actor_cpt' );

/**
 * Meta Box - Detalhes da Cena
 */
function hotboys_scene_meta_boxes() {
    add_meta_box(
        'hotboys_scene_details',
        'Detalhes da Cena',
        'hotboys_scene_meta_box_html',
        'scene',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'hotboys_scene_meta_boxes' );

function hotboys_scene_meta_box_html( $post ) {
    wp_nonce_field( 'hotboys_scene_nonce', 'hotboys_scene_nonce_field' );

    $duration     = get_post_meta( $post->ID, '_scene_duration', true );
    $release_date = get_post_meta( $post->ID, '_scene_release_date', true );
    $external_url = get_post_meta( $post->ID, '_scene_external_url', true );
    $trailer_url  = get_post_meta( $post->ID, '_scene_trailer_url', true );
    $quality      = get_post_meta( $post->ID, '_scene_quality', true );
    $actors       = get_post_meta( $post->ID, '_scene_actors', true );

    if ( ! is_array( $actors ) ) {
        $actors = array();
    }

    // Buscar todos os atores
    $all_actors = get_posts( array(
        'post_type'      => 'actor',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ) );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="scene_duration">Duração (MM:SS)</label></th>
            <td><input type="text" id="scene_duration" name="scene_duration" value="<?php echo esc_attr( $duration ); ?>" placeholder="25:30" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="scene_release_date">Data de Lançamento</label></th>
            <td><input type="date" id="scene_release_date" name="scene_release_date" value="<?php echo esc_attr( $release_date ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="scene_quality">Qualidade</label></th>
            <td>
                <select id="scene_quality" name="scene_quality">
                    <option value="">Selecione</option>
                    <option value="4K" <?php selected( $quality, '4K' ); ?>>4K</option>
                    <option value="Full HD" <?php selected( $quality, 'Full HD' ); ?>>Full HD</option>
                    <option value="HD" <?php selected( $quality, 'HD' ); ?>>HD</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="scene_external_url">URL Externa (Assistir)</label></th>
            <td><input type="url" id="scene_external_url" name="scene_external_url" value="<?php echo esc_url( $external_url ); ?>" placeholder="https://..." class="large-text"></td>
        </tr>
        <tr>
            <th><label for="scene_trailer_url">URL do Trailer (Embed)</label></th>
            <td><input type="url" id="scene_trailer_url" name="scene_trailer_url" value="<?php echo esc_url( $trailer_url ); ?>" placeholder="https://..." class="large-text"></td>
        </tr>
        <tr>
            <th><label>Atores nesta Cena</label></th>
            <td>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                    <?php if ( ! empty( $all_actors ) ) : ?>
                        <?php foreach ( $all_actors as $actor ) : ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="scene_actors[]" value="<?php echo esc_attr( $actor->ID ); ?>"
                                    <?php checked( in_array( $actor->ID, $actors ) ); ?>>
                                <?php echo esc_html( $actor->post_title ); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>Nenhum ator cadastrado. <a href="<?php echo admin_url( 'post-new.php?post_type=actor' ); ?>">Adicionar ator</a></p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Salvar meta da cena
 */
function hotboys_save_scene_meta( $post_id ) {
    if ( ! isset( $_POST['hotboys_scene_nonce_field'] ) || ! wp_verify_nonce( $_POST['hotboys_scene_nonce_field'], 'hotboys_scene_nonce' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = array(
        'scene_duration'     => '_scene_duration',
        'scene_release_date' => '_scene_release_date',
        'scene_quality'      => '_scene_quality',
    );

    foreach ( $fields as $field => $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field ] ) );
        }
    }

    // URLs
    if ( isset( $_POST['scene_external_url'] ) ) {
        update_post_meta( $post_id, '_scene_external_url', esc_url_raw( $_POST['scene_external_url'] ) );
    }
    if ( isset( $_POST['scene_trailer_url'] ) ) {
        update_post_meta( $post_id, '_scene_trailer_url', esc_url_raw( $_POST['scene_trailer_url'] ) );
    }

    // Atores (array de IDs)
    $actors = isset( $_POST['scene_actors'] ) ? array_map( 'absint', $_POST['scene_actors'] ) : array();
    update_post_meta( $post_id, '_scene_actors', $actors );

    // Invalidar cache de contagem de cenas dos atores
    foreach ( $actors as $actor_id ) {
        delete_transient( 'hotboys_actor_scene_count_' . $actor_id );
    }
}
add_action( 'save_post_scene', 'hotboys_save_scene_meta' );

/**
 * Meta Box - Detalhes do Ator
 */
function hotboys_actor_meta_boxes() {
    add_meta_box(
        'hotboys_actor_details',
        'Detalhes do Ator',
        'hotboys_actor_meta_box_html',
        'actor',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'hotboys_actor_meta_boxes' );

function hotboys_actor_meta_box_html( $post ) {
    wp_nonce_field( 'hotboys_actor_nonce', 'hotboys_actor_nonce_field' );

    $age          = get_post_meta( $post->ID, '_actor_age', true );
    $city         = get_post_meta( $post->ID, '_actor_city', true );
    $instagram    = get_post_meta( $post->ID, '_actor_instagram', true );
    $twitter      = get_post_meta( $post->ID, '_actor_twitter', true );
    $external_url = get_post_meta( $post->ID, '_actor_external_url', true );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="actor_age">Idade</label></th>
            <td><input type="text" id="actor_age" name="actor_age" value="<?php echo esc_attr( $age ); ?>" placeholder="25" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="actor_city">Cidade</label></th>
            <td><input type="text" id="actor_city" name="actor_city" value="<?php echo esc_attr( $city ); ?>" placeholder="São Paulo, SP" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="actor_instagram">Instagram</label></th>
            <td><input type="url" id="actor_instagram" name="actor_instagram" value="<?php echo esc_url( $instagram ); ?>" placeholder="https://instagram.com/..." class="large-text"></td>
        </tr>
        <tr>
            <th><label for="actor_twitter">Twitter / X</label></th>
            <td><input type="url" id="actor_twitter" name="actor_twitter" value="<?php echo esc_url( $twitter ); ?>" placeholder="https://x.com/..." class="large-text"></td>
        </tr>
        <tr>
            <th><label for="actor_external_url">URL do Perfil Externo</label></th>
            <td><input type="url" id="actor_external_url" name="actor_external_url" value="<?php echo esc_url( $external_url ); ?>" placeholder="https://hotboys.com.br/..." class="large-text"></td>
        </tr>
    </table>
    <?php
}

/**
 * Salvar meta do ator
 */
function hotboys_save_actor_meta( $post_id ) {
    if ( ! isset( $_POST['hotboys_actor_nonce_field'] ) || ! wp_verify_nonce( $_POST['hotboys_actor_nonce_field'], 'hotboys_actor_nonce' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $text_fields = array(
        'actor_age'  => '_actor_age',
        'actor_city' => '_actor_city',
    );

    foreach ( $text_fields as $field => $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field ] ) );
        }
    }

    $url_fields = array(
        'actor_instagram'    => '_actor_instagram',
        'actor_twitter'      => '_actor_twitter',
        'actor_external_url' => '_actor_external_url',
    );

    foreach ( $url_fields as $field => $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $meta_key, esc_url_raw( $_POST[ $field ] ) );
        }
    }
}
add_action( 'save_post_actor', 'hotboys_save_actor_meta' );

/**
 * Colunas customizadas no admin - Cenas
 */
function hotboys_scene_admin_columns( $columns ) {
    $new = array();
    $new['cb']             = $columns['cb'];
    $new['scene_thumb']    = 'Thumb';
    $new['title']          = $columns['title'];
    $new['scene_actors']   = 'Atores';
    $new['scene_duration'] = 'Duração';
    $new['scene_quality']  = 'Qualidade';
    $new['taxonomy-scene_category'] = 'Categoria';
    $new['date']           = $columns['date'];
    return $new;
}
add_filter( 'manage_scene_posts_columns', 'hotboys_scene_admin_columns' );

function hotboys_scene_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'scene_thumb':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, array( 80, 45 ) );
            } else {
                echo '—';
            }
            break;
        case 'scene_actors':
            $actors = get_post_meta( $post_id, '_scene_actors', true );
            if ( is_array( $actors ) && ! empty( $actors ) ) {
                $names = array();
                foreach ( $actors as $actor_id ) {
                    $names[] = get_the_title( $actor_id );
                }
                echo esc_html( implode( ', ', $names ) );
            } else {
                echo '—';
            }
            break;
        case 'scene_duration':
            $duration = get_post_meta( $post_id, '_scene_duration', true );
            echo $duration ? esc_html( $duration ) : '—';
            break;
        case 'scene_quality':
            $quality = get_post_meta( $post_id, '_scene_quality', true );
            echo $quality ? esc_html( $quality ) : '—';
            break;
    }
}
add_action( 'manage_scene_posts_custom_column', 'hotboys_scene_admin_column_content', 10, 2 );

/**
 * Colunas customizadas no admin - Atores
 */
function hotboys_actor_admin_columns( $columns ) {
    $new = array();
    $new['cb']           = $columns['cb'];
    $new['actor_photo']  = 'Foto';
    $new['title']        = $columns['title'];
    $new['actor_scenes'] = 'Cenas';
    $new['actor_city']   = 'Cidade';
    $new['date']         = $columns['date'];
    return $new;
}
add_filter( 'manage_actor_posts_columns', 'hotboys_actor_admin_columns' );

function hotboys_actor_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'actor_photo':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, array( 50, 67 ) );
            } else {
                echo '—';
            }
            break;
        case 'actor_scenes':
            echo hotboys_get_actor_scene_count( $post_id );
            break;
        case 'actor_city':
            $city = get_post_meta( $post_id, '_actor_city', true );
            echo $city ? esc_html( $city ) : '—';
            break;
    }
}
add_action( 'manage_actor_posts_custom_column', 'hotboys_actor_admin_column_content', 10, 2 );
