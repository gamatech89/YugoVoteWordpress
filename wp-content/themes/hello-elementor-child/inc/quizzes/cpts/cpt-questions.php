<?php
/**
 * Register Custom Post Type: Question
 */
function register_cpt_questions() {
    $labels = [
        'name'          => 'Questions',
        'singular_name' => 'Question',
        'add_new'       => 'Add New Question',
        'edit_item'     => 'Edit Question',
        'all_items'     => 'All Questions'
    ];

    $args = [
        'label'         => 'Questions',
        'labels'        => $labels,
        'public'        => true,
        'show_in_menu'  => true,
        'menu-position' => 7,
        'menu_icon'     => 'dashicons-editor-help',
        'supports'      => ['title'], // We'll use a custom field for the actual question text
        'has_archive'   => false,
        'menu_class'    => 'cs-quiz-data-menu',
    ];

    register_post_type('question', $args);
}
add_action('init', 'register_cpt_questions');
