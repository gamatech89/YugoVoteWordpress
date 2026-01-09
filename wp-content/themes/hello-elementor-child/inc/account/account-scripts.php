<?php
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
    global $post;

    $needs_assets = false;
    $needs_leaderboard = false;

    // Load on Moj nalog page by slug
    if (is_page('moj-nalog')) {
        $needs_assets = true;
    }

    // Also load wherever the account shortcodes appear (Elementor/blocks)
    if (!$needs_assets && isset($post) && $post instanceof WP_Post && isset($post->post_content)) {
        if (has_shortcode($post->post_content, 'yugo_account') ||
            has_shortcode($post->post_content, 'yugo_login_form') ||
            has_shortcode($post->post_content, 'yugo_account_panel')) {
            $needs_assets = true;
        }
    }
    
    // Check for leaderboard shortcode
    if (isset($post) && $post instanceof WP_Post && isset($post->post_content)) {
        if (has_shortcode($post->post_content, 'ygv_leaderboard')) {
            $needs_leaderboard = true;
        }
    }

    if ($needs_assets) {
        // Base account CSS
        wp_enqueue_style(
            'ygv-account-css',
            get_stylesheet_directory_uri() . '/css/account.css',
            [],
            defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0'
        );

        // Per-tab logic (only load panel JS on Kvizovi tab)
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'kvizovi';
        if ($tab === 'kvizovi') {
            if (!wp_script_is('ygv-account', 'enqueued')) {
                wp_enqueue_script(
                    'ygv-account',
                    get_stylesheet_directory_uri() . '/js/account/ygv-account.js',
                    [], // no jQuery
                    defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0',
                    true
                );
                wp_localize_script('ygv-account', 'YGV_ACCOUNT', [
                    'restRoot' => esc_url_raw( rest_url('yugovote/v1') ),
                    'nonce'    => wp_create_nonce('wp_rest'),
                ]);
            }
        }
    }
    
    // Leaderboard CSS
    if ($needs_leaderboard) {
        wp_enqueue_style(
            'ygv-leaderboard-css',
            get_stylesheet_directory_uri() . '/css/leaderboard.css',
            [],
            defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0'
        );
    }
});
