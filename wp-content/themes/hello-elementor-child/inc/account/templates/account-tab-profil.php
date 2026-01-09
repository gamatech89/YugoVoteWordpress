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

// Category icons/emojis
$category_icons = [
    'Sport' => '⚽',
    'Muzika' => '🎵',
    'Film' => '🎬',
    'FIlm' => '🎬',
    'Film i TV' => '🎬',
    'Biznis' => '💼',
    'Culture Club' => '🎭',
    'Trendy' => '🔥',
    'Lifestyle' => '🔥',
    'Trendy/Lifestyle' => '🔥',
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
                    $icon = $category_icons[$cat_name] ?? '📁';
                    
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
                    <div class="ygv-cat-icon"><?php echo $icon; ?></div>
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
