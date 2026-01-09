
<section class="cs-archive-list-section cs-container">
  <?php
  $current_term = get_queried_object();

  if ($current_term->parent === 0) {
      $children = get_terms([
          'taxonomy'   => 'voting_list_category',
          'parent'     => $current_term->term_id,
          'hide_empty' => false,
      ]);

      $index = 0;
      foreach ($children as $child_term) {
          $featured = get_featured_posts_for_term($child_term->term_id, 1);
          $featured_post = $featured[0] ?? null;

          $exclude = $featured_post ? [$featured_post->ID] : [];
          $latest = get_latest_posts_for_term($child_term->term_id, $exclude, 4);

          // Skip if no content
          if (!$featured_post && count($latest) < 2) continue;

          $reverse_class = ($index % 2 === 1) ? 'is-reversed' : '';

          get_template_part('inc/voting/templates/partials/archive-featured-latest-block', null, [
              'term'            => $child_term,
              'featured'        => $featured_post,
              'latest'          => $latest,
              'modifier_class'  => $reverse_class,
          ]);

          $index++;
      }
  }
  ?>
</section>
