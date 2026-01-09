<?php

function register_voting_item_category() {
    $labels = array(
        'name'              => 'Voting Item Categories',
        'singular_name'     => 'Voting Item Category',
        'search_items'      => 'Search Voting Item Categories',
        'all_items'         => 'All Voting Item Categories',
        'edit_item'         => 'Edit Voting Item Category',
        'update_item'       => 'Update Voting Item Category',
        'add_new_item'      => 'Add New Voting Item Category',
        'new_item_name'     => 'New Voting Item Category Name',
        'menu_name'         => 'Voting Item Categories'
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'voting-item-category'),
    );

    register_taxonomy('voting_item_category', array('voting_items'), $args);
}
add_action('init', 'register_voting_item_category');
