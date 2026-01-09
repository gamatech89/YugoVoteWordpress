<?php
$current_term = get_queried_object();

if (!$current_term || $current_term->parent === 0) return;

$term_id = $current_term->term_id;
$term_slug = sanitize_title($current_term->slug);

$posts = get_posts([
  'post_type' => 'voting_list',
  'posts_per_page' => -1,
  'orderby' => 'date',
  'order' => 'DESC',
  'meta_query' => [
    [
      'key' => '_is_tournament_match',
      'compare' => 'NOT EXISTS',
    ],
  ],
  'tax_query' => [[
    'taxonomy' => 'voting_list_category',
    'field' => 'term_id',
    'terms' => $term_id,
  ]],
]);

if (empty($posts)) return;

$chunks = array_chunk($posts, 5);
$index = 0;

echo '<div class="cs-container">';

foreach ($chunks as $chunk) {
  $big = $chunk[0] ?? null;
  $small = array_slice($chunk, 1);
  $modifier = ($index % 2 === 1) ? 'is-reversed' : '';

  get_template_part('inc/voting/templates/partials/archive-block-alternate', null, [
    'big' => $big,
    'small' => $small,
    'modifier_class' => $modifier,
    'term' => $current_term,
  ]);

  $index++;
}

echo '</div>';