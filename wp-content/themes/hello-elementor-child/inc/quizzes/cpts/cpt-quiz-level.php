<?php

function register_cpt_quiz_levels() {
    $labels = [
        'name'          => 'Quiz Levels',
        'singular_name' => 'Quiz Level',
        'add_new'       => 'Add New Level',
        'edit_item'     => 'Edit Level',
        'all_items'     => 'All Levels'
    ];

    $args = [
        'label'         => 'Quiz Levels',
        'labels'        => $labels,
        'public'        => true,
        'show_in_menu'  => true,
        'menu-position' => 5,
        'menu_icon'     => 'dashicons-awards',
        'supports'      => ['title'],
        'has_archive'   => false,
        'menu_class'    => 'cs-quiz-data-menu',
    ];

    register_post_type('quiz_levels', $args);
}
add_action('init', 'register_cpt_quiz_levels');
