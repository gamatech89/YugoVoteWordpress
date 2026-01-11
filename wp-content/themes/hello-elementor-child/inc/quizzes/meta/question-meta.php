<?php
/**
 * Add Meta Box for Question Fields
 */
function add_question_meta_boxes() {
    add_meta_box(
        'question_settings',
        'Question Settings',
        'render_question_meta_box',
        'question',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_question_meta_boxes');

function render_question_meta_box($post) {
    $num_answers = get_post_meta($post->ID, '_num_answers', true) ?: 4;
    $question_text = get_post_meta($post->ID, '_question_text', true);
    $difficulty_level = get_post_meta($post->ID, '_question_difficulty', true);
    $answers = get_post_meta($post->ID, '_quiz_answers', true) ?: [];
    $correct_answer = get_post_meta($post->ID, '_correct_answer', true);

    // ✅ PERFORMANCE: Use cached quiz levels helper
    $quiz_levels = function_exists('ygv_get_quiz_levels_cached') 
        ? ygv_get_quiz_levels_cached() 
        : get_posts(['post_type' => 'quiz_levels', 'posts_per_page' => -1]);

    wp_nonce_field('save_question_meta', 'question_meta_nonce');

    echo '<label><strong>Question Text:</strong></label>';
    echo '<textarea name="question_text" rows="3" style="width:100%;">' . esc_textarea($question_text) . '</textarea><br><br>';

    echo '<label><strong>Difficulty Level:</strong></label>';
    echo '<select name="question_difficulty" style="width:100%;">';
    echo '<option value="">-- Select Level --</option>';
    foreach ($quiz_levels as $level) {
        echo '<option value="' . $level->ID . '" ' . selected($difficulty_level, $level->ID, false) . '>' . esc_html($level->post_title) . '</option>';
    }
    echo '</select><br><br>';

    echo '<label><strong>Number of Answers:</strong></label><br>';
    echo '<input type="number" id="num_answers" name="num_answers" value="' . esc_attr($num_answers) . '" min="2" max="6" style="width: 60px; margin-bottom: 10px;">';

    echo '<div id="answer_fields">';

    for ($i = 0; $i < $num_answers; $i++) {
        $answer_value = isset($answers[$i]) ? esc_attr($answers[$i]) : '';
        echo '<div class="cs-quiz-answer-container">
                <input type="text" name="quiz_answers[]" value="' . $answer_value . '" placeholder="Enter answer" required>
                <input type="radio" name="correct_answer" value="' . $i . '" ' . checked($correct_answer, $i, false) . '> Correct
              </div>';
    }

    echo '</div>';
}

function save_question_meta($post_id) {
    if (!isset($_POST['question_meta_nonce']) || !wp_verify_nonce($_POST['question_meta_nonce'], 'save_question_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (get_post_type($post_id) !== 'question') {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $question_text     = isset($_POST['question_text']) ? sanitize_textarea_field(wp_unslash($_POST['question_text'])) : '';
    $difficulty_level  = isset($_POST['question_difficulty']) ? (int) $_POST['question_difficulty'] : 0;
    $num_answers       = isset($_POST['num_answers']) ? max(2, min(6, (int) $_POST['num_answers'])) : 4;
    $answers_raw       = isset($_POST['quiz_answers']) && is_array($_POST['quiz_answers']) ? $_POST['quiz_answers'] : [];
    $answers_sanitized = array_map('sanitize_text_field', array_slice($answers_raw, 0, $num_answers));
    $correct_answer    = isset($_POST['correct_answer']) ? (int) $_POST['correct_answer'] : -1;

    update_post_meta($post_id, '_question_text', $question_text);
    update_post_meta($post_id, '_question_difficulty', $difficulty_level);
    update_post_meta($post_id, '_num_answers', $num_answers);
    update_post_meta($post_id, '_quiz_answers', $answers_sanitized);
    update_post_meta($post_id, '_correct_answer', $correct_answer);
}
add_action('save_post_question', 'save_question_meta');

