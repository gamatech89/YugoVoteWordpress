<?php

/**
 * Migration: Add 'url' column to voting_list_item_relations
 */

global $wpdb;

$table = $wpdb->prefix . 'voting_list_item_relations';
$column = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'url'");

if (empty($column)) {
    $wpdb->query("ALTER TABLE $table ADD COLUMN url VARCHAR(255) NULL AFTER custom_image_url");
}
