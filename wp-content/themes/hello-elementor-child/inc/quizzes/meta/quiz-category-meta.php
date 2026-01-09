<?php
/**
 * Quiz Category Meta Fields
 * Adds custom color field to quiz_category taxonomy
 */

if (!defined('ABSPATH')) exit;

// Add color field to "Add New Category" form
add_action('quiz_category_add_form_fields', 'ygv_quiz_category_add_color_field');
function ygv_quiz_category_add_color_field() {
    ?>
    <div class="form-field">
        <label for="quiz_category_color"><?php _e('Category Color', 'hello-elementor-child'); ?></label>
        <input type="color" name="quiz_category_color" id="quiz_category_color" value="#6A0DAD">
        <p class="description"><?php _e('Select a color for this quiz category. Used in UI elements.', 'hello-elementor-child'); ?></p>
    </div>
    <?php
}

// Add color field to "Edit Category" form
add_action('quiz_category_edit_form_fields', 'ygv_quiz_category_edit_color_field');
function ygv_quiz_category_edit_color_field($term) {
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
    <?php
}

// Save color meta
add_action('created_quiz_category', 'ygv_save_quiz_category_color');
add_action('edited_quiz_category', 'ygv_save_quiz_category_color');
function ygv_save_quiz_category_color($term_id) {
    if (isset($_POST['quiz_category_color'])) {
        update_term_meta($term_id, 'quiz_category_color', sanitize_hex_color($_POST['quiz_category_color']));
    }
}
