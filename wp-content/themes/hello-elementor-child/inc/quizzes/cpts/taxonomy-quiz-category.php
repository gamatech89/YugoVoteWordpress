<?php
if (!defined('ABSPATH')) exit;

// Register taxonomy (runs after CPTs at priority 11)
add_action('init', function () {
    $labels = [
        'name'          => __('Kategorije kvizova', 'hello-elementor-child'),
        'singular_name' => __('Kategorija kviza', 'hello-elementor-child'),
    ];
    register_taxonomy('quiz_category', ['quiz','question'], [
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'quiz-category'],
    ]);
}, 11);

// Keep exactly one quiz_category on quizzes
add_action('save_post_quiz', function ($post_id) {
    if (wp_is_post_revision($post_id)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $terms = wp_get_object_terms($post_id, 'quiz_category', ['fields' => 'ids']);
    if (is_wp_error($terms) || empty($terms)) return;

    if (count($terms) > 1) {
        wp_set_object_terms($post_id, [(int)$terms[0]], 'quiz_category', false);
    }
}, 99);
