<?php get_header(); ?>
<main id="main" class="single-member">
  <div class="l-container">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post();
      $position = get_field('position');
      $profile  = get_field('profile');
      $photo    = get_field('photo');
    ?>
      <article <?php post_class('member-single'); ?>>
        <header class="head">
          <h1 class="name"><?php the_title(); ?></h1>
          <?php if ( $position ) : ?><p class="position"><?php echo esc_html($position); ?></p><?php endif; ?>
        </header>

        <div class="cols">
          <div class="col img">
            <?php
              if ( is_array($photo) ) {
                echo wp_get_attachment_image( $photo['ID'], 'large', false, ['loading'=>'lazy'] );
              } elseif ( has_post_thumbnail() ) {
                the_post_thumbnail( 'large', ['loading'=>'lazy'] );
              }
            ?>
          </div>
          <div class="col body">
            <?php if ( $profile ) : ?>
              <div class="comment">
                <?php echo wp_kses_post( $profile ); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endwhile; endif; ?>
  </div>
</main>
<?php get_footer(); ?>