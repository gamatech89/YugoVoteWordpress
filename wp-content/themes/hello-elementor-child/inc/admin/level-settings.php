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
        'xp_thresholds' => [
            1 => 0,
            2 => 50,
            3 => 120,
            4 => 210,
            5 => 320,
            6 => 450,
            7 => 600,
            8 => 780,
            9 => 1000,
            10 => 1250,
            // After level 10, each level requires 300 more XP
            // This continues programmatically
        ],
        'xp_per_level_after_10' => 300, // XP increase per level after 10
        'max_level' => 100,
        // List creation requirements
        'list_creation_category_level' => 10, // Category level required to create lists in that category
        'list_creation_global_level' => 5,    // Global level required to create any list
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
    $sanitized['xp_per_level_after_10'] = absint($input['xp_per_level_after_10'] ?? 300);
    $sanitized['max_level'] = absint($input['max_level'] ?? 100);
    $sanitized['list_creation_category_level'] = absint($input['list_creation_category_level'] ?? 10);
    $sanitized['list_creation_global_level'] = absint($input['list_creation_global_level'] ?? 5);
    
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
            
            <h2>XP Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">XP per Level (after 10)</th>
                    <td>
                        <input type="number" name="ygv_level_config[xp_per_level_after_10]" 
                               value="<?php echo esc_attr($config['xp_per_level_after_10']); ?>" 
                               min="100" max="1000" step="50" style="width: 80px;">
                        <p class="description">XP required for each level after level 10 (default: 300)</p>
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
        
        <h2>Current System Overview</h2>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; max-width: 600px;">
            <h4 style="margin-top: 0;">How XP is Earned:</h4>
            <ul>
                <li>🎯 <strong>Quizzes:</strong> 5-50 XP based on score (in quiz's category)</li>
                <li>🗳️ <strong>Voting:</strong> 2 XP per vote (max 50 votes = 100 XP/day)</li>
                <li>📝 <strong>List Creation:</strong> 50 XP (in list's category)</li>
                <li>🏆 <strong>Tournament Prediction:</strong> 10 XP</li>
                <li>👥 <strong>Referral:</strong> 100 XP (global)</li>
            </ul>
            
            <h4>Two Types of Levels:</h4>
            <ul>
                <li><strong>Global Level:</strong> Sum of all XP across categories</li>
                <li><strong>Category Level:</strong> XP earned only in that category (Sport, Music, Film, etc.)</li>
            </ul>
            
            <h4>Expert Vote Bonus:</h4>
            <p>When voting on a list, your category level in that list's category determines your bonus.<br>
            Example: Level 25 in Sport → +2 bonus → Your "8" vote counts as "10"</p>
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
