<?php
function enqueue_quizzes_assets() {
    // ✅ STEP 1: Enqueue RemixIcon CSS from CDN
    wp_enqueue_style(
        'remixicon',
        'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css',
        [],
        '3.5.0'
    );

    wp_enqueue_style(
        'quizzes-css',
        get_stylesheet_directory_uri() . '/css/quizzes.css',
        ['remixicon'], // ✅ Depend on RemixIcon
        defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0'
    );

    wp_enqueue_script(
        'quizzes-js',
        get_stylesheet_directory_uri() . '/js/quizzes/main.js',
        [],
        defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0',
        true
    );

    // ✅ NEW: Enqueue Quiz Grid Filter JS
    wp_enqueue_script(
        'quiz-grid-filter',
        get_stylesheet_directory_uri() . '/js/quizzes/quiz-grid-filter.js',
        ['quizzes-js'], // Depends on main quiz JS
        defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0',
        true
    );

    // ✅ NEW: Enqueue Quiz Archive AJAX JS
    wp_enqueue_script(
        'quiz-archive-js',
        get_stylesheet_directory_uri() . '/js/quizzes/quiz-archive.js',
        ['quizzes-js'], // Depends on main quiz JS
        defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0',
        true
    );

    wp_localize_script('quizzes-js', 'quizSettings', [
        'apiUrl'    => esc_url_raw( rest_url('yugovote/v1') ),
        'soundPath' => get_stylesheet_directory_uri() . '/assets/sounds/',
        'nonce'     => wp_create_nonce('wp_rest'),
    ]);

    // ✅ Localize ajaxurl for AJAX requests
    wp_localize_script('quiz-archive-js', 'yuvAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('yuv_quiz_archive'),
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_quizzes_assets');

