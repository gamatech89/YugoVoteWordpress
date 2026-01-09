<?php

function regenerate_category_color_css() {
    $terms = get_terms([
        'taxonomy' => 'voting_list_category',
        'hide_empty' => false,
    ]);

    if (empty($terms) || is_wp_error($terms)) return;

    $css = ":root {\n";

    foreach ($terms as $term) {
        $color = get_term_meta($term->term_id, 'category_color', true);
        if ($color) {
            $slug = sanitize_title($term->slug);
            $css .= "  --category-color-{$slug}: {$color};\n";
        }
    }

    $css .= "}\n\n";

    // Add utility classes
    foreach ($terms as $term) {
        $color = get_term_meta($term->term_id, 'category_color', true);
        if ($color) {
            $slug = sanitize_title($term->slug);

            $css .= ".cs-bg--{$slug} { background-color: var(--category-color-{$slug}); }\n";
            $css .= ".cs-color--{$slug} { color: var(--category-color-{$slug}); }\n";
            $css .= ".cs-border--{$slug} { border-color: var(--category-color-{$slug}); }\n";

            // Hover versions
            $css .= ".cs-bg-hover--{$slug}:hover { background-color: var(--category-color-{$slug}); }\n";
            $css .= ".cs-color-hover--{$slug}:hover { color: var(--category-color-{$slug}); }\n";
        }
    }

    $file_path = get_stylesheet_directory() . '/css/category-colors.css';
    file_put_contents($file_path, $css);
}

add_action('created_voting_list_category', 'regenerate_category_color_css');
add_action('edited_voting_list_category', 'regenerate_category_color_css');
