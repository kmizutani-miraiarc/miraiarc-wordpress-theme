<?php
/**
 * Template: Archive News
 * Place: /wp-content/uploads/custom_template/archive-news.php
 */

get_header();

// —— 設定（必要に応じて変更）——
$cpt_slug = 'news';
$tax_candidates = ['news_category', 'category']; // 使っている方が自動で選ばれる

// 実在するタクソノミーを決定
$tax_slug = '';
foreach ($tax_candidates as $cand) {
  if ( taxonomy_exists($cand) ) { $tax_slug = $cand; break; }
}

?>
<style>
/* ====== このテンプレ専用の軽量CSS ====== */
.news-archive{--gap:20px}
.news-archive__head{margin:24px 0 36px;text-align:center}
.news-archive__sub{color:#777;margin-top:8px}

.news-list{display:flex;flex-direction:column;gap:var(--gap)}
.news-item{padding:18px 0;border-bottom:1px solid #e6e6e6}
.news-item__date{white-space:nowrap}
.news-item__cats a{color:#6b7280;text-decoration:none}
.news-item__cats a:hover{text-decoration:underline}
.news-item__title{font-size:1.08rem;font-weight:700;line-height:1.6;margin:0}
.news-item__title a{color:inherit;text-decoration:none}
.news-item__title a:hover{text-decoration:underline}
</style>

<div class="l-container news-archive">
  <main id="main" class="l-content">

    <!-- ヘッダー -->
    <header class="news-archive__head c-section">
      <h1 class="news-archive__title">NEWS</h1>
      <p class="news-archive__sub">お知らせ</p>
    </header>

    <?php if ( have_posts() ) : ?>
      <div class="news-list">
        <?php while ( have_posts() ) : the_post(); ?>
          <article <?php post_class('news-item'); ?>>
            <div class="news-item__meta">
              <i class="fa-regular fa-clock"></i><time class="news-item__date" datetime="<?php echo esc_attr( get_the_date('c') ); ?>">
                <?php echo esc_html( get_the_date('Y年n月j日') ); ?>
              </time>
              <?php if ( $tax_slug ) : ?>
                <span class="news-item__cats">
                  <i class="fa-solid fa-folder"></i><?php
                  $terms = get_the_terms( get_the_ID(), $tax_slug );
                  if ( $terms && ! is_wp_error($terms) ) {
                    $links = [];
                    foreach ( $terms as $t ) {
                      $links[] = '<a href="'. esc_url( get_term_link($t) ) .'">'. esc_html( $t->name ) .'</a>';
                    }
                    echo implode(' ／ ', $links);
                  }
                  ?>
                </span>
              <?php endif; ?>
            </div>

            <h2 class="news-item__title">
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>
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
      <p>お知らせはまだありません。</p>
    <?php endif; ?>
  </main>
</div>

<?php get_footer(); ?>