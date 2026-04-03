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

/**
 * Render floating poll button on all pages
 */
function ygv_render_floating_poll() {
    // Skip on ankete archive page to avoid duplication
    if (is_page_template('page-ankete.php')) return;
    
    // Skip on all voting list pages (single, category, archive)
    if (is_singular('voting_list') || is_tax('voting_list_category') || is_post_type_archive('voting_list')) return;
    
    $template = get_stylesheet_directory() . '/inc/polls/templates/floating-poll.php';
    if (file_exists($template)) {
        include $template;
    }
}
add_action('wp_footer', 'ygv_render_floating_poll');