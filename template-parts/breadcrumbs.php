<?php
/**
 * Template Part: Breadcrumbs
 *
 * @package HotBoys
 */

if ( is_front_page() ) {
    return;
}

$items = array();
$items[] = array(
    'url'  => home_url( '/' ),
    'name' => 'Home',
);

if ( is_singular( 'scene' ) ) {
    $items[] = array(
        'url'  => get_post_type_archive_link( 'scene' ),
        'name' => 'Cenas',
    );
    // Adicionar categoria principal no breadcrumb
    $scene_cats = get_the_terms( get_the_ID(), 'scene_category' );
    if ( ! empty( $scene_cats ) && ! is_wp_error( $scene_cats ) ) {
        $primary_cat = $scene_cats[0];
        $items[] = array(
            'url'  => get_term_link( $primary_cat ),
            'name' => $primary_cat->name,
        );
    }
    $items[] = array(
        'name' => get_the_title(),
    );
} elseif ( is_singular( 'actor' ) ) {
    $items[] = array(
        'url'  => get_post_type_archive_link( 'actor' ),
        'name' => 'Atores',
    );
    $items[] = array(
        'name' => get_the_title(),
    );
} elseif ( is_post_type_archive( 'scene' ) ) {
    $items[] = array(
        'name' => 'Cenas',
    );
} elseif ( is_post_type_archive( 'actor' ) ) {
    $items[] = array(
        'name' => 'Atores',
    );
} elseif ( is_tax( 'scene_category' ) ) {
    $items[] = array(
        'url'  => get_post_type_archive_link( 'scene' ),
        'name' => 'Cenas',
    );
    $items[] = array(
        'name' => single_term_title( '', false ),
    );
} elseif ( is_tax( 'scene_tag' ) ) {
    $items[] = array(
        'url'  => get_post_type_archive_link( 'scene' ),
        'name' => 'Cenas',
    );
    $items[] = array(
        'name' => single_term_title( '', false ),
    );
} elseif ( is_search() ) {
    $items[] = array(
        'name' => sprintf( 'Resultados para: %s', get_search_query() ),
    );
} elseif ( is_404() ) {
    $items[] = array(
        'name' => 'Página não encontrada',
    );
} elseif ( is_page() ) {
    $items[] = array(
        'name' => get_the_title(),
    );
}
?>

<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <?php foreach ( $items as $i => $item ) : ?>
            <li class="breadcrumbs__item">
                <?php if ( isset( $item['url'] ) && $i < count( $items ) - 1 ) : ?>
                    <a href="<?php echo esc_url( $item['url'] ); ?>" class="breadcrumbs__link">
                        <?php echo esc_html( $item['name'] ); ?>
                    </a>
                <?php else : ?>
                    <span class="breadcrumbs__current" aria-current="page">
                        <?php echo esc_html( $item['name'] ); ?>
                    </span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
