<?php
/**
 * Template: Single Purchase Achievement (Fixed Page)
 * Path: /wp-content/uploads/template/page-purchase-achievements-detail.php
 * 
 * 固定ページ用の買取実績詳細テンプレート
 * mirai-apiから買取実績詳細データを取得して表示
 */

get_header();

// 詳細ページではmain_visualを非表示にする
?>
<style>
#main_visual {
  display: none !important;
}
</style>
<?php

// 環境変数からAPI設定を取得
$api_url = getenv('MIRAI_API_URL') ?: 'http://localhost:8000';
$api_key = getenv('MIRAI_API_KEY') ?: '';

// URLパラメーターからIDを取得（例: /purchase-achievements-detail/?id=123）
$achievement_id = 0;
if ( isset($_GET['id']) && !empty($_GET['id']) ) {
    $achievement_id = intval($_GET['id']);
}

$achievement = null;
$achievement_title = '詳細';
$contact_url = home_url('/contact/');
$tel_raw = '0368093420';
$tel_disp = '03-6809-3420';

if ($achievement_id > 0) {
    // APIから買取実績詳細を取得
    $api_endpoint = rtrim($api_url, '/') . '/purchase-achievements/' . $achievement_id;
    
    $response = wp_remote_get($api_endpoint, [
        'headers' => [
            'X-API-Key' => $api_key,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
    ]);
    
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data && isset($data['status']) && $data['status'] === 'success' && isset($data['data'])) {
            $achievement = $data['data'];
            $achievement_title = $achievement['title'] ?? $achievement['property_name'] ?? '詳細';
        }
    }
}

?>
<div class="l-container ex-single">
  <main id="main" class="l-content">
    <?php if ( $achievement ) : ?>

      <article <?php post_class('p-entry'); ?>>

        <!-- タイトル -->
        <header class="p-entry__header c-section">
          <h1 class="p-entry__title"><?php echo esc_html($achievement['title'] ?? $achievement['property_name'] ?? ''); ?></h1>
        </header>

        <!-- 物件画像 -->
        <figure class="p-entry__eyecatch" style="margin:24px 0;">
        <?php
        $image_url = $achievement['property_image_url'] ?? '';
        if ( $image_url ) {
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($achievement['title'] ?? '') . '" class="u-img">';
        } else {
            echo '<img src="' . esc_url( get_stylesheet_directory_uri() . '/assets/img/noimg.png' ) . '" alt="">';
        }
        ?>
        </figure>

        <!-- 基本情報テーブル -->
        <?php
        // 指定された項目を常に表示（データがない場合もタイトルだけ表示）
        $rows = [
            ['label' => '物件名', 'value' => $achievement['property_name'] ?? ''],
            ['label' => '築年数', 'value' => !empty($achievement['building_age']) ? $achievement['building_age'] . '年' : ''],
            ['label' => '構造', 'value' => $achievement['structure'] ?? ''],
            ['label' => '最寄り', 'value' => $achievement['nearest_station'] ?? ''],
            ['label' => '買取日', 'value' => $achievement['purchase_date'] ?? ''],
        ];
        ?>

        <section class="c-section" aria-label="物件情報">
          <dl class="c-table" style="display:grid;grid-template-columns:70px 1fr;gap:12px 20px;">
            <?php foreach ( $rows as $r ) : ?>
              <dt style="font-weight:700;"><?php echo esc_html( $r['label'] ); ?></dt>
              <dd><?php echo !empty($r['value']) ? wp_kses_post( nl2br( esc_html($r['value']) ) ) : ''; ?></dd>
            <?php endforeach; ?>
          </dl>
        </section>

        <!-- 本文（必要なら事例ストーリーや補足をエディタで） -->
        <?php if ( !empty($achievement['description']) || !empty($achievement['comment']) ) : ?>
          <section class="c-section">
            <div class="p-entry__body">
              <?php 
              $content = $achievement['description'] ?? $achievement['comment'] ?? '';
              if ( $content ) {
                  echo wp_kses_post( wpautop( $content ) );
              }
              ?>
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

    <?php elseif ( $achievement_id == 0 ) : ?>
      <p>買取実績IDが指定されていません。URLパラメーターに <code>?id=数字</code> を指定してください。</p>
    <?php else : ?>
      <p>買取実績が見つかりませんでした。</p>
    <?php endif; ?>
  </main>

  <!-- サイドバー -->
  <aside class="l-sidebar">
    <!-- 検索 -->
    <div class="widget">
      <?php get_search_form(); ?>
    </div>

    <!-- 物件カテゴリー -->
    <div class="widget">
      <h2 class="widget__title">エリアカテゴリー</h2>
      <ul>
        <li><a href="<?php echo esc_url(home_url('/purchase-achievements/')); ?>">すべて</a></li>
        <li><a href="<?php echo esc_url(add_query_arg('prefecture', '千葉県', home_url('/purchase-achievements/'))); ?>">千葉県</a></li>
        <li><a href="<?php echo esc_url(add_query_arg('prefecture', '埼玉県', home_url('/purchase-achievements/'))); ?>">埼玉県</a></li>
        <li><a href="<?php echo esc_url(add_query_arg('prefecture', '栃木県', home_url('/purchase-achievements/'))); ?>">栃木県</a></li>
        <li><a href="<?php echo esc_url(add_query_arg('prefecture', '神奈川県', home_url('/purchase-achievements/'))); ?>">神奈川県</a></li>
        <li><a href="<?php echo esc_url(add_query_arg('prefecture', '群馬県', home_url('/purchase-achievements/'))); ?>">群馬県</a></li>
        <li><a href="<?php echo esc_url(add_query_arg('prefecture', '茨城県', home_url('/purchase-achievements/'))); ?>">茨城県</a></li>
        <li><a href="<?php echo esc_url(add_query_arg('prefecture', '東京都', home_url('/purchase-achievements/'))); ?>">都内エリア</a></li>
      </ul>
    </div>
  </aside>
</div>

<?php get_footer(); ?>

