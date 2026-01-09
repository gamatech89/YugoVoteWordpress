<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    if (defined('WP_DEBUG') && WP_DEBUG) error_log('YGV: registering user state endpoint');

    register_rest_route('yugovote/v1', '/user/state', [
        'methods'  => 'GET',
        'permission_callback' => function(){ return is_user_logged_in(); },
        'callback' => function () {
            $user_id = get_current_user_id();

            // Tokens
            $tokens_current = 0; $tokens_max = 48; $next_in = 0;
            if (function_exists('ygv_tokens')) {
                $wallet = ygv_tokens()->lazy_refill($user_id);
                $tokens_current = (int)$wallet['tokens'];
                $tokens_max     = (int)$wallet['max_tokens'];
                $next_in        = (int) ygv_tokens()->seconds_to_next_refill($user_id);
            }

            // Categories (guard progress service)
            $categories = [];
            if (function_exists('ygv_progress')) {
                global $wpdb;
                $t_cat = $wpdb->prefix . 'ygv_user_category_progress';
                $rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT category_term_id, xp, level, streak, last_attempt_at
                     FROM {$t_cat} WHERE user_id = %d ORDER BY category_term_id ASC",
                    $user_id
                ), ARRAY_A) ?: [];

                foreach ($rows as $r) {
                    $term = get_term((int)$r['category_term_id'], 'quiz_category');
                    if (is_wp_error($term) || !$term) continue;

                    $thresholds = ygv_progress()->get_thresholds((int)$r['category_term_id']);
                    $current_lvl = (int)$r['level'];
                    $next_lvl = $current_lvl < 5 ? $current_lvl + 1 : 5;
                    $next_lvl_xp = $thresholds[$next_lvl] ?? null;

                    $to_next = null;
                    if ($next_lvl_xp !== null && $current_lvl < 5) {
                        $to_next = max(0, (int)$next_lvl_xp - (int)$r['xp']);
                    }

                    $categories[] = [
                        'term_id' => (int)$term->term_id,
                        'name'    => $term->name,
                        'level'   => $current_lvl,
                        'xp'      => (int)$r['xp'],
                        'to_next' => $to_next,
                        'streak'  => (int)$r['streak'],
                    ];
                }
            }

            // Overall (guard progress service)
            $overall = ['overall_xp'=>0, 'overall_level'=>1];
            if (function_exists('ygv_progress')) {
                global $wpdb;
                $t_over = $wpdb->prefix . 'ygv_user_overall_progress';
                $row = $wpdb->get_row($wpdb->prepare(
                    "SELECT overall_xp, overall_level FROM {$t_over} WHERE user_id=%d",
                    $user_id
                ), ARRAY_A);
                if (!$row) {
                    $overall = ygv_progress()->recompute_overall_progress($user_id);
                } else {
                    $overall = [
                        'overall_xp'    => (int)$row['overall_xp'],
                        'overall_level' => (int)$row['overall_level'],
                    ];
                }
            }

            return rest_ensure_response([
                'tokens' => [
                    'current'        => $tokens_current,
                    'max'            => $tokens_max,
                    'next_refill_in' => $next_in,
                ],
                'overall'    => $overall,
                'categories' => $categories,
            ]);
        }
    ]);
});
