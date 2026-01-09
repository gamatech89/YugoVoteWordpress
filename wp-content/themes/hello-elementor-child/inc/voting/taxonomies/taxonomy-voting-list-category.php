<?php

function register_voting_list_category() {
    $labels = array(
        'name'              => 'Voting List Categories',
        'singular_name'     => 'Voting List Category',
        'search_items'      => 'Search Voting List Categories',
        'all_items'         => 'All Voting List Categories',
        'edit_item'         => 'Edit Voting List Category',
        'update_item'       => 'Update Voting List Category',
        'add_new_item'      => 'Add New Voting List Category',
        'new_item_name'     => 'New Voting List Category Name',
        'menu_name'         => 'Voting List Categories'
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
            'rewrite'           => array(
            'slug' => 'liste-za-glasanje',                 // Empty string means no base slug
            'with_front' => false,
            'hierarchical' => true
        ),
    );

    register_taxonomy('voting_list_category', array('voting_list'), $args);
}
add_action('init', 'register_voting_list_category');
