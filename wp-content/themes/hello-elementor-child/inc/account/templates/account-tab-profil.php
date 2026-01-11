<?php if (!defined('ABSPATH')) exit;

// Ensure icons are loaded
if (!function_exists('ygv_icon_e')) {
    require_once get_stylesheet_directory() . '/inc/icons.php';
}

$user = wp_get_current_user();
$user_id = $user->ID;

// Basic profile info
$gender  = get_user_meta($user_id, '_user_gender', true);
$dob     = get_user_meta($user_id, '_user_dob', true);
$country = get_user_meta($user_id, '_user_country', true);
$poi     = (array) get_user_meta($user_id, '_user_points_of_interest', true);

$poi_names = [];
if ($poi) {
    foreach ($poi as $tid) {
        $term = get_term((int)$tid, 'voting_list_category');
        if ($term && !is_wp_error($term)) $poi_names[] = $term->name;
    }
}
$edit_url = home_url('/kompletiranje-naloga/');

// Get XP and Level data
global $wpdb;
$t_cat = $wpdb->prefix . 'ygv_user_category_progress';
$t_over = $wpdb->prefix . 'ygv_user_overall_progress';

// Overall progress
$overall = $wpdb->get_row($wpdb->prepare(
    "SELECT overall_xp, overall_level FROM {$t_over} WHERE user_id = %d",
    $user_id
), ARRAY_A) ?: ['overall_xp' => 0, 'overall_level' => 1];

// Category progress - Only show PARENT categories (those with parent = 0)
// Join with term_taxonomy to filter for parent categories only
$cat_progress = $wpdb->get_results($wpdb->prepare(
    "SELECT cp.category_term_id, cp.xp, cp.level, t.name as category_name
     FROM {$t_cat} cp
     LEFT JOIN {$wpdb->terms} t ON cp.category_term_id = t.term_id
     LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
     WHERE cp.user_id = %d 
       AND tt.taxonomy = 'voting_list_category'
       AND tt.parent = 0
     ORDER BY cp.xp DESC",
    $user_id
), ARRAY_A) ?: [];

// Get level config for titles
$level_config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : null;

// Helper to get title from level
if (!function_exists('ygv_get_title_for_level')) {
    function ygv_get_title_for_level($level, $config) {
        if (!$config) return 'Rookie';
        foreach ($config['tiers'] as $tier) {
            if ($level >= $tier['min_level'] && $level <= $tier['max_level']) {
                return $tier['title'];
            }
        }
        return 'Legend';
    }
}

// Helper to get XP needed for next level
if (!function_exists('ygv_get_next_level_xp')) {
    function ygv_get_next_level_xp($current_level, $config) {
        if (!$config) return null;
        $thresholds = $config['xp_thresholds'] ?? [];
        $xp_per_level = $config['xp_per_level_after_10'] ?? 300;
        
        $next_level = $current_level + 1;
        if ($next_level > ($config['max_level'] ?? 100)) return null;
        
        if (isset($thresholds[$next_level])) {
            return $thresholds[$next_level];
        }
        
        // Calculate for levels > 10
        $base = $thresholds[10] ?? 1250;
        return $base + (($next_level - 10) * $xp_per_level);
    }
}

$global_title = ygv_get_title_for_level((int)$overall['overall_level'], $level_config);
$global_next_xp = ygv_get_next_level_xp((int)$overall['overall_level'], $level_config);

// Get voting streak info
$voting_streak = 0;
$streak_bonus = 0;
if (class_exists('YGV_Achievement_Service')) {
    $achievement_service = new YGV_Achievement_Service();
    $user_stats = $achievement_service->get_user_stats($user_id);
    $voting_streak = $user_stats['voting_streak'] ?? 0;
    $streak_bonus = min($voting_streak, 10);
} else {
    // Fallback: calculate directly
    require_once get_stylesheet_directory() . '/inc/quizzes/services/class-ygv-achievement-service.php';
    $achievement_service = new YGV_Achievement_Service();
    $user_stats = $achievement_service->get_user_stats($user_id);
    $voting_streak = $user_stats['voting_streak'] ?? 0;
    $streak_bonus = min($voting_streak, 10);
}

// Get YugoCoins
$yugocoins = function_exists('ygv_get_user_yugocoins') ? ygv_get_user_yugocoins($user_id) : (int) get_user_meta($user_id, 'yugocoins', true);

// Get rewards config from admin settings
$rewards_config = function_exists('ygv_get_rewards_config') ? ygv_get_rewards_config() : [];
$streak_rewards = $rewards_config['streak_rewards'] ?? [
    ['days' => 3, 'xp' => 50, 'coins' => 0, 'vote_bonus' => 1],
    ['days' => 7, 'xp' => 100, 'coins' => 10, 'vote_bonus' => 2],
    ['days' => 14, 'xp' => 200, 'coins' => 20, 'vote_bonus' => 4],
    ['days' => 21, 'xp' => 250, 'coins' => 25, 'vote_bonus' => 5],
    ['days' => 30, 'xp' => 500, 'coins' => 100, 'vote_bonus' => 10],
];
$streak_cycle_days = $rewards_config['streak_cycle_days'] ?? 30;
$streak_milestones = $rewards_config['display']['streak_milestones'] ?? [3, 7, 14, 21, 30];
$show_yugocoins = $rewards_config['display']['show_yugocoins_in_profile'] ?? true;
$show_streak = $rewards_config['display']['show_streak_progress'] ?? true;

// Get current streak tier using helper functions or fallback
$current_tier = function_exists('ygv_get_user_streak_tier') 
    ? ygv_get_user_streak_tier($voting_streak) 
    : null;
$next_tier = function_exists('ygv_get_next_streak_tier') 
    ? ygv_get_next_streak_tier($voting_streak) 
    : ($streak_rewards[0] ?? null);

// Fallback tier calculation if functions not available
if (!function_exists('ygv_get_user_streak_tier')) {
    foreach ($streak_rewards as $i => $tier) {
        if ($voting_streak >= $tier['days']) {
            $current_tier = $tier;
            $next_tier = isset($streak_rewards[$i + 1]) ? $streak_rewards[$i + 1] : null;
        }
    }
}
$streak_vote_bonus = $current_tier ? ($current_tier['vote_bonus'] ?? 0) : 0;

// Category icons - mapiranje na Lucide icon imena
$category_icons = [
    'Sport' => 'dribbble',
    'Muzika' => 'music',
    'Film' => 'film',
    'FIlm' => 'film',
    'Film i TV' => 'tv',
    'Biznis' => 'briefcase',
    'Culture Club' => 'palette',
    'Trendy' => 'flame',
    'Lifestyle' => 'flame',
    'Trendy/Lifestyle' => 'flame',
];
?>

<div class="ygv-profile-dashboard">
    
    <!-- Global Level Card -->
    <div class="ygv-card ygv-level-hero">
        <div class="ygv-level-hero-content">
            <div class="ygv-level-badge">
                <span class="ygv-level-number"><?php echo esc_html($overall['overall_level']); ?></span>
            </div>
            <div class="ygv-level-info">
                <h2 class="ygv-level-title"><?php echo esc_html($global_title); ?></h2>
                <p class="ygv-level-subtitle"><?php echo esc_html__('Globalni Nivo', 'hello-elementor-child'); ?></p>
                <div class="ygv-xp-bar-container">
                    <?php 
                    $current_xp = (int)$overall['overall_xp'];
                    $progress_percent = 0;
                    if ($global_next_xp) {
                        // Get current level threshold
                        $current_threshold = 0;
                        if ($level_config) {
                            $thresholds = $level_config['xp_thresholds'] ?? [];
                            $current_level = (int)$overall['overall_level'];
                            if (isset($thresholds[$current_level])) {
                                $current_threshold = $thresholds[$current_level];
                            } elseif ($current_level > 10) {
                                $base = $thresholds[10] ?? 1250;
                                $xp_per = $level_config['xp_per_level_after_10'] ?? 300;
                                $current_threshold = $base + (($current_level - 10) * $xp_per);
                            }
                        }
                        $xp_in_level = $current_xp - $current_threshold;
                        $xp_needed = $global_next_xp - $current_threshold;
                        $progress_percent = $xp_needed > 0 ? min(100, ($xp_in_level / $xp_needed) * 100) : 100;
                    }
                    ?>
                    <div class="ygv-xp-bar">
                        <div class="ygv-xp-bar-fill" style="width: <?php echo esc_attr($progress_percent); ?>%"></div>
                    </div>
                    <div class="ygv-xp-text">
                        <span><?php echo number_format($current_xp); ?> XP</span>
                        <?php if ($global_next_xp): ?>
                        <span><?php echo number_format($global_next_xp); ?> XP <?php echo esc_html__('za nivo', 'hello-elementor-child'); ?> <?php echo (int)$overall['overall_level'] + 1; ?></span>
                        <?php else: ?>
                        <span><?php echo esc_html__('Max nivo!', 'hello-elementor-child'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- YugoCoin & Streak Cards Row -->
    <div class="ygv-stats-row">
        <?php if ($show_yugocoins): ?>
        <!-- YugoCoin Card -->
        <div class="ygv-card ygv-coin-card">
            <div class="ygv-coin-icon">
                <?php ygv_icon_e('coins', 32); ?>
            </div>
            <div class="ygv-coin-info">
                <h3 class="ygv-coin-title"><?php echo esc_html__('YugoCoins', 'hello-elementor-child'); ?></h3>
                <div class="ygv-coin-balance">
                    <span class="ygv-coin-amount"><?php echo number_format($yugocoins); ?></span>
                </div>
                <p class="ygv-coin-hint"><?php echo esc_html__('Zarađuj kroz streak i dostignuća', 'hello-elementor-child'); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($show_streak): ?>
        <!-- Voting Streak Card - Redesigned -->
        <div class="ygv-card ygv-streak-card <?php echo $voting_streak >= 3 ? 'ygv-streak-active' : ''; ?>">
            <div class="ygv-streak-header">
                <div class="ygv-streak-icon">
                    <?php if ($voting_streak >= $streak_cycle_days): ?>
                        <?php ygv_icon_e('crown', 28); ?>
                    <?php elseif ($voting_streak >= 7): ?>
                        <?php ygv_icon_e('flame', 28); ?>
                    <?php elseif ($voting_streak >= 1): ?>
                        <?php ygv_icon_e('zap', 28); ?>
                    <?php else: ?>
                        <?php ygv_icon_e('calendar', 28); ?>
                    <?php endif; ?>
                </div>
                <div class="ygv-streak-title-wrap">
                    <h3 class="ygv-streak-title"><?php echo esc_html__('Dnevni Streak', 'hello-elementor-child'); ?></h3>
                    <div class="ygv-streak-day-count">
                        <span class="ygv-streak-number"><?php echo $voting_streak; ?></span>
                        <span class="ygv-streak-max">/ <?php echo $streak_cycle_days; ?> <?php echo esc_html__('dana', 'hello-elementor-child'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="ygv-streak-progress-30">
                <div class="ygv-streak-bar">
                    <div class="ygv-streak-bar-fill" style="width: <?php echo min(100, ($voting_streak / $streak_cycle_days) * 100); ?>%"></div>
                </div>
                <div class="ygv-streak-milestones">
                    <?php 
                    foreach ($streak_milestones as $m): 
                        $reached = $voting_streak >= $m;
                    ?>
                    <div class="ygv-streak-milestone <?php echo $reached ? 'reached' : ''; ?>" style="left: <?php echo ($m / $streak_cycle_days) * 100; ?>%">
                        <span class="ygv-milestone-dot"></span>
                        <span class="ygv-milestone-label"><?php echo $m; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="ygv-streak-rewards">
                <?php if ($next_tier): ?>
                <div class="ygv-next-reward">
                    <span class="ygv-reward-label"><?php echo esc_html__('Sledeća nagrada (dan', 'hello-elementor-child'); ?> <?php echo $next_tier['days']; ?>):</span>
                    <div class="ygv-reward-items">
                        <span class="ygv-reward-xp">+<?php echo $next_tier['xp']; ?> XP</span>
                        <?php if ($next_tier['coins'] > 0): ?>
                        <span class="ygv-reward-coins">+<?php echo $next_tier['coins']; ?> <?php ygv_icon_e('coins', 14); ?></span>
                        <?php endif; ?>
                        <span class="ygv-reward-vote-bonus">+<?php echo $next_tier['vote_bonus']; ?> XP/glas</span>
                    </div>
                </div>
                <?php else: ?>
                <div class="ygv-streak-complete">
                    <?php ygv_icon_e('trophy', 20); ?>
                    <span><?php echo esc_html__('Maksimalni streak!', 'hello-elementor-child'); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($streak_vote_bonus > 0): ?>
                <div class="ygv-current-bonus">
                    <span class="ygv-bonus-tag">+<?php echo $streak_vote_bonus; ?> XP</span>
                    <span class="ygv-bonus-desc"><?php echo esc_html__('bonus po glasu', 'hello-elementor-child'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Category Levels - 2 Column Grid -->
    <div class="ygv-card">
        <h3><?php echo esc_html__('Ekspertiza po Kategorijama', 'hello-elementor-child'); ?></h3>
        <p class="ygv-card-subtitle"><?php echo esc_html__('Tvoj nivo u svakoj kategoriji određuje bonus glasova', 'hello-elementor-child'); ?></p>
        
        <?php if (empty($cat_progress)): ?>
            <p class="ygv-muted"><?php echo esc_html__('Još nemaš napredak u kategorijama. Glasaj da zaradiš XP!', 'hello-elementor-child'); ?></p>
        <?php else: ?>
            <div class="ygv-category-grid"><?php foreach ($cat_progress as $cat): 
                    $cat_level = (int)$cat['level'];
                    $cat_title = ygv_get_title_for_level($cat_level, $level_config);
                    $cat_name = $cat['category_name'] ?: 'Unknown';
                    $icon_name = $category_icons[$cat_name] ?? 'folder';
                    
                    // Get category color for dynamic styling
                    $cat_color = function_exists('ygv_get_category_color_by_term_id') 
                        ? ygv_get_category_color_by_term_id($cat['category_term_id']) 
                        : '#6db24a';
                    
                    // Get vote bonus for this level
                    $vote_bonus = 0;
                    if ($level_config) {
                        foreach ($level_config['tiers'] as $tier) {
                            if ($cat_level >= $tier['min_level'] && $cat_level <= $tier['max_level']) {
                                $vote_bonus = $tier['vote_bonus'];
                                break;
                            }
                        }
                    }
                    
                    // Calculate progress to next level
                    $cat_xp = (int)$cat['xp'];
                    $cat_next_xp = ygv_get_next_level_xp($cat_level, $level_config);
                    $cat_current_threshold = 0;
                    if ($level_config) {
                        $thresholds = $level_config['xp_thresholds'] ?? [];
                        if (isset($thresholds[$cat_level])) {
                            $cat_current_threshold = $thresholds[$cat_level];
                        } elseif ($cat_level > 10) {
                            $base = $thresholds[10] ?? 1250;
                            $xp_per = $level_config['xp_per_level_after_10'] ?? 300;
                            $cat_current_threshold = $base + (($cat_level - 10) * $xp_per);
                        }
                    }
                    $cat_xp_in_level = $cat_xp - $cat_current_threshold;
                    $cat_xp_needed = $cat_next_xp ? ($cat_next_xp - $cat_current_threshold) : 0;
                    $cat_progress_pct = $cat_xp_needed > 0 ? min(100, ($cat_xp_in_level / $cat_xp_needed) * 100) : 100;
                ?>
                <div class="ygv-category-level-item" style="--cat-color: <?php echo esc_attr($cat_color); ?>;">
                    <div class="ygv-cat-accent" style="background: var(--cat-color);"></div>
                    <div class="ygv-cat-icon" style="color: var(--cat-color);"><?php ygv_icon_e($icon_name, 24); ?></div>
                    <div class="ygv-cat-info">
                        <div class="ygv-cat-name"><?php echo esc_html($cat_name); ?></div>
                        <div class="ygv-cat-level">
                            <?php echo esc_html__('Nivo', 'hello-elementor-child'); ?> <?php echo esc_html($cat_level); ?> 
                            <span class="ygv-cat-title" style="color: var(--cat-color);">(<?php echo esc_html($cat_title); ?>)</span>
                        </div>
                        <div class="ygv-cat-progress-bar">
                            <div class="ygv-progress-unified ygv-progress-unified--sm">
                                <div class="ygv-progress-unified__fill" style="width: <?php echo esc_attr($cat_progress_pct); ?>%;"></div>
                            </div>
                        </div>
                        <div class="ygv-cat-xp"><?php echo number_format($cat_xp); ?> XP</div>
                    </div>
                    <div class="ygv-cat-bonus">
                        <?php if ($vote_bonus > 0): ?>
                            <span class="ygv-bonus-badge" style="background: var(--cat-color);">+<?php echo $vote_bonus; ?></span>
                            <span class="ygv-bonus-label"><?php echo esc_html__('bonus', 'hello-elementor-child'); ?></span>
                        <?php else: ?>
                            <span class="ygv-bonus-none"><?php echo esc_html__('Nivo 10 za bonus', 'hello-elementor-child'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Basic Profile Info - Redesigned -->
    <div class="ygv-card ygv-profile-card">
        <div class="ygv-profile-card__header">
            <div class="ygv-profile-avatar">
                <?php 
                $avatar_url = get_avatar_url($user_id, ['size' => 120]);
                $user_level = (int)$overall['overall_level'];
                ?>
                <div class="ygv-avatar-wrapper">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($user->display_name); ?>" class="ygv-avatar-img">
                    <span class="ygv-avatar-level"><?php echo $user_level; ?></span>
                </div>
            </div>
            <div class="ygv-profile-card__name">
                <h3><?php echo esc_html($user->display_name); ?></h3>
                <span class="ygv-profile-card__title"><?php echo esc_html($global_title); ?></span>
            </div>
            <a href="<?php echo esc_url(home_url('/uredi-profil/')); ?>" class="ygv-btn ygv-btn-outline ygv-btn-sm">
                <?php ygv_icon_e('edit', 16); ?>
                <?php echo esc_html__('Uredi', 'hello-elementor-child'); ?>
            </a>
        </div>
        
        <div class="ygv-profile-card__details">
            <div class="ygv-profile-detail">
                <span class="ygv-profile-detail__icon"><?php ygv_icon_e('mail', 18); ?></span>
                <span class="ygv-profile-detail__label"><?php echo esc_html__('Email', 'hello-elementor-child'); ?></span>
                <span class="ygv-profile-detail__value"><?php echo esc_html($user->user_email); ?></span>
            </div>
            
            <div class="ygv-profile-detail">
                <span class="ygv-profile-detail__icon"><?php ygv_icon_e('user', 18); ?></span>
                <span class="ygv-profile-detail__label"><?php echo esc_html__('Pol', 'hello-elementor-child'); ?></span>
                <span class="ygv-profile-detail__value">
                    <?php 
                    $gender_labels = ['male' => 'Muški', 'female' => 'Ženski', 'other' => 'Drugo'];
                    echo $gender ? esc_html($gender_labels[$gender] ?? $gender) : '<span class="ygv-muted">—</span>'; 
                    ?>
                </span>
            </div>
            
            <div class="ygv-profile-detail">
                <span class="ygv-profile-detail__icon"><?php ygv_icon_e('calendar', 18); ?></span>
                <span class="ygv-profile-detail__label"><?php echo esc_html__('Datum rođenja', 'hello-elementor-child'); ?></span>
                <span class="ygv-profile-detail__value">
                    <?php 
                    if ($dob) {
                        $dob_formatted = date_i18n('j. F Y', strtotime($dob));
                        echo esc_html($dob_formatted);
                    } else {
                        echo '<span class="ygv-muted">—</span>';
                    }
                    ?>
                </span>
            </div>
            
            <div class="ygv-profile-detail">
                <span class="ygv-profile-detail__icon"><?php ygv_icon_e('map-pin', 18); ?></span>
                <span class="ygv-profile-detail__label"><?php echo esc_html__('Država', 'hello-elementor-child'); ?></span>
                <span class="ygv-profile-detail__value"><?php echo $country ? esc_html($country) : '<span class="ygv-muted">—</span>'; ?></span>
            </div>
            
            <?php if (!empty($poi_names)): ?>
            <div class="ygv-profile-detail ygv-profile-detail--full">
                <span class="ygv-profile-detail__icon"><?php ygv_icon_e('heart', 18); ?></span>
                <span class="ygv-profile-detail__label"><?php echo esc_html__('Interesovanja', 'hello-elementor-child'); ?></span>
                <div class="ygv-profile-detail__tags">
                    <?php foreach ($poi_names as $interest): ?>
                        <span class="ygv-tag"><?php echo esc_html($interest); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
</div>
