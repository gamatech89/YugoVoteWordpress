<?php
/**
 * Showdown Database Schema
 * Creates the showdown_sessions table
 */

if (!defined('ABSPATH'))
    exit;

// v2: kolona guest_token (gost se prepoznaje po kolačiću, ne po IP-u)
define('YUV_SHOWDOWN_DB_VERSION', 2);

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
        guest_token CHAR(32) NOT NULL DEFAULT '',
        results_json LONGTEXT NOT NULL,
        winner_item_id BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY showdown_id (showdown_id),
        KEY user_id (user_id),
        KEY showdown_user (showdown_id, user_id),
        KEY showdown_ip (showdown_id, ip_address),
        KEY guest_showdown (guest_token, showdown_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Verziju podižemo tek kad kolona stvarno postoji (npr. bez ALTER prava pokušaće ponovo sledeći put)
    $has_column = (bool) $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'guest_token'");
    if ($has_column) {
        update_option('yuv_showdown_db_version', YUV_SHOWDOWN_DB_VERSION, true); // autoload: bez upita po zahtevu
    }
}

// Run on theme switch / activation
add_action('after_switch_theme', 'yuv_showdown_create_tables');

// Also run once if table doesn't exist, or when the schema version changes (v2: guest_token)
add_action('init', function () {
    if ((int) get_option('yuv_showdown_db_version', 1) >= YUV_SHOWDOWN_DB_VERSION) {
        return;
    }
    yuv_showdown_create_tables();
}, 1);