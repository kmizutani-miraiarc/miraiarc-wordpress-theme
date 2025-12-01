<?php
/**
 * Template: Single Example (CPT: example)
 * Place this file in your child theme: /wp-content/themes/swell_child/single-example.php
 */

get_header();

$cpt_slug = 'example';                 // CPT UI で作った投稿タイプ slug
$tax_slug = 'example_category';        // 関連カテゴリのタクソノミー slug（なければ空に）
$contact_url = home_url('/contact/');  // フォームのURL
$tel_raw = '0368093420';               // ハイフン無しの電話番号
$tel_disp = '03-6809-3420';            // 表示用

?>
<div class="l-container ex-single">
  <main id="main" class="l-content">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

      <article <?php post_class('p-entry'); ?>>

        <!-- タイトル -->
        <header class="p-entry__header c-section">
          <h1 class="p-entry__title"><?php the_title(); ?></h1>
        </header>

        <!-- 物件画像（ACFフィールド） -->
        <figure class="p-entry__eyecatch" style="margin:24px 0;">
        <?php
        $img = get_field('property_image'); // ← ACFフィールド名に合わせて変更！
        if ( $img ) {
            // $img が配列の場合（ACFの画像フィールドは通常これ）
            echo wp_get_attachment_image( $img['ID'], 'large', false, ['class' => 'u-img'] );
        } else {
            // 画像が未設定のとき
            echo '<img src="' . esc_url( get_stylesheet_directory_uri() . '/assets/img/noimg.png' ) . '" alt="">';
        }
        ?>
        </figure>

        <!-- ACF 基本情報テーブル -->
        <?php
        // ACF フィールド（ACFで以下のfield_nameを作成）
        // 物件名: property_name（Text）
        // 築年数: built_years（Text 例: "xx年"）
        // 最寄駅: nearest_station（Text）
        // 取引額: price（Text 例: "詳しくはお問合せください" でもOK）
        $rows = [
          [ 'label' => '物件名',   'key' => 'property_name'   ],
          [ 'label' => '築年数',   'key' => 'built_years'     ],
          [ 'label' => '最寄駅',   'key' => 'nearest_station' ],
          [ 'label' => '取引額',   'key' => 'price'           ],
        ];

        // 1つでも値があるかチェック
        $has_any = false;
        foreach ( $rows as $r ) {
          if ( get_field( $r['key'] ) ) { $has_any = true; break; }
        }
        ?>

        <?php if ( $has_any ) : ?>
          <section class="c-section" aria-label="物件情報">
            <dl class="c-table" style="display:grid;grid-template-columns:70px 1fr;gap:12px 20px;">
              <?php foreach ( $rows as $r ) :
                $val = get_field( $r['key'] );
                if ( ! $val ) continue; ?>
                <dt style="font-weight:700;"><?php echo esc_html( $r['label'] ); ?></dt>
                <dd><?php echo wp_kses_post( nl2br( $val ) ); ?></dd>
              <?php endforeach; ?>
            </dl>
          </section>
        <?php endif; ?>

        <!-- 本文（必要なら事例ストーリーや補足をエディタで） -->
        <?php if ( get_the_content() ) : ?>
          <section class="c-section">
            <div class="p-entry__body">
              <?php the_content(); ?>
            </div>
          </section>
        <?php endif; ?>

        <!-- CTAボタン -->
        <div class="c-section" style="display:flex;gap:16px;flex-wrap:wrap;margin-top:24px;">
          <a class="c-btn c-btn--primary" href="<?php echo esc_url( $contact_url ); ?>">
            <span class="c-btn__label"><i class="fa-solid fa-file-pen"></i>フォームから問い合わせる</span>
          </a>
          <a class="c-btn c-btn--outline" href="<?php echo esc_url( 'tel:' . $tel_raw ); ?>">
            <span class="c-btn__label"><i class="fa-solid fa-mobile-screen-button"></i>電話で問い合わせる</span>
          </a>
        </div>

      </article>

    <?php endwhile; endif; ?>
  </main>

  <!-- サイドバー -->
  <aside class="l-sidebar">
    <!-- 検索 -->
    <div class="widget">
      <?php get_search_form(); ?>
    </div>

    <!-- 物件カテゴリー（カスタム分類） -->
    <?php if ( $tax_slug && taxonomy_exists( $tax_slug ) ) : ?>
      <div class="widget">
        <h2 class="widget__title">物件カテゴリー</h2>
        <ul>
          <?php
          wp_list_categories( [
            'taxonomy'   => $tax_slug,
            'title_li'   => '',
            'hide_empty' => false,
          ] );
          ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- 最近の事例（同じCPT） -->
    <div class="widget">
      <h2 class="widget__title">最新事例</h2>
      <ul>
        <?php
        $recent = new WP_Query( [
          'post_type'      => $cpt_slug,
          'posts_per_page' => 5,
          'post_status'    => 'publish',
          'orderby'        => 'date',
          'order'          => 'DESC',
        ] );
        if ( $recent->have_posts() ) :
          while ( $recent->have_posts() ) : $recent->the_post(); ?>
            <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
          <?php endwhile;
          wp_reset_postdata();
        else :
          echo '<li>まだ投稿がありません</li>';
        endif;
        ?>
      </ul>
    </div>
  </aside>
</div>

<?php get_footer();