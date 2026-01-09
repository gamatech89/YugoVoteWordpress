<?php

function register_user_levels_cpt() {
    $labels = array(
        'name'               => 'User Levels',
        'singular_name'      => 'User Level',
        'menu_name'          => 'User Levels',
        'add_new'            => 'Add New Level',
        'add_new_item'       => 'Add New User Level',
        'edit_item'          => 'Edit User Level',
        'new_item'           => 'New User Level',
        'view_item'          => 'View User Level',
        'search_items'       => 'Search User Levels',
        'not_found'          => 'No user levels found',
        'not_found_in_trash' => 'No user levels found in trash'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 25,
        'menu_icon'          => 'dashicons-chart-bar',
        'supports'           => array('title'),
        'capability_type'    => 'post',
        'map_meta_cap'       => true
    );

    register_post_type('user_levels', $args);
}
add_action('init', 'register_user_levels_cpt');
