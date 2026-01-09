<?php
// Helper function to get term styling details
if (!function_exists('get_term_styling_details')) {
    /**
     * Gets the slug, name, and custom 'category_color' for a given term.
     *
     * @param int    $term_id  The ID of the term.
     * @param string $taxonomy The taxonomy of the term.
     * @return array An array containing 'id', 'slug', 'name', and 'color' (with a default).
     */
    function get_term_styling_details($term_id, $taxonomy) {
        $term = get_term($term_id, $taxonomy);
        if (is_wp_error($term) || !$term) {
            // Return default values if term is not found or there's an error
            return [
                'id'    => 0, 
                'slug'  => 'default', 
                'name'  => __('Default', 'your-text-domain'), 
                'color' => '#cccccc' // A neutral default color
            ];
        }

        $color = get_term_meta($term->term_id, 'category_color', true);
        return [
            'id'    => $term->term_id,
            'slug'  => $term->slug,
            'name'  => $term->name,
            'color' => !empty($color) ? $color : '#cccccc', // Fallback color if meta not set
        ];
    }
}

if (!function_exists('get_term_slug_or_default')) {
    /**
     * Safely gets the slug of a term by its ID and taxonomy.
     *
     * @param int    $term_id      The ID of the term.
     * @param string $taxonomy     The taxonomy of the term.
     * @param string $default_slug The slug to return if term is not found or has no slug.
     * @return string The term's slug or the default slug.
     */
    function get_term_slug_or_default($term_id, $taxonomy, $default_slug = 'default') {
        if (empty($term_id) || empty($taxonomy)) {
            return $default_slug;
        }
        $term = get_term(intval($term_id), $taxonomy);
        if ($term && !is_wp_error($term) && isset($term->slug)) {
            return $term->slug;
        }
        return $default_slug;
    }
}
