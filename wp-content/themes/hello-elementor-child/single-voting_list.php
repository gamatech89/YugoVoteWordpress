<?php
/**
 * Single Template for Voting List posts
 * 
 * This bypasses Elementor completely for better performance.
 * Place in theme root to auto-load for voting_list post type.
 * 
 * @package HelloElementorChild
 */

get_header();

// Enqueue the fullpage CSS
wp_enqueue_style(
    'voting-fullpage-css',
    get_stylesheet_directory_uri() . '/css/voting-fullpage.css',
    [],
    defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0'
);

// Check if this is a tournament match (redirect those)
$is_tournament = get_post_meta(get_the_ID(), '_is_tournament_match', true);
if ($is_tournament == '1') {
    wp_redirect(home_url('/'));
    exit;
}

// Set the voting list ID for the template
set_query_var('voting_list_id', get_the_ID());

// Load the fullpage template directly
get_template_part('inc/voting/templates/voting-list/voting-list-fullpage');

get_footer();
