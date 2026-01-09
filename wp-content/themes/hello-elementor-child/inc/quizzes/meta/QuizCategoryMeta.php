<?php
namespace HelloElementorChild\Quizzes\Meta;

if (!defined('ABSPATH')) {
    exit();
}

class QuizCategoryMeta {
    public static function register(): void {
        add_action('quiz_category_add_form_fields', [self::class, 'addField']);
        add_action('quiz_category_edit_form_fields', [self::class, 'editField']);
        add_action('created_quiz_category', [self::class, 'save']);
        add_action('edited_quiz_category', [self::class, 'save']);
    }

    public static function addField(): void {
        ?>
        <div class="form-field">
            <label for="quiz_category_color"><?php _e('Category Color', 'hello-elementor-child'); ?></label>
            <input type="color" name="quiz_category_color" id="quiz_category_color" value="#6A0DAD">
            <p class="description"><?php _e('Select a color for this quiz category. Used in UI elements.', 'hello-elementor-child'); ?></p>
        </div>
        <?php wp_nonce_field('ygv_save_quiz_category_color', 'ygv_quiz_category_color_nonce'); ?>
        <?php
    }

    public static function editField($term): void {
        $color = get_term_meta($term->term_id, 'quiz_category_color', true) ?: '#6A0DAD';
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="quiz_category_color"><?php _e('Category Color', 'hello-elementor-child'); ?></label>
            </th>
            <td>
                <input type="color" name="quiz_category_color" id="quiz_category_color" value="<?php echo esc_attr($color); ?>">
                <p class="description"><?php _e('Select a color for this quiz category.', 'hello-elementor-child'); ?></p>
            </td>
        </tr>
        <?php wp_nonce_field('ygv_save_quiz_category_color', 'ygv_quiz_category_color_nonce'); ?>
        <?php
    }

    public static function save(int $term_id): void {
        if (!isset($_POST['ygv_quiz_category_color_nonce']) || !wp_verify_nonce($_POST['ygv_quiz_category_color_nonce'], 'ygv_save_quiz_category_color')) {
            return;
        }

        if (!current_user_can('manage_categories')) {
            return;
        }

        if (isset($_POST['quiz_category_color'])) {
            $color = sanitize_hex_color($_POST['quiz_category_color']);
            if ($color) {
                update_term_meta($term_id, 'quiz_category_color', $color);
            }
        }
    }
}
