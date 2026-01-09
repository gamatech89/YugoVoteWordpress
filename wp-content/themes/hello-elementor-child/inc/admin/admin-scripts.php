<?php
/**
 * Load Admin Scripts & Styles for CPTs and Taxonomy pages
 */
function load_admin_assets($hook) {
    global $post;

    // Enqueue Media Uploader
    wp_enqueue_media();

    // ✅ General Admin Styles (for all admin pages or specific ones as needed)
    wp_enqueue_style(
        'admin-style',
        get_stylesheet_directory_uri() . '/admin-style.css',
        [],
        '1.0'
    );

    // ✅ CPT-Specific Scripts
    if (in_array($hook, ['post.php', 'post-new.php']) && isset($post)) {

        switch ($post->post_type) {
            case 'quiz':
                wp_enqueue_script(
                    'admin-quiz-script',
                    get_stylesheet_directory_uri() . '/js/admin/admin-quiz.js',
                    ['jquery'],
                    HELLO_ELEMENTOR_CHILD_VERSION,
                    true
                );
                break;

            case 'question':
                wp_enqueue_script(
                    'admin-questions-script',
                    get_stylesheet_directory_uri() . '/js/admin/admin-questions.js',
                    ['jquery'],
                    HELLO_ELEMENTOR_CHILD_VERSION,
                    true
                );
                break;

            case 'quiz_levels':
                wp_enqueue_script(
                    'admin-quiz-levels-script',
                    get_stylesheet_directory_uri() . '/js/admin/admin-quiz-levels.js',
                    HELLO_ELEMENTOR_CHILD_VERSION,
                    '1.0',
                    true
                );
                break;

            case 'voting_list':
                wp_enqueue_script(
                    'admin-voting-list-script', 
                    get_stylesheet_directory_uri() . '/js/admin/admin-voting-list.js',
                    ['jquery', 'wp-util'], 
                    HELLO_ELEMENTOR_CHILD_VERSION,
                    true
                );

                // Localize data (ajaxurl and nonce) specifically for 'admin-voting-list-script'
                wp_localize_script(
                    'admin-voting-list-script',  
                    'admin_voting_vars',         
                    array(
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'nonce'   => wp_create_nonce('voting_list_actions_nonce') 
                    )
                );
                break;
        }
    }

    // ✅ Taxonomy-Specific Scripts (Edit & Add New Term pages)
    if (in_array($hook, ['term.php', 'edit-tags.php'])) {
        $screen = get_current_screen();

        if ($screen->taxonomy === 'voting_list_category') {
            wp_enqueue_script(
                'admin-voting-category-media',
                get_stylesheet_directory_uri() . '/js/admin/voting-category-media.js',
                ['jquery'],
                HELLO_ELEMENTOR_CHILD_VERSION,
                true
            );
        }
    }
}
add_action('admin_enqueue_scripts', 'load_admin_assets');