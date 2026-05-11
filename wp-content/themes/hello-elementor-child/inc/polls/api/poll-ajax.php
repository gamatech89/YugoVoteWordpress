<?php
if (!defined('ABSPATH')) exit;

// Provera da li je user glasao
function cs_has_user_voted_poll($poll_id) {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $voted_polls = get_user_meta($user_id, '_cs_voted_polls', true) ?: [];
        return in_array($poll_id, $voted_polls);
    }
    return isset($_COOKIE['cs_poll_' . $poll_id]);
}

// AJAX
function cs_handle_poll_vote_ajax() {
    check_ajax_referer('cs_poll_vote_nonce', 'nonce');

    $poll_id = intval($_POST['poll_id']);
    $answer_idx = intval($_POST['answer_index']);

    if (cs_has_user_voted_poll($poll_id)) {
        wp_send_json_error(['message' => 'Već ste glasali!']);
    }

    $answers = get_post_meta($poll_id, '_cs_poll_answers', true);
    
    if (isset($answers[$answer_idx])) {
        // Povećaj glas
        $answers[$answer_idx]['votes']++;
        update_post_meta($poll_id, '_cs_poll_answers', $answers);
        
        // Update total
        $total = array_sum(array_column($answers, 'votes'));
        update_post_meta($poll_id, '_cs_poll_total_votes', $total);

        // Zabeleži Usera (Cookie 30 dana)
        setcookie('cs_poll_' . $poll_id, '1', time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN);

        // Purge LiteSpeed cache for the poll post so archive page re-renders
        do_action('litespeed_purge_post', $poll_id);
        
        if (is_user_logged_in()) {
            $u_id = get_current_user_id();
            $voted = get_user_meta($u_id, '_cs_voted_polls', true) ?: [];
            $voted[] = $poll_id;
            update_user_meta($u_id, '_cs_voted_polls', array_unique($voted));
        }

        // Pripremi rezultate za AJAX odgovor (bez reload-a)
        $results = [];
        foreach ($answers as $idx => $ans) {
            $v = intval($ans['votes']);
            $p = ($total > 0) ? round(($v / $total) * 100, 2) : 0;
            $results[] = [
                'index' => $idx,
                'text' => esc_html($ans['text']),
                'votes' => $v,
                'percent' => $p
            ];
        }

        wp_send_json_success([
            'message' => 'Glas upisan!',
            'results' => $results,
            'total' => $total
        ]);
    }

    wp_send_json_error(['message' => 'Greška u podacima.']);
}
add_action('wp_ajax_cs_vote_poll', 'cs_handle_poll_vote_ajax');
add_action('wp_ajax_nopriv_cs_vote_poll', 'cs_handle_poll_vote_ajax');

// Load more polls for infinite scroll
function cs_load_more_polls_ajax() {
    check_ajax_referer('cs_load_more_polls', 'nonce');

    $page     = max(1, intval($_POST['page'] ?? 1));
    $per_page = 9;
    // Page 1 of archive skips the latest post (offset 1); subsequent pages shift accordingly
    $offset   = 1 + ($page - 1) * $per_page;

    $query = new WP_Query([
        'post_type'      => 'voting_poll',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'offset'         => $offset,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    if (!$query->have_posts()) {
        wp_send_json_success(['html' => '', 'has_more' => false]);
    }

    $total_archive = max(0, wp_count_posts('voting_poll')->publish - 1);
    $max_pages     = (int) ceil($total_archive / $per_page);

    ob_start();
    $card_template = get_stylesheet_directory() . '/inc/polls/templates/poll-card.php';
    while ($query->have_posts()) {
        $query->the_post();
        $poll_id     = get_the_ID();
        $date        = get_the_date('d. M Y.');
        $img_url     = get_the_post_thumbnail_url($poll_id, 'medium_large');
        $total_votes = (int) get_post_meta($poll_id, '_cs_poll_total_votes', true);
        include $card_template;
    }
    wp_reset_postdata();
    $html = ob_get_clean();

    wp_send_json_success([
        'html'     => $html,
        'has_more' => $page < $max_pages,
        'page'     => $page,
    ]);
}
add_action('wp_ajax_cs_load_more_polls', 'cs_load_more_polls_ajax');
add_action('wp_ajax_nopriv_cs_load_more_polls', 'cs_load_more_polls_ajax');