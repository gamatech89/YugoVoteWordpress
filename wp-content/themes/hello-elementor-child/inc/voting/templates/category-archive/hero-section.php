<?php
$term_id = get_queried_object_id();
$hero_posts = get_hero_posts_for_category($term_id, 4);
//Get Category Logo
$category_color = get_term_meta($term_id, 'category_color', true); 
$category_logo_id = get_term_meta($term_id, 'category_logo', true);
$category_logo_url = $category_logo_id ? wp_get_attachment_url($category_logo_id) : '';

?>

<section class="cs-hero-archive cs-container">
    <div class="cs-hero-archive__inner">
        <div class="cs-hero-archive__logo">
            <img src="<?php echo $category_logo_url; ?>">
        </div>
        <div class="swiper cs-hero-archive__carousel">
          <div class="swiper-wrapper">
            <?php foreach ($hero_posts as $post) : setup_postdata($post); ?>
            <div class="swiper-slide cs-hero-archive__slide">
              <div class="cs-hero-archive__image">
                <?php echo get_the_post_thumbnail($post->ID, 'medium', ['class' => 'cs-hero-archive__image-img']); ?>
              </div>
              <div class="cs-hero-archive__content">
                <p class="cs-hero-archive__content-score" style="color:<?php echo esc_attr($category_color);?>">
                  <?php echo get_total_score_for_voting_list($post->ID); ?> glasova
                </p>
                <h2><?php echo esc_html(get_the_title($post)); ?></h2>
                <p class="cs-hero-archive__content-excerpt">
                  <?php echo esc_html(get_the_excerpt($post)); ?>
                </p>
           
                <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="cs-hero-archive__content-button" style="background:<?php echo esc_attr($category_color);?>">
                  Vidi Listu
                </a>
              </div>
            </div>
            <?php endforeach; wp_reset_postdata(); ?>
          </div>
        </div>

    </div>
</section>
