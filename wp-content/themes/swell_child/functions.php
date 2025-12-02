<?php

/* 子テーマのfunctions.phpは、親テーマのfunctions.phpより先に読み込まれることに注意してください。 */


/**
 * 親テーマのfunctions.phpのあとで読み込みたいコードはこの中に。
 */
// add_filter('after_setup_theme', function(){
// }, 11);


/**
 * 子テーマでのファイルの読み込み
 */
add_action('wp_enqueue_scripts', function() {
	
	$timestamp = date( 'Ymdgis', filemtime( get_stylesheet_directory() . '/style.css' ) );
	wp_enqueue_style( 'child_style', get_stylesheet_directory_uri() .'/style.css', [], $timestamp );

	/* その他の読み込みファイルはこの下に記述 */

}, 11);

//override.css読み込み
function my_custom_style_override() {
  wp_enqueue_style(
    'style-override',
    content_url('/uploads/custom-css/style-override.css'),
    array(),
    filemtime(WP_CONTENT_DIR . '/uploads/custom-css/style-override.css')
  );
}
add_action('wp_enqueue_scripts', 'my_custom_style_override');

//member・example一覧・個別、news一覧テンプレは uploads/template/に置いています（2025/10時点）
add_filter( 'template_include', function( $template ) {

  $maps = [
    'members' => [
      'archive'   => WP_CONTENT_DIR . '/uploads/template/archive-member.php',
      'single'    => WP_CONTENT_DIR . '/uploads/template/single-member.php',
    ],
    'example'  => [ // 事例用CPT
      'archive'   => WP_CONTENT_DIR . '/uploads/template/archive-example.php',
      'single'    => WP_CONTENT_DIR . '/uploads/template/single-example.php',
      'taxonomy'  => WP_CONTENT_DIR . '/uploads/template/taxonomy-example_category.php', // ←★追加！
    ],
    'news'  => [ // ニュース用
      'archive'   => WP_CONTENT_DIR . '/uploads/template/archive-news.php',
    ],
  ];

  foreach ( $maps as $slug => $files ) {

    // アーカイブページ
    if ( is_post_type_archive( $slug ) && ! empty( $files['archive'] ) && file_exists( $files['archive'] ) ) {
      return $files['archive'];
    }

    // 個別ページ
    if ( is_singular( $slug ) && ! empty( $files['single'] ) && file_exists( $files['single'] ) ) {
      return $files['single'];
    }

    // ★ カテゴリアーカイブ（taxonomy）
    if ( is_tax( "{$slug}_category" ) && ! empty( $files['taxonomy'] ) && file_exists( $files['taxonomy'] ) ) {
      return $files['taxonomy'];
    }
  }

  return $template;

}, 99 );

// 買取実績（example）のアーカイブ件数を6件に制限
add_action('pre_get_posts', function( $query ) {
  if ( ! is_admin() && $query->is_main_query() && is_post_type_archive('example') ) {
    $query->set('posts_per_page', 6);
  }
});

// 買取実績（example）のアーカイブ件数を6件に制限
add_action('pre_get_posts', function( $query ) {
  if ( ! is_admin() && $query->is_main_query() && is_post_type_archive('members') ) {
    $query->set('posts_per_page', 6);
  }
});

// 固定ページ用の買取実績テンプレートルーティング
add_filter( 'template_include', function( $template ) {
  // 固定ページの買取実績テンプレート
  if ( is_page() ) {
    global $post;
    $page_slug = $post->post_name;
    
    // 買取実績一覧ページ（固定ページのスラッグが 'purchase-achievements' の場合）
    if ( $page_slug === 'purchase-achievements' ) {
      // URLパラメーターで都道府県が指定されている場合は都道府県別テンプレート
      if ( isset($_GET['prefecture']) && !empty($_GET['prefecture']) ) {
        $prefecture_template = WP_CONTENT_DIR . '/uploads/template/page-purchase-achievements-prefecture.php';
        if ( file_exists( $prefecture_template ) ) {
          return $prefecture_template;
        }
      }
      // 通常の一覧テンプレート
      $archive_template = WP_CONTENT_DIR . '/uploads/template/page-purchase-achievements.php';
      if ( file_exists( $archive_template ) ) {
        return $archive_template;
      }
    }
    
    // 買取実績詳細ページ（固定ページのスラッグが 'purchase-achievements-detail' の場合）
    if ( $page_slug === 'purchase-achievements-detail' ) {
      $detail_template = WP_CONTENT_DIR . '/uploads/template/page-purchase-achievements-detail.php';
      if ( file_exists( $detail_template ) ) {
        return $detail_template;
      }
    }
  }
  
  return $template;
}, 100 );

// 買取実績詳細ページのパンくずリストをカスタマイズ
// 固定ページとして認識されるが、URLパラメーターからタイトルを取得してパンくずを動的に生成
add_filter('swell_breadcrumb_list_data', function($list_data) {
  // 買取実績詳細ページ（固定ページ）かどうかを判定
  if ( is_page() ) {
    global $post;
    $page_slug = $post->post_name;
    
    if ( $page_slug === 'purchase-achievements-detail' ) {
      // URLパラメーターからIDを取得
      $achievement_id = 0;
      if ( isset($_GET['id']) && !empty($_GET['id']) ) {
        $achievement_id = intval($_GET['id']);
      }
      
      if ( $achievement_id > 0 ) {
        // APIからタイトルを取得
        $api_url = getenv('MIRAI_API_URL') ?: 'http://localhost:8000';
        $api_key = getenv('MIRAI_API_KEY') ?: '';
        $api_endpoint = rtrim($api_url, '/') . '/purchase-achievements/' . $achievement_id;
        
        $response = wp_remote_get($api_endpoint, [
          'headers' => [
            'X-API-Key' => $api_key,
            'Content-Type' => 'application/json',
          ],
          'timeout' => 10,
        ]);
        
        $achievement_title = '詳細';
        if (!is_wp_error($response)) {
          $body = wp_remote_retrieve_body($response);
          $data = json_decode($body, true);
          
          if ($data && isset($data['status']) && $data['status'] === 'success' && isset($data['data'])) {
            $achievement = $data['data'];
            $achievement_title = $achievement['title'] ?? $achievement['property_name'] ?? '詳細';
          }
        }
        
        // パンくずリストを置き換え
        // 固定ページのタイトルをAPIから取得したタイトルに置き換える
        $list_data = [
          [
            'url'  => home_url('/purchase-achievements/'),
            'name' => '買取実績',
          ],
          [
            'url'  => '',
            'name' => $achievement_title,
          ],
        ];
      }
    }
  }
  
  return $list_data;
}, 999, 1);
