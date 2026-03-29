<?php

function register_vip_person_cpt() {
    $labels = array(
        'name'               => 'VIP Persons',
        'singular_name'      => 'VIP Person',
        'menu_name'          => 'VIP Persons',
        'add_new'            => 'Add New VIP Person',
        'add_new_item'       => 'Add New VIP Person',
        'edit_item'          => 'Edit VIP Person',
        'new_item'           => 'New VIP Person',
        'view_item'          => 'View VIP Person',
        'search_items'       => 'Search VIP Persons',
        'not_found'          => 'No VIP persons found',
        'not_found_in_trash' => 'No VIP persons found in trash'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 28,
        'menu_icon'          => 'dashicons-star-filled',
        'supports'           => array('title', 'editor', 'thumbnail'),
        'capability_type'    => 'post',
        'map_meta_cap'       => true,

        'rewrite'            => array(
            'slug'       => 'vip',
            'with_front' => false
        ),
        'has_archive'        => 'vip-osobe'
    );

    register_post_type('vip_person', $args);
}
add_action('init', 'register_vip_person_cpt');
