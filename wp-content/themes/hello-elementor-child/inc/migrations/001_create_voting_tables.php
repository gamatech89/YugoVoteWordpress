<?php

/**
 * Migration: Create voting_list_item_relations and voting_list_votes tables
 */

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

// Table 1: voting_list_item_relations
$table1 = $wpdb->prefix . 'voting_list_item_relations';
$sql1 = "CREATE TABLE IF NOT EXISTS $table1 (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voting_list_id BIGINT UNSIGNED NOT NULL,
    voting_item_id BIGINT UNSIGNED NOT NULL,
    short_description TEXT NULL,
    long_description TEXT NULL,
    custom_image_url VARCHAR(255) NULL,
    url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_list_item (voting_list_id, voting_item_id)
) $charset_collate;";

// Table 2: voting_list_votes
$table2 = $wpdb->prefix . 'voting_list_votes';
$sql2 = "CREATE TABLE IF NOT EXISTS $table2 (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voting_list_id BIGINT UNSIGNED NOT NULL,
    voting_item_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    vote_value INT(2) NOT NULL,
    votes_limit INT(3) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (voting_list_id, voting_item_id, user_id, ip_address, vote_value)
) $charset_collate;";

// Execute both
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql1);
dbDelta($sql2);
