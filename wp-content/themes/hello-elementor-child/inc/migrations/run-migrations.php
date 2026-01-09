<?php
/**
 * Run all DB migrations for voting system
 */
function run_voting_migrations() {
    global $wpdb;
    $migrations_table = $wpdb->prefix . 'voting_migrations';

    // Create the migrations tracking table if it doesn't exist
    $wpdb->query("CREATE TABLE IF NOT EXISTS `{$migrations_table}` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) {$wpdb->get_charset_collate()}"); 

    $applied = $wpdb->get_col("SELECT migration FROM `{$migrations_table}`");

    $migrations_path = get_stylesheet_directory() . '/inc/migrations/';
    $files = [
        '001_create_voting_tables.php',
        '002_add_url_to_pivot.php',
        '003_alter_relations_remove_long_desc_add_image_source.php',
        '004_create_quiz_core_tables.php',
        '005_add_refill_anchor.php'
    ];

    foreach ($files as $filename) {
        $file = $migrations_path . $filename;
        if (!in_array($filename, $applied) && file_exists($file)) {
            // It's good practice to include WordPress upgrade functions if migrations might use dbDelta or other schema functions
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            include $file; // Your migration file will use $wpdb global
            
            // Check for errors after including the migration file (optional but good)
            if ($wpdb->last_error) {
                error_log("WPDB Error after running migration {$filename}: " . $wpdb->last_error);
                // Decide if you want to stop further migrations or just log
            } else {
                $wpdb->insert($migrations_table, ['migration' => $filename]);
            }
        }
    }
}
add_action('after_setup_theme', 'run_voting_migrations', 20); // Added priority 20 to run a bit later if needed