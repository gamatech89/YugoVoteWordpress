<?php
/**
 * AJAX handlers for the Voting System.
 *
 * This file contains functions that respond to AJAX requests from the front-end
 * for actions like submitting votes, removing votes, fetching vote data, etc.
 *
 * @package HelloElementorChild
 */

// Ensure helpers are available (e.g., for update_vote_score_cache)
require_once get_stylesheet_directory() . '/inc/voting/helpers.php';

/**
 * AJAX handler to save custom details for a voting item within a specific list.
 *
 * This is typically an admin-facing action or used when detailed item
 * customization per list is allowed. It updates or inserts data into
 * the 'voting_list_item_relations' pivot table.
 *
 * Expected $_POST parameters:
 * - '_ajax_nonce' (string) Nonce for security.
 * - 'voting_list_id' (int) ID of the voting list.
 * - 'item_id' (int) ID of the voting item.
 * - 'short_description' (string) Custom short description for the item in this list.
 * - 'long_description' (string) Custom long description for the item in this list.
 * - 'custom_image' (string) URL for a custom image for the item in this list.
 * - 'url' (string) Custom URL/link for the item in this list.
 *
 * @action wp_ajax_save_voting_item_details
 */
function save_voting_item_details() {
    check_ajax_referer('voting_list_actions_nonce', 'nonce'); // Assuming JS sends 'nonce'

    global $wpdb;

    $list_id = isset($_POST['voting_list_id']) ? intval($_POST['voting_list_id']) : 0;
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    
    if (!$list_id || !$item_id) {
        wp_send_json_error("Invalid list or item ID provided.");
        return;
    }

    $table_name = $wpdb->prefix . "voting_list_item_relations";

    // Prepare data for the pivot table. Start with keys that are always present.
    $data_to_save = [
        'voting_list_id'    => $list_id,
        'voting_item_id'    => $item_id,
        'updated_at'        => current_time('mysql', 1) // GMT time
    ];

    // Process optional override fields: save NULL if submitted empty after trimming.

    // Short Description Override
    if (isset($_POST['short_description'])) {
        $val = trim($_POST['short_description']); // Trim first
        $data_to_save['short_description'] = !empty($val) ? stripslashes(wp_kses_post($val)) : null;
    }

    // Custom Image URL Override
    $custom_image_input = null;
    if (isset($_POST['custom_image_url'])) {
        $custom_image_input = trim($_POST['custom_image_url']);
    } elseif (isset($_POST['custom_image'])) { // For compatibility if JS sent 'custom_image'
        $custom_image_input = trim($_POST['custom_image']);
    }
    if ($custom_image_input !== null) { // If the key was present in $_POST
        $val = esc_url_raw($custom_image_input);
        $data_to_save['custom_image_url'] = !empty($val) ? $val : null;
    }


    // Custom URL (e.g., video) Override
    if (isset($_POST['url'])) {
        $val = trim($_POST['url']);
        $data_to_save['url'] = !empty($val) ? esc_url_raw($val) : null;
    }

    // Custom Image Source Override (New)
    if (isset($_POST['custom_image_source'])) {
        $val = trim($_POST['custom_image_source']);
        $data_to_save['custom_image_source'] = !empty($val) ? sanitize_text_field($val) : null;
    }
    
    // Note: The `long_description` field is now removed from this process
    // as per your database migration to drop that column.

    // Use $wpdb->replace for insert/update based on unique key (voting_list_id, voting_item_id)
    $result = $wpdb->replace($table_name, $data_to_save);

    if ($result === false) {
        wp_send_json_error("Database error while saving item override details: " . $wpdb->last_error);
    } else {
        wp_send_json_success("Item override details saved successfully.");
    }
}
add_action("wp_ajax_save_voting_item_details", "save_voting_item_details");


/**
 * AJAX handler to get custom details for a voting item within a specific list.
 *
 * Fetches data from the 'voting_list_item_relations' pivot table and also
 * provides fallback/original data from the voting item's post meta.
 *
 * Expected $_POST parameters:
 * - 'voting_list_id' (int) ID of the voting list.
 * - 'item_id' (int) ID of the voting item.
 * (Nonce might be added here if deemed necessary for GET-like actions retrieving sensitive/specific data)
 *
 * @action wp_ajax_get_voting_item_details
 */
function get_voting_item_details() {

    global $wpdb;

    $list_id = isset($_POST['voting_list_id']) ? intval($_POST['voting_list_id']) : 0;
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

    if (!$list_id || !$item_id) {
        wp_send_json_error("Invalid Voting List ID or Item ID provided.");
        return;
    }

    $table_name = $wpdb->prefix . "voting_list_item_relations";
    $item_post_title = get_the_title($item_id);

    // Ensure the item actually exists if an ID is provided
    if (!$item_post_title && $item_id !== 0) { 
        wp_send_json_error("Invalid Item ID - item not found.");
        return;
    }

    // Define columns to select from the pivot table
    // Removed: long_description
    // Added: custom_image_source
    $select_columns = "short_description, custom_image_url, url, custom_image_source"; 
    
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT {$select_columns} FROM {$table_name} WHERE voting_list_id = %d AND voting_item_id = %d",
        $list_id, $item_id
    ));

    // Prepare response data
    // If $row is null (no entry in pivot table), $row->property will result in notice if not checked.
    // Using null coalescing operator (??) for cleaner fallbacks.

    $response_data = [
        'title'                        => $item_post_title,
        
        // Override values from pivot table (or null/empty if not set in pivot)
        'short_description'            => $row->short_description ?? null, 
        'custom_image_url'             => $row->custom_image_url ?? null,
        'url'                          => $row->url ?? null,
        'custom_image_source'          => $row->custom_image_source ?? null, // NEW: Get custom image source from pivot

        // Original item meta values (to be used as defaults/fallbacks by JS if pivot values are null/empty)
        'original_short_description'   => get_post_meta($item_id, '_short_description', true) ?? '',
        'original_item_url'            => get_post_meta($item_id, '_item_url', true) ?? '',
        'original_image_source_text'   => get_post_meta($item_id, '_image_source_text', true) ?? '' // The item's own main image source text
    ];

    wp_send_json_success($response_data);
}
add_action("wp_ajax_get_voting_item_details", "get_voting_item_details");

/**
 * AJAX handler for submitting a user's vote for an item on a list.
 *
 * Ensures that a specific vote value (e.g., "5 points") can only be assigned
 * to one item by a user within a given list. It also ensures a user has only
 * one active vote value per item on that list.
 *
 * Expected $_POST parameters:
 * - '_ajax_nonce' (string) Nonce for security.
 * - 'voting_list_id' (int) ID of the voting list.
 * - 'voting_item_id' (int) ID of the item being voted for.
 * - 'vote_value' (int) The point value being assigned.
 *
 * @action wp_ajax_submit_vote
 * @action wp_ajax_nopriv_submit_vote (for guest users)
 */
function submit_vote() {
    // 1. Verify the nonce
    check_ajax_referer('voting_list_actions_nonce', 'nonce'); 

    global $wpdb;
    $user_id = get_current_user_id(); // 0 if guest
    $voting_list_id = isset($_POST['voting_list_id']) ? intval($_POST['voting_list_id']) : 0;
    $voting_item_id = isset($_POST['voting_item_id']) ? intval($_POST['voting_item_id']) : 0;
    $vote_value = isset($_POST['vote_value']) ? intval($_POST['vote_value']) : 0;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!$voting_list_id || !$voting_item_id || !$vote_value) {
        wp_send_json_error("Invalid vote data provided.");
        return;
    }

    $table = $wpdb->prefix . "voting_list_votes";
    $where_user_or_ip = ($user_id > 0) ? ['user_id' => $user_id] : ['ip_address' => $ip_address];

    // Step A: Remove any previous assignment of this specific $vote_value 
    // by this user/IP on this $voting_list_id, regardless of the item it was on.
    $wpdb->delete($table, array_merge($where_user_or_ip, [
        'voting_list_id' => $voting_list_id,
        'vote_value'     => $vote_value 
    ]));

    // Step B: Remove any other vote values this user/IP might have 
    // previously assigned to the current $voting_item_id on this $voting_list_id.
    $wpdb->delete($table, array_merge($where_user_or_ip, [
        'voting_list_id' => $voting_list_id,
        'voting_item_id' => $voting_item_id 
    ]));

    // Step C: Insert the new vote
    $insert_data = array_merge($where_user_or_ip, [
        'voting_list_id' => $voting_list_id,
        'voting_item_id' => $voting_item_id,
        'vote_value'     => $vote_value,
        'created_at'     => current_time('mysql', 1) // GMT time
    ]);
    // If it's a guest, user_id will not be in $where_user_or_ip, so add explicitly if it's 0 for the column
    if ($user_id === 0) {
        $insert_data['user_id'] = null; // Or 0, depending on your DB column definition for guest (NULL is better)
    }


    $inserted = $wpdb->insert($table, $insert_data);

    if ($inserted === false) {
        wp_send_json_error("Database error: Could not record vote.");
        return;
    }
    
    // update_vote_score_cache is assumed to be in helpers.php
    if (function_exists('update_vote_score_cache')) {
        update_vote_score_cache($voting_item_id);
    }

    wp_send_json_success("Vote submitted.");
}
add_action("wp_ajax_submit_vote", "submit_vote");
add_action("wp_ajax_nopriv_submit_vote", "submit_vote");

/**
 * AJAX handler to remove a specific vote cast by a user/IP.
 *
 * Requires all identifying parts of the vote to ensure the correct one is removed.
 *
 * Expected $_POST parameters:
 * - '_ajax_nonce' (string) Nonce for security.
 * - 'voting_list_id' (int) ID of the voting list.
 * - 'voting_item_id' (int) ID of the item whose vote is to be removed.
 * - 'vote_value' (int) The specific point value of the vote to remove.
 *
 * @action wp_ajax_remove_vote
 * @action wp_ajax_nopriv_remove_vote (for guest users)
 */
function remove_vote() {
    // 1. Verify the nonce
    check_ajax_referer('voting_list_actions_nonce', 'nonce'); 

    global $wpdb;
    $user_id = get_current_user_id();
    $voting_list_id = isset($_POST['voting_list_id']) ? intval($_POST['voting_list_id']) : 0;
    $voting_item_id = isset($_POST['voting_item_id']) ? intval($_POST['voting_item_id']) : 0;
    $vote_value = isset($_POST['vote_value']) ? intval($_POST['vote_value']) : 0; // The specific vote to remove

    if (!$voting_list_id || !$voting_item_id || !$vote_value) {
        wp_send_json_error("Invalid vote data for removal.");
        return;
    }

    $table = $wpdb->prefix . "voting_list_votes";
    $where_conditions = [
        'voting_list_id' => $voting_list_id,
        'voting_item_id' => $voting_item_id,
        'vote_value'     => $vote_value
    ];

    if ($user_id > 0) {
        $where_conditions['user_id'] = $user_id;
    } else {
        $where_conditions['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }

    // Debug: Construct SQL for logging if needed (PHP 7.4+ for arrow function)
    // $sql_debug = "DELETE FROM $table WHERE " . implode(" AND ", array_map(fn($key, $val) => "$key = '" . esc_sql($val) . "'", array_keys($where_conditions), array_values($where_conditions)));
    // error_log("Remove vote SQL attempt: " . $sql_debug);
    
    $deleted = $wpdb->delete($table, $where_conditions);
    
    if ($deleted === false) {
        // This means there was a query error.
        wp_send_json_error("Database error: Could not remove vote. " . $wpdb->last_error);
    } elseif ($deleted === 0) {
        // No rows were deleted, meaning the vote didn't exist as specified.
        // This might not be an "error" from the user's perspective if they clicked twice,
        // but good to know. Could send success or a specific message.
        wp_send_json_success("Vote not found or already removed.");
    } else {
        // $deleted is number of rows affected (should be 1)
        if (function_exists('update_vote_score_cache')) {
            update_vote_score_cache($voting_item_id); 
        }
        wp_send_json_success("Vote removed.");
    }
}
add_action("wp_ajax_remove_vote", "remove_vote");
add_action("wp_ajax_nopriv_remove_vote", "remove_vote");


/**
 * AJAX handler to get all votes cast by the current user (or IP for guests)
 * on a specific voting list.
 *
 * Expected $_GET parameters:
 * - 'voting_list_id' (int) ID of the voting list.
 * (Nonce might be added here if deemed necessary)
 *
 * @action wp_ajax_get_user_votes
 * @action wp_ajax_nopriv_get_user_votes (for guest users)
 */
function get_user_votes() {
    // Optional: Nonce check if deemed necessary for this GET action
    // check_ajax_referer('voting_list_actions_nonce', '_ajax_nonce_get_votes');

    global $wpdb;
    $user_id = get_current_user_id();
    $voting_list_id = isset($_GET['voting_list_id']) ? intval($_GET['voting_list_id']) : 0;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!$voting_list_id) {
        wp_send_json_error("Invalid voting list ID provided.");
        return;
    }

    $table = $wpdb->prefix . "voting_list_votes";
    $params = [$voting_list_id];
    $sql_where_user = '';

    if ($user_id > 0) {
        $sql_where_user = " AND user_id = %d";
        $params[] = $user_id;
    } else {
        $sql_where_user = " AND ip_address = %s";
        $params[] = $ip_address;
    }

    $query = "SELECT voting_item_id, vote_value FROM {$table} WHERE voting_list_id = %d {$sql_where_user}";
    
    $votes = $wpdb->get_results($wpdb->prepare($query, ...$params));

    if ($wpdb->last_error) {
        wp_send_json_error("Database error retrieving user votes: " . $wpdb->last_error);
        return;
    }

    wp_send_json_success($votes);
}
add_action("wp_ajax_get_user_votes", "get_user_votes");
add_action("wp_ajax_nopriv_get_user_votes", "get_user_votes");

/**
 * AJAX handler to get total scores for all items in a voting list and the grand total for the list.
 *
 * Used for displaying results and potentially for client-side sorting/ranking.
 *
 * Expected $_GET parameters:
 * - 'voting_list_id' (int) ID of the voting list.
 * (Nonce might be added here if deemed necessary)
 *
 * @action wp_ajax_get_voting_list_totals
 * @action wp_ajax_nopriv_get_voting_list_totals (for guest users)
 */
function get_voting_list_totals() {
    global $wpdb;
    
    $voting_list_id = isset($_GET['voting_list_id']) ? intval($_GET['voting_list_id']) : 0;
    if (!$voting_list_id) {
        wp_send_json_error("Invalid voting list ID provided.");
        return;
    }

    $table = $wpdb->prefix . "voting_list_votes";

    // Fetch total points per item for the given list
    $items_scores = $wpdb->get_results($wpdb->prepare("
        SELECT voting_item_id, SUM(vote_value) as total_points 
        FROM {$table} 
        WHERE voting_list_id = %d 
        GROUP BY voting_item_id
    ", $voting_list_id), ARRAY_A);

    if ($wpdb->last_error) {
        wp_send_json_error("Database error fetching item scores: " . $wpdb->last_error);
        return;
    }

    // Fetch total points for the entire list (sum of all item scores for this list)
    // This could also be calculated by summing $items_scores in PHP to save a query,
    // but a direct SQL sum is also fine and perhaps more direct.
    $total_score_for_list = 0;
    if (is_array($items_scores)) {
        foreach($items_scores as $score_data) {
            $total_score_for_list += intval($score_data['total_points']);
        }
    }
    // Or, a separate query:
    // $total_score_for_list = $wpdb->get_var($wpdb->prepare("
    // SELECT SUM(vote_value) 
    // FROM {$table} 
    // WHERE voting_list_id = %d
    // ", $voting_list_id));
    // $total_score_for_list = intval($total_score_for_list);


    wp_send_json_success([
        'items' => $items_scores ?: [], // Ensure items is an array even if no scores
        'total' => $total_score_for_list 
    ]);
}
add_action("wp_ajax_get_voting_list_totals", "get_voting_list_totals");
add_action("wp_ajax_nopriv_get_voting_list_totals", "get_voting_list_totals");

/**
 * AJAX handler for Full Screen Search
 * Searches only 'voting_list' post type.
 *
 * @action wp_ajax_cs_search_voting_lists
 * @action wp_ajax_nopriv_cs_search_voting_lists
 */
function cs_search_voting_lists() {
    // Provera Nonce-a (sigurnost) - koristimo postojeći voting nonce
    // Ako tvoj JS šalje 'nonce' ključ, ovo je ok.
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'voting_list_actions_nonce')) {
        wp_send_json_error('Security check failed.');
        return;
    }

    $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';

    if (empty($term)) {
        wp_send_json_success([]); // Vrati prazan niz ako nema pojma pretrage
        return;
    }

    $args = [
        'post_type'      => 'voting_list', // Pretražujemo samo liste
        'post_status'    => 'publish',
        's'              => $term,         // Search query
        'posts_per_page' => 10,            // Limit na 10 rezultata
        'orderby'        => 'relevance',   // Najrelevantniji prvi
    ];

    $query = new WP_Query($args);
    $results = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            // Uzmi sliku (thumbnail ili default)
            $image_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
            if (!$image_url) {
                // Placeholder ako nema slike
                $image_url = get_stylesheet_directory_uri() . '/assets/images/default-search-thumb.jpg'; 
            }

            // Uzmi kategoriju (prvu) za prikaz (opciono, lepo izgleda)
            $terms = get_the_terms(get_the_ID(), 'voting_list_category');
            $category_name = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';

            $results[] = [
                'id'       => get_the_ID(),
                'title'    => get_the_title(),
                'url'      => get_permalink(),
                'image'    => $image_url,
                'category' => $category_name
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json_success($results);
}
add_action('wp_ajax_cs_search_voting_lists', 'cs_search_voting_lists');
add_action('wp_ajax_nopriv_cs_search_voting_lists', 'cs_search_voting_lists');