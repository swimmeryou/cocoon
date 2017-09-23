<?php //SEO関係の関数

//Wordpress4.1からのタイトル自動作成
//https://www.nxworld.net/wordpress/wp-custom-title-tag.html
add_action( 'after_setup_theme', 'setup_theme_custum' );
if ( !function_exists( 'setup_theme_custum' ) ):
function setup_theme_custum() {
  add_theme_support( 'title-tag' );
}
endif;

//Wordpress4.4以上でのタイトルセパレーターの設定
add_filter( 'document_title_separator', 'title_separator_custom' );
if ( !function_exists( 'title_separator_custom' ) ):
function title_separator_custom( $sep ){
    $sep = get_title_separator_caption();
    return $sep;
}
endif;


//Wordpress4.4以上でのタイトルカスタマイズ
add_filter( 'document_title_parts', 'title_parts_custom' );
if ( !function_exists( 'title_parts_custom' ) ):
function title_parts_custom( $title ){
  $site_name = trim( get_bloginfo('name') );
  $title['tagline'] = '';

  if(is_front_page()): //フロントページ
    $title['title'] = $site_name;
    $title['site'] = '';

    if ( is_tagline_to_front_page_title() )://キャッチフレーズを追加する場合
      $title['tagline'] = trim( get_bloginfo('description') );
    endif;
  elseif(is_singular()): //投稿・固定ページ
    $title['title'] = trim( get_the_title() );
    //SEO向けのタイトルが設定されているとき
    // if (get_seo_title_singular_page()) {
    //   $title['title'] = get_seo_title_singular_page();
    // }
    $title['site'] = '';
    switch (get_singular_page_title_format()) {
      case 'pagetitle_sitename':
        $title['site'] = $site_name;
        break;
      case 'sitename_pagetitle':
        $title['title'] = $site_name;
        $title['site'] = trim( get_the_title() );
        break;
    }
    // if ( is_site_name_to_singular_title() )://サイト名を追加する場合
    //   $title['site'] = $site_name;
    // endif;
  elseif (is_category()):
    $cat_name = $title['title'];
    $title['site'] = '';
    switch (get_category_page_title_format()) {
      case 'category_sitename':
        $title['site'] = $site_name;
        break;
      case 'sitename_category':
        $title['title'] = $site_name;
        $title['site'] = $cat_name;
        break;
    }

  endif;

  return $title;
}
endif;


//noindexページの判別関数
if ( !function_exists( 'is_noindex_page' ) ):
function is_noindex_page(){
  return (is_archive() && !is_category()) || //アーカイブページはインデックスに含めない
  is_tag() || //タグページをインデックスしたい場合はこの行を削除
  ( is_paged() && is_paged_category_page_noindex() )  || //ページの2ページ目以降はインデックスに含めない（似たような内容の薄いコンテンツの除外）
  is_search() || //検索結果ページはインデックスに含めない
  is_404() || //404ページはインデックスに含めない
  is_attachment(); //添付ファイルページも含めない
}
endif;


//noindexページを出力する
add_action( 'wp_head', 'the_noindex_follow_tag' );
if ( !function_exists( 'the_noindex_follow_tag' ) ):
function the_noindex_follow_tag(){
  $tag = null;
  if (is_noindex_page()) {
    $tag .= '<meta name="robots" content="noindex,follow">'.PHP_EOL;
  } elseif (is_singular()) {
    if ( is_singular_page_noindex() && is_singular_page_nofollow()) {
      $tag = '<meta name="robots" content="noindex,nofollow">'.PHP_EOL;
    } elseif ( is_singular_page_noindex() ) {
      $tag = '<meta name="robots" content="noindex">'.PHP_EOL;
    } elseif ( is_singular_page_nofollow() ) {
      $tag = '<meta name="robots" content="nofollow">'.PHP_EOL;
    }
  }
  if ($tag) {
    //var_dump($tag);
    $tag = '<!-- '.THEME_NAME_CAMEL.' noindex nofollow -->'.PHP_EOL.$tag;
    echo $tag;
  }
}
endif;

////ページネーションと分割ページ（マルチページ）タグを出力
if ( is_prev_next_enable() ) {
  //デフォルトのrel="next"/"prev"を消す
  remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
  //分割ページのみnext/prevを表示
  add_action( 'wp_head', 'the_prev_next_link_tag' );
}
if ( !function_exists( 'the_prev_next_link_tag' ) ):
function the_prev_next_link_tag() {
  //1ページを複数に分けた分割ページ
  if(is_single() || is_page()) {
    global $wp_query;
    $multipage = get_the_post_has_multi_page();
    if($multipage[0] > 1) {
      $prev = generate_multipage_url('prev');
      $next = generate_multipage_url('next');
      if($prev) {
        echo '<!-- '.THEME_NAME_CAMEL.' next -->'.PHP_EOL;
        echo '<link rel="prev" href="'.$prev.'" />'.PHP_EOL;
      }
      if($next) {
        echo '<!-- '.THEME_NAME_CAMEL.' next -->'.PHP_EOL;
        echo '<link rel="next" href="'.$next.'" />'.PHP_EOL;
      }
    }
  } else{
    //トップページやカテゴリページなどの分割ページの設定
    global $paged;
    if ( get_previous_posts_link() ){
      echo '<!-- '.THEME_NAME_CAMEL.' prev -->'.PHP_EOL;
      echo '<link rel="prev" href="'.get_pagenum_link( $paged - 1 ).'" />'.PHP_EOL;
    }
    if ( get_next_posts_link() ){
      echo '<!-- '.THEME_NAME_CAMEL.' next -->'.PHP_EOL;
      echo '<link rel="next" href="'.get_pagenum_link( $paged + 1 ).'" />'.PHP_EOL;
    }
  }
}
endif;

//分割ページ（マルチページ）URLの取得
//参考ページ：
//http://seophp.net/wordpress-fix-rel-prev-and-rel-next-without-plugin/
if ( !function_exists( 'generate_multipage_url' ) ):
function generate_multipage_url($rel='prev') {
  global $post;
  $url = '';
  $multipage = get_the_post_has_multi_page();
  if($multipage[0] > 1) {
    $numpages = $multipage[0];
    $page = $multipage[1] == 0 ? 1 : $multipage[1];
    $i = 'prev' == $rel? $page - 1: $page + 1;
    if($i && $i > 0 && $i <= $numpages) {
      if(1 == $i) {
        $url = get_permalink();
      } else {
        if ('' == get_option('permalink_structure') || in_array($post->post_status, array('draft', 'pending'))) {
          $url = add_query_arg('page', $i, get_permalink());
        } else {
          $url = trailingslashit(get_permalink()).user_trailingslashit($i, 'single_paged');
        }
      }
    }
  }
  return $url;
}
endif;


//分割ページ（マルチページ）かチェックする
if ( !function_exists( 'get_the_post_has_multi_page' ) ):
function get_the_post_has_multi_page() {
  $num_pages    = substr_count(
      $GLOBALS['post']->post_content,
      '<!--nextpage-->'
  ) + 1;
  $current_page = get_query_var( 'page' );
  return array ( $num_pages, $current_page );
}
endif;


//canonical URLの生成
if ( !function_exists( 'generate_canonical_url' ) ):
function generate_canonical_url(){
  global $paged;
  global $page;

  //canonicalの疑問点
  //アーカイブはnoindexにしているけどcanonicalタグは必要か？
  //タグページはnoindexにしているけどcanonicalタグは必要か？
  //404ページはAll in One SEO Packはcanonicalタグを出力していないようだけど必要か？
  $canonical_url = null;
  if (is_home()) {
    $canonical_url = home_url();
  } elseif (is_category()) {
    $canonical_url = get_category_link(get_query_var('cat'));
  } elseif (is_tag()) {
    $postTag = get_the_tags();
    $canonical_url = get_tag_link( $postTag[0]->term_id );
  } elseif (is_page() || is_single()) {
    $canonical_url = get_permalink();
  } elseif(is_404()) {
    $canonical_url =  home_url()."/404";
  }

  if ($canonical_url && ( $paged >= 2 || $page >= 2)) {
    $canonical_url = home_url().'/page/'.max( $paged, $page ).'';
  }

  return $canonical_url;
}
endif;

//canonicalタグの取得
//取得条件；http://bazubu.com/seo101/how-to-use-canonical
if (is_canonical_tag_enable()) {
  //デフォルトのcanonicalタグ削除
  remove_action('wp_head', 'rel_canonical');
  //分割ページのみnext/prevを表示
  add_action( 'wp_head', 'the_canonical_tag' );
}
if ( !function_exists( 'the_canonical_tag' ) ):
function the_canonical_tag(){
  $canonical_url = generate_canonical_url();
  var_dump($canonical_url);
  if ( $canonical_url ) {
    echo '<!-- '.THEME_NAME_CAMEL.' canonical -->'.PHP_EOL;
    echo '<link rel="canonical" href="'.$canonical_url.'">'.PHP_EOL;
  }
}
endif;


//カテゴリーメタディスクリプション用の説明文を取得
if ( !function_exists( 'get_category_meta_description' ) ):
function get_category_meta_description($category = null){
  $cat_desc = trim( strip_tags( category_description() ) );
  if ( $cat_desc ) {//カテゴリ設定に説明がある場合はそれを返す
    return htmlspecialchars($cat_desc);
  }
  if ($category) {
    $cat_name = $category->name;
  } else {
    $cat_name = single_cat_title('', false);
  }

  $cat_desc = sprintf( __( '「%s」の記事一覧です。', THEME_NAME ), $cat_name );
  return htmlspecialchars($cat_desc);
}
endif;


//カテゴリーメタディスクリプション用の説明文を取得
if ( !function_exists( 'get_category_meta_keywords' ) ):
function get_category_meta_keywords(){
  return single_cat_title('', false);
}
endif;


//投稿・固定ページのメタキーワードの取得
if ( !function_exists( 'get_singular_meta_keywores' ) ):
function get_singular_meta_keywores(){
  global $post;
  $keywords = '';//get_meta_keywords_singular_page();
  if (!$keywords) {
    $categories = get_the_category($post->ID);
    $category_names = array();
    foreach($categories as $category):
      array_push( $category_names, $category -> cat_name);
    endforeach ;
    $keywords = implode($category_names, ',');
  }
  return $keywords;
}
endif;

//メタディスクリプションタグを出力する
add_action( 'wp_head', 'the_meta_description_tag' );
if ( !function_exists( 'the_meta_description_tag' ) ):
function the_meta_description_tag() {
  $description = null;
  if (is_front_page() && get_front_page_meta_description()) {
    $description = get_front_page_meta_description();
  } elseif (is_singular() && is_meta_description_to_singular()) {
    $description = get_singular_page_meta_description();
  } elseif (is_category() && is_meta_description_to_category()) {
    $description = get_category_meta_description();
  } else {

  }
  if ($description) {
    echo '<!-- '.THEME_NAME_CAMEL.' meta description -->'.PHP_EOL;
    var_dump('<meta name="description" content="'.$description.'">');
    echo '<meta name="description" content="'.$description.'">'.PHP_EOL;
  }
}
endif;


//メタキーワードタグを出力する
add_action( 'wp_head', 'the_meta_keywords_tag' );
if ( !function_exists( 'the_meta_keywords_tag' ) ):
function the_meta_keywords_tag() {
  $keywords = null;
  if (is_front_page() && get_front_page_meta_keywords()) {
    $keywords = get_front_page_meta_keywords();
  } elseif (is_singular() && is_meta_keywords_to_singular()) {
    $keywords = get_the_description();
  } elseif (is_category() && is_meta_keywords_to_category()) {
    $keywords = get_category_meta_keywords();
  } else {

  }
  if ($keywords) {
    echo '<!-- '.THEME_NAME_CAMEL.' meta keywords -->'.PHP_EOL;
    var_dump('<meta name="keywords" content="'.$keywords.'">');
    echo '<meta name="keywords" content="'.$keywords.'">'.PHP_EOL;
  }
}
endif;

//json-ldタグを出力する
add_action( 'wp_head', 'the_json_ld_tag' );
if ( !function_exists( 'the_json_ld_tag' ) ):
function the_json_ld_tag() {
  if (is_singular()) {
    echo '<!-- '.THEME_NAME_CAMEL.' JSON-LD -->'.PHP_EOL;
    get_template_part('tmp/json-ld');
  }
}
endif;


//サイト概要の取得
if ( !function_exists( 'get_the_description' ) ):
function get_the_description(){
  global $post;

  //抜粋を取得
  $desc = trim(strip_tags( $post->post_excerpt ));
  //投稿・固定ページにメタディスクリプションが設定してあれば取得
  if (get_singular_page_meta_description()) {
    $desc = get_singular_page_meta_description();
  }
  if ( !$desc ) {//投稿で抜粋が設定されていない場合は、110文字の冒頭の抽出分
    $desc = strip_shortcodes(get_the_custom_excerpt( $post->post_content, 150 ));
    $desc = mb_substr(str_replace(array("\r\n", "\r", "\n"), '', strip_tags($desc)), 0, 120);

  }
  $desc = htmlspecialchars($desc);
  return $desc;
}
endif;


//本文抜粋を取得する関数
//使用方法：http://nelog.jp/get_the_custom_excerpt
if ( !function_exists( 'get_the_custom_excerpt' ) ):
function get_the_custom_excerpt($content, $length = 70, $is_card = false) {
  global $post;
  //SEO設定のディスクリプション取得
  $description = get_blogcard_snippet_meta_description($post->ID);
  //SEO設定のディスクリプションがない場合は「All in One SEO Packの値」を取得
  if (!$description) {
    if (class_exists( 'All_in_One_SEO_Pack' )) {
      $aioseop_description = get_post_meta($post->ID, '_aioseop_description', true);
      if ($aioseop_description) {
        $description = $aioseop_description;
      }
    }
  }
  //SEO設定のディスクリプションがない場合は「抜粋」を取得
  return htmlspecialchars(get_content_excerpt($content, $length));
}
endif;
