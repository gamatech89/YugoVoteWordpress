<?php

function add_user_level_metabox() {
    add_meta_box(
        'user_level_metabox',
        'User Level Details',
        'user_level_metabox_callback',
        'user_levels',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_user_level_metabox');

function user_level_metabox_callback($post) {
    $level = get_post_meta($post->ID, '_user_level', true);
    $multiplier = get_post_meta($post->ID, '_vote_multiplier', true);
    $points_required = get_post_meta($post->ID, '_points_required', true);
    ?>
    <p>
        <label for="user_level">User Level (e.g., 1, 2, 3...)</label>
        <input type="number" name="user_level" id="user_level" value="<?php echo esc_attr($level); ?>" class="widefat">
    </p>
    <p>
        <label for="vote_multiplier">Vote Multiplier (e.g., 1.0, 1.5, 2.0...)</label>
        <input type="number" step="0.1" name="vote_multiplier" id="vote_multiplier" value="<?php echo esc_attr($multiplier); ?>" class="widefat">
    </p>
    <p>
        <label for="points_required">Points Required for This Level</label>
        <input type="number" name="points_required" id="points_required" value="<?php echo esc_attr($points_required); ?>" class="widefat">
    </p>
    <?php
}

function save_user_level_data($post_id) {
    if (array_key_exists('user_level', $_POST)) {
        update_post_meta($post_id, '_user_level', sanitize_text_field($_POST['user_level']));
    }
    if (array_key_exists('vote_multiplier', $_POST)) {
        update_post_meta($post_id, '_vote_multiplier', sanitize_text_field($_POST['vote_multiplier']));
    }
    if (array_key_exists('points_required', $_POST)) {
        update_post_meta($post_id, '_points_required', sanitize_text_field($_POST['points_required']));
    }
}
add_action('save_post', 'save_user_level_data');
