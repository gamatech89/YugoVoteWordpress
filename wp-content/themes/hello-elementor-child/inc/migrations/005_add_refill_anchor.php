<?php
// inc/migrations/004_add_refill_anchor.php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'ygv_user_tokens';

// Add INT column if missing
$col = $wpdb->get_results($wpdb->prepare(
    "SHOW COLUMNS FROM {$table} LIKE %s", 'refill_anchor'
));
if (!$col) {
    $wpdb->query("ALTER TABLE {$table} ADD COLUMN refill_anchor INT NOT NULL DEFAULT 0 AFTER regen_interval_minutes");
    // Initialize anchor: use existing updated_at if present, else now()
    $has_updated = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM {$table} LIKE %s", 'updated_at'
    ));
    if ($has_updated) {
        // Convert updated_at (UTC) to UNIX timestamp
        $rows = $wpdb->get_results("SELECT user_id, UNIX_TIMESTAMP(updated_at) AS ts FROM {$table}", ARRAY_A);
        foreach ($rows as $r) {
            $ts = (int)$r['ts'];
            if ($ts <= 0) $ts = time();
            $wpdb->update($table, ['refill_anchor'=>$ts], ['user_id'=>(int)$r['user_id']], ['%d'], ['%d']);
        }
    } else {
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET refill_anchor = %d WHERE refill_anchor = 0",
            time()
        ));
    }
}
