<?php
/**
 * Custom Header
 * 
 * Renders custom PHP header for non-Elementor pages,
 * falls back to Elementor header for pages that need it (like homepage)
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

// Check if we should use custom header
// Use custom header for: category pages, single lists, single items, quiz archive, account pages
$use_custom_header = false;

// Check template file being used
$template = get_page_template_slug();

// Post types that should use custom header
$custom_header_post_types = ['voting_list', 'voting_items', 'quiz'];

// Check various conditions for custom header
if (is_tax('voting_list_category')) {
    $use_custom_header = true;
} elseif (is_singular($custom_header_post_types)) {
    $use_custom_header = true;
} elseif (is_post_type_archive('quiz')) {
    $use_custom_header = true;
} elseif (is_page()) {
    // Check for our custom page templates
    $page_slug = get_post_field('post_name', get_the_ID());
    $custom_pages = ['moj-nalog', 'prijava', 'registracija', 'kompletiranje-naloga'];
    if (in_array($page_slug, $custom_pages)) {
        $use_custom_header = true;
    }
}

// Allow filtering
$use_custom_header = apply_filters('ygv_use_custom_header', $use_custom_header);

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if ($use_custom_header): ?>
    <?php 
    // Enqueue header styles
    wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], HELLO_ELEMENTOR_CHILD_VERSION);
    
    // Load custom header
    get_template_part('template-parts/header/header', 'custom'); 
    ?>
<?php else: ?>
    <?php 
    // Let Elementor handle the header
    if (!function_exists('elementor_theme_do_location') || !elementor_theme_do_location('header')) {
        // Fallback: load custom header if Elementor header not set
        wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], HELLO_ELEMENTOR_CHILD_VERSION);
        get_template_part('template-parts/header/header', 'custom');
    }
    ?>
<?php endif; ?>
