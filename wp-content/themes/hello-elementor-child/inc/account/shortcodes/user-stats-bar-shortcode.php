<?php
/**
 * User Stats Bar Shortcode
 * 
 * Displays a compact stats bar for logged-in users showing:
 * - Level & XP progress
 * - Quiz Tokens
 * - YugoCoins (future)
 * - Notification bell (future)
 * 
 * Usage: [ygv_user_stats_bar]
 * 
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

/**
 * Hide WordPress admin bar for non-admin users
 */
add_filter('show_admin_bar', function($show) {
    if (!current_user_can('administrator')) {
        return false;
    }
    return $show;
});

/**
 * Register the shortcode
 */
add_shortcode('ygv_user_stats_bar', 'ygv_user_stats_bar_shortcode');

function ygv_user_stats_bar_shortcode($atts) {
    // Only show for logged-in users
    if (!is_user_logged_in()) {
        return '';
    }
    
    $user_id = get_current_user_id();
    $user = wp_get_current_user();
    
    // Get level/XP data
    $progress_data = ygv_get_user_progress_data($user_id);
    
    // Get token data
    $token_data = ygv_get_user_token_data($user_id);
    
    // Get YugoCoins (future - placeholder for now)
    $yugocoins = ygv_get_user_yugocoins($user_id);
    
    // Get unread notifications count (future)
    $notifications_count = ygv_get_unread_notifications_count($user_id);
    
    ob_start();
    ?>
    <div class="ygv-user-stats-bar">
        <div class="ygv-stats-bar-inner">
            
            <!-- User Avatar & Name -->
            <a href="<?php echo esc_url(home_url('/moj-nalog/')); ?>" class="ygv-stats-user">
                <div class="ygv-stats-avatar">
                    <?php echo get_avatar($user_id, 32); ?>
                    <span class="ygv-stats-level-badge"><?php echo esc_html($progress_data['level']); ?></span>
                </div>
                <span class="ygv-stats-username"><?php echo esc_html($user->display_name); ?></span>
            </a>
            
            <!-- XP Progress -->
            <div class="ygv-stats-item ygv-stats-xp" title="<?php echo esc_attr($progress_data['xp'] . ' / ' . $progress_data['next_xp'] . ' XP do sledećeg nivoa'); ?>">
                <div class="ygv-stats-icon">
                    <?php ygv_icon_e('star', 18); ?>
                </div>
                <div class="ygv-stats-content">
                    <div class="ygv-stats-label">XP</div>
                    <div class="ygv-stats-value"><?php echo number_format($progress_data['xp']); ?></div>
                </div>
                <div class="ygv-stats-xp-bar">
                    <div class="ygv-stats-xp-fill" style="width: <?php echo esc_attr($progress_data['progress_percent']); ?>%"></div>
                </div>
            </div>
            
            <!-- Quiz Tokens -->
            <div class="ygv-stats-item ygv-stats-tokens" title="<?php echo esc_attr('Kviz Tokeni: ' . $token_data['tokens'] . '/' . $token_data['max_tokens']); ?>">
                <div class="ygv-stats-icon ygv-stats-icon-token">
                    <?php ygv_icon_e('coins', 18); ?>
                </div>
                <div class="ygv-stats-content">
                    <div class="ygv-stats-label">Tokeni</div>
                    <div class="ygv-stats-value">
                        <span class="ygv-token-current"><?php echo esc_html($token_data['tokens']); ?></span>
                        <span class="ygv-token-max">/<?php echo esc_html($token_data['max_tokens']); ?></span>
                    </div>
                </div>
                <?php if ($token_data['tokens'] < $token_data['max_tokens'] && $token_data['next_token_in'] > 0): ?>
                    <div class="ygv-stats-timer" data-next-token="<?php echo esc_attr($token_data['next_token_in']); ?>">
                        <span class="ygv-timer-icon"><?php ygv_icon_e('timer', 14); ?></span>
                        <span class="ygv-timer-value"><?php echo esc_html(ygv_format_time_short($token_data['next_token_in'])); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- YugoCoins -->
            <div class="ygv-stats-item ygv-stats-coins" title="YugoCoins">
                <div class="ygv-stats-icon ygv-stats-icon-coin">
                    <?php ygv_icon_e('circle-dollar', 18); ?>
                </div>
                <div class="ygv-stats-content">
                    <div class="ygv-stats-label">Coins</div>
                    <div class="ygv-stats-value"><?php echo number_format($yugocoins); ?></div>
                </div>
            </div>
            
            <!-- Notifications Bell -->
            <a href="<?php echo esc_url(home_url('/moj-nalog/?tab=obavestenja')); ?>" class="ygv-stats-item ygv-stats-notifications" title="Obaveštenja">
                <div class="ygv-stats-icon ygv-stats-icon-bell">
                    <?php ygv_icon_e('bell', 20); ?>
                    <?php if ($notifications_count > 0): ?>
                        <span class="ygv-notification-badge"><?php echo $notifications_count > 9 ? '9+' : $notifications_count; ?></span>
                    <?php endif; ?>
                </div>
            </a>
            
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Get user progress data (level, XP)
 */
function ygv_get_user_progress_data($user_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ygv_user_overall_progress';
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT overall_xp, overall_level FROM {$table} WHERE user_id = %d",
        $user_id
    ), ARRAY_A);
    
    $xp = $row['overall_xp'] ?? 0;
    $level = $row['overall_level'] ?? 1;
    
    // Calculate progress to next level
    $thresholds = ygv_get_xp_thresholds();
    $current_threshold = $thresholds[$level] ?? 0;
    $next_threshold = $thresholds[$level + 1] ?? ($current_threshold + 300);
    
    $xp_in_level = $xp - $current_threshold;
    $xp_needed = $next_threshold - $current_threshold;
    $progress_percent = $xp_needed > 0 ? min(100, ($xp_in_level / $xp_needed) * 100) : 100;
    
    return [
        'level' => $level,
        'xp' => $xp,
        'current_threshold' => $current_threshold,
        'next_xp' => $next_threshold,
        'progress_percent' => round($progress_percent, 1),
    ];
}

/**
 * Get XP thresholds for levels
 */
function ygv_get_xp_thresholds() {
    if (function_exists('ygv_get_level_config')) {
        $config = ygv_get_level_config();
        $thresholds = $config['xp_thresholds'] ?? [];
        $xp_per_level = $config['xp_per_level_after_10'] ?? 300;
        $max_level = $config['max_level'] ?? 100;
        
        $last_threshold = $thresholds[10] ?? 1250;
        for ($lvl = 11; $lvl <= $max_level; $lvl++) {
            $last_threshold += $xp_per_level;
            $thresholds[$lvl] = $last_threshold;
        }
        
        return $thresholds;
    }
    
    // Fallback
    return [1=>0, 2=>50, 3=>120, 4=>210, 5=>320, 6=>450, 7=>620, 8=>830, 9=>1080, 10=>1370];
}

/**
 * Get user token data
 */
function ygv_get_user_token_data($user_id) {
    // Try to use the token service if available
    if (class_exists('YGV_Token_Service')) {
        $token_service = new YGV_Token_Service();
        $wallet = $token_service->current_wallet($user_id);
        
        // Calculate next token time
        $tokens = (int)($wallet['tokens'] ?? 0);
        $max_tokens = (int)($wallet['max_tokens'] ?? 48);
        $regen_rate = (int)($wallet['regen_rate'] ?? 2);
        $regen_interval = (int)($wallet['regen_interval_minutes'] ?? 60) * 60; // to seconds
        $refill_anchor = (int)($wallet['refill_anchor'] ?? time());
        
        // Calculate current tokens based on refill
        $now = time();
        $elapsed = $now - $refill_anchor;
        $intervals_passed = floor($elapsed / $regen_interval);
        $regenerated = $intervals_passed * $regen_rate;
        $current_tokens = min($max_tokens, $tokens + $regenerated);
        
        // Time until next token
        $next_token_in = 0;
        if ($current_tokens < $max_tokens) {
            $next_token_in = $regen_interval - ($elapsed % $regen_interval);
        }
        
        return [
            'tokens' => $current_tokens,
            'max_tokens' => $max_tokens,
            'next_token_in' => $next_token_in,
        ];
    }
    
    // Fallback to user meta
    $token_bucket = get_user_meta($user_id, 'quiz_token_bucket', true);
    $max_tokens = 20;
    $current_tokens = $max_tokens;
    $next_token_in = 0;
    
    if (is_array($token_bucket) && isset($token_bucket['tokens'])) {
        $current_tokens = (int)$token_bucket['tokens'];
    }
    
    return [
        'tokens' => $current_tokens,
        'max_tokens' => $max_tokens,
        'next_token_in' => $next_token_in,
    ];
}

/**
 * Get user YugoCoins balance
 * Placeholder for future implementation
 */
function ygv_get_user_yugocoins($user_id) {
    // Check if coins are stored in user meta
    $coins = get_user_meta($user_id, 'yugocoins', true);
    return (int)($coins ?: 0);
}

/**
 * Get unread notifications count
 * Placeholder for future implementation
 */
function ygv_get_unread_notifications_count($user_id) {
    // Check if notifications count is stored
    $count = get_user_meta($user_id, 'ygv_unread_notifications', true);
    return (int)($count ?: 0);
}

/**
 * Format time in short format (e.g., "45m", "2h")
 */
function ygv_format_time_short($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . 'm';
    } else {
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'm' : '');
    }
}

/**
 * Enqueue stats bar assets
 * Always load for logged-in users since Elementor widgets don't use has_shortcode properly
 */
add_action('wp_enqueue_scripts', function() {
    // Always load for logged in users - Elementor widgets bypass has_shortcode check
    if (is_user_logged_in()) {
        wp_enqueue_style(
            'ygv-user-stats-bar-css',
            get_stylesheet_directory_uri() . '/css/user-stats-bar.css',
            [],
            filemtime(get_stylesheet_directory() . '/css/user-stats-bar.css')
        );
        
        wp_enqueue_script(
            'ygv-user-stats-bar-js',
            get_stylesheet_directory_uri() . '/js/account/user-stats-bar.js',
            [],
            filemtime(get_stylesheet_directory() . '/js/account/user-stats-bar.js'),
            true
        );
    }
});
