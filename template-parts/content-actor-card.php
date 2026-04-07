<?php
/**
 * Template Part: Card de Ator
 *
 * @package HotBoys
 */

$scene_count = hotboys_get_actor_scene_count( get_the_ID() );
?>

<article class="actor-card">
    <a href="<?php the_permalink(); ?>" class="actor-card__link" title="<?php the_title_attribute(); ?>">
        <div class="actor-card__photo">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php echo wp_get_attachment_image( get_post_thumbnail_id(), 'actor-thumb', false, array(
                    'class'    => 'actor-card__img',
                    'loading'  => 'lazy',
                    'alt'      => get_the_title(),
                    'width'    => 300,
                    'height'   => 400,
                    'decoding' => 'async',
                ) ); ?>
            <?php else : ?>
                <div class="actor-card__placeholder" aria-hidden="true"></div>
            <?php endif; ?>
        </div>

        <div class="actor-card__info">
            <h3 class="actor-card__name"><?php the_title(); ?></h3>
            <?php if ( $scene_count > 0 ) : ?>
                <p class="actor-card__count">
                    <?php printf( _n( '%d cena', '%d cenas', $scene_count, 'hotboys' ), $scene_count ); ?>
                </p>
            <?php endif; ?>
        </div>
    </a>
</article>
