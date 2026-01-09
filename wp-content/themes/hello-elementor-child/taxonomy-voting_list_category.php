<?php
/**
 * Template for Voting List Categories (Parent and Subcategories)
 * This handles taxonomy "voting_list_category"
 */
get_header();

$current_term = get_queried_object();
?>

<div class="cs-voting-category-page">

  <!-- 🔹 HERO SECTION -->
  <?php get_template_part('inc/voting/templates/category-archive/hero-section'); ?>

  <?php if ($current_term->parent === 0): ?>
    <!-- 🔹 SUBCATEGORY CAROUSEL SECTION -->
    <?php get_template_part('inc/voting/templates/category-archive/subcategories-section'); ?>
    <!-- 🔹 FEATURED + LATEST PER SUBCATEGORY -->
    <?php get_template_part('inc/voting/templates/category-archive/archive-list-section'); ?>
  <?php else: ?>
    <!-- 🔹 CHILD CATEGORY POST LIST -->
    <?php get_template_part('inc/voting/templates/category-archive/archive-child-section'); ?>
  <?php endif; ?>

</div>

<?php get_footer(); ?>
