<?php

/**
 * Enqueue scripts and styles for Voting functionality.
 * Only loads assets on relevant pages (Voting Lists, Voting Items, Categories)
 * or if specific shortcodes are present in the content.
 */
function enqueue_voting_list_scripts() {
    global $post;

    // Check if $post is valid to avoid errors when checking content
    $post_content_exists = isset($post) && is_a($post, 'WP_Post') && isset($post->post_content);

    // Default to not loading scripts
    $load_scripts = false;

    // Condition 1: Specific Post Types and Taxonomies
    if (is_singular('voting_list') || 
        is_singular('voting_items') || 
        is_tax('voting_list_category')) {
        $load_scripts = true;
    }

    // Condition 2: Check for Shortcodes in Content
    if (!$load_scripts && $post_content_exists) {
        
        // List of all shortcodes that require these scripts
        $required_shortcodes = [
            'voting_list',
            'lists_with_this_item',
            'related_lists_carousel',
            'homepage_categories_slider', // Added: Homepage Slider
            'voting_category_hero',       // Added: Category Hero
            'voting_top_categories'       // Added: Top Categories Carousel
        ];

        foreach ($required_shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                $load_scripts = true;
                break; // Stop checking if one is found
            }
        }
    }

    if ($load_scripts) {
        
        // --- JavaScript Classes ---
        
        // 1. VotingList Class
        wp_enqueue_script(
            'voting-list-class',
            get_stylesheet_directory_uri() . '/js/voting/voting-list.js',
            ['jquery'],
            HELLO_ELEMENTOR_CHILD_VERSION,
            true // Load in footer
        );

        // 2. VotingItem Class (Depends on VotingList)
        wp_enqueue_script(
            'voting-item-class',
            get_stylesheet_directory_uri() . '/js/voting/voting-item.js',
            ['jquery', 'voting-list-class'],
            HELLO_ELEMENTOR_CHILD_VERSION,
            true 
        );

        // 3. Search Controller Class
        wp_enqueue_script(
            'voting-search-js',
            get_stylesheet_directory_uri() . '/js/voting/SearchController.js',
            ['jquery'],
            HELLO_ELEMENTOR_CHILD_VERSION,
            true
        );

        // --- Main Initialization Script ---
        
        // 4. Voting Init (Initializes classes on DOM ready)
        wp_enqueue_script(
            'voting-init',
            get_stylesheet_directory_uri() . '/js/voting/voting-init.js',
            ['jquery', 'voting-list-class', 'voting-item-class', 'voting-search-js'],
            HELLO_ELEMENTOR_CHILD_VERSION,
            true
        );

        // Localize data for JavaScript (AJAX URL, Nonce)
        wp_localize_script('voting-init', 'voting_list_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('voting_list_actions_nonce')
        ));


        // --- Styles ---

        wp_enqueue_style(
            'voting-css',
            get_stylesheet_directory_uri() . '/css/voting.css',
            [],
            HELLO_ELEMENTOR_CHILD_VERSION
        );


        // --- Third-Party Libraries (Swiper) ---

        wp_enqueue_style('swiper-css', 'https://unpkg.com/swiper@10/swiper-bundle.min.css', [], '10.0.0');
        
        wp_enqueue_script(
            'swiper-js', 
            'https://unpkg.com/swiper@10/swiper-bundle.min.js', 
            [], 
            '10.0.0', 
            true
        );

        // Custom Swiper Init (Hero Carousel)
        // This file is likely optional if you are using inline JS in the template, 
        // but keeping it doesn't hurt.
        wp_enqueue_script(
            'cs-carousel-init',
            get_stylesheet_directory_uri() . '/js/voting/voting-carousel.js',
            ['swiper-js'],
            HELLO_ELEMENTOR_CHILD_VERSION,
            true
        );

        // Top Categories Carousel
        wp_enqueue_script(
            'yuv-top-categories-carousel',
            get_stylesheet_directory_uri() . '/js/voting/top-categories-carousel.js',
            ['jquery'],
            HELLO_ELEMENTOR_CHILD_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_voting_list_scripts');