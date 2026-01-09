<?php

function add_quiz_level_meta_boxes() {
    add_meta_box(
        'quiz_level_details',
        'Quiz Level Details',
        'render_quiz_level_meta_box',
        'quiz_levels',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_quiz_level_meta_boxes');

function render_quiz_level_meta_box($post) {
    $required_points = get_post_meta($post->ID, '_required_points', true) ?: 0;
    $voting_multiplicator = get_post_meta($post->ID, '_voting_multiplicator', true) ?: 1;
    $quiz_question_points = get_post_meta($post->ID, '_quiz_question_points', true) ?: 0;
    $image = get_post_meta($post->ID, '_quiz_level_image', true) ?: '';

    wp_nonce_field('save_quiz_level_meta', 'quiz_level_meta_nonce');

    echo '<label for="required_points"><strong>Required Points:</strong></label>';
    echo '<input type="number" name="required_points" value="' . esc_attr($required_points) . '" min="0" step="1" style="width:100%;"><br><br>';

    echo '<label for="voting_multiplicator"><strong>Voting Multiplicator:</strong></label>';
    echo '<input type="number" name="voting_multiplicator" value="' . esc_attr($voting_multiplicator) . '" min="1" step="0.1" style="width:100%;"><br><br>';

    echo '<label for="quiz_question_points"><strong>Points Per Question:</strong></label>';
    echo '<input type="number" name="quiz_question_points" value="' . esc_attr($quiz_question_points) . '" min="0" step="1" style="width:100%;"><br><br>';

    echo '<label for="quiz_level_image"><strong>Level Image:</strong></label><br>';
    echo '<input type="text" name="quiz_level_image" id="quiz_level_image" value="' . esc_attr($image) . '" style="width:80%;" />';
    echo '<button class="button button-secondary upload_image_button">Upload Image</button>';

    if ($image) {
        echo '<br><img id="quiz_level_image_preview" src="' . esc_url($image) . '" style="max-width:100px;margin-top:10px;">';
    }
}

function save_quiz_level_meta($post_id) {
    if (!isset($_POST['quiz_level_meta_nonce']) || !wp_verify_nonce($_POST['quiz_level_meta_nonce'], 'save_quiz_level_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_required_points', intval($_POST['required_points']));
    update_post_meta($post_id, '_voting_multiplicator', floatval($_POST['voting_multiplicator']));
    update_post_meta($post_id, '_quiz_question_points', intval($_POST['quiz_question_points']));
    update_post_meta($post_id, '_quiz_level_image', esc_url($_POST['quiz_level_image']));
}
add_action('save_post', 'save_quiz_level_meta');


