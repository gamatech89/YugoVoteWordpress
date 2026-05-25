<?php
/**
 * One-time backfill: ensure every voting list item has a pivot table row.
 * 
 * Triggered via admin AJAX action: wp_ajax_yuv_backfill_pivot
 * Call: POST /wp-admin/admin-ajax.php?action=yuv_backfill_pivot
 * 
 * Safe to run multiple times — only INSERTs missing rows.
 */

if (!defined('ABSPATH'))
    exit;

add_action('wp_ajax_yuv_backfill_pivot', 'yuv_backfill_pivot_rows');

function yuv_backfill_pivot_rows()
{
    // Only admins
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'voting_list_item_relations';

    // Get all published voting lists with their items
    $lists = get_posts([
        'post_type' => 'voting_list',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    $total_inserted = 0;
    $total_empty_fixed = 0;
    $lists_processed = 0;

    foreach ($lists as $list_id) {
        $item_ids = get_post_meta($list_id, '_voting_items', true);
        if (!is_array($item_ids) || empty($item_ids)) {
            continue;
        }

        $lists_processed++;
        $item_ids = array_map('intval', $item_ids);

        // Get existing pivot rows for this list
        $existing = $wpdb->get_col($wpdb->prepare(
            "SELECT voting_item_id FROM $table WHERE voting_list_id = %d",
            $list_id
        ));
        $existing = array_map('intval', $existing);

        // Insert missing rows
        $missing = array_diff($item_ids, $existing);
        foreach ($missing as $item_id) {
            $image_url = get_the_post_thumbnail_url($item_id, 'medium');

            $wpdb->insert($table, [
                'voting_list_id' => $list_id,
                'voting_item_id' => $item_id,
                'custom_image_url' => $image_url ?: null,
                'created_at' => current_time('mysql', 1),
                'updated_at' => current_time('mysql', 1),
            ]);
            $total_inserted++;
        }

        // Fix existing rows with empty custom_image_url (the '' bug)
        $empty_image_rows = $wpdb->get_col($wpdb->prepare(
            "SELECT voting_item_id FROM $table WHERE voting_list_id = %d AND (custom_image_url = '' OR custom_image_url IS NULL)",
            $list_id
        ));

        foreach ($empty_image_rows as $item_id) {
            $image_url = get_the_post_thumbnail_url(intval($item_id), 'medium');
            if ($image_url) {
                $wpdb->update(
                    $table,
                ['custom_image_url' => $image_url, 'updated_at' => current_time('mysql', 1)],
                ['voting_list_id' => $list_id, 'voting_item_id' => $item_id]
                );
                $total_empty_fixed++;
            }
        }
    }

    wp_send_json_success([
        'message' => "Backfill complete",
        'lists_processed' => $lists_processed,
        'rows_inserted' => $total_inserted,
        'empty_fixed' => $total_empty_fixed,
    ]);
}