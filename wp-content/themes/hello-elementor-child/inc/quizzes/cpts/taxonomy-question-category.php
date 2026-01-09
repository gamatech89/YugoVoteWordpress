<?php
/**
 * Register Custom Taxonomy: Question Category
 */
function register_question_category_taxonomy() {
    $labels = [
        'name'              => 'Question Categories',
        'singular_name'     => 'Question Category',
        'search_items'      => 'Search Categories',
        'all_items'         => 'All Categories',
        'edit_item'         => 'Edit Category',
        'update_item'       => 'Update Category',
        'add_new_item'      => 'Add New Category',
        'new_item_name'     => 'New Category Name',
        'menu_name'         => 'Question Categories'
    ];

    $args = [
        'label'             => 'Question Categories',
        'labels'            => $labels,
        'public'            => true,
        'hierarchical'      => true, 
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'question-category'],
    ];

    register_taxonomy('question_category', ['question'], $args);
}
add_action('init', 'register_question_category_taxonomy');
