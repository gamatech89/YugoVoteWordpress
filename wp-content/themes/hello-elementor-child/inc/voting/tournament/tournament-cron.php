<?php
/**
 * Tournament Cron Jobs
 * Automatically advances tournaments every hour
 */

if (!defined('ABSPATH')) exit;

// Schedule cron on theme activation
add_action('after_setup_theme', 'yuv_tournament_schedule_cron');
function yuv_tournament_schedule_cron() {
    if (!wp_next_scheduled('yuv_advance_tournaments_cron')) {
        wp_schedule_event(time(), 'hourly', 'yuv_advance_tournaments_cron');
    }
}

// Hook cron event to tournament advancement
add_action('yuv_advance_tournaments_cron', 'yuv_run_tournament_advancement');
function yuv_run_tournament_advancement() {
    $manager = new YUV_Tournament_Manager();
    $manager->advance_all_tournaments();
    
    // Log cron execution
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('YUV Tournament Cron: Executed at ' . current_time('mysql'));
    }
}

// Manual cron trigger (for testing)
add_action('wp_ajax_yuv_trigger_tournament_cron', 'yuv_trigger_tournament_cron_ajax');
function yuv_trigger_tournament_cron_ajax() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    do_action('yuv_advance_tournaments_cron');
    wp_send_json_success(['message' => 'Cron triggered manually']);
}
