<?php
/**
 * Tournament Module Initialization
 * Loads all tournament-related files
 */

if (!defined('ABSPATH')) exit;

$tournament_path = get_stylesheet_directory() . '/inc/voting/tournament/';

// Load tournament CPT
require_once $tournament_path . 'cpts/cpt-tournament.php';

// Load tournament meta boxes
require_once $tournament_path . 'meta/tournament-meta.php';

// Load tournament manager class
require_once $tournament_path . 'classes/class-tournament-manager.php';

// Load AJAX handlers
require_once $tournament_path . 'api/tournament-ajax.php';

// Load cron jobs
require_once $tournament_path . 'tournament-cron.php';

// Load shortcodes
require_once $tournament_path . 'shortcodes/bracket-shortcode.php';

// Load cleanup utilities
require_once $tournament_path . 'tournament-cleanup.php';

// Enqueue tournament assets
add_action('wp_enqueue_scripts', 'yuv_enqueue_tournament_assets');

function yuv_enqueue_tournament_assets() {
    // Enqueue tournament CSS
    wp_enqueue_style(
        'yuv-tournament-arena',
        get_stylesheet_directory_uri() . '/css/tournament.css',
        [],
        '1.0.1'
    );
    
    // Enqueue carousel animations CSS
    wp_enqueue_style(
        'yuv-tournament-carousel',
        get_stylesheet_directory_uri() . '/css/tournament-carousel.css',
        ['yuv-tournament-arena'],
        '1.0.0'
    );

    // Enqueue tournament JS
    wp_enqueue_script(
        'yuv-tournament',
        get_stylesheet_directory_uri() . '/js/tournament.js',
        ['jquery'],
        '1.0.2',
        true
    );

    // Localize script with AJAX data
    wp_localize_script('yuv-tournament', 'yuvTournament', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yuv_tournament_nonce'),
    ]);
}
