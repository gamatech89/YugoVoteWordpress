<?php
$big = $args['big'] ?? get_query_var('big');
$small = $args['small'] ?? get_query_var('small');
$modifier_class = $args['modifier_class'] ?? get_query_var('modifier_class') ?? '';
$term = $args['term'] ?? get_query_var('term');

if (!$big && empty($small)) return;

$term_slug = $term ? sanitize_title($term->slug) : 'default';
?>

<section class="cs-archive-featured-block <?php echo esc_attr($modifier_class); ?>">
  <div class="cs-archive-featured-block__grid">
    <div class="cs-archive-featured-block__main">
      <?php if ($big): ?>
        <?php
          set_query_var('post', $big);
          set_query_var('image_size', 'large');
          set_query_var('term_slug', $term_slug);
          set_query_var('modifier_class', 'cs-voting-card--large');
          get_template_part('inc/voting/templates/partials/voting-list-card-simple');
          
        ?>
      <?php endif; ?>
    </div>
    <div class="cs-archive-featured-block__side">
      <?php foreach ($small as $post): ?>
        <?php
          set_query_var('post', $post);
          set_query_var('term_slug', $term_slug);
          set_query_var('modifier_class', 'cs-voting-card--small');
          get_template_part('inc/voting/templates/partials/voting-list-card-simple');
        ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
