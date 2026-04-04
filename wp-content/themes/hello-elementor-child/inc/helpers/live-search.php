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
 * Helper function to restrict search to title only
 */
function ygv_search_by_title_only($search, $wp_query) {
    if (!empty($search) && $wp_query->get('search_title_only')) {
        global $wpdb;
        $q = $wp_query->query_vars;
        $search_term = $q['s'];
        $search = $wpdb->prepare(" AND ({$wpdb->posts}.post_title LIKE %s)", '%' . $wpdb->esc_like($search_term) . '%');
    }
    return $search;
}

/**
 * Handle live search AJAX request
 */
function ygv_live_search_handler() {
    // Verify nonce - skip if nonce is expired (for cached pages)
    $nonce_valid = isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ygv_live_search');
    
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    
    if (strlen($search) < 2) {
        wp_send_json_error(['message' => 'Search query too short']);
        return;
    }
    
    $args = [
        'post_type' => 'voting_list',
        'post_status' => 'publish',
        's' => $search, // Provide 's' to trigger search logic
        'search_title_only' => true, // custom flag for our filter
        'posts_per_page' => 8,
        'orderby' => 'relevance',
    ];
    
    add_filter('posts_search', 'ygv_search_by_title_only', 10, 2);
    $query = new WP_Query($args);
    remove_filter('posts_search', 'ygv_search_by_title_only', 10, 2);

    $results = [];
    $seen_list_ids = []; // Track which lists we've already added
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $seen_list_ids[] = $post_id;
            
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
    
    // Also search voting_items and find lists containing them
    $items_args = [
        'post_type' => 'voting_items',
        'post_status' => 'publish',
        's' => $search,
        'search_title_only' => true,
        'posts_per_page' => 5,
        'orderby' => 'relevance',
    ];
    
    add_filter('posts_search', 'ygv_search_by_title_only', 10, 2);
    $items_query = new WP_Query($items_args);
    remove_filter('posts_search', 'ygv_search_by_title_only', 10, 2);
    
    if ($items_query->have_posts()) {
        global $wpdb;
        $relations_table = $wpdb->prefix . 'voting_list_items';
        
        while ($items_query->have_posts()) {
            $items_query->the_post();
            $item_id = get_the_ID();
            $item_title = get_the_title();
            
            // Find all voting lists that contain this item
            $list_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT voting_list_id FROM {$relations_table} WHERE voting_item_id = %d",
                $item_id
            ));
            
            if (empty($list_ids)) {
                // Fallback: check _voting_items meta
                $meta_lists = get_posts([
                    'post_type' => 'voting_list',
                    'post_status' => 'publish',
                    'posts_per_page' => 5,
                    'meta_query' => [[
                        'key' => '_voting_items',
                        'value' => serialize(strval($item_id)),
                        'compare' => 'LIKE',
                    ]],
                ]);
                $list_ids = wp_list_pluck($meta_lists, 'ID');
            }
            
            foreach ($list_ids as $list_id) {
                $list_id = intval($list_id);
                if (in_array($list_id, $seen_list_ids)) continue;
                $seen_list_ids[] = $list_id;
                
                $list_post = get_post($list_id);
                if (!$list_post || $list_post->post_status !== 'publish') continue;
                
                // Get category
                $terms = get_the_terms($list_id, 'voting_list_category');
                $category_name = '';
                $cat_color = '#283363';
                if ($terms && !is_wp_error($terms)) {
                    $category_name = $terms[0]->name;
                    if (function_exists('ygv_get_category_color')) {
                        $cat_color = ygv_get_category_color($terms[0]->term_id);
                    }
                }
                
                $thumbnail = get_the_post_thumbnail_url($list_id, 'thumbnail') ?: '';
                
                $results[] = [
                    'id' => $list_id,
                    'title' => $list_post->post_title,
                    'url' => get_permalink($list_id),
                    'thumbnail' => $thumbnail,
                    'category' => $category_name,
                    'cat_color' => $cat_color,
                    'items_count' => 0,
                    'match_info' => sprintf('Sadrži: %s', $item_title),
                ];
                
                // Limit total results
                if (count($results) >= 12) break;
            }
            
            if (count($results) >= 12) break;
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