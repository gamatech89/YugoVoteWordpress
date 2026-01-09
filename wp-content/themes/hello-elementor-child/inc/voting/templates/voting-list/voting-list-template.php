<?php

// Ensure this file is accessed within WordPress
if (!defined('ABSPATH')) exit;

// Get the voting list ID from the shortcode
$voting_list_id = get_query_var('voting_list_id');
if (!$voting_list_id) {
    echo "<p>No voting list ID found.</p>";
    return;
}

$list_post = get_post($voting_list_id);
if (!$list_post || $list_post->post_type !== 'voting_list') {
    echo "<p>Invalid voting list ID or not a voting list post type.</p>";
    return;
}

// Fetch Voting Scale (default 10)
$voting_scale = get_post_meta($voting_list_id, '_voting_scale', true) ?: 10;

// --- Determine Top-Level Parent Category Slug for the LIST's theme ---
$list_theme_slug = 'default'; // Initialize with a default value. Let's use a distinct name.

$list_categories = get_the_terms($voting_list_id, 'voting_list_category'); // Taxonomy for voting lists

if (!empty($list_categories) && !is_wp_error($list_categories) && isset($list_categories[0])) {
    $current_term_for_slug = $list_categories[0]; // Consider the first category of the list

    // Climb up to the top-level parent category
    while (isset($current_term_for_slug->parent) && $current_term_for_slug->parent != 0) {
        $parent_term = get_term($current_term_for_slug->parent, 'voting_list_category');
        if (is_wp_error($parent_term) || !$parent_term) {
            // Parent term not found or error, stop ascending
            break; 
        }
        $current_term_for_slug = $parent_term;
    }
    
    // After the loop, $current_term_for_slug holds the top-level term (or the original if it had no parent)
    // Ensure it's a valid term object and has a slug property
    if (is_object($current_term_for_slug) && isset($current_term_for_slug->slug)) {
        $list_theme_slug = $current_term_for_slug->slug;
    }
}
// --- End: Top-Level Parent Category Slug for the LIST ---

// Fetch selected Voting Items for the list
global $wpdb;
$table_name = $wpdb->prefix . "voting_list_item_relations"; // Pivot table for overrides

$voting_items_ids = get_post_meta($voting_list_id, '_voting_items', true);
if (!is_array($voting_items_ids)) {
    $voting_items_ids = [];
}

if (empty($voting_items_ids)) {
    echo "<p>No voting items found for this list.</p>";
    return;
}

// Query for Voting Items
$args = [
    'post_type'      => 'voting_items',
    'post_status'    => 'publish',
    'post__in'       => $voting_items_ids,
    'posts_per_page' => -1,
    'orderby'        => 'post__in'
];

$query = new WP_Query($args);




if ($query->have_posts()) :
       
    echo '<section class="cs-container">';   
    echo '<div class="cs-vote-list" data-list-id="' . esc_attr($voting_list_id) . '">';
    while ($query->have_posts()) :
        $query->the_post();
        // Fetch item details
        $item_id     = get_the_ID();
        $title       = get_the_title();
        $default_short_desc  = get_post_meta($item_id, '_short_description', true);
        $default_image       = get_the_post_thumbnail_url($item_id, 'medium') ?: get_template_directory_uri() . '/images/default.jpg';
        $categories  = get_the_terms($item_id, 'voting_item_category');
        $category    = !empty($categories) && !is_wp_error($categories) ? $categories[0]->name : 'Uncategorized';
        $ranking     = array_search($item_id, $voting_items_ids) + 1;

        // Check if this item has custom data in the pivot table
       $pivot_data = $wpdb->get_row($wpdb->prepare(
        "SELECT short_description, custom_image_url, url 
        FROM $table_name 
        WHERE voting_list_id = %d AND voting_item_id = %d",
        $voting_list_id, $item_id
    ), ARRAY_A);

        // Use pivot table data if available, otherwise fallback to default
        $short_desc = !empty($pivot_data) && !empty($pivot_data['short_description']) ? $pivot_data['short_description'] : $default_short_desc;
        $image      = !empty($pivot_data) && !empty($pivot_data['custom_image_url']) ? $pivot_data['custom_image_url'] : $default_image;
        $video_url = $pivot_data['url'] ?? get_post_meta($item_id, '_item_url', true);


        // Render voting card
        ?>


        <div class="cs-voting-card <?php if (!empty($term_slug) && $term_slug !== 'default') { echo 'cs-voting-card--' . esc_attr($term_slug); } ?>" 
             data-item-id="<?php echo esc_attr($item_id); ?>" 
             data-item-cat="<?php echo esc_attr($category); // Or use $term_slug here if more appropriate ?>">
            
            <div class="cs-voting-card__content">
                <div class="cs-voting-card__media">
                    <div class="cs-voting-card__rank"><?php echo esc_html($ranking); ?></div>
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" class="cs-voting-card__image">
                    
                    <?php 
                 
                  if (!empty($video_url) && (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false /* Add other conditions as needed */)) : ?>
                        <button class="cs-voting-card__play-button" data-video-url="<?php echo esc_url($video_url); ?>">
                            <?php echo cs_get_svg_icon('play', 'cs-icon cs-icon-play'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="cs-voting-card__info">
               
                    <div class="cs-voting-card__header">
                        <h2 class="cs-voting-card__title">
                            <a href="<?php echo esc_url(get_permalink($item_id)); ?>"><?php echo esc_html($title); ?></a>
                        </h2>
                
                        <?php if (!empty($short_desc)): ?>
                            <div class="cs-voting-card__short-description"><?php echo wp_kses_post(wpautop($short_desc)); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cs-voting-card__vote-interface"> 
                        <div class="cs-voting-card__score-display" data-item-id="<?php echo esc_attr($item_id); ?>">
                            
                            <span class="cs-voting-card__score-value">0</span>
                            <span class="cs-voting-card__score-label"> poena</span>
                        </div>
                        <div class="cs-voting-card__options">
                            <?php for ($i = 1; $i <= $voting_scale; $i++) : ?>
                                <button class="cs-voting-card__option-button" data-value="<?php echo $i; ?>"><?php echo $i; ?></button>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <?php
    endwhile;
    echo '</div>';
    echo '<section>';
    wp_reset_postdata();
else :
    echo "No voting items found.";
endif;
