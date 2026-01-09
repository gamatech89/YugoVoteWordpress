<?php
function enqueue_quizzes_assets() {
    wp_enqueue_style(
        'quizzes-css',
        get_stylesheet_directory_uri() . '/css/quizzes.css',
        [],
        defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0'
    );

    wp_enqueue_script(
        'quizzes-js',
        get_stylesheet_directory_uri() . '/js/quizzes/main.js',
        [], // no jQuery dependency
        defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0',
        true
    );

    wp_localize_script('quizzes-js', 'quizSettings', [
        'apiUrl'    => esc_url_raw( rest_url('yugovote/v1') ),
        'soundPath' => get_stylesheet_directory_uri() . '/assets/sounds/',
        'nonce'     => wp_create_nonce('wp_rest'),
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_quizzes_assets');

