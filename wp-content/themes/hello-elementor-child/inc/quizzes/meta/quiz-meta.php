<?php
/**
 * Quiz meta box (settings)
 */

if (!defined('ABSPATH')) exit;

/** Meta box */
function ygv_add_quiz_meta_boxes() {
    add_meta_box(
        'quiz_settings',
        __('Quiz Settings', 'hello-elementor-child'),
        'ygv_render_quiz_meta_box',
        'quiz',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ygv_add_quiz_meta_boxes');

/** Render */
function ygv_render_quiz_meta_box(WP_Post $post) {
    $num_questions      = (int) get_post_meta($post->ID, '_num_questions', true) ?: 10;
    $time_per_question  = (int) get_post_meta($post->ID, '_time_per_question', true) ?: 10;
    $quiz_type          = get_post_meta($post->ID, '_quiz_type', true) ?: 'automatic';
    $quiz_mode          = get_post_meta($post->ID, '_quiz_mode', true) ?: 'classic';
    $quiz_description   = get_post_meta($post->ID, '_quiz_description', true) ?: '';
    $quiz_difficulty    = get_post_meta($post->ID, '_quiz_difficulty', true) ?: '';
    $selected_categories= (array) get_post_meta($post->ID, '_quiz_question_categories', true) ?: [];
    $quiz_token_cost    = (int) get_post_meta($post->ID, '_quiz_token_cost', true) ?: 8;
    $quiz_xp_value      = (int) get_post_meta($post->ID, '_quiz_xp_value', true) ?: 20;

    $categories = get_terms([
        'taxonomy'   => 'question_category',
        'hide_empty' => false,
    ]);

    $quiz_levels = get_posts([
        'post_type'   => 'quiz_levels',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);

    wp_nonce_field('ygv_save_quiz_meta', 'ygv_quiz_meta_nonce');

    echo '<label><strong>'.esc_html__('Quiz Description', 'hello-elementor-child').':</strong></label>';
    echo '<textarea name="quiz_description" rows="4" style="width:100%;">' . esc_textarea($quiz_description) . '</textarea><br><br>';

    echo '<label><strong>'.esc_html__('Number of Questions', 'hello-elementor-child').':</strong></label>';
    echo '<input type="number" name="num_questions" value="' . esc_attr($num_questions) . '" min="1" style="width:100%;"><br><br>';

    echo '<label><strong>'.esc_html__('Time per Question (seconds)', 'hello-elementor-child').':</strong></label>';
    echo '<input type="number" name="time_per_question" value="' . esc_attr($time_per_question) . '" min="1" style="width:100%;"><br><br>';

    echo '<label><strong>'.esc_html__('Token Cost (per attempt)', 'hello-elementor-child').':</strong></label>';
    echo '<input type="number" name="quiz_token_cost" value="' . esc_attr($quiz_token_cost) . '" min="0" step="1" style="width:100%;"><br><br>';

    echo '<label><strong>'.esc_html__('Base XP (per quiz)', 'hello-elementor-child').':</strong></label>';
    echo '<input type="number" name="quiz_xp_value" value="' . esc_attr($quiz_xp_value) . '" min="1" style="width:100%;"><br><br>';

    echo '<label><strong>'.esc_html__('Quiz Difficulty', 'hello-elementor-child').':</strong></label>';
    echo '<select name="quiz_difficulty" style="width:100%;">';
    echo '<option value="">-- '.esc_html__('Select Difficulty', 'hello-elementor-child').' --</option>';
    foreach ($quiz_levels as $level) {
        echo '<option value="' . esc_attr($level->ID) . '" ' . selected($quiz_difficulty, $level->ID, false) . '>' . esc_html($level->post_title) . '</option>';
    }
    echo '</select><br><br>';

    echo '<label><strong>'.esc_html__('Question Selection Type', 'hello-elementor-child').':</strong></label>';
    echo '<select id="quiz_type" name="quiz_type" style="width:100%;">';
    echo '<option value="automatic" ' . selected($quiz_type, 'automatic', false) . '>Automatic</option>';
    echo '<option value="manual" ' . selected($quiz_type, 'manual', false) . '>Manual</option>';
    echo '</select><br><br>';

    echo '<label><strong>'.esc_html__('Quiz Mode', 'hello-elementor-child').':</strong></label>';
    echo '<select id="quiz_mode" name="quiz_mode" style="width:100%;">';
    echo '<option value="classic" ' . selected($quiz_mode, 'classic', false) . '>Classic</option>';
    echo '<option value="speedtime" ' . selected($quiz_mode, 'speedtime', false) . '>SpeedTime</option>';
    echo '</select><br><br>';

    echo '<label><strong>'.esc_html__('Choose Question Categories', 'hello-elementor-child').':</strong></label>';
    echo '<select id="quiz_question_categories" name="quiz_question_categories[]" multiple="multiple" style="width:100%;">';
    foreach ($categories as $category) {
        $sel = in_array($category->term_id, $selected_categories, true) ? 'selected' : '';
        echo '<option value="' . esc_attr($category->term_id) . '" '.$sel.'>' . esc_html($category->name) . '</option>';
    }
    echo '</select><br><br>';

    echo '<div id="automatic_options" style="display:' . ($quiz_type === 'automatic' ? 'block' : 'none') . ';">';
    echo '<p><em>'.esc_html__('Automatic mode will randomly select questions based on selected categories.', 'hello-elementor-child').'</em></p>';
    echo '</div>';

    echo '<div id="manual_options" style="display:' . ($quiz_type === 'manual' ? 'block' : 'none') . ';">';
    echo '<p><em>'.esc_html__('Manually select questions for the quiz below.', 'hello-elementor-child').'</em></p>';

    echo '<label><strong>'.esc_html__('Filter Questions by Category', 'hello-elementor-child').':</strong></label>';
    echo '<select id="quiz_category_filter" style="width:100%;">';
    echo '<option value="">-- '.esc_html__('Select Category', 'hello-elementor-child').' --</option>';
    foreach ($categories as $category) {
        echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
    }
    echo '</select> ';
    echo '<button type="button" id="filter_questions" class="button">'.esc_html__('Filter Questions', 'hello-elementor-child').'</button><br><br>';

    echo '<label><strong>'.esc_html__('Select Questions', 'hello-elementor-child').':</strong></label>';
    echo '<select id="quiz_question_select" style="width:100%;"></select> ';
    echo '<button type="button" id="add_quiz_question" class="button">'.esc_html__('Add Question', 'hello-elementor-child').'</button><br><br>';

    echo '<table id="quiz_questions_table" class="widefat">';
    echo '<thead><tr><th>'.esc_html__('Question','hello-elementor-child').'</th><th>'.esc_html__('Action','hello-elementor-child').'</th></tr></thead>';
    echo '<tbody></tbody>';
    echo '</table><br>';

    $selected_questions_json = get_post_meta($post->ID, '_quiz_questions', true);
    if (!is_string($selected_questions_json)) $selected_questions_json = '[]';

    echo '<input type="hidden" id="quiz_questions" name="quiz_questions" value="' . esc_attr($selected_questions_json) . '">';
    echo '</div>';
}

/** Save (only for quiz post type) */
function ygv_save_quiz_meta($post_id) {
    if (wp_is_post_revision($post_id)) return;
    if (!isset($_POST['ygv_quiz_meta_nonce']) || !wp_verify_nonce($_POST['ygv_quiz_meta_nonce'], 'ygv_save_quiz_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // ints
    if (isset($_POST['num_questions'])) {
        update_post_meta($post_id, '_num_questions', max(1, (int) $_POST['num_questions']));
    }
    if (isset($_POST['time_per_question'])) {
        update_post_meta($post_id, '_time_per_question', max(1, (int) $_POST['time_per_question']));
    }
    if (isset($_POST['quiz_token_cost'])) {
        update_post_meta($post_id, '_quiz_token_cost', max(0, (int) $_POST['quiz_token_cost']));
    }
    if (isset($_POST['quiz_xp_value'])) {
        update_post_meta($post_id, '_quiz_xp_value', max(1, (int) $_POST['quiz_xp_value']));
    }
    if (isset($_POST['quiz_difficulty'])) {
        update_post_meta($post_id, '_quiz_difficulty', (int) $_POST['quiz_difficulty']);
    }

    // strings
    if (isset($_POST['quiz_type'])) {
        update_post_meta($post_id, '_quiz_type', sanitize_text_field($_POST['quiz_type']));
    }
    if (isset($_POST['quiz_mode'])) {
        update_post_meta($post_id, '_quiz_mode', sanitize_text_field($_POST['quiz_mode']));
    }
    if (isset($_POST['quiz_description'])) {
        update_post_meta($post_id, '_quiz_description', sanitize_textarea_field($_POST['quiz_description']));
    }

    // categories (array of ints)
    if (isset($_POST['quiz_question_categories']) && is_array($_POST['quiz_question_categories'])) {
        $cats = array_map('intval', $_POST['quiz_question_categories']);
        update_post_meta($post_id, '_quiz_question_categories', $cats);
    }

    // manual questions (JSON → array of ints, then re-store as JSON for your API)
    if (isset($_POST['quiz_questions'])) {
        $json = wp_unslash($_POST['quiz_questions']);
        $arr  = json_decode($json, true);
        if (is_array($arr)) {
            $arr = array_map('intval', $arr);
            update_post_meta($post_id, '_quiz_questions', wp_json_encode(array_values($arr)));
        }
    }
}
add_action('save_post_quiz', 'ygv_save_quiz_meta');
