<?php

function register_voting_items_cpt() {
    $labels = array(
        'name'               => 'Voting Items',
        'singular_name'      => 'Voting Item',
        'menu_name'          => 'Voting Items',
        'add_new'            => 'Add New Item',
        'add_new_item'       => 'Add New Voting Item',
        'edit_item'          => 'Edit Voting Item',
        'new_item'           => 'New Voting Item',
        'view_item'          => 'View Voting Item',
        'search_items'       => 'Search Voting Items',
        'not_found'          => 'No voting items found',
        'not_found_in_trash' => 'No voting items found in trash'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 27,
        'menu_icon'          => 'dashicons-star-filled',
        'supports'           => array('title', 'editor', 'thumbnail'),
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
  
        'rewrite'            => array( 
            'slug'       => 'predmet-glasanja', 
            'with_front' => false 
        )
    
    );

    register_post_type('voting_items', $args);
}
add_action('init', 'register_voting_items_cpt');