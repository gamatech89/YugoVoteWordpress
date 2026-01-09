<?php
/**
 * Migration 006: Add expert bonus columns to voting_list_votes
 * 
 * Adds:
 * - base_vote_value: The original vote value user selected (1-10)
 * - expert_bonus: The bonus points added based on category expertise (0-4+)
 * 
 * The existing vote_value column becomes: base_vote_value + expert_bonus
 * 
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'voting_list_votes';

// Check if columns already exist
$columns = $wpdb->get_col("DESCRIBE {$table}", 0);

// Add base_vote_value column if not exists
if (!in_array('base_vote_value', $columns)) {
    $wpdb->query("ALTER TABLE {$table} ADD COLUMN base_vote_value SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER vote_value");
    
    // Copy existing vote_value to base_vote_value for backward compatibility
    $wpdb->query("UPDATE {$table} SET base_vote_value = vote_value WHERE base_vote_value = 0");
}

// Add expert_bonus column if not exists
if (!in_array('expert_bonus', $columns)) {
    $wpdb->query("ALTER TABLE {$table} ADD COLUMN expert_bonus SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER base_vote_value");
}

// Update the unique key to use base_vote_value instead of vote_value
// This is important because users select base values (1-10), not final values
$wpdb->query("ALTER TABLE {$table} DROP INDEX IF EXISTS unique_vote");
$wpdb->query("ALTER TABLE {$table} ADD UNIQUE KEY unique_vote (voting_list_id, voting_item_id, user_id, ip_address, base_vote_value)");

// Also add an index for faster lookups on base_vote_value
$indexes = $wpdb->get_results("SHOW INDEX FROM {$table} WHERE Key_name = 'idx_base_vote'");
if (empty($indexes)) {
    $wpdb->query("ALTER TABLE {$table} ADD INDEX idx_base_vote (voting_list_id, base_vote_value)");
}
