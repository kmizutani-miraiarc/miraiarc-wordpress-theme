<?php
/**
 * Template: Archive Example (CPT: example)
 * Path: /wp-content/themes/swell_child/archive-example.php
 * もしくは /wp-content/uploads/custom-templates/archive-example.php（ローダー使用時）
 */

get_header();

// —— 設定 ——（環境に合わせて変更OK）
$cpt_slug = 'example';
$tax_slug = 'example_category';
$acf_image_field = 'property_image'; // ACFの物件画像フィールド名
$noimg = get_stylesheet_directory_uri() . '/assets/img/noimg.png'; // 代替画像

?>
<style>
/* ====== このテンプレにだけ効く軽量スタイル ====== */
.ex-archive{--gap:32px;--radius:10px}
.ex-archive .grid{
  display:grid;gap:var(--gap);
  grid-template-columns:repeat(2,minmax(0,1fr));
}
@media (max-width: 781px){
  .ex-archive .grid{grid-template-columns:1fr}
}

.ex-card__thumb{display:block;position:relative;overflow:hidden}
.ex-card__thumb::before{
  content:"";display:block;aspect-ratio:16/10; /* 画像比率（必要なら調整） */
}
.ex-card__thumb img{
  position:absolute;inset:0;width:100%;height:100%;object-fit:cover;
}
.ex-card__body{padding:14px 16px 16px}
.ex-card__cats{color:#6b7280;font-size:.86rem;margin-bottom:8px}
.ex-card__title{
  font-size:1.06rem;line-height:1.55;margin:0 0 6px;font-weight:700;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden
}
.ex-card__excerpt{color:#555;font-size:.95rem;margin:8px 0 0}
.ex-archive__head{margin:10px 0 28px;text-align:center}
.ex-archive__title{font-size:clamp(24px,3.2vw,40px);letter-spacing:.05em;margin:0 0 6px}
.ex-archive__desc{color:#666}
</style>

<div class="l-container ex-archive">
  <main id="main" class="l-content">

    <!-- ヘッダー -->
    <header class="ex-archive__head c-section">
      <h1 class="ex-archive__title">EXAMPLE</h1>
      <p class="ex-archive__desc">買取実績</p>
      <?php if ( term_description() && is_tax($tax_slug) ) : ?>
        <div class="p-archive__desc"><?php echo wp_kses_post( term_description() ); ?></div>
      <?php endif; ?>
    </header>

    <div class="ex-archive_leadtxt">
        <p>弊社がこれまでに手がけた<br class="spbr">買取事例の一部をご紹介します。</p>
    </div>

    <aside class="ex-archive__cats">
    <div class="widget">
      <h2 class="widget__title">エリアカテゴリー</h2>
      <ul>
        <?php
        wp_list_categories([
          'taxonomy'   => $tax_slug,
          'title_li'   => '',
          'hide_empty' => false,
        ]);
        ?>
      </ul>
    </div>

    <!--<div class="widget">
      <h2 class="widget__title">最新の事例</h2>
      <ul>
        <?php
        $recent = new WP_Query([
          'post_type'      => $cpt_slug,
          'posts_per_page' => 5,
          'post_status'    => 'publish',
          'orderby'        => 'date',
          'order'          => 'DESC',
        ]);
        if ( $recent->have_posts() ) :
          while ( $recent->have_posts() ) : $recent->the_post(); ?>
            <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
          <?php endwhile; wp_reset_postdata();
        else :
          echo '<li>まだ投稿がありません</li>';
        endif;
        ?>
      </ul>
    </div>-->
  </aside>

    <?php if ( have_posts() ) : ?>
      <div class="grid">

        <?php while ( have_posts() ) : the_post(); ?>
          <article <?php post_class('ex-card'); ?>>

            <!-- サムネイル（ACF画像 > アイキャッチ > 代替） -->
            <a class="ex-card__thumb" href="<?php the_permalink(); ?>">
              <?php
              $img_html = '';
              if ( function_exists('get_field') ) {
                $acf_img = get_field( $acf_image_field );
                if ( $acf_img ) {
                  // 返り値：配列 or ID に両対応
                  if ( is_array($acf_img) && !empty($acf_img['ID']) ) {
                    $img_html = wp_get_attachment_image( $acf_img['ID'], 'medium_large', false );
                  } elseif ( is_numeric($acf_img) ) {
                    $img_html = wp_get_attachment_image( $acf_img, 'medium_large', false );
                  } elseif ( is_string($acf_img) ) {
                    $img_html = '<img src="' . esc_url($acf_img) . '" alt="">';
                  }
                }
              }
              if ( ! $img_html ) {
                if ( has_post_thumbnail() ) {
                  $img_html = get_the_post_thumbnail( get_the_ID(), 'medium_large' );
                } else {
                  $img_html = '<img src="' . esc_url( $noimg ) . '" alt="">';
                }
              }
              echo $img_html;
              ?>
            </a>

            <div class="ex-card__body">
              <!-- カテゴリ（タクソノミー） -->
              <div class="ex-card__cats">
                <?php
                $terms = get_the_terms( get_the_ID(), $tax_slug );
                if ( $terms && ! is_wp_error($terms) ) {
                  $links = [];
                  foreach ( $terms as $t ) {
                    $links[] = '<a href="' . esc_url( get_term_link($t) ) . '">' . esc_html( $t->name ) . '</a>';
                  }
                  echo implode('、', $links);
                } else {
                  echo '物件カテゴリ';
                }
                ?>
              </div>

              <!-- タイトル -->
              <h2 class="ex-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

              <!-- 説明（抜粋 or ACF要約があれば） -->
              <div class="ex-card__excerpt">
                <?php
                // もし ACF の要約フィールドを使うなら 'summary' などにして優先表示
                $summary = function_exists('get_field') ? get_field('summary') : '';
                if ( $summary ) {
                  echo wp_kses_post( wp_trim_words( wp_strip_all_tags($summary), 36, '…' ) );
                } else {
                  echo esc_html( wp_trim_words( get_the_excerpt() ?: wp_strip_all_tags( get_the_content() ), 36, '…' ) );
                }
                ?>
              </div>
            </div>
          </article>
        <?php endwhile; ?>

      </div>

      <!-- ページネーション -->
      <div class="c-pagination" style="margin-top:40px;text-align:center;">
        <?php the_posts_pagination([
          'mid_size'  => 1,
          'prev_text' => '<',
          'next_text' => '>',
        ]); ?>
      </div>

    <?php else : ?>
      <p>まだ事例がありません。</p>
    <?php endif; ?>
  </main>


</div>

<?php get_footer(); ?>