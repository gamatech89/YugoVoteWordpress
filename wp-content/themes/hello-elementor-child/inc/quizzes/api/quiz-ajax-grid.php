<?php
/**
 * AJAX Handler: Load More Quizzes
 * Handles dynamic quiz grid loading with filters
 */

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_yuv_load_quizzes', 'yuv_ajax_load_quizzes');
add_action('wp_ajax_nopriv_yuv_load_quizzes', 'yuv_ajax_load_quizzes');

function yuv_ajax_load_quizzes() {
    // Get parameters
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 9;
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'latest';

    // Build query
    $args = yuv_build_quiz_query_args($per_page, $page, $category, $sort_by);
    $quiz_query = new WP_Query($args);

    if (!$quiz_query->have_posts()) {
        wp_send_json_error([
            'message' => 'Nema više kvizova.',
        ]);
    }

    // Render cards
    ob_start();
    while ($quiz_query->have_posts()) {
        $quiz_query->the_post();
        get_template_part('inc/quizzes/templates/content-quiz-card');
    }
    wp_reset_postdata();
    $html = ob_get_clean();

    // Response
    wp_send_json_success([
        'html' => $html,
        'current_page' => $page,
        'max_pages' => $quiz_query->max_num_pages,
        'total_posts' => $quiz_query->found_posts,
        'posts_count' => $quiz_query->post_count,
    ]);
}
