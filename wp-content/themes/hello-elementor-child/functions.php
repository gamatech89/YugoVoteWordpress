<?php
/**
 * Theme functions and definitions.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
    exit();
}

define('HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0');

// **** LOAD THEME CONFIGURATION CONSTANTS ****
if (file_exists(get_stylesheet_directory() . '/inc/config.php')) {
    require_once get_stylesheet_directory() . '/inc/config.php';
}

require_once ABSPATH . 'wp-admin/includes/upgrade.php';


// Enqueue main styles
function hello_elementor_child_enqueue_scripts() {
    

    wp_enqueue_style(
        'category-colors',
        get_stylesheet_directory_uri() . '/css/category-colors.css',
        ['hello-elementor-theme-style'],
        HELLO_ELEMENTOR_CHILD_VERSION
    );

    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['hello-elementor-theme-style'],
        HELLO_ELEMENTOR_CHILD_VERSION
    );

	    wp_enqueue_script(
        'hello-elementor-main-script',
        get_stylesheet_directory_uri() . '/js/app.js',
        ['jquery'],
        HELLO_ELEMENTOR_CHILD_VERSION,
        true
    );

}
add_action('wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts');


// Load Custom Features
require_once get_stylesheet_directory() . '/inc/init.php';




