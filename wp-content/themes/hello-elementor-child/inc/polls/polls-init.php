<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/cpts/poll-cpt.php';
require_once __DIR__ . '/meta/poll-meta.php';
require_once __DIR__ . '/api/poll-ajax.php';

require_once __DIR__ . '/admin/poll-admin.php'; 
require_once __DIR__ . '/polls-shortcode.php';

/**
 * Enqueue Poll Scripts
 */
function ygv_enqueue_poll_scripts() {
    wp_enqueue_script(
        'ygv-polls',
        get_stylesheet_directory_uri() . '/inc/polls/assets/polls.js',
        [],
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'ygv_enqueue_poll_scripts');