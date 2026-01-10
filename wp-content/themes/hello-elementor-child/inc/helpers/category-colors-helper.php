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
 * Get unified category color by term ID
 * 
 * First checks if the term has a color in voting_list_category.
 * If not (e.g., quiz_category), tries to find a matching slug in voting_list_category.
 *
 * @param int $term_id The term ID (from any taxonomy)
 * @param string $default Default color if none found
 * @return string Hex color code
 */
function ygv_get_unified_category_color($term_id, $default = '#6db24a') {
    if (!$term_id) {
        return $default;
    }
    
    // Get the term to find its slug and taxonomy
    $term = get_term($term_id);
    if (!$term || is_wp_error($term)) {
        return $default;
    }
    
    // If it's already a voting_list_category, use its color directly
    if ($term->taxonomy === 'voting_list_category') {
        $color = get_term_meta($term_id, 'category_color', true);
        return $color ?: $default;
    }
    
    // For quiz_category or other taxonomies, try to match by slug to voting_list_category
    $voting_term = get_term_by('slug', $term->slug, 'voting_list_category');
    if ($voting_term && !is_wp_error($voting_term)) {
        $color = get_term_meta($voting_term->term_id, 'category_color', true);
        if ($color) {
            return $color;
        }
    }
    
    // Also try matching by name (case-insensitive)
    $voting_terms = get_terms([
        'taxonomy' => 'voting_list_category',
        'hide_empty' => false,
        'name__like' => $term->name,
    ]);
    
    if (!empty($voting_terms) && !is_wp_error($voting_terms)) {
        foreach ($voting_terms as $vt) {
            if (strcasecmp($vt->name, $term->name) === 0) {
                $color = get_term_meta($vt->term_id, 'category_color', true);
                if ($color) {
                    return $color;
                }
            }
        }
    }
    
    // Fall back to quiz_category_color if available
    $quiz_color = get_term_meta($term_id, 'quiz_category_color', true);
    if ($quiz_color) {
        return $quiz_color;
    }
    
    return $default;
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
