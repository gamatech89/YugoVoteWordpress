<?php
/**
 * Admin Columns & Filters for Voting Lists and Voting Items
 * 
 * Handles:
 * - Voting List columns (Featured status)
 * - Voting List filters (Category, ID search)
 * - Voting Item columns (Vote count, Score)
 * - Voting Item filters (Category)
 * - Quick edit for featured status
 * 
 * @package YugoVote
 */

if (!defined('ABSPATH')) {
    exit();
}

/* --------------------------------------------------------------------------
 * VOTING LISTS (CPT: voting_list)
 * -------------------------------------------------------------------------- */

// Filter dropdown: Category
if (!function_exists('cs_filter_voting_lists_by_category')) {
    function cs_filter_voting_lists_by_category($post_type) {
        if ($post_type !== 'voting_list') return;

        $taxonomy = 'voting_list_category';
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
    }
}
add_action('restrict_manage_posts', 'cs_filter_voting_lists_by_category');

// Query: category filter + ID search (via search box or cs_lookup_id)
if (!function_exists('cs_filter_voting_lists_query')) {
    function cs_filter_voting_lists_query($query) {
        global $pagenow;

        if (!is_admin() || $pagenow !== 'edit.php' || !$query->is_main_query()) return;
        if ($query->get('post_type') !== 'voting_list') return;

        // Custom param from metabox button: &cs_lookup_id=123
        $lookup_id = isset($_GET['cs_lookup_id']) ? absint($_GET['cs_lookup_id']) : 0;
        if ($lookup_id) {
            $query->set('post__in', [$lookup_id]);
            $query->set('s', '');
            $query->set('orderby', 'post__in');
        } else {
            // Numeric search typed into the standard search box → treat as ID
            $s = isset($_GET['s']) ? trim((string) wp_unslash($_GET['s'])) : '';
            if ($s !== '' && ctype_digit($s)) {
                $query->set('post__in', [(int) $s]);
                $query->set('s', '');
                $query->set('orderby', 'post__in');
            }
        }

        // Category filter stays intact
        if (!empty($_GET['voting_list_category'])) {
            $tax_query   = (array) $query->get('tax_query');
            $tax_query[] = [
                'taxonomy' => 'voting_list_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field(wp_unslash($_GET['voting_list_category'])),
            ];
            $query->set('tax_query', $tax_query);
        }
    }
}
add_filter('pre_get_posts', 'cs_filter_voting_lists_query');

// Columns: Featured + quick edit
if (!function_exists('cs_add_voting_list_columns')) {
    function cs_add_voting_list_columns($columns) {
        $columns['is_featured'] = 'Featured';
        return $columns;
    }
}
add_filter('manage_voting_list_posts_columns', 'cs_add_voting_list_columns');

if (!function_exists('cs_populate_voting_list_columns')) {
    function cs_populate_voting_list_columns($column, $post_id) {
        if ($column === 'is_featured') {
            $is_featured = get_post_meta($post_id, '_is_featured', true);
            echo $is_featured ? '✅ Yes' : '—';
        }
    }
}
add_action('manage_voting_list_posts_custom_column', 'cs_populate_voting_list_columns', 10, 2);

if (!function_exists('cs_add_quick_edit_featured_checkbox')) {
    function cs_add_quick_edit_featured_checkbox($column_name, $post_type) {
        if ($post_type !== 'voting_list' || $column_name !== 'is_featured') return; ?>
        <fieldset class="inline-edit-col-left">
            <div class="inline-edit-col">
                <label class="alignleft">
                    <input type="checkbox" name="voting_list_is_featured" value="1">
                    <span class="checkbox-title"><?php esc_html_e('Featured', 'your-text-domain'); ?></span>
                </label>
            </div>
        </fieldset>
    <?php }
}
add_action('quick_edit_custom_box', 'cs_add_quick_edit_featured_checkbox', 10, 2);

if (!function_exists('cs_save_quick_edit_featured_status')) {
    function cs_save_quick_edit_featured_status($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (get_post_type($post_id) !== 'voting_list') return;

        if (isset($_POST['voting_list_is_featured'])) {
            update_post_meta($post_id, '_is_featured', '1');
        } else {
            delete_post_meta($post_id, '_is_featured');
        }
    }
}
add_action('save_post', 'cs_save_quick_edit_featured_status');


/* --------------------------------------------------------------------------
 * VOTING ITEMS (CPT: voting_items)
 * -------------------------------------------------------------------------- */

// Filter dropdown: Item Category
if (!function_exists('cs_filter_voting_items_by_category')) {
    function cs_filter_voting_items_by_category($post_type) {
        if ($post_type !== 'voting_items') return;

        $taxonomy = 'voting_item_category';
        $selected = isset($_GET[$taxonomy]) ? sanitize_text_field(wp_unslash($_GET[$taxonomy])) : '';
        $terms    = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);

        echo '<select name="' . esc_attr($taxonomy) . '" class="postform">';
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
    }
}
add_action('restrict_manage_posts', 'cs_filter_voting_items_by_category');

// Apply category filter
if (!function_exists('cs_filter_voting_items_query')) {
    function cs_filter_voting_items_query($query) {
        global $pagenow;

        if (!is_admin() || $pagenow !== 'edit.php' || !$query->is_main_query()) return;
        if ($query->get('post_type') !== 'voting_items') return;

        if (!empty($_GET['voting_item_category'])) {
            $query->set('tax_query', [
                [
                    'taxonomy' => 'voting_item_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field(wp_unslash($_GET['voting_item_category'])),
                ],
            ]);
        }
    }
}
add_filter('pre_get_posts', 'cs_filter_voting_items_query');

// Columns: vote_count / vote_score + sorting
if (!function_exists('cs_add_voting_item_columns')) {
    function cs_add_voting_item_columns($columns) {
        $columns['vote_count'] = 'Votes';
        $columns['vote_score'] = 'Score';
        return $columns;
    }
}
add_filter('manage_voting_items_posts_columns', 'cs_add_voting_item_columns');

if (!function_exists('cs_make_voting_item_columns_sortable')) {
    function cs_make_voting_item_columns_sortable($columns) {
        $columns['vote_count'] = 'vote_count_cache';
        $columns['vote_score'] = 'vote_score_cache';
        return $columns;
    }
}
add_filter('manage_edit-voting_items_sortable_columns', 'cs_make_voting_item_columns_sortable');

if (!function_exists('cs_handle_voting_item_orderby')) {
    function cs_handle_voting_item_orderby($query) {
        if (!is_admin() || !$query->is_main_query()) return;

        $orderby = $query->get('orderby');
        if ($orderby === 'vote_count_cache') {
            $query->set('meta_key', '_vote_count_cache');
            $query->set('orderby', 'meta_value_num');
        } elseif ($orderby === 'vote_score_cache') {
            $query->set('meta_key', '_vote_score_cache');
            $query->set('orderby', 'meta_value_num');
        }
    }
}
add_action('pre_get_posts', 'cs_handle_voting_item_orderby');

if (!function_exists('cs_populate_voting_item_vote_columns')) {
    function cs_populate_voting_item_vote_columns($column, $post_id) {
        if ($column === 'vote_count') {
            echo esc_html(get_post_meta($post_id, '_vote_count_cache', true) ?: '0');
        } elseif ($column === 'vote_score') {
            echo esc_html(get_post_meta($post_id, '_vote_score_cache', true) ?: '0');
        }
    }
}
add_action('manage_voting_items_posts_custom_column', 'cs_populate_voting_item_vote_columns', 10, 2);
