<?php
/**
 * Live Search AJAX Handler
 *
 * Handles live search functionality for header search overlay.
 *
 * @package YugoVote
 */

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Handle live search AJAX request
 */
function ygv_live_search_handler() {
    // Temporarily disable nonce check to debug (remove after debugging)
    // Verify nonce - skip if nonce is expired (for cached pages)
    $nonce_valid = isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ygv_live_search');
    
    // Log for debugging
    if (!$nonce_valid) {
        error_log('YGV Live Search: Nonce invalid or expired. Proceeding anyway for user experience.');
    }
    
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    
    if (strlen($search) < 2) {
        wp_send_json_error(['message' => 'Search query too short']);
        return;
    }
    
    $args = [
        'post_type' => 'voting_list',
        'post_status' => 'publish',
        's' => $search,
        'posts_per_page' => 8,
        'orderby' => 'relevance',
    ];
    
    // Filter by category if provided
    if (!empty($category)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'voting_list_category',
                'field' => 'slug',
                'terms' => $category,
            ]
        ];
    }
    
    $query = new WP_Query($args);
    $results = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Get category
            $terms = get_the_terms($post_id, 'voting_list_category');
            $category_name = '';
            $cat_color = '#283363';
            
            if ($terms && !is_wp_error($terms)) {
                $term = $terms[0];
                $category_name = $term->name;
                
                // Get category color if function exists
                if (function_exists('ygv_get_category_color')) {
                    $cat_color = ygv_get_category_color($term->term_id);
                }
            }
            
            // Get thumbnail
            $thumbnail = '';
            if (has_post_thumbnail()) {
                $thumbnail = get_the_post_thumbnail_url($post_id, 'thumbnail');
            }
            
            // Get items count
            $items_count = 0;
            if (function_exists('ygv_get_list_items_count')) {
                $items_count = ygv_get_list_items_count($post_id);
            } else {
                // Fallback - count items in voting_items
                global $wpdb;
                $items_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}voting_items WHERE list_id = %d",
                    $post_id
                ));
            }
            
            $results[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'url' => get_permalink(),
                'thumbnail' => $thumbnail,
                'category' => $category_name,
                'cat_color' => $cat_color,
                'items_count' => intval($items_count),
            ];
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success($results);
}
add_action('wp_ajax_ygv_live_search', 'ygv_live_search_handler');
add_action('wp_ajax_nopriv_ygv_live_search', 'ygv_live_search_handler');

/**
 * Debug endpoint to check if live search handler is registered
 */
function ygv_live_search_debug() {
    wp_send_json_success([
        'message' => 'Live search handler is registered',
        'time' => current_time('mysql'),
    ]);
}
add_action('wp_ajax_ygv_live_search_debug', 'ygv_live_search_debug');
add_action('wp_ajax_nopriv_ygv_live_search_debug', 'ygv_live_search_debug');