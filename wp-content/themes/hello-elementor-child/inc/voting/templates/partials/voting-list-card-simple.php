<?php
$post = $args['post'] ?? get_query_var('post');
if (!$post) {
  return;
}

setup_postdata($post);

$term_slug = $args['term_slug'] ?? get_query_var('term_slug') ?? 'default';
$score = get_total_score_for_voting_list($post->ID);
$image_size = $args['image_size'] ?? get_query_var('image_size') ?? 'medium';
$modifier_class = $args['modifier_class'] ?? get_query_var('modifier_class') ?? '';
?>

<article class="cs-voting-card cs-border--<?php echo esc_attr($term_slug); ?>">
  <a href="<?php echo esc_url(get_permalink($post)); ?>" class="cs-voting-card__inner">
    <div class="cs-voting-card__thumb">
      <?php echo get_the_post_thumbnail($post->ID, $image_size); ?>
    </div>
    <div class="cs-voting-card__info">
   
      <h3 class="cs-voting-card__title">
        <?php echo esc_html(get_the_title($post)); ?>
      </h3>
        <p class="cs-voting-card__score cs-color--<?php echo esc_attr($term_slug); ?>">
        +<?php echo intval($score); ?> glasova
      </p>
    </div>
  </a>
</article>

<?php wp_reset_postdata(); ?>