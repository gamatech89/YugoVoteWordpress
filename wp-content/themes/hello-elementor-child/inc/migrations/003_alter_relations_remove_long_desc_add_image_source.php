<?php
/**
 * Migration: Modify 'voting_list_item_relations' table.
 * - Remove 'long_description' column.
 * - Add 'custom_image_source' column.
 */

global $wpdb;
$table_name = $wpdb->prefix . 'voting_list_item_relations';
$charset_collate = $wpdb->get_charset_collate();

// 1. Remove 'long_description' column if it exists
// We need to check if the column exists before trying to drop it.
$column_exists = $wpdb->get_results($wpdb->prepare(
    "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
    'long_description'
));

if (!empty($column_exists)) {
    $wpdb->query("ALTER TABLE `{$table_name}` DROP COLUMN `long_description`");
    // Optional: Log success or handle potential errors from $wpdb->last_error
    // error_log("Dropped column 'long_description' from {$table_name}");
}

// 2. Add 'custom_image_source' column if it doesn't exist
// Let's place it after 'custom_image_url' for logical grouping.
$column_exists = $wpdb->get_results($wpdb->prepare(
    "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
    'custom_image_source'
));

if (empty($column_exists)) {
    // Check if custom_image_url exists to place the new column after it, otherwise add at end.
    $custom_image_url_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
        'custom_image_url'
    ));
    if (!empty($custom_image_url_exists)) {
        $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `custom_image_source` VARCHAR(255) NULL DEFAULT NULL AFTER `custom_image_url`");
    } else {
        // Fallback if custom_image_url doesn't exist for some reason, just add the column
        $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `custom_image_source` VARCHAR(255) NULL DEFAULT NULL");
    }
    // Optional: Log success or handle potential errors
    // error_log("Added column 'custom_image_source' to {$table_name}");
}

// Note: We are not using dbDelta() here because it doesn't handle DROP COLUMN.
// These ALTER TABLE queries directly modify the schema.
?>