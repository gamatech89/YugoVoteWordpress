<?php
/**
 * Tournament AJAX Handlers
 */

if (!defined('ABSPATH')) exit;

// Manual advance tournament
add_action('wp_ajax_yuv_manual_advance_tournament', 'yuv_manual_advance_tournament_ajax');

function yuv_manual_advance_tournament_ajax() {
    check_ajax_referer('yuv_manual_advance', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Nemate dozvolu']);
    }

    $tournament_id = intval($_POST['tournament_id']);
    if (!$tournament_id) {
        wp_send_json_error(['message' => 'Nevažeći ID turnira']);
    }

    $manager = new YUV_Tournament_Manager();
    $results = $manager->advance_tournament($tournament_id);

    if (empty($results)) {
        wp_send_json_success([
            'message' => 'Nema mečeva za napredovanje (svi su ili aktivni ili već završeni)',
            'results' => []
        ]);
    }

    $messages = array_map(function($r) {
        return $r['message'] ?? 'Unknown result';
    }, $results);

    wp_send_json_success([
        'message' => 'Uspešno napredovanje! ' . count($results) . ' meč(eva) završeno.',
        'details' => implode(', ', $messages),
        'results' => $results,
    ]);
}

// Search voting items for tournament candidates
add_action('wp_ajax_yuv_search_voting_items', 'yuv_search_voting_items_ajax');

function yuv_search_voting_items_ajax() {
    check_ajax_referer('yuv_search_items', 'nonce');

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

    // Add category filter if provided
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
            
            // Get featured image
            $image_id = get_post_thumbnail_id($post_id);
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
            
            // Get description (short description meta or excerpt)
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

// Cast tournament vote
add_action('wp_ajax_yuv_cast_tournament_vote', 'yuv_cast_tournament_vote_ajax');
add_action('wp_ajax_nopriv_yuv_cast_tournament_vote', 'yuv_cast_tournament_vote_ajax');

function yuv_cast_tournament_vote_ajax() {
    // Verify nonce
    $nonce_check = check_ajax_referer('yuv_tournament_nonce', 'nonce', false);
    if (!$nonce_check) {
        wp_send_json_error(['message' => 'Sigurnosna provera nije uspela. Osvežite stranicu i pokušajte ponovo.']);
    }

    // Get user ID (0 for guests)
    $user_id = is_user_logged_in() ? get_current_user_id() : 0;
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $match_id = intval($_POST['match_id']);
    $item_id = intval($_POST['item_id']);

    if (!$match_id || !$item_id) {
        wp_send_json_error(['message' => 'Nevažeći parametri']);
    }

    // Check if match is active
    $match_completed = get_post_meta($match_id, '_yuv_match_completed', true);
    if ($match_completed == '1') {
        wp_send_json_error(['message' => 'Ovaj meč je već završen']);
    }

    // Check if match has expired
    $end_time = (int) get_post_meta($match_id, '_yuv_end_time', true);
    $current_time = current_time('timestamp');
    if ($end_time <= $current_time) {
        wp_send_json_error(['message' => 'Vreme za glasanje je isteklo']);
    }

    // Check if user/guest already voted
    global $wpdb;
    $votes_table = $wpdb->prefix . 'voting_list_votes';
    
    if ($user_id > 0) {
        // Logged-in user: check by user_id
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$votes_table} 
            WHERE voting_list_id = %d AND user_id = %d",
            $match_id,
            $user_id
        ));
    } else {
        // Guest: check by IP address
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$votes_table} 
            WHERE voting_list_id = %d AND user_id = 0 AND ip_address = %s",
            $match_id,
            $user_ip
        ));
    }

    if ($existing_vote) {
        wp_send_json_error(['message' => 'Već ste glasali u ovom meču']);
    }

    // Verify item belongs to this match
    $match_items = get_post_meta($match_id, '_voting_items', true);
    if (!in_array($item_id, (array)$match_items)) {
        wp_send_json_error(['message' => 'Nevažeći izbor']);
    }

    // Insert vote
    $inserted = $wpdb->insert(
        $votes_table,
        [
            'voting_list_id' => $match_id,
            'voting_item_id' => $item_id,
            'user_id' => $user_id,
            'ip_address' => $user_ip,
            'vote_value' => 10,
            'created_at' => current_time('mysql'),
        ],
        ['%d', '%d', '%d', '%s', '%d', '%s']
    );

    if ($inserted === false) {
        wp_send_json_error(['message' => 'Greška pri beleženju glasa']);
    }
    
    // Get updated vote counts and percentages for UI update
    $match_items = get_post_meta($match_id, '_voting_items', true);
    $results = [];
    $total_votes = 0;
    
    // First pass: get vote counts
    foreach ($match_items as $item_id_check) {
        $vote_count = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(vote_value) FROM {$votes_table} 
            WHERE voting_list_id = %d AND voting_item_id = %d",
            $match_id,
            $item_id_check
        ));
        
        $results[$item_id_check] = [
            'id' => $item_id_check,
            'votes' => (int) $vote_count,
        ];
        $total_votes += (int) $vote_count;
    }
    
    // Second pass: calculate percentages
    foreach ($results as &$result) {
        $result['percent'] = $total_votes > 0 ? round(($result['votes'] / $total_votes) * 100) : 50;
    }
    
    // Get tournament and stage info
    $tournament_id = get_post_meta($match_id, '_yuv_tournament_id', true);
    $stage = get_post_meta($match_id, '_yuv_stage', true);
    
    // Find next unvoted match and get its full data
    $next_match_data = yuv_find_next_unvoted_match($tournament_id, $stage, $user_id, $user_ip);
    
    // Calculate user's progress in this stage
    $progress = yuv_calculate_stage_progress($tournament_id, $stage, $user_id, $user_ip);
    
    $response_data = [
        'message' => 'Glas uspešno zabeležen!',
        'vote_id' => $wpdb->insert_id,
        'results' => array_values($results), // Convert to indexed array for JS
        'next_match' => $next_match_data,
        'progress' => $progress,
    ];

    wp_send_json_success($response_data);
}

/**
 * Find next unvoted match in same stage for user/IP
 * Returns full match data ready for rendering
 */
function yuv_find_next_unvoted_match($tournament_id, $stage, $user_id, $user_ip) {
    global $wpdb;
    $current_time = current_time('timestamp');
    
    if ($user_id > 0) {
        $next_match_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yuv_stage'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
            INNER JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_yuv_match_completed'
            INNER JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_yuv_end_time'
            LEFT JOIN {$wpdb->prefix}voting_list_votes v ON p.ID = v.voting_list_id AND v.user_id = %d
            WHERE p.post_type = 'voting_list'
            AND p.post_status = 'publish'
            AND pm1.meta_value = %s
            AND pm2.meta_value = %d
            AND (pm3.meta_value = '0' OR pm3.meta_value = '')
            AND pm4.meta_value > %d
            AND v.id IS NULL
            ORDER BY pm4.meta_value ASC
            LIMIT 1",
            $user_id,
            $stage,
            $tournament_id,
            $current_time
        ));
    } else {
        $next_match_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yuv_stage'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
            INNER JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_yuv_match_completed'
            INNER JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_yuv_end_time'
            LEFT JOIN {$wpdb->prefix}voting_list_votes v ON p.ID = v.voting_list_id AND v.user_id = 0 AND v.ip_address = %s
            WHERE p.post_type = 'voting_list'
            AND p.post_status = 'publish'
            AND pm1.meta_value = %s
            AND pm2.meta_value = %d
            AND (pm3.meta_value = '0' OR pm3.meta_value = '')
            AND pm4.meta_value > %d
            AND v.id IS NULL
            ORDER BY pm4.meta_value ASC
            LIMIT 1",
            $user_ip,
            $stage,
            $tournament_id,
            $current_time
        ));
    }
    
    if (!$next_match_id) {
        return null;
    }
    
    return yuv_get_match_data($next_match_id);
}

/**
 * Get full match data for rendering
 */
function yuv_get_match_data($match_id) {
    $items = get_post_meta($match_id, '_voting_items', true) ?: [];
    if (count($items) < 2) {
        return null;
    }
    
    $end_time = (int) get_post_meta($match_id, '_yuv_end_time', true);
    $stage = get_post_meta($match_id, '_yuv_stage', true);
    $match_number = get_post_meta($match_id, '_yuv_match_number', true);
    
    $contenders = [];
    foreach ($items as $item_id) {
        $item = get_post($item_id);
        if (!$item) continue;
        
        $contenders[] = [
            'id' => $item_id,
            'name' => $item->post_title,
            'description' => get_post_meta($item_id, '_short_description', true) ?: wp_trim_words($item->post_content, 20),
            'image' => get_post_meta($item_id, '_custom_image_url', true) ?: get_the_post_thumbnail_url($item_id, 'large'),
        ];
    }
    
    return [
        'match_id' => $match_id,
        'stage' => $stage,
        'match_number' => $match_number,
        'end_time' => $end_time,
        'contenders' => $contenders,
    ];
}

/**
 * Calculate user's progress in a stage
 */
function yuv_calculate_stage_progress($tournament_id, $stage, $user_id, $user_ip) {
    global $wpdb;
    
    // Total matches in this stage
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yuv_stage'
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
        WHERE p.post_type = 'voting_list'
        AND pm1.meta_value = %s
        AND pm2.meta_value = %d",
        $stage,
        $tournament_id
    ));
    
    // Matches user has voted in
    if ($user_id > 0) {
        $voted = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT v.voting_list_id)
            FROM {$wpdb->prefix}voting_list_votes v
            INNER JOIN {$wpdb->postmeta} pm1 ON v.voting_list_id = pm1.post_id AND pm1.meta_key = '_yuv_stage'
            INNER JOIN {$wpdb->postmeta} pm2 ON v.voting_list_id = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
            WHERE v.user_id = %d
            AND pm1.meta_value = %s
            AND pm2.meta_value = %d",
            $user_id,
            $stage,
            $tournament_id
        ));
    } else {
        $voted = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT v.voting_list_id)
            FROM {$wpdb->prefix}voting_list_votes v
            INNER JOIN {$wpdb->postmeta} pm1 ON v.voting_list_id = pm1.post_id AND pm1.meta_key = '_yuv_stage'
            INNER JOIN {$wpdb->postmeta} pm2 ON v.voting_list_id = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
            WHERE v.user_id = 0 
            AND v.ip_address = %s
            AND pm1.meta_value = %s
            AND pm2.meta_value = %d",
            $user_ip,
            $stage,
            $tournament_id
        ));
    }
    
    return [
        'total' => (int) $total,
        'voted' => (int) $voted,
        'remaining' => (int) ($total - $voted),
        'percent' => $total > 0 ? round(($voted / $total) * 100) : 0,
    ];
}

/**
 * Get next match data for Tinder-style progression
 */
add_action('wp_ajax_yuv_get_next_match', 'yuv_get_next_match_ajax');
add_action('wp_ajax_nopriv_yuv_get_next_match', 'yuv_get_next_match_ajax');

function yuv_get_next_match_ajax() {
    $user_id = is_user_logged_in() ? get_current_user_id() : 0;
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $tournament_id = intval($_POST['tournament_id'] ?? 0);
    $stage = sanitize_text_field($_POST['stage'] ?? '');
    
    if (!$tournament_id || !$stage) {
        wp_send_json_error(['message' => 'Nevažeći parametri']);
    }

    global $wpdb;
    
    // Find next unvoted match in the same stage
    if ($user_id > 0) {
        $next_match_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yuv_stage'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
            INNER JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_yuv_match_completed'
            INNER JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_yuv_end_time'
            LEFT JOIN {$wpdb->prefix}voting_list_votes v ON p.ID = v.voting_list_id AND v.user_id = %d
            WHERE p.post_type = 'voting_list'
            AND p.post_status = 'publish'
            AND pm1.meta_value = %s
            AND pm2.meta_value = %d
            AND (pm3.meta_value = '0' OR pm3.meta_value = '')
            AND pm4.meta_value > %d
            AND v.id IS NULL
            ORDER BY pm4.meta_value ASC
            LIMIT 1",
            $user_id,
            $stage,
            $tournament_id,
            current_time('timestamp')
        ));
    } else {
        $next_match_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yuv_stage'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
            INNER JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_yuv_match_completed'
            INNER JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_yuv_end_time'
            LEFT JOIN {$wpdb->prefix}voting_list_votes v ON p.ID = v.voting_list_id AND v.user_id = 0 AND v.ip_address = %s
            WHERE p.post_type = 'voting_list'
            AND p.post_status = 'publish'
            AND pm1.meta_value = %s
            AND pm2.meta_value = %d
            AND (pm3.meta_value = '0' OR pm3.meta_value = '')
            AND pm4.meta_value > %d
            AND v.id IS NULL
            ORDER BY pm4.meta_value ASC
            LIMIT 1",
            $user_ip,
            $stage,
            $tournament_id,
            current_time('timestamp')
        ));
    }
    
    if (!$next_match_id) {
        wp_send_json_error(['message' => 'Nema više mečeva', 'completed' => true]);
    }
    
    // Get match data
    $match_items = get_post_meta($next_match_id, '_voting_items', true) ?: [];
    if (count($match_items) < 2) {
        wp_send_json_error(['message' => 'Meč nema dovoljno takmičara']);
    }
    
    $item1_id = $match_items[0];
    $item2_id = $match_items[1];
    
    $item1 = get_post($item1_id);
    $item2 = get_post($item2_id);
    
    $item1_img = get_post_meta($item1_id, '_custom_image_url', true) ?: get_the_post_thumbnail_url($item1_id, 'large');
    $item2_img = get_post_meta($item2_id, '_custom_image_url', true) ?: get_the_post_thumbnail_url($item2_id, 'large');
    
    $item1_desc = get_post_meta($item1_id, '_short_description', true) ?: $item1->post_excerpt;
    $item2_desc = get_post_meta($item2_id, '_short_description', true) ?: $item2->post_excerpt;
    
    $end_time = (int) get_post_meta($next_match_id, '_yuv_end_time', true);
    $match_number = get_post_meta($next_match_id, '_yuv_match_number', true);
    
    wp_send_json_success([
        'match_id' => $next_match_id,
        'match_number' => $match_number,
        'end_time' => $end_time,
        'item1' => [
            'id' => $item1_id,
            'name' => $item1->post_title,
            'description' => $item1_desc,
            'image' => $item1_img,
        ],
        'item2' => [
            'id' => $item2_id,
            'name' => $item2->post_title,
            'description' => $item2_desc,
            'image' => $item2_img,
        ],
    ]);
}

/**
 * AJAX: Load tournament match HTML (for seamless navigation)
 * Returns complete arena HTML for AJAX replacement
 */
add_action('wp_ajax_yuv_load_tournament_match_html', 'yuv_load_tournament_match_html_ajax');
add_action('wp_ajax_nopriv_yuv_load_tournament_match_html', 'yuv_load_tournament_match_html_ajax');

function yuv_load_tournament_match_html_ajax() {
    global $wpdb;
    
    $user_id = get_current_user_id();
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $current_time = current_time('timestamp');
    
    // Get match_id from request (optional)
    $requested_match_id = isset($_POST['match_id']) ? (int) $_POST['match_id'] : null;
    
    // Step 1: Find active tournament
    $active_tournament = $wpdb->get_var(
        "SELECT ID FROM {$wpdb->posts} 
        WHERE post_type = 'yuv_tournament' 
        AND post_status = 'publish' 
        ORDER BY post_date DESC 
        LIMIT 1"
    );
    
    if (!$active_tournament) {
        wp_send_json_error(['message' => 'Nema aktivnih turnira']);
    }
    
    $tournament_id = $active_tournament;
    $tournament_title = get_the_title($tournament_id);
    $bracket_lists = get_post_meta($tournament_id, '_yuv_bracket_lists', true);
    
    if (empty($bracket_lists)) {
        wp_send_json_error(['message' => 'Bracket nije kreiran']);
    }
    
    // Step 2: Get ALL active matches
    $all_match_ids = [];
    foreach ($bracket_lists as $stage => $match_ids) {
        $all_match_ids = array_merge($all_match_ids, $match_ids);
    }
    
    // Filter to only published, non-expired, non-completed matches
    $active_matches = [];
    foreach ($all_match_ids as $match_id) {
        $post_status = get_post_status($match_id);
        $end_time = (int) get_post_meta($match_id, '_yuv_end_time', true);
        $completed = get_post_meta($match_id, '_yuv_match_completed', true);
        
        if ($post_status === 'publish' && $end_time > $current_time && !$completed) {
            $active_matches[] = $match_id;
        }
    }
    
    if (empty($active_matches)) {
        wp_send_json_error(['message' => 'Nema aktivnih mečeva', 'stage_complete' => true]);
    }
    
    // Step 3: Determine which match to load
    $target_match_id = null;
    
    if ($requested_match_id && in_array($requested_match_id, $active_matches, true)) {
        // Use requested match if valid
        $target_match_id = $requested_match_id;
    } else {
        // Find first unvoted match
        foreach ($active_matches as $match_id) {
            if ($user_id > 0) {
                $user_vote = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}voting_list_votes 
                    WHERE voting_list_id = %d AND user_id = %d",
                    $match_id,
                    $user_id
                ));
            } else {
                $user_vote = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}voting_list_votes 
                    WHERE voting_list_id = %d AND user_id = 0 AND ip_address = %s",
                    $match_id,
                    $user_ip
                ));
            }
            
            if (!$user_vote) {
                $target_match_id = $match_id;
                break;
            }
        }
        
        // If all voted, show first match with results
        if (!$target_match_id) {
            $target_match_id = $active_matches[0];
        }
    }
    
    // Step 4: Render arena HTML
    // Include the shortcode file to access yuv_render_arena_html
    require_once get_stylesheet_directory() . '/inc/voting/tournament/shortcodes/bracket-shortcode.php';
    
    $html = yuv_render_arena_html($target_match_id, $tournament_id, $tournament_title, $active_matches, $user_id, $user_ip);
    
    wp_send_json_success([
        'html' => $html,
        'match_id' => $target_match_id
    ]);
}
