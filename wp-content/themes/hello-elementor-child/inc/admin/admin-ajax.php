<?php
/**
 * Handles all AJAX requests for the theme.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
    exit();
}

// ✅ Handle filtering quiz questions by category
function filter_quiz_questions() {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : '';

    error_log("🔍 DEBUG: Fetching questions for category ID: " . $category_id); // Debug Log

    $args = [
        'post_type'   => 'question',
        'post_status' => 'publish',
        'numberposts' => -1,
    ];

    // ✅ If a category is selected, apply filtering
    if (!empty($category_id)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'question_category',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ]
        ];
    }

    $questions = get_posts($args);
    $response = [];

    foreach ($questions as $question) {
        $response[] = [
            'id'    => $question->ID,
            'title' => $question->post_title,
        ];
    }

    error_log("✅ DEBUG: Questions Retrieved: " . print_r($response, true)); // Debug Log

    wp_send_json($response);
}
add_action('wp_ajax_filter_quiz_questions', 'filter_quiz_questions');


// ✅ Fetch all question categories for dropdowns
function get_question_categories() {
    $categories = get_terms([
        'taxonomy'   => 'question-category',
        'hide_empty' => false
    ]);

    $response = [];
    foreach ($categories as $category) {
        $response[] = [
            'id'   => $category->term_id,
            'name' => $category->name
        ];
    }

    wp_send_json_success($response);
}
add_action('wp_ajax_get_question_categories', 'get_question_categories');

// ✅ Fetch quiz question data (for loading question fields in admin)
function get_quiz_question_data() {
    if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
        wp_send_json_error(['message' => 'Invalid Post ID']);
    }

    $post_id = intval($_POST['post_id']);

    $num_answers = get_post_meta($post_id, '_num_answers', true) ?: 4;
    $answers = get_post_meta($post_id, '_quiz_answers', true);
    $correct_answer = get_post_meta($post_id, '_correct_answer', true);

    wp_send_json_success([
        'num_answers' => $num_answers,
        'answers' => $answers,
        'correct_answer' => $correct_answer
    ]);
}
add_action('wp_ajax_get_quiz_question_data', 'get_quiz_question_data');

function get_quiz_question_titles() {
    if (!isset($_POST['question_ids']) || !is_array($_POST['question_ids'])) {
        wp_send_json_error(['message' => 'Invalid question IDs']);
    }

    $question_ids = array_map('intval', $_POST['question_ids']);
    $questions = [];

    foreach ($question_ids as $id) {
        $questions[] = [
            'id'    => $id,
            'title' => get_the_title($id) ?: "Unknown Question"
        ];
    }

    wp_send_json_success(['questions' => $questions]);
}
add_action('wp_ajax_get_quiz_question_titles', 'get_quiz_question_titles');


/**
 * AJAX Handler: Fetch Voting Items based on search & category filter.
 * Returns a list of items matching the search query and selected category.
 */
function fetch_voting_items() {
    $category = isset($_POST['category']) ? intval($_POST['category']) : '';

    $args = [
        'post_type'      => 'voting_items',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];

    if ($category) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'voting_item_category',
                'field'    => 'term_id',
                'terms'    => $category,
            ],
        ];
    }

    $items = get_posts($args);
    $results = [];

    foreach ($items as $item) {
        $results[] = [
            'id'    => $item->ID,
            'title' => $item->post_title,
        ];
    }

    wp_send_json($results);
}
add_action('wp_ajax_fetch_voting_items', 'fetch_voting_items');




