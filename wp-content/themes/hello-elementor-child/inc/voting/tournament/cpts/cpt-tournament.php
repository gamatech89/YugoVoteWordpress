<?php
/**
 * Custom Post Type: Tournament
 * Manages automated voting tournaments with bracket progression
 */

if (!defined('ABSPATH')) exit;

function yuv_register_tournament_cpt() {
    $labels = [
        'name'               => 'Turniri',
        'singular_name'      => 'Turnir',
        'menu_name'          => 'Turniri',
        'add_new'            => 'Novi Turnir',
        'add_new_item'       => 'Dodaj novi turnir',
        'edit_item'          => 'Izmeni turnir',
        'new_item'           => 'Novi turnir',
        'view_item'          => 'Prikaži turnir',
        'search_items'       => 'Pretraži turnire',
        'not_found'          => 'Nema pronađenih turnira',
        'not_found_in_trash' => 'Nema turnira u korpi'
    ];

    $args = [
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => 'edit.php?post_type=voting_list',
        'menu_position'       => 27,
        'menu_icon'           => 'dashicons-awards',
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'supports'            => ['title', 'editor', 'thumbnail'],
        'has_archive'         => true,
        'rewrite'             => [
            'slug' => 'turnir',
            'with_front' => false
        ],
    ];

    register_post_type('yuv_tournament', $args);
}
add_action('init', 'yuv_register_tournament_cpt');

/**
 * Delete all associated matches when a tournament is deleted
 */
function yuv_delete_tournament_matches($post_id) {
    // Only run for tournaments
    if (get_post_type($post_id) !== 'yuv_tournament') {
        return;
    }

    global $wpdb;
    
    // Find all voting_list posts associated with this tournament
    $match_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_yuv_tournament_id' 
        AND meta_value = %d",
        $post_id
    ));

    // Force delete each match (bypass trash)
    foreach ($match_ids as $match_id) {
        wp_delete_post($match_id, true);
    }
}
add_action('before_delete_post', 'yuv_delete_tournament_matches');
add_action('trashed_post', 'yuv_delete_tournament_matches');
