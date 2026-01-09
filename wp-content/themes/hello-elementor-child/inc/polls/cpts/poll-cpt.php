<?php
if (!defined('ABSPATH')) exit;

function cs_register_poll_cpt() {
    register_post_type('voting_poll', [
        'labels' => [
            'name'               => 'Ankete',
            'singular_name'      => 'Anketa',
            'add_new'            => 'Nova Anketa',
            'add_new_item'       => 'Dodaj novu anketu',
            'edit_item'          => 'Izmeni anketu',
            'menu_name'          => 'Ankete',
            'all_items'          => 'Sve Ankete',
        ],
        'public'              => true,
        'has_archive'         => false,
        'menu_icon'           => 'dashicons-chart-pie',
        'rewrite'             => ['slug' => 'anketa'],
    
        'supports'            => ['title', 'editor', 'thumbnail'], 
  
        'show_in_rest'        => false, 
    ]);
}
add_action('init', 'cs_register_poll_cpt');