<?php
if (!defined('ABSPATH')) exit;

/** Returns the main quiz_category term_id for a quiz (or 0 if none). */
function ygv_get_quiz_category_term_id(int $quiz_id): int {
    $t = wp_get_object_terms($quiz_id, 'quiz_category', ['fields' => 'ids']);
    return (is_wp_error($t) || empty($t)) ? 0 : (int) $t[0];
}

/** Map your difficulty meta/term to a numeric required level 1..5 */
function ygv_get_quiz_required_level(int $quiz_id): int {
    // If you have a taxonomy for levels, read that slug; otherwise use meta:
    $difficulty = get_post_meta($quiz_id, 'quiz_difficulty', true);
    $map = [
        'beginner' => 1,
        'intermediate' => 2,
        'advanced' => 3,
        'expert' => 4,
        'master' => 5,
    ];
    $key = strtolower(trim((string)$difficulty));
    return $map[$key] ?? 1;
}

/**
 * Get N random questions for a quiz by:
 *  - matching its main quiz_category
 *  - limiting to the chosen question_category terms (array of term_ids or slugs)
 */
function ygv_get_random_questions_for_quiz(int $quiz_id, int $count, array $question_categories = []): array {
    $main_cat = ygv_get_quiz_category_term_id($quiz_id);
    if (!$main_cat) return [];

    $tax_query = [
        'relation' => 'AND',
        [
            'taxonomy' => 'quiz_category',
            'field'    => 'term_id',
            'terms'    => [$main_cat],
        ],
    ];

    if (!empty($question_categories)) {
        $tax_query[] = [
            'taxonomy' => 'question_category',
            'field'    => is_int($question_categories[0]) ? 'term_id' : 'slug',
            'terms'    => $question_categories,
        ];
    }

    $q = new WP_Query([
        'post_type'      => 'question',
        'posts_per_page' => $count,
        'orderby'        => 'rand',
        'tax_query'      => $tax_query,
        'no_found_rows'  => true,
        'fields'         => 'ids',
    ]);

    return $q->posts ?? [];
}

/** Get quiz category color (returns hex code or default) */
function ygv_get_quiz_category_color(int $quiz_id, string $default = '#6A0DAD'): string {
    $terms = wp_get_object_terms($quiz_id, 'quiz_category', ['fields' => 'ids']);
    
    if (is_wp_error($terms) || empty($terms)) {
        return $default;
    }
    
    $color = get_term_meta($terms[0], 'quiz_category_color', true);
    return $color ?: $default;
}

/** Get quiz category name */
function ygv_get_quiz_category_name(int $quiz_id): string {
    $terms = wp_get_object_terms($quiz_id, 'quiz_category', ['fields' => 'names']);
    return (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : __('General', 'hello-elementor-child');
}
