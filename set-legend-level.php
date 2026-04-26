<?php
/**
 * WP-CLI eval-file: Set user to Legend level (40) in all parent categories.
 * Usage: wp eval-file set-legend-level.php
 */

$target_email = 'popsonsgm@gmail.com';
$target_level = 40;

// 1. Find user
$user = get_user_by('email', $target_email);
if (!$user) {
    WP_CLI::error("User not found: {$target_email}");
    return;
}
$user_id = (int) $user->ID;
WP_CLI::log("User found: ID={$user_id}, login={$user->user_login}");

// 2. Calculate XP needed for level 40 using same formula as ProgressService
$config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : [];
$xp_thresholds = $config['xp_thresholds'] ?? [
    1=>0, 2=>150, 3=>350, 4=>600, 5=>900, 6=>1250, 7=>1650, 8=>2100, 9=>2600, 10=>3200
];
$base_increment = $config['xp_per_level_after_10'] ?? 500;
$growth_rate    = ($config['xp_level_growth_rate'] ?? 5) / 100;
$max_level      = $config['max_level'] ?? 100;

$last_threshold = $xp_thresholds[10] ?? 3200;
$cur_increment  = (float) $base_increment;
for ($lvl = 11; $lvl <= $max_level; $lvl++) {
    $last_threshold      += (int) round($cur_increment);
    $xp_thresholds[$lvl] = $last_threshold;
    $cur_increment       *= (1 + $growth_rate);
}

$xp_for_level_40 = $xp_thresholds[$target_level] ?? 0;
WP_CLI::log("XP threshold for level {$target_level}: {$xp_for_level_40}");

// 3. Get all parent categories (parent = 0) from voting_list_category
$parent_terms = get_terms([
    'taxonomy'   => 'voting_list_category',
    'hide_empty' => false,
    'parent'     => 0,
]);

if (is_wp_error($parent_terms) || empty($parent_terms)) {
    WP_CLI::error("No parent categories found.");
    return;
}

WP_CLI::log("Found " . count($parent_terms) . " parent categories.");

// 4. Upsert level+XP in wp_ygv_user_category_progress for each category
global $wpdb;
$table = $wpdb->prefix . 'ygv_user_category_progress';
$now   = current_time('mysql', true);
$updated = 0;
$inserted = 0;

foreach ($parent_terms as $term) {
    $cat_id = (int) $term->term_id;

    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE user_id = %d AND category_term_id = %d",
        $user_id, $cat_id
    ));

    if ($existing) {
        // Only upgrade if current level < target
        $current_level = (int) $existing->level;
        if ($current_level >= $target_level) {
            WP_CLI::log("  [{$term->name}] already level {$current_level}, skipping.");
            continue;
        }
        $current_xp = (int) $existing->xp;
        $new_xp     = max($current_xp, $xp_for_level_40);
        $wpdb->update(
            $table,
            ['xp' => $new_xp, 'level' => $target_level, 'last_attempt_at' => $now],
            ['user_id' => $user_id, 'category_term_id' => $cat_id],
            ['%d', '%d', '%s'],
            ['%d', '%d']
        );
        WP_CLI::log("  [{$term->name}] updated: level {$current_level} → {$target_level}, xp {$current_xp} → {$new_xp}");
        $updated++;
    } else {
        $wpdb->insert(
            $table,
            [
                'user_id'          => $user_id,
                'category_term_id' => $cat_id,
                'xp'               => $xp_for_level_40,
                'level'            => $target_level,
                'streak'           => 0,
                'last_attempt_at'  => $now,
            ],
            ['%d', '%d', '%d', '%d', '%d', '%s']
        );
        WP_CLI::log("  [{$term->name}] inserted: level {$target_level}, xp {$xp_for_level_40}");
        $inserted++;
    }
}

// 5. Also update overall progress if overall level needs updating
$total_xp = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(xp), 0) FROM {$table} WHERE user_id = %d",
    $user_id
));
// Recalculate overall level using same thresholds
$new_overall_level = 1;
foreach ($xp_thresholds as $lvl => $need) {
    if ($lvl > $max_level) break;
    if ($total_xp >= $need) $new_overall_level = $lvl;
}
$overall_table = $wpdb->prefix . 'ygv_user_overall_progress';
$over_exists = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$overall_table} WHERE user_id = %d", $user_id
));
if ($over_exists) {
    $wpdb->update(
        $overall_table,
        ['overall_xp' => $total_xp, 'overall_level' => $new_overall_level, 'last_updated' => $now],
        ['user_id' => $user_id],
        ['%d', '%d', '%s'],
        ['%d']
    );
} else {
    $wpdb->insert(
        $overall_table,
        ['user_id' => $user_id, 'overall_xp' => $total_xp, 'overall_level' => $new_overall_level, 'last_updated' => $now],
        ['%d', '%d', '%d', '%s']
    );
}

WP_CLI::success("Done! {$inserted} inserted, {$updated} updated. Overall level: {$new_overall_level}, total XP: {$total_xp}");
