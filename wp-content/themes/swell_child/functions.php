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