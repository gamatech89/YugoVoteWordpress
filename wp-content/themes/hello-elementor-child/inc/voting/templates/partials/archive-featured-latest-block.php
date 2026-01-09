<?php
$term = $args['term'] ?? null;
$featured = $args['featured'] ?? null;
$latest = $args['latest'] ?? [];
$modifier_class = $args['modifier_class'] ?? '';


if (!$term) return;

$slug = sanitize_title($term->slug);
$link = get_term_link($term);
?>

<section class="cs-archive-featured-block  <?php echo esc_attr($modifier_class); ?>">
  <h2 class="cs-archive-featured-block__title"><?php echo esc_html($term->name); ?></h2>

  <div class="cs-archive-featured-block__grid">
    <div class="cs-archive-featured-block__main">
      <?php if ($featured): ?>
        <?php get_template_part('inc/voting/templates/partials/voting-list-card-simple', null, ['post' => $featured,'image_size' => 'large','term_slug' => $slug]); ?>
      <?php endif; ?>
    </div>

    <div class="cs-archive-featured-block__side">
      <?php foreach ($latest as $post): ?>
        <?php get_template_part('inc/voting/templates/partials/voting-list-card-simple', null, ['post' => $post,'term_slug' => $slug]); ?>
      <?php endforeach; ?>
    </div>
    
  </div>

<a href="<?php echo esc_url($link); ?>" class="cs-archive-featured-block__link cs-color--<?php echo esc_attr($slug); ?>">
  Vidi više
</a>
</section>

