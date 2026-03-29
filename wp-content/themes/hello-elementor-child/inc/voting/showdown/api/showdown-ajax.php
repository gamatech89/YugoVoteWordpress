<?php
/**
 * Showdown AJAX Handlers
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Get showdown items (for client-side pairing)
 */
add_action('wp_ajax_yuv_showdown_get_items', 'yuv_showdown_get_items_ajax');
add_action('wp_ajax_nopriv_yuv_showdown_get_items', 'yuv_showdown_get_items_ajax');

function yuv_showdown_get_items_ajax()
{
    check_ajax_referer('yuv_showdown_nonce', 'nonce');

    $showdown_id = intval($_POST['showdown_id'] ?? 0);
    if (!$showdown_id) {
        wp_send_json_error(['message' => 'Nevažeći ID']);
    }

    $manager = new YUV_Showdown_Manager();
    $items = $manager->get_items($showdown_id);

    if (empty($items)) {
        wp_send_json_error(['message' => 'Nema učesnika u ovom Showdown-u']);
    }

    $user_id = get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $has_played = $manager->has_user_played($showdown_id, $user_id, $ip);

    $response = [
        'items' => $items,
        'has_played' => $has_played,
    ];

    // If already played, include their session + leaderboard
    if ($has_played) {
        $response['session'] = $manager->get_user_session($showdown_id, $user_id, $ip);
        $response['leaderboard'] = $manager->get_leaderboard($showdown_id);
        $response['total_players'] = $manager->get_session_count($showdown_id);
    }

    wp_send_json_success($response);
}

/**
 * Save completed showdown session
 */
add_action('wp_ajax_yuv_showdown_save_session', 'yuv_showdown_save_session_ajax');
add_action('wp_ajax_nopriv_yuv_showdown_save_session', 'yuv_showdown_save_session_ajax');

function yuv_showdown_save_session_ajax()
{
    check_ajax_referer('yuv_showdown_nonce', 'nonce');

    $showdown_id = intval($_POST['showdown_id'] ?? 0);
    $results_raw = $_POST['results'] ?? '';
    $winner = sanitize_text_field($_POST['winner'] ?? '');

    if (!$showdown_id || empty($results_raw) || empty($winner)) {
        wp_send_json_error(['message' => 'Nedostaju podaci']);
    }

    // Parse results - comes as JSON string
    if (is_string($results_raw)) {
        $results = json_decode(stripslashes($results_raw), true);
    }
    else {
        $results = $results_raw;
    }

    if (!is_array($results) || empty($results)) {
        wp_send_json_error(['message' => 'Nevažeći rezultati']);
    }

    $user_id = get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $manager = new YUV_Showdown_Manager();
    $result = $manager->save_session($showdown_id, $user_id, $ip, $results, $winner);

    if (!$result['success']) {
        wp_send_json_error(['message' => $result['message']]);
    }

    // Return leaderboard after saving
    $leaderboard = $manager->get_leaderboard($showdown_id);
    $total_players = $manager->get_session_count($showdown_id);

    wp_send_json_success([
        'message' => 'Rezultati sačuvani!',
        'session_id' => $result['session_id'],
        'leaderboard' => $leaderboard,
        'total_players' => $total_players,
    ]);
}

/**
 * Get leaderboard for a showdown
 */
add_action('wp_ajax_yuv_showdown_get_leaderboard', 'yuv_showdown_get_leaderboard_ajax');
add_action('wp_ajax_nopriv_yuv_showdown_get_leaderboard', 'yuv_showdown_get_leaderboard_ajax');

function yuv_showdown_get_leaderboard_ajax()
{
    check_ajax_referer('yuv_showdown_nonce', 'nonce');

    $showdown_id = intval($_POST['showdown_id'] ?? 0);
    if (!$showdown_id) {
        wp_send_json_error(['message' => 'Nevažeći ID']);
    }

    $manager = new YUV_Showdown_Manager();
    $leaderboard = $manager->get_leaderboard($showdown_id);
    $total_players = $manager->get_session_count($showdown_id);

    wp_send_json_success([
        'leaderboard' => $leaderboard,
        'total_players' => $total_players,
    ]);
}

/**
 * Admin: Search voting items for showdown candidates
 */
add_action('wp_ajax_yuv_showdown_search_items', 'yuv_showdown_search_items_ajax');

function yuv_showdown_search_items_ajax()
{
    check_ajax_referer('yuv_showdown_search', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Nemate dozvolu']);
    }

    $query = sanitize_text_field($_POST['query'] ?? '');
    $category = intval($_POST['category'] ?? 0);

    if (strlen($query) < 2) {
        wp_send_json_error(['message' => 'Pretraga mora biti duža od 2 karaktera']);
    }

    $args = [
        'post_type' => 'voting_items',
        'post_status' => 'publish',
        'posts_per_page' => 10,
        's' => $query,
    ];

    if ($category > 0) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'voting_item_category',
                'field' => 'term_id',
                'terms' => $category,
            ]
        ];
    }

    $items_query = new WP_Query($args);
    $results = [];

    if ($items_query->have_posts()) {
        while ($items_query->have_posts()) {
            $items_query->the_post();
            $post_id = get_the_ID();

            $image_id = get_post_thumbnail_id($post_id);
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';

            $description = get_post_meta($post_id, '_short_description', true);
            if (empty($description)) {
                $description = get_the_excerpt();
            }

            $results[] = [
                'id' => $post_id,
                'name' => get_the_title(),
                'description' => wp_trim_words($description, 15),
                'image_id' => $image_id,
                'image' => $image_url,
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json_success($results);
}