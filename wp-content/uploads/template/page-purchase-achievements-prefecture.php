<?php
/**
 * Taxonomy archive (prefecture filter for purchase achievements)
 * /wp-content/uploads/template/page-purchase-achievements-prefecture.php
 * 
 * 都道府県別買取実績一覧テンプレート（固定ページ用）
 * URLパラメーターで都道府県を切り替え
 */
get_header();

// 環境変数からAPI設定を取得
$api_url = getenv('MIRAI_API_URL') ?: 'http://localhost:8000';
$api_key = getenv('MIRAI_API_KEY') ?: '';

// URLパラメーターから都道府県を取得
$prefecture_param = isset($_GET['prefecture']) ? sanitize_text_field($_GET['prefecture']) : '';

// 都道府県マッピング（表示名 → API用の都道府県名）
$prefecture_map = [
    '千葉県' => '千葉県',
    '埼玉県' => '埼玉県',
    '栃木県' => '栃木県',
    '神奈川県' => '神奈川県',
    '群馬県' => '群馬県',
    '茨城県' => '茨城県',
    '都内エリア' => '東京都',
];

// 都道府県名を取得（表示用）
$prefecture_display = $prefecture_param;
if (isset($prefecture_map[$prefecture_param])) {
    $prefecture_api = $prefecture_map[$prefecture_param];
} else {
    $prefecture_api = $prefecture_param;
}

// ページネーション設定
$per_page = 6;
$current_page = max(1, get_query_var('paged') ?: 1);
$offset = ($current_page - 1) * $per_page;

// APIから買取実績一覧を取得
$api_endpoint = rtrim($api_url, '/') . '/purchase-achievements';
$api_params = [
    'is_public' => 'true',
    'limit' => $per_page,
    'offset' => $offset,
];

if ($prefecture_api) {
    $api_params['prefecture'] = $prefecture_api;
}

$api_url_with_params = $api_endpoint . '?' . http_build_query($api_params);

$response = wp_remote_get($api_url_with_params, [
    'headers' => [
        'X-API-Key' => $api_key,
        'Content-Type' => 'application/json',
    ],
    'timeout' => 30,
]);

$achievements = [];
$total = 0;
$total_pages = 0;

if (!is_wp_error($response)) {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data && isset($data['status']) && $data['status'] === 'success') {
        $achievements = $data['data'] ?? [];
        $total = $data['total'] ?? count($achievements);
        $total_pages = ceil($total / $per_page);
    }
}

$noimg = get_stylesheet_directory_uri() . '/assets/img/noimg.png';
?>
<style>
.tx-archive{--gap:32px;--radius:10px}
.tx-archive .grid{display:grid;gap:var(--gap);grid-template-columns:repeat(2,minmax(0,1fr))}
@media (max-width:781px){.tx-archive .grid{grid-template-columns:1fr}}
.tx-card{background:#fff;overflow:hidden}
.tx-thumb{position:relative;display:block;overflow:hidden}
.tx-thumb::before{content:"";display:block;aspect-ratio:16/10}
.tx-thumb img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.tx-body{padding:14px 16px 16px}
.tx-title{margin:0 0 6px;font-weight:700;font-size:1.06rem;line-height:1.6}
.tx-cats{color:#6b7280;font-size:.86rem;margin-bottom:8px}
</style>

<div class="l-container tx-archive">
  <main id="main" class="l-content">

    <header class="c-section" style="text-align:center;margin:10px 0 28px;">
      <h1 class="p-archive__title"><?php echo esc_html($prefecture_display ?: '買取実績'); ?></h1>
      <?php if ($prefecture_display) : ?>
        <div class="p-archive__desc"></div>
      <?php endif; ?>
    </header>

    <?php if ( !empty($achievements) ) : ?>
      <div class="grid">
        <?php foreach ( $achievements as $achievement ) : 
          $title = esc_html($achievement['title'] ?? '');
          $property_name = esc_html($achievement['property_name'] ?? '');
          $prefecture = esc_html($achievement['prefecture'] ?? '');
          $image_url = esc_url($achievement['property_image_url'] ?? '');
          $detail_url = add_query_arg('id', intval($achievement['id']), home_url('/purchase-achievements-detail/'));
          $summary = esc_html($achievement['title'] ?? '');
        ?>
          <article class="tx-card">
            <a href="<?php echo $detail_url; ?>" class="tx-thumb">
              <?php if ( $image_url ) : ?>
                <img src="<?php echo $image_url; ?>" alt="<?php echo esc_attr($title); ?>">
              <?php else : ?>
                <img src="<?php echo esc_url($noimg); ?>" alt="">
              <?php endif; ?>
            </a>
            <div class="tx-body">
              <div class="tx-cats">
                <?php if ( $prefecture ) : ?>
                  <a href="<?php echo esc_url(add_query_arg('prefecture', $prefecture, home_url('/purchase-achievements/'))); ?>"><?php echo $prefecture; ?></a>
                <?php endif; ?>
              </div>
              <h2 class="tx-title"><a href="<?php echo $detail_url; ?>"><?php echo $title ?: $property_name; ?></a></h2>
              <div class="p-card__excerpt">
                <?php echo esc_html( wp_trim_words( $summary, 36, '…' ) ); ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <!-- ページネーション -->
      <?php if ( $total_pages > 1 ) : ?>
        <div class="c-pagination" style="margin-top:40px;text-align:center;">
          <?php
          $pagination_base = add_query_arg('prefecture', $prefecture_param, home_url('/purchase-achievements/'));
          $pagination_args = [
            'base' => str_replace(999999999, '%#%', esc_url(add_query_arg('paged', 999999999, $pagination_base))),
            'format' => '?paged=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text' => '<',
            'next_text' => '>',
            'mid_size' => 1,
          ];
          if ($prefecture_param) {
              $pagination_args['add_args'] = ['prefecture' => $prefecture_param];
          }
          echo paginate_links($pagination_args);
          ?>
        </div>
      <?php endif; ?>
    <?php else : ?>
      <p>このエリアにはまだ買取実績がありません。</p>
    <?php endif; ?>
  </main>
</div>

<?php get_footer(); ?>

