<?php
/**
 * Taxonomy archive (example_category)
 * /wp-content/themes/uploads/custom_template/taxonomy-example_category.php
 */
get_header();

$tax_slug = 'example_category';
$term     = get_queried_object();
$term_id  = $term->term_id;

// ACFでタームに画像を持たせている場合（返り値=ID/配列どちらでも）
$term_kv  = function_exists('get_field') ? get_field('term_kv', $tax_slug . '_' . $term_id) : null;
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
      <h1 class="p-archive__title"><?php echo esc_html( single_term_title('', false) ); ?></h1>
      <?php if ($term_kv): ?>
        <figure style="margin:12px auto 16px;max-width:960px;">
          <?php
            if (is_array($term_kv) && !empty($term_kv['ID'])) {
              echo wp_get_attachment_image($term_kv['ID'],'large');
            } elseif (is_numeric($term_kv)) {
              echo wp_get_attachment_image($term_kv,'large');
            }
          ?>
        </figure>
      <?php endif; ?>
      <?php if (term_description()): ?>
        <div class="p-archive__desc"><?php echo wp_kses_post( term_description() ); ?></div>
      <?php endif; ?>
    </header>

    <?php if ( have_posts() ) : ?>
      <div class="grid">
        <?php while ( have_posts() ) : the_post(); ?>
          <article <?php post_class('tx-card'); ?>>
            <a href="<?php the_permalink(); ?>" class="tx-thumb">
              <?php
              // ACFの物件画像を優先（なければアイキャッチ→代替）
              $img_html = '';
              if ( function_exists('get_field') ) {
                $acf_img = get_field('property_image');
                if ($acf_img) {
                  if (is_array($acf_img) && !empty($acf_img['ID']))      $img_html = wp_get_attachment_image($acf_img['ID'],'medium_large',false);
                  elseif (is_numeric($acf_img))                           $img_html = wp_get_attachment_image($acf_img,'medium_large',false);
                  elseif (is_string($acf_img))                            $img_html = '<img src="'.esc_url($acf_img).'" alt="">';
                }
              }
              if (!$img_html) $img_html = has_post_thumbnail() ? get_the_post_thumbnail(get_the_ID(),'medium_large') : '<img src="'.esc_url(get_stylesheet_directory_uri().'/assets/img/noimg.png').'" alt="">';
              echo $img_html;
              ?>
            </a>
            <div class="tx-body">
              <div class="tx-cats">
                <?php
                $terms = get_the_terms(get_the_ID(), $tax_slug);
                if ($terms && !is_wp_error($terms)) {
                  $links = array_map(fn($t)=>'<a href="'.esc_url(get_term_link($t)).'">'.esc_html($t->name).'</a>', $terms);
                  echo implode('、', $links);
                }
                ?>
              </div>
              <h2 class="tx-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
              <div class="p-card__excerpt">
                <?php
                  $summary = function_exists('get_field') ? get_field('summary') : '';
                  echo esc_html( wp_trim_words( $summary ?: ( get_the_excerpt() ?: wp_strip_all_tags(get_the_content()) ), 36, '…' ) );
                ?>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      </div>

      <div class="c-pagination" style="margin-top:40px;text-align:center;">
        <?php the_posts_pagination(['mid_size'=>1,'prev_text'=>'← 前へ','next_text'=>'次へ →']); ?>
      </div>
    <?php else : ?>
      <p>このカテゴリーにはまだ投稿がありません。</p>
    <?php endif; ?>
  </main>
</div>

<?php get_footer(); ?>