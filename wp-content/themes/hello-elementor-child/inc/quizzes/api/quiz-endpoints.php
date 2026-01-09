<?php
function get_quiz_data(WP_REST_Request $request) {
    $quiz_id = $request->get_param('id');

    if (!$quiz_id) {
        return new WP_Error('missing_quiz_id', 'Quiz ID is required.', ['status' => 400]);
    }

    // Fetch Quiz Post
    $quiz_post = get_post($quiz_id);
    if (!$quiz_post) {
        return new WP_Error('invalid_quiz', 'Quiz not found.', ['status' => 404]);
    }

    // Get quiz settings from meta
    $num_questions       = get_post_meta($quiz_id, '_num_questions', true) ?: 3;
    $time_per_question   = get_post_meta($quiz_id, '_time_per_question', true) ?: 10;
    $quiz_difficulty_id  = get_post_meta($quiz_id, '_quiz_difficulty', true);
    $quiz_mode           = get_post_meta($quiz_id, '_quiz_mode', true) ?: 'classic';
    $quiz_type           = get_post_meta($quiz_id, '_quiz_type', true) ?: 'automatic';
    $quiz_description    = get_post_meta($quiz_id, '_quiz_description', true) ?: '';
    $selected_questions  = get_post_meta($quiz_id, '_quiz_questions', true) ?: [];
    $selected_categories = get_post_meta($quiz_id, '_quiz_question_categories', true) ?: [];
    $featured_image      = get_the_post_thumbnail_url($quiz_id, 'full') ?: '';
    $allow_guest_play    = get_post_meta($quiz_id, '_allow_guest_play', true);

    // Fetch Quiz Difficulty Title
    $quiz_difficulty = "";
    if (!empty($quiz_difficulty_id)) {
        $difficulty_post = get_post($quiz_difficulty_id);
        if ($difficulty_post) {
            $quiz_difficulty = $difficulty_post->post_title;
        }
    }

    // Fetch category names
    $category_names = [];
    if (!empty($selected_categories)) {
        foreach ($selected_categories as $cat_id) {
            $term = get_term($cat_id, 'question_category');
            if (!is_wp_error($term) && $term) {
                $category_names[] = $term->name;
            }
        }
    }

    $questions = [];

    if ($quiz_type === 'manual') {
        if (!empty($selected_questions)) {
            shuffle($selected_questions);
            $questions = array_slice($selected_questions, 0, $num_questions);
        }
    } else {
        if (!empty($selected_categories)) {
            $args = [
                'post_type'      => 'question',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'tax_query'      => [
                    [
                        'taxonomy' => 'question_category',
                        'field'    => 'term_id',
                        'terms'    => $selected_categories,
                    ]
                ],
                'meta_query'     => [
                    [
                        'key'     => '_question_difficulty',
                        'value'   => $quiz_difficulty_id,
                        'compare' => '='
                    ]
                ]
            ];

            $query = new WP_Query($args);
            $all_questions = [];

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $all_questions[] = get_the_ID();
                }
                wp_reset_postdata();
            }

            shuffle($all_questions);
            $questions = array_slice($all_questions, 0, $num_questions);
        }
    }

    // Prepare response with full question details
    $response_questions = [];
    foreach ($questions as $question_id) {
        $question_difficulty_id = get_post_meta($question_id, '_question_difficulty', true);
        $question_difficulty = "";

        if (!empty($question_difficulty_id)) {
            $difficulty_post = get_post($question_difficulty_id);
            if ($difficulty_post) {
                $question_difficulty = $difficulty_post->post_title;
            }
        }

        $response_questions[] = [
            'id'         => $question_id,
            'title'      => get_the_title($question_id),
            'answers'    => get_post_meta($question_id, '_quiz_answers', true),
            'correct'    => get_post_meta($question_id, '_correct_answer', true),
            'difficulty' => $question_difficulty
        ];
    }

    $quiz_image = get_the_post_thumbnail_url($quiz_id, 'full') ?: '';

    // Return full quiz data with fields
    return rest_ensure_response([
        'success'           => true,
        'quiz_id'           => $quiz_id,
        'title'             => $quiz_post->post_title,
        'description'       => $quiz_description,
        'featured_image'    => $quiz_image,
        'num_questions'     => (int) $num_questions,
        'time_per_question' => (int) $time_per_question,
        'quiz_difficulty'   => $quiz_difficulty,
        'quiz_mode'         => $quiz_mode,
        'quiz_type'         => $quiz_type,
        'categories'        => $category_names,
        'category_color'    => function_exists('ygv_get_quiz_category_color') ? ygv_get_quiz_category_color($quiz_id) : '#6A0DAD',
        'category_name'     => function_exists('ygv_get_quiz_category_name') ? ygv_get_quiz_category_name($quiz_id) : 'General',
        'allow_guest_play'  => $allow_guest_play === '1',
        'questions'         => $response_questions
    ]);
}

// Register REST API route
function register_yugovote_quiz_route() {
    register_rest_route('yugovote/v1', '/quiz/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'get_quiz_data',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'register_yugovote_quiz_route');
