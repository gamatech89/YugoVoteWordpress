<?php
/**
 * Stranica ankete (/anketa/x/) — isti okvir kao stranica kviza (inc/quizzes/quiz-landing.php, css/landing.css).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * „Još anketa“: najnovije objavljene ankete bez trenutne i bez zaključanih
 * (u zaključanoj nema glasanja, a i isključila bi javni keš stranice).
 *
 * @return list<int>
 */
function ygv_poll_related_ids(int $poll_id, int $limit = 3): array {
    $ids = get_posts([
        'post_type'      => 'voting_poll',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'post__not_in'   => [$poll_id],
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            'relation' => 'OR',
            ['key' => '_cs_poll_locked', 'compare' => 'NOT EXISTS'],
            ['key' => '_cs_poll_locked', 'value' => '1', 'compare' => '!='],
        ],
    ]);
    return array_values(array_map('intval', $ids));
}
