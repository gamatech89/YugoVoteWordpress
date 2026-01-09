<?php

function register_voting_list_cpt() {
    $labels = array(
        'name'               => 'Voting Lists',
        'singular_name'      => 'Voting List',
        'menu_name'          => 'Voting Lists',
        'add_new'            => 'Add New List',
        'add_new_item'       => 'Add New Voting List',
        'edit_item'          => 'Edit Voting List',
        'new_item'           => 'New Voting List',
        'view_item'          => 'View Voting List',
        'search_items'       => 'Search Voting Lists',
        'not_found'          => 'No voting lists found',
        'not_found_in_trash' => 'No voting lists found in trash'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 26,
        'menu_icon'          => 'dashicons-list-view',
        'supports'           => array('title', 'editor', 'thumbnail'),
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
   
        'rewrite'            => array( 
            'slug'       => 'lista', 
            'with_front' => false 
        ),
        'has_archive'        => 'liste' 
    );

    register_post_type('voting_list', $args);
}
add_action('init', 'register_voting_list_cpt');