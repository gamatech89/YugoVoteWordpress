<?php
/**
 * Level Settings Admin Page
 * 
 * Configurable settings for:
 * - Level thresholds (XP required for each level)
 * - Level titles (Rookie, Fan, Expert, Master, Legend)
 * - Expert vote bonus per level tier
 * 
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

/**
 * Register the settings page under Settings menu
 */
function ygv_register_level_settings_page() {
    add_options_page(
        'Level & XP Settings',
        'Level Settings',
        'manage_options',
        'ygv-level-settings',
        'ygv_render_level_settings_page'
    );
}
add_action('admin_menu', 'ygv_register_level_settings_page');

/**
 * Register settings
 */
function ygv_register_level_settings() {
    register_setting('ygv_level_settings', 'ygv_level_config', [
        'type' => 'array',
        'sanitize_callback' => 'ygv_sanitize_level_config',
        'default' => ygv_get_default_level_config()
    ]);
}
add_action('admin_init', 'ygv_register_level_settings');

/**
 * Get default level configuration
 */
function ygv_get_default_level_config(): array {
    return [
        'tiers' => [
            [
                'min_level' => 1,
                'max_level' => 9,
                'title' => 'Rookie',
                'vote_bonus' => 0
            ],
            [
                'min_level' => 10,
                'max_level' => 19,
                'title' => 'Fan',
                'vote_bonus' => 1
            ],
            [
                'min_level' => 20,
                'max_level' => 29,
                'title' => 'Expert',
                'vote_bonus' => 2
            ],
            [
                'min_level' => 30,
                'max_level' => 39,
                'title' => 'Master',
                'vote_bonus' => 3
            ],
            [
                'min_level' => 40,
                'max_level' => 100,
                'title' => 'Legend',
                'vote_bonus' => 4
            ]
        ],
        // XP thresholds - XP required to reach each level
        // Levels 1-10 are set explicitly; beyond that the exponential formula takes over.
        'xp_thresholds' => [
            1  => 0,
            2  => 150,
            3  => 350,
            4  => 600,
            5  => 900,
            6  => 1250,
            7  => 1650,
            8  => 2100,
            9  => 2600,
            10 => 3200,
        ],
        // Base XP cost for the first step after level 10 (Level 10 → 11).
        // Each subsequent step costs (xp_level_growth_rate)% more than the previous.
        'xp_per_level_after_10' => 500,
        // Compound growth rate per level (%). 5 = each level costs 5% more than the last.
        'xp_level_growth_rate' => 5,
        'max_level' => 100,
        // List creation requirements
        'list_creation_category_level' => 10, // Category level required to create lists in that category
        'list_creation_global_level' => 10,   // Global level required to create any list
        // XP rewards
        'xp_per_vote' => 2,              // XP earned per vote cast
        'daily_vote_limit' => 50,        // Maximum votes per day that earn XP
        'xp_for_list_creation' => 50,    // XP earned when creating a list
    ];
}

/**
 * Get the current level config (with defaults)
 */
function ygv_get_level_config(): array {
    $saved = get_option('ygv_level_config', []);
    $defaults = ygv_get_default_level_config();
    
    return wp_parse_args($saved, $defaults);
}

/**
 * Sanitize level config on save
 */
function ygv_sanitize_level_config($input): array {
    $sanitized = [];
    
    // Sanitize tiers
    if (isset($input['tiers']) && is_array($input['tiers'])) {
        $sanitized['tiers'] = [];
        foreach ($input['tiers'] as $tier) {
            $sanitized['tiers'][] = [
                'min_level' => absint($tier['min_level'] ?? 1),
                'max_level' => absint($tier['max_level'] ?? 9),
                'title' => sanitize_text_field($tier['title'] ?? 'Rookie'),
                'vote_bonus' => absint($tier['vote_bonus'] ?? 0)
            ];
        }
    }
    
    // Sanitize XP thresholds
    if (isset($input['xp_thresholds']) && is_array($input['xp_thresholds'])) {
        $sanitized['xp_thresholds'] = array_map('absint', $input['xp_thresholds']);
    }
    
    // Sanitize other settings
    $sanitized['xp_per_level_after_10'] = absint($input['xp_per_level_after_10'] ?? 500);
    $sanitized['xp_level_growth_rate']  = max(0, min(50, (int)($input['xp_level_growth_rate'] ?? 5)));
    $sanitized['max_level'] = absint($input['max_level'] ?? 100);
    $sanitized['list_creation_category_level'] = absint($input['list_creation_category_level'] ?? 10);
    $sanitized['list_creation_global_level'] = absint($input['list_creation_global_level'] ?? 5);
    
    // Preserve XP reward settings
    $sanitized['xp_per_vote'] = absint($input['xp_per_vote'] ?? 2);
    $sanitized['daily_vote_limit'] = absint($input['daily_vote_limit'] ?? 50);
    $sanitized['xp_for_list_creation'] = absint($input['xp_for_list_creation'] ?? 50);
    
    return $sanitized;
}

/**
 * Render the settings page
 */
function ygv_render_level_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $config = ygv_get_level_config();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form action="options.php" method="post">
            <?php settings_fields('ygv_level_settings'); ?>
            
            <h2>Expert Vote Bonus Tiers</h2>
            <p class="description">Configure how category expertise affects voting power. Users get bonus points added to their votes based on their level in that category.</p>
            
            <table class="wp-list-table widefat fixed striped" style="max-width: 700px;">
                <thead>
                    <tr>
                        <th>Level Range</th>
                        <th>Title</th>
                        <th>Vote Bonus</th>
                    </tr>
                </thead>
                <tbody id="ygv-tiers-table">
                    <?php foreach ($config['tiers'] as $i => $tier): ?>
                    <tr>
                        <td>
                            <input type="number" name="ygv_level_config[tiers][<?php echo $i; ?>][min_level]" 
                                   value="<?php echo esc_attr($tier['min_level']); ?>" 
                                   min="1" max="100" style="width: 60px;"> 
                            - 
                            <input type="number" name="ygv_level_config[tiers][<?php echo $i; ?>][max_level]" 
                                   value="<?php echo esc_attr($tier['max_level']); ?>" 
                                   min="1" max="100" style="width: 60px;">
                        </td>
                        <td>
                            <input type="text" name="ygv_level_config[tiers][<?php echo $i; ?>][title]" 
                                   value="<?php echo esc_attr($tier['title']); ?>" 
                                   style="width: 120px;">
                        </td>
                        <td>
                            +<input type="number" name="ygv_level_config[tiers][<?php echo $i; ?>][vote_bonus]" 
                                   value="<?php echo esc_attr($tier['vote_bonus']); ?>" 
                                   min="0" max="10" style="width: 50px;">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p class="description" style="margin-top: 10px;">
                <strong>Example:</strong> A user at Level 25 in Sport is an "Expert" and gets +2 added to their votes on Sport lists.<br>
                If they vote "8", their actual vote value becomes 10 (8 + 2 bonus).
            </p>
            
            <hr style="margin: 30px 0;">
            
            <h2>List Creation Requirements</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Global Level Required</th>
                    <td>
                        <input type="number" name="ygv_level_config[list_creation_global_level]" 
                               value="<?php echo esc_attr($config['list_creation_global_level']); ?>" 
                               min="1" max="50" style="width: 60px;">
                        <p class="description">Minimum global level to create any list</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Category Level Required</th>
                    <td>
                        <input type="number" name="ygv_level_config[list_creation_category_level]" 
                               value="<?php echo esc_attr($config['list_creation_category_level']); ?>" 
                               min="1" max="50" style="width: 60px;">
                        <p class="description">Minimum category level to create lists in that specific category</p>
                    </td>
                </tr>
            </table>
            
            <hr style="margin: 30px 0;">
            
            <h2>XP Progression (Levels 11+)</h2>
            <p class="description">After level 10 the cost of each level grows exponentially. Each step costs a fixed percentage more than the previous one.</p>
            <table class="form-table">
                <tr>
                    <th scope="row">Base Increment (Level 10→11)</th>
                    <td>
                        <input type="number" name="ygv_level_config[xp_per_level_after_10]"
                               value="<?php echo esc_attr($config['xp_per_level_after_10'] ?? 500); ?>"
                               min="100" max="5000" step="50" style="width: 90px;"> XP
                        <p class="description">XP cost of the first step after level 10. Default: 500</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Growth Rate per Level</th>
                    <td>
                        <input type="number" name="ygv_level_config[xp_level_growth_rate]"
                               value="<?php echo esc_attr($config['xp_level_growth_rate'] ?? 5); ?>"
                               min="0" max="30" step="1" style="width: 70px;"> %
                        <p class="description">Each level costs this % more than the previous. 5% means Level 11→12 costs 5% more than 10→11, compounding. Default: 5%</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Maximum Level</th>
                    <td>
                        <input type="number" name="ygv_level_config[max_level]"
                               value="<?php echo esc_attr($config['max_level']); ?>"
                               min="50" max="200" style="width: 80px;">
                        <p class="description">Maximum achievable level (default: 100)</p>
                    </td>
                </tr>
            </table>
            
            <hr style="margin: 30px 0;">
            
            <h2>XP Rewards</h2>
            <p class="description">Configure how much XP users earn from different actions.</p>
            <table class="form-table">
                <tr>
                    <th scope="row">XP per Vote</th>
                    <td>
                        <input type="number" name="ygv_level_config[xp_per_vote]" 
                               value="<?php echo esc_attr($config['xp_per_vote'] ?? 2); ?>" 
                               min="1" max="20" style="width: 60px;">
                        <p class="description">XP earned per vote cast (default: 2)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Daily Vote XP Limit</th>
                    <td>
                        <input type="number" name="ygv_level_config[daily_vote_limit]" 
                               value="<?php echo esc_attr($config['daily_vote_limit'] ?? 50); ?>" 
                               min="10" max="200" style="width: 80px;">
                        <p class="description">Max votes per day that earn XP (default: 50)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">XP for List Creation</th>
                    <td>
                        <input type="number" name="ygv_level_config[xp_for_list_creation]" 
                               value="<?php echo esc_attr($config['xp_for_list_creation'] ?? 50); ?>" 
                               min="10" max="500" step="10" style="width: 80px;">
                        <p class="description">XP earned when creating a list (default: 50)</p>
                    </td>
                </tr>
            </table>
            
            <hr style="margin: 30px 0;">
            
            <h2>XP Thresholds (Levels 1-10)</h2>
            <p class="description">XP required to reach each level. After level 10, uses the "XP per Level" setting above.</p>
            
            <table class="wp-list-table widefat fixed striped" style="max-width: 400px;">
                <thead>
                    <tr>
                        <th>Level</th>
                        <th>XP Required</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($lvl = 1; $lvl <= 10; $lvl++): ?>
                    <tr>
                        <td>Level <?php echo $lvl; ?></td>
                        <td>
                            <input type="number" name="ygv_level_config[xp_thresholds][<?php echo $lvl; ?>]" 
                                   value="<?php echo esc_attr($config['xp_thresholds'][$lvl] ?? 0); ?>" 
                                   min="0" step="10" style="width: 100px;">
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            
            <?php submit_button('Save Settings'); ?>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <h2>Level Progression Preview</h2>
        <p class="description">
            Calculated from current settings. "Days (voting only)" assumes a user maxes out at
            <strong><?php echo esc_html(($config['xp_per_vote'] ?? 2) * ($config['daily_vote_limit'] ?? 50)); ?> XP/day</strong>
            (<?php echo esc_html($config['xp_per_vote'] ?? 2); ?> XP × <?php echo esc_html($config['daily_vote_limit'] ?? 50); ?> votes).
            Quizzes and streaks accelerate progress.
        </p>
        <?php
        // Build full threshold table using same logic as ProgressService::get_thresholds()
        $prev_thresholds  = $config['xp_thresholds'] ?? [];
        $base_inc         = (float)($config['xp_per_level_after_10'] ?? 500);
        $growth           = ($config['xp_level_growth_rate'] ?? 5) / 100;
        $max_lvl          = min((int)($config['max_level'] ?? 100), 60); // preview up to 60
        $xp_per_day       = ($config['xp_per_vote'] ?? 2) * ($config['daily_vote_limit'] ?? 50);

        $preview_thresholds = $prev_thresholds;
        $last_thr = $prev_thresholds[10] ?? 3200;
        $cur_inc  = $base_inc;
        for ($l = 11; $l <= $max_lvl; $l++) {
            $last_thr              += (int) round($cur_inc);
            $preview_thresholds[$l] = $last_thr;
            $cur_inc               *= (1 + $growth);
        }

        // Determine tier for a given level
        $get_tier = function(int $lvl) use ($config): string {
            foreach ($config['tiers'] as $tier) {
                if ($lvl >= $tier['min_level'] && $lvl <= $tier['max_level']) {
                    return $tier['title'];
                }
            }
            return '';
        };

        $tier_colors = ['Rookie' => '#aaa', 'Fan' => '#4CAF50', 'Expert' => '#2196F3', 'Master' => '#9C27B0', 'Legend' => '#FF9800'];
        ?>
        <table class="wp-list-table widefat fixed striped" style="max-width: 620px;">
            <thead>
                <tr>
                    <th style="width:70px;">Level</th>
                    <th style="width:120px;">Tier</th>
                    <th style="width:130px;">Total XP needed</th>
                    <th style="width:120px;">XP this level</th>
                    <th>Days (voting only)</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $prev_xp = 0;
            for ($l = 1; $l <= $max_lvl; $l++):
                $xp    = $preview_thresholds[$l] ?? 0;
                $delta = $l > 1 ? ($xp - $prev_xp) : 0;
                $days  = $xp_per_day > 0 ? ceil($xp / $xp_per_day) : '—';
                $tier  = $get_tier($l);
                $color = $tier_colors[$tier] ?? '#aaa';
                $prev_xp = $xp;
                // Highlight tier boundaries
                $tier_boundary = ($l === 1 || $get_tier($l) !== $get_tier($l - 1));
                $row_style = $tier_boundary ? 'border-top: 2px solid ' . $color . ';' : '';
            ?>
                <tr style="<?php echo esc_attr($row_style); ?>">
                    <td><strong><?php echo $l; ?></strong></td>
                    <td><span style="color:<?php echo esc_attr($color); ?>;font-weight:600;"><?php echo esc_html($tier); ?></span></td>
                    <td><?php echo number_format($xp); ?> XP</td>
                    <td><?php echo $l > 1 ? '+' . number_format($delta) : '—'; ?></td>
                    <td><?php echo is_numeric($days) ? $days . ' days' : $days; ?></td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
        <p class="description" style="margin-top: 8px;">Showing levels 1–<?php echo $max_lvl; ?>. Full cap is <?php echo esc_html($config['max_level'] ?? 100); ?>.</p>

        <h2 style="margin-top: 30px;">How XP is Earned</h2>
        <div style="background: #f9f9f9; padding: 16px 20px; border-radius: 5px; max-width: 560px;">
            <ul style="margin:0;">
                <li><strong>Quizzes:</strong> XP based on score (in quiz's category)</li>
                <li><strong>Voting:</strong> <?php echo esc_html($config['xp_per_vote'] ?? 2); ?> XP per vote, max <?php echo esc_html($config['daily_vote_limit'] ?? 50); ?> votes/day earning XP</li>
                <li><strong>List Creation:</strong> <?php echo esc_html($config['xp_for_list_creation'] ?? 50); ?> XP (goes to that list's category)</li>
                <li><strong>Streak bonuses:</strong> Up to +10 XP per vote for consecutive daily voting</li>
            </ul>
            <p style="margin-bottom:0;"><strong>Expert Vote Bonus:</strong> Your category level determines bonus vote weight.<br>
            Example: Level 25 in Sport (Expert) → +2 → voting "8" counts as 10.</p>
        </div>
    </div>
    <?php
}

/**
 * Helper function to get vote bonus for a user in a category
 * 
 * @param int $user_id
 * @param int $category_term_id Parent category term ID
 * @return array ['bonus' => int, 'title' => string, 'level' => int]
 */
function ygv_get_user_vote_bonus(int $user_id, int $category_term_id): array {
    global $wpdb;
    
    $config = ygv_get_level_config();
    
    // Get user's level in this category
    $table = $wpdb->prefix . 'ygv_user_category_progress';
    $level = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT level FROM {$table} WHERE user_id = %d AND category_term_id = %d",
        $user_id,
        $category_term_id
    ));
    
    // Default to level 1 if no record
    if (!$level) {
        $level = 1;
    }
    
    // Find matching tier
    $bonus = 0;
    $title = 'Rookie';
    
    foreach ($config['tiers'] as $tier) {
        if ($level >= $tier['min_level'] && $level <= $tier['max_level']) {
            $bonus = (int) $tier['vote_bonus'];
            $title = $tier['title'];
            break;
        }
    }
    
    return [
        'bonus' => $bonus,
        'title' => $title,
        'level' => $level
    ];
}

/**
 * Get the parent category term ID for a voting list
 * 
 * @param int $voting_list_id
 * @return int|null Parent category term ID or null
 */
function ygv_get_list_parent_category(int $voting_list_id): ?int {
    $terms = wp_get_object_terms($voting_list_id, 'voting_list_category', ['fields' => 'all']);
    
    if (empty($terms) || is_wp_error($terms)) {
        return null;
    }
    
    // Find the parent category (where parent = 0)
    foreach ($terms as $term) {
        if ($term->parent === 0) {
            return (int) $term->term_id;
        }
    }
    
    // If no direct parent, get the parent of the assigned term
    foreach ($terms as $term) {
        if ($term->parent > 0) {
            $parent = get_term($term->parent, 'voting_list_category');
            if ($parent && !is_wp_error($parent)) {
                // Check if this is the root parent
                if ($parent->parent === 0) {
                    return (int) $parent->term_id;
                }
                // Go up one more level if needed
                $grandparent = get_term($parent->parent, 'voting_list_category');
                if ($grandparent && !is_wp_error($grandparent)) {
                    return (int) $grandparent->term_id;
                }
            }
        }
    }
    
    return null;
}

/**
 * Check if user can create lists in a category
 * 
 * @param int $user_id
 * @param int|null $category_term_id Parent category term ID (null for any category)
 * @return array ['can_create' => bool, 'reason' => string|null]
 */
function ygv_can_user_create_list(int $user_id, ?int $category_term_id = null): array {
    global $wpdb;
    
    $config = ygv_get_level_config();
    
    // Check global level first
    $overall_table = $wpdb->prefix . 'ygv_user_overall_progress';
    $global_level = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT overall_level FROM {$overall_table} WHERE user_id = %d",
        $user_id
    )) ?: 1;
    
    if ($global_level < $config['list_creation_global_level']) {
        return [
            'can_create' => false,
            'reason' => sprintf(
                'You need Global Level %d to create lists. You are Level %d.',
                $config['list_creation_global_level'],
                $global_level
            )
        ];
    }
    
    // If specific category, check category level
    if ($category_term_id) {
        $cat_table = $wpdb->prefix . 'ygv_user_category_progress';
        $cat_level = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT level FROM {$cat_table} WHERE user_id = %d AND category_term_id = %d",
            $user_id,
            $category_term_id
        )) ?: 1;
        
        if ($cat_level < $config['list_creation_category_level']) {
            $term = get_term($category_term_id, 'voting_list_category');
            $cat_name = $term ? $term->name : 'this category';
            
            return [
                'can_create' => false,
                'reason' => sprintf(
                    'You need Level %d in %s to create lists there. You are Level %d.',
                    $config['list_creation_category_level'],
                    $cat_name,
                    $cat_level
                )
            ];
        }
    }
    
    return ['can_create' => true, 'reason' => null];
}
