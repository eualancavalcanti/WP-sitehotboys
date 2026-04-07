<?php
/**
 * Template Part: Card de Cena
 *
 * @package HotBoys
 */

$duration     = get_post_meta( get_the_ID(), '_scene_duration', true );
$quality      = get_post_meta( get_the_ID(), '_scene_quality', true );
$release_date = get_post_meta( get_the_ID(), '_scene_release_date', true );
?>

<article class="scene-card" itemscope itemtype="https://schema.org/VideoObject">
    <a href="<?php the_permalink(); ?>" class="scene-card__link" title="<?php the_title_attribute(); ?>">
        <div class="scene-card__thumb">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php echo wp_get_attachment_image( get_post_thumbnail_id(), 'scene-thumb', false, array(
                    'class'    => 'scene-card__img',
                    'loading'  => 'lazy',
                    'alt'      => get_the_title(),
                    'itemprop' => 'thumbnailUrl',
                    'width'    => 400,
                    'height'   => 225,
                    'decoding' => 'async',
                ) ); ?>
            <?php else : ?>
                <div class="scene-card__placeholder" aria-hidden="true"></div>
            <?php endif; ?>

            <?php if ( $duration ) : ?>
                <span class="scene-card__duration" itemprop="duration" content="<?php echo esc_attr( hotboys_duration_to_iso8601( $duration ) ); ?>">
                    <?php echo esc_html( $duration ); ?>
                </span>
            <?php endif; ?>

            <?php if ( $quality ) : ?>
                <span class="scene-card__quality"><?php echo esc_html( $quality ); ?></span>
            <?php endif; ?>

            <span class="scene-card__play" aria-hidden="true">
                <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="23" stroke="#fff" stroke-width="2" opacity=".85"/><polygon points="20,16 34,24 20,32" fill="#fff"/></svg>
            </span>
            <span class="scene-card__overlay"></span>
        </div>

        <div class="scene-card__info">
            <h3 class="scene-card__title" itemprop="name"><?php the_title(); ?></h3>

            <?php
            $actor_names = hotboys_get_actor_names();
            if ( $actor_names ) :
            ?>
                <p class="scene-card__actors"><?php echo esc_html( $actor_names ); ?></p>
            <?php endif; ?>

            <?php if ( $release_date ) : ?>
                <time class="scene-card__date" datetime="<?php echo esc_attr( $release_date ); ?>" itemprop="uploadDate">
                    <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $release_date ) ) ); ?>
                </time>
            <?php endif; ?>
        </div>
    </a>
</article>
