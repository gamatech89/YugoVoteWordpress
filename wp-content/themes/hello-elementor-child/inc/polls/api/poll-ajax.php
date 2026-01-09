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
    check_ajax_referer('cs_poll_vote_nonce', 'nonce'); // Novi nonce specifičan za polls

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
        
        if (is_user_logged_in()) {
            $u_id = get_current_user_id();
            $voted = get_user_meta($u_id, '_cs_voted_polls', true) ?: [];
            $voted[] = $poll_id;
            update_user_meta($u_id, '_cs_voted_polls', array_unique($voted));
        }

        wp_send_json_success(['message' => 'Glas upisan!']);
    }

    wp_send_json_error(['message' => 'Greška u podacima.']);
}
add_action('wp_ajax_cs_vote_poll', 'cs_handle_poll_vote_ajax');
add_action('wp_ajax_nopriv_cs_vote_poll', 'cs_handle_poll_vote_ajax');