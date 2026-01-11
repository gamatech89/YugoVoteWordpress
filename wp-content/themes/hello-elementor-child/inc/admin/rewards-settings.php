<?php
/**
 * Rewards & Achievements Settings Admin Page
 * 
 * Configurable settings for:
 * - Streak rewards (XP, YugoCoins, vote bonus per streak tier)
 * - YugoCoin economy settings
 * - Achievement rewards (future)
 * 
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

/**
 * Register the settings page under Settings menu
 */
function ygv_register_rewards_settings_page() {
    add_options_page(
        'Rewards & Achievements',
        'Rewards Settings',
        'manage_options',
        'ygv-rewards-settings',
        'ygv_render_rewards_settings_page'
    );
}
add_action('admin_menu', 'ygv_register_rewards_settings_page');

/**
 * Register settings
 */
function ygv_register_rewards_settings() {
    register_setting('ygv_rewards_settings', 'ygv_rewards_config', [
        'type' => 'array',
        'sanitize_callback' => 'ygv_sanitize_rewards_config',
        'default' => ygv_get_default_rewards_config()
    ]);
}
add_action('admin_init', 'ygv_register_rewards_settings');

/**
 * Get default rewards configuration
 */
function ygv_get_default_rewards_config(): array {
    return [
        // Streak cycle settings
        'streak_cycle_days' => 30,
        'streak_reset_on_miss' => true, // Reset to 0 if a day is missed
        
        // Streak reward tiers
        'streak_rewards' => [
            [
                'days' => 3,
                'xp' => 50,
                'coins' => 0,
                'vote_bonus' => 1,
                'title' => 'Početnik'
            ],
            [
                'days' => 6,
                'xp' => 100,
                'coins' => 10,
                'vote_bonus' => 2,
                'title' => 'Aktivan'
            ],
            [
                'days' => 9,
                'xp' => 150,
                'coins' => 15,
                'vote_bonus' => 3,
                'title' => 'Redovan'
            ],
            [
                'days' => 14,
                'xp' => 200,
                'coins' => 20,
                'vote_bonus' => 4,
                'title' => 'Posvećen'
            ],
            [
                'days' => 21,
                'xp' => 250,
                'coins' => 25,
                'vote_bonus' => 5,
                'title' => 'Veteran'
            ],
            [
                'days' => 29,
                'xp' => 300,
                'coins' => 30,
                'vote_bonus' => 7,
                'title' => 'Šampion'
            ],
            [
                'days' => 30,
                'xp' => 500,
                'coins' => 100,
                'vote_bonus' => 10,
                'title' => 'Legenda'
            ],
        ],
        
        // YugoCoin economy
        'yugocoins' => [
            'enabled' => true,
            'daily_login_bonus' => 5,        // Coins per daily login
            'vote_coin_chance' => 0,         // % chance to earn coin per vote (0 = disabled)
            'quiz_completion_coins' => 10,   // Coins for completing a quiz
            'achievement_multiplier' => 1.0, // Multiplier for achievement coin rewards
        ],
        
        // Display settings
        'display' => [
            'show_yugocoins_in_profile' => true,
            'show_streak_progress' => true,
            'streak_milestones' => [3, 7, 14, 21, 30], // Days to show as milestones
        ]
    ];
}

/**
 * Get the current rewards config (with defaults)
 */
function ygv_get_rewards_config(): array {
    $saved = get_option('ygv_rewards_config', []);
    $defaults = ygv_get_default_rewards_config();
    
    // Deep merge for nested arrays
    $config = wp_parse_args($saved, $defaults);
    
    // Ensure streak_rewards is properly set
    if (empty($config['streak_rewards'])) {
        $config['streak_rewards'] = $defaults['streak_rewards'];
    }
    
    return $config;
}

/**
 * Sanitize rewards config on save
 */
function ygv_sanitize_rewards_config($input): array {
    $sanitized = [];
    
    // Streak cycle settings
    $sanitized['streak_cycle_days'] = absint($input['streak_cycle_days'] ?? 30);
    $sanitized['streak_reset_on_miss'] = !empty($input['streak_reset_on_miss']);
    
    // Sanitize streak rewards
    if (isset($input['streak_rewards']) && is_array($input['streak_rewards'])) {
        $sanitized['streak_rewards'] = [];
        foreach ($input['streak_rewards'] as $tier) {
            if (empty($tier['days'])) continue; // Skip empty rows
            $sanitized['streak_rewards'][] = [
                'days' => absint($tier['days'] ?? 1),
                'xp' => absint($tier['xp'] ?? 0),
                'coins' => absint($tier['coins'] ?? 0),
                'vote_bonus' => absint($tier['vote_bonus'] ?? 0),
                'title' => sanitize_text_field($tier['title'] ?? '')
            ];
        }
        // Sort by days
        usort($sanitized['streak_rewards'], fn($a, $b) => $a['days'] - $b['days']);
    }
    
    // YugoCoin settings
    $sanitized['yugocoins'] = [
        'enabled' => !empty($input['yugocoins']['enabled']),
        'daily_login_bonus' => absint($input['yugocoins']['daily_login_bonus'] ?? 5),
        'vote_coin_chance' => min(100, absint($input['yugocoins']['vote_coin_chance'] ?? 0)),
        'quiz_completion_coins' => absint($input['yugocoins']['quiz_completion_coins'] ?? 10),
        'achievement_multiplier' => floatval($input['yugocoins']['achievement_multiplier'] ?? 1.0),
    ];
    
    // Display settings
    $sanitized['display'] = [
        'show_yugocoins_in_profile' => !empty($input['display']['show_yugocoins_in_profile']),
        'show_streak_progress' => !empty($input['display']['show_streak_progress']),
        'streak_milestones' => array_map('absint', explode(',', $input['display']['streak_milestones'] ?? '3,7,14,21,30')),
    ];
    
    return $sanitized;
}

/**
 * Render the settings page
 */
function ygv_render_rewards_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $config = ygv_get_rewards_config();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form action="options.php" method="post">
            <?php settings_fields('ygv_rewards_settings'); ?>
            
            <!-- Streak System Section -->
            <div class="ygv-admin-section">
                <h2>🔥 Streak Rewards System</h2>
                <p class="description">Configure the 30-day voting streak system. Users earn rewards for consecutive days of voting activity.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Streak Cycle (Days)</th>
                        <td>
                            <input type="number" name="ygv_rewards_config[streak_cycle_days]" 
                                   value="<?php echo esc_attr($config['streak_cycle_days']); ?>" 
                                   min="7" max="90" style="width: 80px;">
                            <p class="description">Number of days in a complete streak cycle (default: 30)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Reset on Miss</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ygv_rewards_config[streak_reset_on_miss]" value="1" 
                                       <?php checked($config['streak_reset_on_miss']); ?>>
                                Reset streak to 0 if user misses a day
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h3>Streak Reward Tiers</h3>
                <p class="description">Define rewards for reaching each streak milestone. Users get XP, YugoCoins, and temporary vote bonus.</p>
                
                <table class="wp-list-table widefat fixed striped" style="max-width: 900px;" id="ygv-streak-tiers-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Day</th>
                            <th style="width: 100px;">XP Reward</th>
                            <th style="width: 100px;">YugoCoins</th>
                            <th style="width: 100px;">Vote Bonus</th>
                            <th style="width: 150px;">Title (Optional)</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($config['streak_rewards'] as $i => $tier): ?>
                        <tr class="ygv-tier-row">
                            <td>
                                <input type="number" name="ygv_rewards_config[streak_rewards][<?php echo $i; ?>][days]" 
                                       value="<?php echo esc_attr($tier['days']); ?>" 
                                       min="1" max="<?php echo esc_attr($config['streak_cycle_days']); ?>" 
                                       class="ygv-tier-days" style="width: 60px;">
                            </td>
                            <td>
                                <input type="number" name="ygv_rewards_config[streak_rewards][<?php echo $i; ?>][xp]" 
                                       value="<?php echo esc_attr($tier['xp']); ?>" 
                                       min="0" max="1000" step="10" style="width: 80px;"> XP
                            </td>
                            <td>
                                <input type="number" name="ygv_rewards_config[streak_rewards][<?php echo $i; ?>][coins]" 
                                       value="<?php echo esc_attr($tier['coins']); ?>" 
                                       min="0" max="500" style="width: 70px;"> 🪙
                            </td>
                            <td>
                                +<input type="number" name="ygv_rewards_config[streak_rewards][<?php echo $i; ?>][vote_bonus]" 
                                       value="<?php echo esc_attr($tier['vote_bonus']); ?>" 
                                       min="0" max="20" style="width: 50px;"> XP/vote
                            </td>
                            <td>
                                <input type="text" name="ygv_rewards_config[streak_rewards][<?php echo $i; ?>][title]" 
                                       value="<?php echo esc_attr($tier['title'] ?? ''); ?>" 
                                       placeholder="e.g. Champion" style="width: 130px;">
                            </td>
                            <td>
                                <button type="button" class="button ygv-remove-tier" title="Remove">&times;</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <button type="button" class="button" id="ygv-add-streak-tier">+ Add Tier</button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                
                <p class="description" style="margin-top: 10px;">
                    <strong>How it works:</strong><br>
                    • Users earn rewards when reaching each tier (e.g., Day 3, Day 7, etc.)<br>
                    • Vote Bonus is active XP bonus per vote while at that tier<br>
                    • After completing the cycle, streak resets and user can earn again
                </p>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <!-- YugoCoin Economy Section -->
            <div class="ygv-admin-section">
                <h2>🪙 YugoCoin Economy</h2>
                <p class="description">Configure how users earn and spend YugoCoins.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable YugoCoins</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ygv_rewards_config[yugocoins][enabled]" value="1" 
                                       <?php checked($config['yugocoins']['enabled'] ?? true); ?>>
                                Enable the YugoCoin currency system
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Daily Login Bonus</th>
                        <td>
                            <input type="number" name="ygv_rewards_config[yugocoins][daily_login_bonus]" 
                                   value="<?php echo esc_attr($config['yugocoins']['daily_login_bonus'] ?? 5); ?>" 
                                   min="0" max="100" style="width: 80px;"> coins
                            <p class="description">YugoCoins earned for logging in each day</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Quiz Completion Coins</th>
                        <td>
                            <input type="number" name="ygv_rewards_config[yugocoins][quiz_completion_coins]" 
                                   value="<?php echo esc_attr($config['yugocoins']['quiz_completion_coins'] ?? 10); ?>" 
                                   min="0" max="100" style="width: 80px;"> coins
                            <p class="description">YugoCoins earned for completing a quiz</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Vote Coin Chance</th>
                        <td>
                            <input type="number" name="ygv_rewards_config[yugocoins][vote_coin_chance]" 
                                   value="<?php echo esc_attr($config['yugocoins']['vote_coin_chance'] ?? 0); ?>" 
                                   min="0" max="100" style="width: 80px;"> %
                            <p class="description">% chance to earn 1 coin per vote (0 = disabled)</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <!-- Display Settings Section -->
            <div class="ygv-admin-section">
                <h2>📊 Display Settings</h2>
                <p class="description">Control how rewards are displayed to users.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Show YugoCoins in Profile</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ygv_rewards_config[display][show_yugocoins_in_profile]" value="1" 
                                       <?php checked($config['display']['show_yugocoins_in_profile'] ?? true); ?>>
                                Display YugoCoin balance on profile page
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Show Streak Progress</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ygv_rewards_config[display][show_streak_progress]" value="1" 
                                       <?php checked($config['display']['show_streak_progress'] ?? true); ?>>
                                Display streak progress bar on profile page
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Streak Milestones</th>
                        <td>
                            <input type="text" name="ygv_rewards_config[display][streak_milestones]" 
                                   value="<?php echo esc_attr(implode(',', $config['display']['streak_milestones'] ?? [3,7,14,21,30])); ?>" 
                                   style="width: 200px;">
                            <p class="description">Comma-separated day numbers to show as milestone markers (e.g., 3,7,14,21,30)</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Save Rewards Settings'); ?>
        </form>
    </div>
    
    <style>
        .ygv-admin-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .ygv-admin-section h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        #ygv-streak-tiers-table input[type="number"],
        #ygv-streak-tiers-table input[type="text"] {
            margin: 0;
        }
        .ygv-remove-tier {
            color: #dc3232;
            font-weight: bold;
            font-size: 18px;
            line-height: 1;
            padding: 2px 8px;
        }
        .ygv-remove-tier:hover {
            color: #fff;
            background: #dc3232;
            border-color: #dc3232;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        var tierIndex = <?php echo count($config['streak_rewards']); ?>;
        
        // Add new tier row
        $('#ygv-add-streak-tier').on('click', function() {
            var newRow = `
                <tr class="ygv-tier-row">
                    <td><input type="number" name="ygv_rewards_config[streak_rewards][${tierIndex}][days]" value="" min="1" max="30" class="ygv-tier-days" style="width: 60px;"></td>
                    <td><input type="number" name="ygv_rewards_config[streak_rewards][${tierIndex}][xp]" value="100" min="0" max="1000" step="10" style="width: 80px;"> XP</td>
                    <td><input type="number" name="ygv_rewards_config[streak_rewards][${tierIndex}][coins]" value="10" min="0" max="500" style="width: 70px;"> 🪙</td>
                    <td>+<input type="number" name="ygv_rewards_config[streak_rewards][${tierIndex}][vote_bonus]" value="1" min="0" max="20" style="width: 50px;"> XP/vote</td>
                    <td><input type="text" name="ygv_rewards_config[streak_rewards][${tierIndex}][title]" value="" placeholder="e.g. Champion" style="width: 130px;"></td>
                    <td><button type="button" class="button ygv-remove-tier" title="Remove">&times;</button></td>
                </tr>
            `;
            $('#ygv-streak-tiers-table tbody').append(newRow);
            tierIndex++;
        });
        
        // Remove tier row
        $(document).on('click', '.ygv-remove-tier', function() {
            if ($('.ygv-tier-row').length > 1) {
                $(this).closest('tr').remove();
            } else {
                alert('You must have at least one reward tier.');
            }
        });
    });
    </script>
    <?php
}

/**
 * Helper function to get streak rewards (for use in templates)
 */
function ygv_get_streak_rewards(): array {
    $config = ygv_get_rewards_config();
    return $config['streak_rewards'] ?? [];
}

/**
 * Helper function to get current streak tier for a user
 */
function ygv_get_user_streak_tier(int $streak_days): ?array {
    $rewards = ygv_get_streak_rewards();
    $current_tier = null;
    
    foreach ($rewards as $tier) {
        if ($streak_days >= $tier['days']) {
            $current_tier = $tier;
        }
    }
    
    return $current_tier;
}

/**
 * Helper function to get next streak tier for a user
 */
function ygv_get_next_streak_tier(int $streak_days): ?array {
    $rewards = ygv_get_streak_rewards();
    
    foreach ($rewards as $tier) {
        if ($streak_days < $tier['days']) {
            return $tier;
        }
    }
    
    return null; // User has completed all tiers
}

/**
 * Get streak vote bonus for current streak
 */
function ygv_get_streak_vote_bonus(int $streak_days): int {
    $tier = ygv_get_user_streak_tier($streak_days);
    return $tier ? ($tier['vote_bonus'] ?? 0) : 0;
}
