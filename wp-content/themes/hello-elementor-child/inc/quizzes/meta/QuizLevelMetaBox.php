<?php
namespace HelloElementorChild\Quizzes\Meta;

use WP_Post;

if (!defined('ABSPATH')) {
    exit();
}

class QuizLevelMetaBox {
    public static function register(): void {
        add_action('add_meta_boxes', [self::class, 'addMetaBox']);
        add_action('save_post_quiz_levels', [self::class, 'save']);
    }

    public static function addMetaBox(): void {
        add_meta_box(
            'quiz_level_details',
            __('Quiz Level Details', 'hello-elementor-child'),
            [self::class, 'render'],
            'quiz_levels',
            'normal',
            'high'
        );
    }

    public static function render(WP_Post $post): void {
        $required_points      = get_post_meta($post->ID, '_required_points', true) ?: 0;
        $voting_multiplicator = get_post_meta($post->ID, '_voting_multiplicator', true) ?: 1;
        $quiz_question_points = get_post_meta($post->ID, '_quiz_question_points', true) ?: 0;
        $image                = get_post_meta($post->ID, '_quiz_level_image', true) ?: '';

        wp_nonce_field('save_quiz_level_meta', 'quiz_level_meta_nonce');
        ?>
        <div class="ygv-quiz-level-meta">
            <label for="required_points"><strong><?php esc_html_e('Required Points:', 'hello-elementor-child'); ?></strong></label>
            <input type="number" name="required_points" value="<?php echo esc_attr($required_points); ?>" min="0" step="1" style="width:100%;">
            <br><br>

            <label for="voting_multiplicator"><strong><?php esc_html_e('Voting Multiplicator:', 'hello-elementor-child'); ?></strong></label>
            <input type="number" name="voting_multiplicator" value="<?php echo esc_attr($voting_multiplicator); ?>" min="1" step="0.1" style="width:100%;">
            <br><br>

            <label for="quiz_question_points"><strong><?php esc_html_e('Points Per Question:', 'hello-elementor-child'); ?></strong></label>
            <input type="number" name="quiz_question_points" value="<?php echo esc_attr($quiz_question_points); ?>" min="0" step="1" style="width:100%;">
            <br><br>

            <label for="quiz_level_image"><strong><?php esc_html_e('Level Image:', 'hello-elementor-child'); ?></strong></label><br>
            <input type="text" name="quiz_level_image" id="quiz_level_image" value="<?php echo esc_attr($image); ?>" style="width:80%;" />
            <button class="button button-secondary upload_image_button"><?php esc_html_e('Upload Image', 'hello-elementor-child'); ?></button>

            <?php if ($image) : ?>
                <br><img id="quiz_level_image_preview" src="<?php echo esc_url($image); ?>" style="max-width:100px;margin-top:10px;">
            <?php endif; ?>
        </div>
        <?php
    }

    public static function save(int $post_id): void {
        if (!isset($_POST['quiz_level_meta_nonce']) || !wp_verify_nonce($_POST['quiz_level_meta_nonce'], 'save_quiz_level_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (get_post_type($post_id) !== 'quiz_levels') {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $required_points      = isset($_POST['required_points']) ? max(0, (int) $_POST['required_points']) : 0;
        $voting_multiplicator = isset($_POST['voting_multiplicator']) ? max(0, (float) $_POST['voting_multiplicator']) : 1;
        $quiz_question_points = isset($_POST['quiz_question_points']) ? max(0, (int) $_POST['quiz_question_points']) : 0;
        $image                = isset($_POST['quiz_level_image']) ? esc_url_raw(wp_unslash($_POST['quiz_level_image'])) : '';

        update_post_meta($post_id, '_required_points', $required_points);
        update_post_meta($post_id, '_voting_multiplicator', $voting_multiplicator);
        update_post_meta($post_id, '_quiz_question_points', $quiz_question_points);
        update_post_meta($post_id, '_quiz_level_image', $image);
    }
}
