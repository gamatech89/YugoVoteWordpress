<?php if (!defined('ABSPATH')) exit;

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

// Category progress
$cat_progress = $wpdb->get_results($wpdb->prepare(
    "SELECT cp.category_term_id, cp.xp, cp.level, t.name as category_name
     FROM {$t_cat} cp
     LEFT JOIN {$wpdb->terms} t ON cp.category_term_id = t.term_id
     WHERE cp.user_id = %d
     ORDER BY cp.xp DESC",
    $user_id
), ARRAY_A) ?: [];

// Get level config for titles
$level_config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : null;

// Helper to get title from level
function ygv_get_title_for_level($level, $config) {
    if (!$config) return 'Rookie';
    foreach ($config['tiers'] as $tier) {
        if ($level >= $tier['min_level'] && $level <= $tier['max_level']) {
            return $tier['title'];
        }
    }
    return 'Legend';
}

// Helper to get XP needed for next level
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
    
    <!-- Voting Streak Card -->
    <div class="ygv-card ygv-streak-card <?php echo $voting_streak >= 3 ? 'ygv-streak-active' : ''; ?>">
        <div class="ygv-streak-content">
            <div class="ygv-streak-icon">
                <?php if ($voting_streak >= 30): ?>
                    <?php ygv_icon_e('gem', 32); ?>
                <?php elseif ($voting_streak >= 7): ?>
                    <?php ygv_icon_e('flame', 32); ?><?php ygv_icon_e('flame', 32); ?>
                <?php elseif ($voting_streak >= 1): ?>
                    <?php ygv_icon_e('flame', 32); ?>
                <?php else: ?>
                    <?php ygv_icon_e('snowflake', 32); ?>
                <?php endif; ?>
            </div>
            <div class="ygv-streak-info">
                <h3 class="ygv-streak-title"><?php echo esc_html__('Glasački Streak', 'hello-elementor-child'); ?></h3>
                <div class="ygv-streak-days">
                    <span class="ygv-streak-number"><?php echo $voting_streak; ?></span>
                    <span class="ygv-streak-label"><?php echo $voting_streak === 1 ? esc_html__('dan', 'hello-elementor-child') : esc_html__('dana', 'hello-elementor-child'); ?> <?php echo esc_html__('zaredom', 'hello-elementor-child'); ?></span>
                </div>
                <?php if ($streak_bonus > 0): ?>
                <div class="ygv-streak-bonus">
                    <span class="ygv-streak-bonus-badge">+<?php echo $streak_bonus; ?> XP</span>
                    <span class="ygv-streak-bonus-text"><?php echo esc_html__('bonus po glasu', 'hello-elementor-child'); ?></span>
                </div>
                <?php else: ?>
                <div class="ygv-streak-hint">
                    <?php echo esc_html__('Glasaj svaki dan za streak bonus!', 'hello-elementor-child'); ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="ygv-streak-progress">
                <?php 
                // Show progress to next milestone
                $next_milestone = 3;
                if ($voting_streak >= 3) $next_milestone = 7;
                if ($voting_streak >= 7) $next_milestone = 14;
                if ($voting_streak >= 14) $next_milestone = 30;
                if ($voting_streak >= 30) $next_milestone = 60;
                if ($voting_streak >= 60) $next_milestone = 100;
                
                $milestone_start = 0;
                if ($next_milestone == 7) $milestone_start = 3;
                if ($next_milestone == 14) $milestone_start = 7;
                if ($next_milestone == 30) $milestone_start = 14;
                if ($next_milestone == 60) $milestone_start = 30;
                if ($next_milestone == 100) $milestone_start = 60;
                
                $progress = min(100, (($voting_streak - $milestone_start) / ($next_milestone - $milestone_start)) * 100);
                if ($voting_streak >= 100) $progress = 100;
                ?>
                <div class="ygv-milestone-progress">
                    <div class="ygv-milestone-bar">
                        <div class="ygv-milestone-bar-fill" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <?php if ($voting_streak < 100): ?>
                    <div class="ygv-milestone-text">
                        <?php echo $voting_streak; ?> / <?php echo $next_milestone; ?> <?php echo esc_html__('dana', 'hello-elementor-child'); ?>
                    </div>
                    <?php else: ?>
                    <div class="ygv-milestone-text ygv-milestone-complete">
                        <?php ygv_icon_e('trophy', 20); ?> <?php echo esc_html__('Legendarni streak!', 'hello-elementor-child'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Category Levels -->
    <div class="ygv-card">
        <h3><?php echo esc_html__('Ekspertiza po Kategorijama', 'hello-elementor-child'); ?></h3>
        <p class="ygv-card-subtitle"><?php echo esc_html__('Tvoj nivo u svakoj kategoriji određuje bonus glasova', 'hello-elementor-child'); ?></p>
        
        <?php if (empty($cat_progress)): ?>
            <p class="ygv-muted"><?php echo esc_html__('Još nemaš napredak u kategorijama. Rešavaj kvizove da zaradiš XP!', 'hello-elementor-child'); ?></p>
        <?php else: ?>
            <div class="ygv-category-levels">
                <?php foreach ($cat_progress as $cat): 
                    $cat_level = (int)$cat['level'];
                    $cat_title = ygv_get_title_for_level($cat_level, $level_config);
                    $cat_name = $cat['category_name'] ?: 'Unknown';
                    $icon_name = $category_icons[$cat_name] ?? 'folder';
                    
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
                ?>
                <div class="ygv-category-level-item">
                    <div class="ygv-cat-icon"><?php ygv_icon_e($icon_name, 24); ?></div>
                    <div class="ygv-cat-info">
                        <div class="ygv-cat-name"><?php echo esc_html($cat_name); ?></div>
                        <div class="ygv-cat-level">
                            <?php echo esc_html__('Nivo', 'hello-elementor-child'); ?> <?php echo esc_html($cat_level); ?> 
                            <span class="ygv-cat-title">(<?php echo esc_html($cat_title); ?>)</span>
                        </div>
                        <div class="ygv-cat-xp"><?php echo number_format((int)$cat['xp']); ?> XP</div>
                    </div>
                    <div class="ygv-cat-bonus">
                        <?php if ($vote_bonus > 0): ?>
                            <span class="ygv-bonus-badge">+<?php echo $vote_bonus; ?></span>
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
    
    <!-- Basic Profile Info -->
    <div class="ygv-card">
        <h3><?php echo esc_html__('Osnovni Podaci', 'hello-elementor-child'); ?></h3>
        <div class="ygv-profile-info">
            <p><strong><?php echo esc_html__('Ime', 'hello-elementor-child'); ?>:</strong> <?php echo esc_html($user->display_name); ?></p>
            <p><strong><?php echo esc_html__('Email', 'hello-elementor-child'); ?>:</strong> <?php echo esc_html($user->user_email); ?></p>
            <p><strong><?php echo esc_html__('Pol', 'hello-elementor-child'); ?>:</strong> <?php echo $gender ? esc_html($gender) : '<span class="ygv-muted">'.esc_html__('nije postavljeno', 'hello-elementor-child').'</span>'; ?></p>
            <p><strong><?php echo esc_html__('Datum rođenja', 'hello-elementor-child'); ?>:</strong> <?php echo $dob ? esc_html($dob) : '<span class="ygv-muted">'.esc_html__('nije postavljeno', 'hello-elementor-child').'</span>'; ?></p>
            <p><strong><?php echo esc_html__('Država', 'hello-elementor-child'); ?>:</strong> <?php echo $country ? esc_html($country) : '<span class="ygv-muted">'.esc_html__('nije postavljeno', 'hello-elementor-child').'</span>'; ?></p>
            <p><strong><?php echo esc_html__('Interesovanja', 'hello-elementor-child'); ?>:</strong>
                <?php echo $poi_names ? esc_html(implode(', ', $poi_names)) : '<span class="ygv-muted">'.esc_html__('nema sačuvanih', 'hello-elementor-child').'</span>'; ?>
            </p>
        </div>
        <p style="margin-top: 1rem;"><a class="ygv-btn" href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html__('Uredi profil', 'hello-elementor-child'); ?></a></p>
    </div>
    
</div>
