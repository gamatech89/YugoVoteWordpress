<?php
/**
 * Showdown Module Initialization
 * Loads all showdown-related files
 */

if (!defined('ABSPATH'))
    exit;

$showdown_path = get_stylesheet_directory() . '/inc/voting/showdown/';

// Load DB schema (creates table if needed)
require_once $showdown_path . 'showdown-db.php';

// Load CPT
require_once $showdown_path . 'cpts/cpt-showdown.php';

// Load manager class
require_once $showdown_path . 'classes/class-showdown-manager.php';

// Load meta boxes
require_once $showdown_path . 'meta/showdown-meta.php';

// Load AJAX handlers
require_once $showdown_path . 'api/showdown-ajax.php';

// Load shortcodes
require_once $showdown_path . 'shortcodes/showdown-shortcodes.php';

// Enqueue showdown assets
add_action('wp_enqueue_scripts', 'yuv_enqueue_showdown_assets');

function yuv_enqueue_showdown_assets()
{
    // Google Fonts: Inter + Jockey One
    wp_enqueue_style(
        'yuv-showdown-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Jockey+One&display=swap',
    [],
        null
    );

    // Remix Icons
    wp_enqueue_style(
        'yuv-remixicon',
        'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css',
    [],
        '3.5.0'
    );

    wp_enqueue_style(
        'yuv-showdown',
        get_stylesheet_directory_uri() . '/css/showdown.css',
    ['yuv-showdown-fonts', 'yuv-remixicon'],
        '2.0.0'
    );

    wp_enqueue_script(
        'yuv-showdown',
        get_stylesheet_directory_uri() . '/js/showdown.js',
    ['jquery'],
        '2.0.0',
        true
    );

    wp_localize_script('yuv-showdown', 'yuvShowdown', [
        'ajaxurl' => '/wp-admin/admin-ajax.php',
        'nonce' => wp_create_nonce('yuv_showdown_nonce'),
    ]);
}