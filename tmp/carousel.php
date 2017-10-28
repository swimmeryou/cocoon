<?php if (is_carousel_visible()): ?>
<?php //カルーセルに関連付けられた投稿の取得
$args = array(
  'cat' => array(1),
);
$query = new WP_Query( $args );
// var_dump($query -> have_posts());
// var_dump($query);
  if( $query -> have_posts() ): //カルーセルが設定されているとき
?>
<div id="carousel" class="carousel">
  <div id="carousel-in" class="carousel-in wrap">
    <div class="carousel-content">
      <?php while ($query -> have_posts()) : $query -> the_post(); ?>
        <?php //カルーセルカードの取得
        get_template_part('tmp/carousel-entry-card'); ?>
      <?php endwhile;?>
    </div>
  </div>
</div>
<?php
endif;
wp_reset_postdata();
?>
<?php endif ?>
