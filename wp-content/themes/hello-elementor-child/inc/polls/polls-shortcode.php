<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: Arhiva Starih Anketa
 * Usage: [voting_poll_archive]
 */
function cs_poll_archive_shortcode($atts) {
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;

    $args = [
        'post_type'      => 'voting_poll',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'paged'          => $paged,
        'offset'         => 1, // Preskoči najnoviju
    ];

    $query = new WP_Query($args);

    if (!$query->have_posts()) return '';

    // Učitaj Template
    ob_start();
    $template = get_stylesheet_directory() . '/inc/polls/templates/archive-poll.php';
    if (file_exists($template)) {
        include $template;
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('voting_poll_archive', 'cs_poll_archive_shortcode');


/**
 * Shortcode: Anketa Dana (Najnovija)
 * Usage: [voting_poll_daily]
 */
function cs_poll_latest_shortcode($atts) {
    // Uzmi najnoviju
    $args = [
        'post_type'      => 'voting_poll',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $query = new WP_Query($args);

    if (!$query->have_posts()) return '';

    $query->the_post();
    
    // Pripremi podatke za template
    $poll_id = get_the_ID();
    $title = get_the_title();
    $image_url = get_the_post_thumbnail_url($poll_id, 'large');
    $description = get_the_content();

    // Učitaj Template
    ob_start();
    $template = get_stylesheet_directory() . '/inc/polls/templates/daily-poll.php';
    if (file_exists($template)) {
        include $template;
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('voting_poll_daily', 'cs_poll_latest_shortcode');


/**
 * Shortcode: Pojedinačna Anketa
 * Usage: [voting_poll id="123"]
 */
function cs_poll_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $poll_id = intval($atts['id']);
    if (!$poll_id) return '';

    // Pripremi podatke za template
    $question = get_the_title($poll_id);
    $answers = get_post_meta($poll_id, '_cs_poll_answers', true) ?: [];
    $total = (int) get_post_meta($poll_id, '_cs_poll_total_votes', true);
    
    $has_voted = function_exists('cs_has_user_voted_poll') ? cs_has_user_voted_poll($poll_id) : isset($_COOKIE['cs_poll_' . $poll_id]);

    // Učitaj Template
    ob_start();
    $template = get_stylesheet_directory() . '/inc/polls/templates/single-poll.php';
    if (file_exists($template)) {
        include $template;
    }
    return ob_get_clean();
}
add_shortcode('voting_poll', 'cs_poll_shortcode');