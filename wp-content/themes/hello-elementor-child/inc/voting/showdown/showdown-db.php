<?php
/**
 * Showdown Database Schema
 * Creates the showdown_sessions table
 */

if (!defined('ABSPATH'))
    exit;

function yuv_showdown_create_tables()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'yuv_showdown_sessions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        showdown_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED DEFAULT NULL,
        ip_address VARCHAR(100) NOT NULL DEFAULT '',
        results_json LONGTEXT NOT NULL,
        winner_item_id BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY showdown_id (showdown_id),
        KEY user_id (user_id),
        KEY showdown_user (showdown_id, user_id),
        KEY showdown_ip (showdown_id, ip_address)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Run on theme switch / activation
add_action('after_switch_theme', 'yuv_showdown_create_tables');

// Also run once if table doesn't exist
add_action('init', function () {
    global $wpdb;
    $table_name = $wpdb->prefix . 'yuv_showdown_sessions';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        yuv_showdown_create_tables();
    }
}, 1);