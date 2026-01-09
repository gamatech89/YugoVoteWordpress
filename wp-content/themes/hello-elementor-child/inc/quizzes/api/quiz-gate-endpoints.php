<?php
if (!defined('ABSPATH')) exit;

/** Read an int from request body or params */
function ygv_req_int(WP_REST_Request $r, string $key, int $default = 0): int {
    $v = $r->get_param($key);
    if ($v === null) {
        $body = json_decode($r->get_body() ?: '[]', true);
        if (is_array($body) && array_key_exists($key, $body)) $v = $body[$key];
    }
    return (int)$v ?: $default;
}

add_action('rest_api_init', function () {
    // Debug log so you know this file actually ran
    if (defined('WP_DEBUG') && WP_DEBUG) error_log('YGV: registering quiz gate endpoints');

    // POST /yugovote/v1/quiz/{id}/start
register_rest_route('yugovote/v1', '/quiz/(?P<id>\d+)/start', [
    'methods'  => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function (WP_REST_Request $req) {
        $user_id = get_current_user_id();
        $quiz_id = (int)$req['id'];

        $quiz = get_post($quiz_id);
        if (!$quiz || $quiz->post_type !== 'quiz') {
            return new WP_Error('quiz_not_found', __('Kviz nije pronađen.', 'hello-elementor-child'), ['status'=>404]);
        }

        // Check guest permission
        $allow_guest = get_post_meta($quiz_id, '_allow_guest_play', true) === '1';
        
        if ($user_id === 0 && !$allow_guest) {
            return new WP_Error('login_required', __('Morate biti prijavljeni da biste igrali ovaj kviz.', 'hello-elementor-child'), ['status'=>401]);
        }

        // Only charge tokens if user is logged in
        if ($user_id > 0 && function_exists('ygv_tokens')) {
            $cost = (int) get_post_meta($quiz_id, '_quiz_token_cost', true);
            if ($cost <= 0) $cost = 8;

            ygv_tokens()->lazy_refill($user_id);
            if (! ygv_tokens()->spend_tokens_atomic($user_id, $cost)) {
                return new WP_Error('insufficient_tokens', __('Nemate dovoljno tokena.', 'hello-elementor-child'), [
                    'status' => 403,
                    'next_refill_in' => ygv_tokens()->seconds_to_next_refill($user_id),
                ]);
            }
        }

        $attempt_id = (int) round(microtime(true) * 1000);

        return rest_ensure_response([
            'success'    => true,
            'attempt_id' => $attempt_id,
            'quiz_id'    => $quiz_id,
            'is_guest'   => $user_id === 0,
            'message'    => __('Pokušaj kreiran. Srećno!', 'hello-elementor-child'),
        ]);
    },
]);

// POST /yugovote/v1/quiz/{id}/submit
register_rest_route('yugovote/v1', '/quiz/(?P<id>\d+)/submit', [
    'methods'  => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function (WP_REST_Request $req) {
        $user_id    = get_current_user_id();
        $quiz_id    = (int)$req['id'];
        $attempt_id = ygv_req_int($req, 'attempt_id', 0);
        $correct    = ygv_req_int($req, 'correct', 0);
        $total      = max(1, ygv_req_int($req, 'total', 1));

        $quiz = get_post($quiz_id);
        if (!$quiz || $quiz->post_type !== 'quiz') {
            return new WP_Error('quiz_not_found', __('Kviz nije pronađen.', 'hello-elementor-child'), ['status'=>404]);
        }

        $score_percent = (int) round(($correct / $total) * 100);

        // Only save progress if logged in
        $result = ['awarded_xp' => 0, 'category' => null, 'overall' => null];
        
        if ($user_id > 0 && function_exists('ygv_progress')) {
            // Get the quiz's category (first quiz_category term)
            $term_ids = wp_get_object_terms($quiz_id, 'quiz_category', ['fields'=>'ids']);
            $cat_id   = (!is_wp_error($term_ids) && !empty($term_ids)) ? (int)$term_ids[0] : 0;

            // Award XP using delta model
            $result = ygv_progress()->record_attempt($user_id, [
                'quiz_id'  => $quiz_id,
                'category' => $cat_id,
                'correct'  => $correct,
                'total'    => $total,
            ]);
        }

        return rest_ensure_response([
            'success'       => true,
            'attempt_id'    => $attempt_id,
            'quiz_id'       => $quiz_id,
            'correct'       => $correct,
            'total'         => $total,
            'score_percent' => $score_percent,
            'is_guest'      => $user_id === 0,
            'awarded_xp'    => (int)($result['awarded_xp'] ?? 0),
            'category'      => $result['category'] ?? null,
            'overall'       => $result['overall'] ?? null,
            'message'       => $user_id === 0 
                ? __('Rezultat izračunat. Prijavite se da sačuvate napredak!', 'hello-elementor-child')
                : __('Rezultat zabeležen.', 'hello-elementor-child'),
        ]);
    },
]);
});
