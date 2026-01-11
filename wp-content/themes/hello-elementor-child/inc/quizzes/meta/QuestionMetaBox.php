<?php
namespace HelloElementorChild\Quizzes\Meta;

use WP_Post;

if (!defined('ABSPATH')) {
    exit();
}

class QuestionMetaBox {
    public static function register(): void {
        add_action('add_meta_boxes', [self::class, 'addMetaBox']);
        add_action('save_post_question', [self::class, 'save']);
    }

    public static function addMetaBox(): void {
        add_meta_box(
            'question_settings',
            __('Question Settings', 'hello-elementor-child'),
            [self::class, 'render'],
            'question',
            'normal',
            'high'
        );
    }

    public static function render(WP_Post $post): void {
        $num_answers      = get_post_meta($post->ID, '_num_answers', true) ?: 4;
        $question_text    = get_post_meta($post->ID, '_question_text', true);
        $difficulty_level = get_post_meta($post->ID, '_question_difficulty', true);
        $answers          = get_post_meta($post->ID, '_quiz_answers', true) ?: [];
        $correct_answer   = get_post_meta($post->ID, '_correct_answer', true);

        // ✅ PERFORMANCE: Use cached quiz levels helper
        $quiz_levels = function_exists('ygv_get_quiz_levels_cached') 
            ? ygv_get_quiz_levels_cached() 
            : get_posts(['post_type' => 'quiz_levels', 'posts_per_page' => -1]);

        wp_nonce_field('save_question_meta', 'question_meta_nonce');
        ?>
        <div class="ygv-question-meta">
            <label><strong><?php esc_html_e('Question Text:', 'hello-elementor-child'); ?></strong></label>
            <textarea name="question_text" rows="3" style="width:100%;"><?php echo esc_textarea($question_text); ?></textarea>
            <br><br>

            <label><strong><?php esc_html_e('Difficulty Level:', 'hello-elementor-child'); ?></strong></label>
            <select name="question_difficulty" style="width:100%;">
                <option value="">-- <?php esc_html_e('Select Level', 'hello-elementor-child'); ?> --</option>
                <?php foreach ($quiz_levels as $level) : ?>
                    <option value="<?php echo esc_attr($level->ID); ?>" <?php selected($difficulty_level, $level->ID); ?>>
                        <?php echo esc_html($level->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label><strong><?php esc_html_e('Number of Answers:', 'hello-elementor-child'); ?></strong></label><br>
            <input type="number" id="num_answers" name="num_answers" value="<?php echo esc_attr($num_answers); ?>" min="2" max="6" style="width: 60px; margin-bottom: 10px;">

            <div id="answer_fields">
                <?php for ($i = 0; $i < $num_answers; $i++) :
                    $answer_value = isset($answers[$i]) ? esc_attr($answers[$i]) : '';
                    ?>
                    <div class="cs-quiz-answer-container">
                        <input type="text" name="quiz_answers[]" value="<?php echo $answer_value; ?>" placeholder="<?php esc_attr_e('Enter answer', 'hello-elementor-child'); ?>" required>
                        <input type="radio" name="correct_answer" value="<?php echo esc_attr($i); ?>" <?php checked($correct_answer, $i); ?>> <?php esc_html_e('Correct', 'hello-elementor-child'); ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php
    }

    public static function save(int $post_id): void {
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
}
