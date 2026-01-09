<?php
/**
 * Admin Columns & Filters for Questions (CPT: question)
 * 
 * Handles:
 * - Question Level column display
 * - Question category filter dropdown
 * - Question level filter dropdown
 * - Query filtering by taxonomy and meta
 * 
 * @package YugoVote
 */

if (!defined('ABSPATH')) {
    exit();
}

/* --------------------------------------------------------------------------
 * QUESTIONS (CPT: question)
 * -------------------------------------------------------------------------- */

// Columns: Question Level
if (!function_exists('cs_add_question_columns')) {
    function cs_add_question_columns($columns) {
        $columns['question_level'] = 'Question Level';
        return $columns;
    }
}
add_filter('manage_question_posts_columns', 'cs_add_question_columns');

if (!function_exists('cs_populate_question_columns')) {
    function cs_populate_question_columns($column, $post_id) {
        if ($column === 'question_level') {
            $level_id = get_post_meta($post_id, '_question_difficulty', true);
            echo $level_id ? esc_html(get_the_title($level_id)) : '—';
        }
    }
}
add_action('manage_question_posts_custom_column', 'cs_populate_question_columns', 10, 2);

// Filters: taxonomy + level
if (!function_exists('cs_filter_questions_by_taxonomies_and_meta')) {
    function cs_filter_questions_by_taxonomies_and_meta($post_type) {
        if ($post_type !== 'question') return;

        // Taxonomy filter
        $taxonomy = 'question_category';
        $selected = isset($_GET[$taxonomy]) ? sanitize_text_field(wp_unslash($_GET[$taxonomy])) : '';
        $terms    = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);

        echo '<select name="' . esc_attr($taxonomy) . '" id="' . esc_attr($taxonomy) . '" class="postform">';
        echo '<option value="">' . esc_html__('Filter by Category', 'your-text-domain') . '</option>';
        foreach ($terms as $term) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($term->slug),
                selected($selected, $term->slug, false),
                esc_html($term->name)
            );
        }
        echo '</select>';

        // Meta (level) filter
        $meta_key       = '_question_difficulty';
        $selected_level = isset($_GET[$meta_key]) ? sanitize_text_field(wp_unslash($_GET[$meta_key])) : '';
        $levels         = get_posts(['post_type' => 'quiz_levels', 'posts_per_page' => -1]);

        echo '<select name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '" class="postform">';
        echo '<option value="">' . esc_html__('Filter by Level', 'your-text-domain') . '</option>';
        foreach ($levels as $level) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($level->ID),
                selected($selected_level, $level->ID, false),
                esc_html($level->post_title)
            );
        }
        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'cs_filter_questions_by_taxonomies_and_meta');

// Apply filters to query
if (!function_exists('cs_filter_questions_query')) {
    function cs_filter_questions_query($query) {
        global $pagenow;
        if (!is_admin() || $pagenow !== 'edit.php' || !$query->is_main_query()) return;
        if ($query->get('post_type') !== 'question') return;

        if (!empty($_GET['question_category'])) {
            $query->set('tax_query', [
                [
                    'taxonomy' => 'question_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field(wp_unslash($_GET['question_category'])),
                ],
            ]);
        }

        if (!empty($_GET['_question_difficulty'])) {
            $query->set('meta_query', [
                [
                    'key'   => '_question_difficulty',
                    'value' => sanitize_text_field(wp_unslash($_GET['_question_difficulty'])),
                ],
            ]);
        }
    }
}
add_filter('pre_get_posts', 'cs_filter_questions_query');
