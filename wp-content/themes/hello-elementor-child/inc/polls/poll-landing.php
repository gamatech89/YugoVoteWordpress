<?php
/**
 * Stranica ankete (/anketa/x/) — isti okvir kao stranica kviza (inc/quizzes/quiz-landing.php, css/landing.css).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kartica ankete (inc/polls/templates/poll-card.php) u svom opsegu, da njene promenljive
 * ($poll_id, $img_url, $total_votes) ne prepišu promenljive šablona koji je poziva.
 */
function ygv_render_poll_card(int $poll_id): void {
    $img_url     = get_the_post_thumbnail_url($poll_id, 'medium_large');
    $total_votes = (int) get_post_meta($poll_id, '_cs_poll_total_votes', true);
    include get_stylesheet_directory() . '/inc/polls/templates/poll-card.php';
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
