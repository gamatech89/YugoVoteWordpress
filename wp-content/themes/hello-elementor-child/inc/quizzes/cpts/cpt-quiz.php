<?php
/**
 * Register Custom Post Type: Quiz
 */
function register_cpt_quiz() {
    $labels = [
        'name'          => 'Kvizovi',
        'singular_name' => 'Kviz',
        'add_new'       => 'Kreiraj nov Kviz',
        'edit_item'     => 'Izmeni Kviz',
        'all_items'     => 'Svi Kvizovi'
    ];

    $args = [
        'label'         => 'Quizzes',
        'labels'        => $labels,
        'public'        => true,
        'show_in_menu'  => true,
        'menu_position' => 6,
        'menu_icon'     => 'dashicons-welcome-learn-more',
        'supports'      => ['title', 'thumbnail'], // ✅ Added 'thumbnail' to enable Featured Image
        'has_archive'   => false,
        'menu_class'    => 'cs-quiz-data-menu',
        'show_in_rest'  => true, // ✅ Required for Elementor compatibility
        'query_var'     => true,
        'rewrite'       => ['slug' => 'quiz'],
    ];

    register_post_type('quiz', $args);
}
add_action('init', 'register_cpt_quiz');
