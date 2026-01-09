<?php

function update_vote_score_cache($item_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'voting_list_votes';

    $score = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(vote_value) FROM $table WHERE voting_item_id = %d", $item_id
    ));

    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE voting_item_id = %d", $item_id
    ));

    update_post_meta($item_id, '_vote_score_cache', $score ?: 0);
    update_post_meta($item_id, '_vote_count_cache', $count ?: 0);
}

/**
 * Get up to $limit featured (or fallback latest) voting_list posts for a category.
 *
 * If the given term is a parent category, this function will collect posts from all its child terms.
 * If the term is a child category, it will fetch only from that specific term.
 * It prioritizes posts marked with the '_is_featured' meta key, and fills in with newest posts if needed.
 *
 * @param int $term_id  The ID of the current voting_list_category term.
 * @param int $limit    The maximum number of posts to return (default: 4).
 * @return array        Array of WP_Post objects.
 */

function get_hero_posts_for_category($term_id, $limit = 4) {
    $term = get_term($term_id, 'voting_list_category');

    if (!$term) return [];

    $term_ids = [];

    if ($term->parent == 0) {
        // Parent category — get all children
        $children = get_terms([
            'taxonomy' => 'voting_list_category',
            'parent'   => $term_id,
            'hide_empty' => false
        ]);

        foreach ($children as $child) {
            $term_ids[] = $child->term_id;
        }
    } else {
        // Child category — use only itself
        $term_ids[] = $term_id;
    }

    // Try to fetch featured posts first
    $featured = get_posts([
        'post_type' => 'voting_list',
        'posts_per_page' => $limit,
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => '_is_featured',
                'value' => '1',
            ],
            [
                'key' => '_is_tournament_match',
                'compare' => 'NOT EXISTS',
            ],
        ],
        'tax_query' => [[
            'taxonomy' => 'voting_list_category',
            'field'    => 'term_id',
            'terms'    => $term_ids
        ]]
    ]);

    // If not enough featured posts, fallback to latest
    if (count($featured) < $limit) {
        $needed = $limit - count($featured);
        $exclude_ids = wp_list_pluck($featured, 'ID');

        $fallback = get_posts([
            'post_type' => 'voting_list',
            'posts_per_page' => $needed,
            'post__not_in' => $exclude_ids,
            'meta_query' => [
                [
                    'key' => '_is_tournament_match',
                    'compare' => 'NOT EXISTS',
                ],
            ],
            'tax_query' => [[
                'taxonomy' => 'voting_list_category',
                'field'    => 'term_id',
                'terms'    => $term_ids
            ]]
        ]);

        $featured = array_merge($featured, $fallback);
    }

    return $featured;
}

/**
 * Calculate total score (sum of all vote values) for a voting list item.
 *
 * @param int $list_id  ID of the voting_list post.
 * @return int           Total score across all votes.
 */
function get_total_score_for_voting_list($list_id) {
    global $wpdb;

    if (get_post_type($list_id) !== 'voting_list') return 0;

    $table = $wpdb->prefix . "voting_list_votes";

    $total_score = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(vote_value) FROM $table WHERE voting_list_id = %d",
        $list_id
    ));

    return intval($total_score);
}

/**
 * Count how many voting_list posts are assigned to a given category term.
 *
 * @param int $term_id  ID of the voting_list_category term.
 * @return int           Number of matching posts.
 */
function get_voting_list_count_by_category($term_id) {
    $query = new WP_Query([
        'post_type'      => 'voting_list',
        'posts_per_page' => 1, // optimize, we only need count
        'fields'         => 'ids',
        'tax_query' => [[
            'taxonomy' => 'voting_list_category',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ]],
    ]);
    $count = $query->found_posts;
    wp_reset_postdata();
    return $count;
}


/**
 * Get featured posts for a specific category term (if set via meta field 'is_featured' = 1).
 *
 * @param int   $term_id    The term ID to pull featured posts from.
 * @param int   $count      How many posts to return (default 1).
 * @return array            Array of WP_Post objects (can be empty).
 */
function get_featured_posts_for_term($term_id, $count = 1) {
    return get_posts([
        'post_type'      => 'voting_list',
        'posts_per_page' => $count,
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => '_is_featured',
                'value' => '1',
            ],
            [
                'key' => '_is_tournament_match',
                'compare' => 'NOT EXISTS',
            ],
        ],
        'tax_query'      => [[
            'taxonomy' => 'voting_list_category',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ]],
    ]);
}

/**
 * Get the latest posts from a term, excluding given post IDs (e.g. featured).
 */
/**
 * Get the latest posts from a term, excluding given post IDs (e.g. featured).
 *
 * @param int   $term_id      The term ID to fetch posts from.
 * @param array $exclude_ids  Array of post IDs to exclude.
 * @param int   $count        Number of posts to return (default 3).
 * @return array              Array of WP_Post objects.
 */
function get_latest_posts_for_term($term_id, $exclude_ids = [], $count = 3) {
    return get_posts([
        'post_type'      => 'voting_list',
        'posts_per_page' => $count,
        'post__not_in'   => $exclude_ids,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query' => [
            [
                'key' => '_is_tournament_match',
                'compare' => 'NOT EXISTS',
            ],
        ],
        'tax_query'      => [[
            'taxonomy' => 'voting_list_category',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ]],
    ]);
}

/**
 * Get the total sum of vote values for a specific item within a specific voting list.
 *
 * This function queries the custom 'voting_list_votes' table to sum up
 * all vote_value entries that match the given item ID and list ID.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $item_id The ID of the voting item.
 * @param int $list_id The ID of the voting list.
 * @return int The total sum of votes for the item in that list, or 0 if none found.
 */
function get_votes_for_item_in_list($item_id, $list_id) {
    global $wpdb;

    $item_id = intval($item_id);
    $list_id = intval($list_id);
    
   
    $votes_table = $wpdb->prefix . 'voting_list_votes'; 

    $total_votes = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(vote_value) FROM {$votes_table} WHERE voting_item_id = %d AND voting_list_id = %d",
        $item_id,
        $list_id
    ));

    return $total_votes ? intval($total_votes) : 0;
}
