<?php
/* Template: archive-member */
get_header(); ?>

<main id="main" class="member-archive">
  <div class="l-container">
    <header class="member-archive__head c-section">
      <h1 class="member-archive__title">MEMBER</h1>
      <p class="member-archive__sub">メンバーのご紹介</p>
    </header>

    <?php if ( have_posts() ) : ?>
      <div class="member-grid">
        <?php while ( have_posts() ) : the_post(); 
          // ACFの値取得
          $position = get_field('position');
          $profile  = get_field('profile');
          $photo    = get_field('photo');  

          // 画像の安全な取得（ACF画像 → アイキャッチ → 何もなければ空）
          $img_src = '';
          $img_alt = esc_attr( get_the_title() );
          if ( is_array($photo) && !empty($photo['sizes']['medium']) ) {
            $img_src = $photo['sizes']['medium'];
            if ( !empty( $photo['alt'] ) ) $img_alt = esc_attr( $photo['alt'] );
          } elseif ( has_post_thumbnail() ) {
            $img_src = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
            $img_alt = esc_attr( get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true ) ?: get_the_title() );
          }
        ?>
          <article class="member-info" <?php post_class('member-card'); ?>>
            <a href="<?php the_permalink(); ?>" class="card-inner" aria-label="<?php the_title_attribute(); ?>">
              <figure class="thumb">
                <?php if ( $img_src ) : ?>
                  <img src="<?php echo esc_url($img_src); ?>" alt="<?php echo $img_alt; ?>" loading="lazy" />
                <?php else : ?>
                  <div class="thumb--noimg" aria-hidden="true"></div>
                <?php endif; ?>
              </figure>
              <div class="meta">
                <h2 class="name"><?php the_title(); ?></h2>
                <?php if ( $position ) : ?>
                  <p class="position"><?php echo esc_html( $position ); ?></p>
                <?php endif; ?>
                <div class="more-link">
                  <a href="<?php the_permalink(); ?>">もっと見る ></a>
                </div>
              </div>
            </a>
          </article>
        <?php endwhile; ?>
      </div>

      <!-- ページネーション -->
      <div class="c-pagination" style="margin-top:32px;text-align:center;">
        <?php the_posts_pagination([
          'mid_size'  => 1,
          'prev_text' => '<',
          'next_text' => '>',
        ]); ?>
      </div>

    <?php else : ?>
      <p>メンバーがまだ登録されていません。</p>
    <?php endif; ?>
  </div>
</main>

<?php get_footer();