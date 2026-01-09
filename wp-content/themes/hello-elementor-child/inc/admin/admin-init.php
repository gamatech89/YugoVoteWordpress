<?php
/**
 * Admin Functionality Initializer
 *
 * Loads all necessary files for custom admin features, scripts,
 * AJAX handlers, menu pages, and tools.
 *
 * @package HelloElementorChild
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit();
}

// Define the base path for the admin includes for cleaner require_once statements
$admin_inc_path = get_stylesheet_directory() . '/inc/admin/';

// Load Admin Scripts & Styles (this file likely contains the admin_enqueue_scripts hook)
if (file_exists($admin_inc_path . 'admin-scripts.php')) {
    require_once $admin_inc_path . 'admin-scripts.php';
}

// Load Admin-specific AJAX Handlers (if any, separate from front-end AJAX)
if (file_exists($admin_inc_path . 'admin-ajax.php')) {
    require_once $admin_inc_path . 'admin-ajax.php';
}

// Load Custom Admin Menu Pages
if (file_exists($admin_inc_path . 'admin-menu.php')) {
    require_once $admin_inc_path . 'admin-menu.php';
}

// Load Custom Admin Tools (like your recalculate vote cache tool)
if (file_exists($admin_inc_path . 'tools-recalculate-vote-cache.php')) {
    require_once $admin_inc_path . 'tools-recalculate-vote-cache.php';
}
// Load User wallet
if (file_exists($admin_inc_path . 'user-admin.php')) {
    require_once $admin_inc_path . 'user-admin.php';
}

// Load Elementor Tags
if (file_exists($admin_inc_path . 'elementor-tags.php')) {
    require_once $admin_inc_path . 'elementor-tags.php';
}

// Load Level & XP Settings
if (file_exists($admin_inc_path . 'level-settings.php')) {
    require_once $admin_inc_path . 'level-settings.php';
}

// Admin styles for quiz-related screens
add_action('admin_enqueue_scripts', function($hook) {
    $screen = get_current_screen();
    if (!$screen) return;

    $post_types = ['quiz', 'question', 'quiz_levels'];
    if (in_array($screen->post_type, $post_types, true)) {
        $style_path = get_stylesheet_directory() . '/admin-style.css';
        $version    = file_exists($style_path) ? filemtime($style_path) : HELLO_ELEMENTOR_CHILD_VERSION;

        wp_enqueue_style(
            'ygv-admin-style',
            get_stylesheet_directory_uri() . '/admin-style.css',
            [],
            $version
        );
    }
});

?>