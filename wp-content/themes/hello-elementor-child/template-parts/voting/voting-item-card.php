// <?php

// /**
//  * Template to render all voting items for a given voting list (used in [voting_list] shortcode).
//  * Pulls voting list ID from query var, retrieves selected items from pivot table,
//  * then delegates rendering to `template-parts/voting/item-card.php`.
//  */

// // Ensure this file is accessed within WordPress
// if (!defined('ABSPATH')) exit;

// // Get the voting list ID from the shortcode
// $voting_list_id = get_query_var('voting_list_id');
// if (!$voting_list_id) {
//     echo "No voting list found.";
//     return;
// }

// // Fetch Voting Scale (default 10)
// $voting_scale = get_post_meta($voting_list_id, '_voting_scale', true) ?: 10;

// // Fetch selected Voting Items for the list
// global $wpdb;
// $table_name = $wpdb->prefix . "voting_list_item_relations"; // Pivot table

// $voting_items = get_post_meta($voting_list_id, '_voting_items', true);
// if (!is_array($voting_items)) {
//     $voting_items = maybe_unserialize($voting_items);
// }

// if (empty($voting_items)) {
//     echo "No voting items found.";
//     return;
// }

// // Query for Voting Items
// $args = [
//     'post_type'      => 'voting_items',
//     'post_status'    => 'publish',
//     'post__in'       => $voting_items,
//     'posts_per_page' => -1,
//     'orderby'        => 'post__in'
// ];

// $query = new WP_Query($args);

// if ($query->have_posts()) :
//     echo '<div class="cs-vote-list" data-list-id="' . esc_attr($voting_list_id) . '">';
//     while ($query->have_posts()) :
//         $query->the_post();

//         $item_id     = get_the_ID();
//         $title       = get_the_title();
//         $permalink   = get_permalink($item_id);
//         $default_short_desc = get_post_meta($item_id, '_short_description', true);
//         $default_long_desc  = get_post_meta($item_id, '_long_description', true);
//         $default_image      = get_the_post_thumbnail_url($item_id, 'medium') ?: get_template_directory_uri() . '/images/default.jpg';
//         $categories  = get_the_terms($item_id, 'voting_item_category');
//         $category    = (!empty($categories) && !is_wp_error($categories)) ? $categories[0]->name : 'Uncategorized';
//         $ranking     = array_search($item_id, $voting_items) + 1;

//         // Get pivot data
//         $pivot_data = $wpdb->get_row($wpdb->prepare(
//             "SELECT short_description, long_description, custom_image_url, url FROM $table_name WHERE voting_list_id = %d AND voting_item_id = %d",
//             $voting_list_id, $item_id
//         ), ARRAY_A);

//         $short_desc = !empty($pivot_data['short_description']) ? $pivot_data['short_description'] : $default_short_desc;
//         $long_desc  = !empty($pivot_data['long_description']) ? $pivot_data['long_description'] : $default_long_desc;
//         $image      = !empty($pivot_data['custom_image_url']) ? $pivot_data['custom_image_url'] : $default_image;
//         $video_url  = !empty($pivot_data['url']) ? $pivot_data['url'] : get_post_meta($item_id, '_item_url', true);

//         get_template_part('template-parts/voting/item-card', null, [
//             'item_id'     => $item_id,
//             'title'       => $title,
//             'permalink'   => $permalink,
//             'short_desc'  => $short_desc,
//             'long_desc'   => $long_desc,
//             'image'       => $image,
//             'video_url'   => $video_url,
//             'category'    => $category,
//             'ranking'     => $ranking,
//             'voting_scale'=> $voting_scale
//         ]);

//     endwhile;
//     echo '</div>';
//     wp_reset_postdata();
// else :
//     echo "No voting items found.";
// endif;
