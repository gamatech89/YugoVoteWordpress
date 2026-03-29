<?php
/**
 * Custom Post Type: Showdown
 * King-of-the-hill style elimination voting
 */

if (!defined('ABSPATH'))
    exit;

function yuv_register_showdown_cpt()
{
    $labels = [
        'name' => 'Showdown',
        'singular_name' => 'Showdown',
        'menu_name' => 'Showdown',
        'add_new' => 'Novi Showdown',
        'add_new_item' => 'Dodaj novi Showdown',
        'edit_item' => 'Izmeni Showdown',
        'new_item' => 'Novi Showdown',
        'view_item' => 'Prikaži Showdown',
        'search_items' => 'Pretraži Showdown',
        'not_found' => 'Nema pronađenih Showdown-ova',
        'not_found_in_trash' => 'Nema Showdown-ova u korpi'
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => 'edit.php?post_type=voting_list',
        'menu_position' => 27,
        'menu_icon' => 'dashicons-superhero',
        'capability_type' => 'post',
        'hierarchical' => false,
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => true,
        'rewrite' => [
            'slug' => 'showdown',
            'with_front' => false
        ],
    ];

    register_post_type('yuv_showdown', $args);
}
add_action('init', 'yuv_register_showdown_cpt');

/**
 * Delete all associated sessions when a showdown is deleted
 */
function yuv_delete_showdown_sessions($post_id)
{
    if (get_post_type($post_id) !== 'yuv_showdown') {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'yuv_showdown_sessions';
    $wpdb->delete($table, ['showdown_id' => $post_id], ['%d']);
}
add_action('before_delete_post', 'yuv_delete_showdown_sessions');
add_action('trashed_post', 'yuv_delete_showdown_sessions');