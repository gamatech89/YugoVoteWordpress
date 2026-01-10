<?php
/**
 * Category Colors Helper Functions
 *
 * Provides utility functions to retrieve category colors for inline styling.
 * Works with the voting_list_category taxonomy.
 *
 * @package YugoVote
 */

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Get category color by term ID
 *
 * @param int $term_id The term ID
 * @param string $default Default color if none found
 * @return string Hex color code
 */
function ygv_get_category_color_by_term_id($term_id, $default = '#6db24a') {
    if (!$term_id) {
        return $default;
    }
    
    $color = get_term_meta($term_id, 'category_color', true);
    return $color ? $color : $default;
}

/**
 * Get category color by term slug
 *
 * @param string $slug The term slug
 * @param string $default Default color if none found
 * @return string Hex color code
 */
function ygv_get_category_color_by_slug($slug, $default = '#6db24a') {
    if (!$slug) {
        return $default;
    }
    
    $term = get_term_by('slug', $slug, 'voting_list_category');
    if (!$term || is_wp_error($term)) {
        return $default;
    }
    
    return ygv_get_category_color_by_term_id($term->term_id, $default);
}

/**
 * Get category color CSS variable name
 *
 * @param string $slug The term slug
 * @return string CSS variable reference
 */
function ygv_get_category_color_var($slug) {
    $sanitized = sanitize_title($slug);
    return "var(--category-color-{$sanitized}, #6db24a)";
}

/**
 * Output inline style attribute with category color
 *
 * @param int|string $term_id_or_slug Term ID or slug
 * @param string $property CSS property name (default: --cat-color)
 */
function ygv_category_color_style($term_id_or_slug, $property = '--cat-color') {
    if (is_numeric($term_id_or_slug)) {
        $color = ygv_get_category_color_by_term_id($term_id_or_slug);
    } else {
        $color = ygv_get_category_color_by_slug($term_id_or_slug);
    }
    
    echo 'style="' . esc_attr($property) . ': ' . esc_attr($color) . ';"';
}

/**
 * Get category color with contrast color for text
 *
 * @param int $term_id The term ID
 * @return array ['bg' => hex, 'text' => hex]
 */
function ygv_get_category_colors_with_contrast($term_id) {
    $bg = ygv_get_category_color_by_term_id($term_id);
    
    // Calculate luminance to determine text color
    $hex = ltrim($bg, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Using relative luminance formula
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    return [
        'bg' => $bg,
        'text' => $luminance > 0.5 ? '#1e293b' : '#ffffff'
    ];
}
