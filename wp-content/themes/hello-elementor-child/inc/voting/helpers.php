<?php

function update_vote_score_cache($item_id)
{
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

function get_hero_posts_for_category($term_id, $limit = 4)
{
    $term = get_term($term_id, 'voting_list_category');

    if (!$term)
        return [];

    $term_ids = [];

    if ($term->parent == 0) {
        // Parent category — get all children
        $children = get_terms([
            'taxonomy' => 'voting_list_category',
            'parent' => $term_id,
            'hide_empty' => false
        ]);

        foreach ($children as $child) {
            $term_ids[] = $child->term_id;
        }
    }
    else {
        // Child category — use only itself
        $term_ids[] = $term_id;
    }

    // Try to fetch featured posts first
    $featured = get_posts([
        'post_type' => 'voting_list',
        'posts_per_page' => $limit,
        'meta_query' => [
            [
                'key' => '_is_featured',
                'value' => '1',
            ],
        ],
        'tax_query' => [[
                'taxonomy' => 'voting_list_category',
                'field' => 'term_id',
                'terms' => $term_ids
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
            'tax_query' => [[
                    'taxonomy' => 'voting_list_category',
                    'field' => 'term_id',
                    'terms' => $term_ids
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
function get_total_score_for_voting_list($list_id)
{
    global $wpdb;

    if (get_post_type($list_id) !== 'voting_list')
        return 0;

    // Try cached score first (stored in postmeta)
    $cached = get_post_meta($list_id, '_list_vote_score_cache', true);
    if ($cached !== '' && $cached !== false) {
        return intval($cached);
    }

    $table = $wpdb->prefix . "voting_list_votes";

    $total_score = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(vote_value) FROM $table WHERE voting_list_id = %d",
        $list_id
    ));

    $score = intval($total_score);
    update_post_meta($list_id, '_list_vote_score_cache', $score);

    return $score;
}

/**
 * Invalidate the cached score for a voting list.
 * Call this after a vote is submitted or removed.
 *
 * @param int $list_id ID of the voting list.
 */
function ygv_invalidate_list_score_cache($list_id)
{
    delete_post_meta($list_id, '_list_vote_score_cache');
}

/**
 * Batch-fetch vote scores for ALL published voting lists in a single query.
 * Returns array of [list_id => score]. Uses transient caching (5 min TTL).
 *
 * @return array
 */
function ygv_get_all_list_scores()
{
    $cached = get_transient('ygv_all_list_scores');
    if ($cached !== false) {
        return $cached;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'voting_list_votes';

    $results = $wpdb->get_results(
        "SELECT voting_list_id, SUM(vote_value) as total_score 
         FROM {$table} 
         GROUP BY voting_list_id",
        OBJECT
    );

    $scores = [];
    if ($results) {
        foreach ($results as $row) {
            $scores[(int)$row->voting_list_id] = (int)$row->total_score;
        }
    }

    set_transient('ygv_all_list_scores', $scores, 5 * MINUTE_IN_SECONDS);
    return $scores;
}

/**
 * Count how many voting_list posts are assigned to a given category term.
 *
 * @param int $term_id  ID of the voting_list_category term.
 * @return int           Number of matching posts.
 */
function get_voting_list_count_by_category($term_id)
{
    $query = new WP_Query([
        'post_type' => 'voting_list',
        'posts_per_page' => 1, // optimize, we only need count
        'fields' => 'ids',
        'tax_query' => [[
                'taxonomy' => 'voting_list_category',
                'field' => 'term_id',
                'terms' => $term_id,
            ]],
    ]);
    $count = $query->found_posts;
    wp_reset_postdata();
    return $count;
}


/**
 * Count how many OTHER published voting lists an item appears in.
 * Uses _voting_items post meta — same source as the single item page — so counts always match.
 *
 * @param int $item_id         ID of the voting item.
 * @param int $current_list_id ID of the list currently being viewed (excluded from count).
 * @return int
 */
function yuv_get_other_lists_count( $item_id, $current_list_id ) {
    $query = new WP_Query( [
        'post_type'      => 'voting_list',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'post__not_in'   => [ (int) $current_list_id ],
        'meta_query'     => [ [
            'key'     => '_voting_items',
            'value'   => '"' . (int) $item_id . '"',
            'compare' => 'LIKE',
        ] ],
    ] );
    $count = (int) $query->found_posts;
    return $count;
}

/**
 * Build an other-lists count map for multiple items in one go.
 * Returns [ item_id => count, ... ] using _voting_items meta.
 *
 * @param int[]  $item_ids        IDs of voting items to count for.
 * @param int    $current_list_id ID of the list currently being viewed (excluded).
 * @return int[]
 */
function yuv_get_other_lists_count_batch( array $item_ids, $current_list_id ) {
    if ( empty( $item_ids ) ) return [];

    global $wpdb;

    // Pull meta_value for every published voting_list except the current one
    $lists = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.ID, pm.meta_value
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_voting_items'
         WHERE p.post_type = 'voting_list'
           AND p.post_status = 'publish'
           AND p.ID != %d",
        (int) $current_list_id
    ) );

    $counts = array_fill_keys( $item_ids, 0 );

    foreach ( $lists as $list ) {
        $stored = maybe_unserialize( $list->meta_value );
        if ( ! is_array( $stored ) ) continue;
        // Cast both sides to string for comparison (WP stores IDs as strings in serialised arrays)
        $stored = array_map( 'strval', $stored );
        foreach ( $item_ids as $item_id ) {
            if ( in_array( (string) $item_id, $stored, true ) ) {
                $counts[ $item_id ]++;
            }
        }
    }

    return $counts;
}

/**
 * Get featured posts for a specific category term (if set via meta field 'is_featured' = 1).
 *
 * @param int   $term_id    The term ID to pull featured posts from.
 * @param int   $count      How many posts to return (default 1).
 * @return array            Array of WP_Post objects (can be empty).
 */
function get_featured_posts_for_term($term_id, $count = 1)
{
    return get_posts([
        'post_type' => 'voting_list',
        'posts_per_page' => $count,
        'meta_query' => [
            [
                'key' => '_is_featured',
                'value' => '1',
            ],
        ],
        'tax_query' => [[
                'taxonomy' => 'voting_list_category',
                'field' => 'term_id',
                'terms' => $term_id,
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
function get_latest_posts_for_term($term_id, $exclude_ids = [], $count = 3)
{
    return get_posts([
        'post_type' => 'voting_list',
        'posts_per_page' => $count,
        'post__not_in' => $exclude_ids,
        'orderby' => 'date',
        'order' => 'DESC',
        'tax_query' => [[
                'taxonomy' => 'voting_list_category',
                'field' => 'term_id',
                'terms' => $term_id,
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
function get_votes_for_item_in_list($item_id, $list_id)
{
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

// ========================================
// VIP LIST HELPERS
// ========================================

/**
 * Check if a voting list is a VIP list.
 *
 * @param int $list_id The voting list post ID.
 * @return bool
 */
function ygv_is_vip_list($list_id) {
    return get_post_meta($list_id, '_is_vip_list', true) === '1';
}

/**
 * Get VIP person data for a voting list.
 *
 * @param int $list_id The voting list post ID.
 * @return array|null {id, name, photo_url, subtitle} or null if not a VIP list.
 */
function ygv_get_vip_person($list_id) {
    if (!ygv_is_vip_list($list_id)) {
        return null;
    }

    $vip_id = get_post_meta($list_id, '_vip_person_id', true);
    if (!$vip_id) return null;

    $vip_post = get_post($vip_id);
    if (!$vip_post || $vip_post->post_type !== 'vip_person') return null;

    return [
        'id'        => $vip_post->ID,
        'name'      => $vip_post->post_title,
        'photo_url' => get_the_post_thumbnail_url($vip_post->ID, 'medium') ?: '',
        'subtitle'  => get_post_meta($vip_post->ID, '_vip_subtitle', true) ?: '',
        'bio'       => $vip_post->post_content,
        'permalink' => get_permalink($vip_post->ID),
    ];
}

/**
 * Get VIP person's rankings for all items in a list.
 *
 * @param int $list_id The voting list post ID.
 * @return array Associative array [item_id => rank], empty if not VIP.
 */
function ygv_get_vip_ranks($list_id) {
    if (!ygv_is_vip_list($list_id)) {
        return [];
    }

    global $wpdb;
    $table = $wpdb->prefix . 'voting_list_item_relations';

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT voting_item_id, vip_rank FROM {$table} WHERE voting_list_id = %d AND vip_rank IS NOT NULL",
        $list_id
    ), ARRAY_A);

    $ranks = [];
    foreach ($rows as $row) {
        $ranks[intval($row['voting_item_id'])] = intval($row['vip_rank']);
    }
    return $ranks;
}

/**
 * Get all voting lists curated by a VIP person.
 *
 * @param int $vip_person_id The vip_person post ID.
 * @return array Array of WP_Post objects.
 */
function ygv_get_vip_person_lists($vip_person_id) {
    return get_posts([
        'post_type'      => 'voting_list',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'   => '_vip_person_id',
                'value' => intval($vip_person_id),
            ]
        ],
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);
}

// ========================================
// VIP RANK COLUMN MIGRATION
// ========================================

/**
 * Add vip_rank column to voting_list_item_relations if it doesn't exist.
 */
function ygv_maybe_add_vip_rank_column() {
    if (get_option('ygv_vip_rank_column_added')) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'voting_list_item_relations';

    // Check if column exists
    $col = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'vip_rank'");
    if (empty($col)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN vip_rank TINYINT UNSIGNED DEFAULT NULL AFTER url");
    }

    update_option('ygv_vip_rank_column_added', 1);
}
add_action('admin_init', 'ygv_maybe_add_vip_rank_column');