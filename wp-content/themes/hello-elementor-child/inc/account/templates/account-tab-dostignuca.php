<?php if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();

// Load achievement service
require_once get_stylesheet_directory() . '/inc/quizzes/services/class-ygv-achievement-service.php';
$achievement_service = new YGV_Achievement_Service();

// Check for newly unlocked achievements
$newly_unlocked = $achievement_service->check_and_unlock($user_id);

// Get all achievements with user progress
$achievements = $achievement_service->get_achievements_for_user($user_id);
$stats = $achievement_service->get_stats($user_id);

// Group achievements by category
$categories = [
    'voting' => ['name' => __('Glasanje', 'hello-elementor-child'), 'icon' => '🗳️', 'achievements' => []],
    'quiz' => ['name' => __('Kvizovi', 'hello-elementor-child'), 'icon' => '🧠', 'achievements' => []],
    'creation' => ['name' => __('Kreiranje', 'hello-elementor-child'), 'icon' => '📝', 'achievements' => []],
    'level' => ['name' => __('Nivoi', 'hello-elementor-child'), 'icon' => '⭐', 'achievements' => []],
    'streak' => ['name' => __('Serije', 'hello-elementor-child'), 'icon' => '🔥', 'achievements' => []],
];

foreach ($achievements as $achievement) {
    $cat = $achievement['category'] ?? 'other';
    if (isset($categories[$cat])) {
        $categories[$cat]['achievements'][] = $achievement;
    }
}
?>

<div class="ygv-achievements">
    
    <!-- Stats Overview -->
    <div class="ygv-card ygv-achievements-summary">
        <div class="ygv-achievements-stats">
            <div class="ygv-stat-circle">
                <svg viewBox="0 0 36 36" class="ygv-circular-progress">
                    <path class="ygv-circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="ygv-circle-progress" stroke-dasharray="<?php echo $stats['percentage']; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <div class="ygv-stat-circle-content">
                    <span class="ygv-stat-number"><?php echo $stats['unlocked']; ?></span>
                    <span class="ygv-stat-divider">/</span>
                    <span class="ygv-stat-total"><?php echo $stats['total']; ?></span>
                </div>
            </div>
            <div class="ygv-achievements-meta">
                <h3><?php echo esc_html__('Dostignuća', 'hello-elementor-child'); ?></h3>
                <p class="ygv-xp-earned">
                    <span class="ygv-xp-icon">✨</span>
                    <?php printf(
                        esc_html__('%d XP zarađeno od dostignuća', 'hello-elementor-child'),
                        $stats['xp_earned']
                    ); ?>
                </p>
            </div>
        </div>
    </div>
    
    <?php if (!empty($newly_unlocked)): ?>
    <!-- Newly Unlocked Banner -->
    <div class="ygv-card ygv-newly-unlocked">
        <div class="ygv-unlocked-banner">
            <span class="ygv-banner-icon">🎉</span>
            <div class="ygv-banner-content">
                <strong><?php echo esc_html__('Nova dostignuća otključana!', 'hello-elementor-child'); ?></strong>
                <div class="ygv-new-achievements-list">
                    <?php foreach ($newly_unlocked as $new): ?>
                    <span class="ygv-new-achievement">
                        <?php echo $new['icon']; ?> <?php echo esc_html($new['name']); ?>
                        <span class="ygv-xp-badge">+<?php echo $new['xp_reward']; ?> XP</span>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Achievement Categories -->
    <?php foreach ($categories as $cat_id => $category): 
        if (empty($category['achievements'])) continue;
        $unlocked_in_cat = count(array_filter($category['achievements'], fn($a) => $a['unlocked']));
        $total_in_cat = count($category['achievements']);
    ?>
    <div class="ygv-card ygv-achievement-category">
        <div class="ygv-card-header">
            <h3>
                <span class="ygv-cat-icon"><?php echo $category['icon']; ?></span>
                <?php echo esc_html($category['name']); ?>
            </h3>
            <span class="ygv-achievement-count"><?php echo $unlocked_in_cat; ?>/<?php echo $total_in_cat; ?></span>
        </div>
        
        <div class="ygv-achievements-grid">
            <?php foreach ($category['achievements'] as $achievement): 
                $is_unlocked = $achievement['unlocked'];
                $has_progress = $achievement['total'] > 0;
                $progress_pct = $has_progress ? ($achievement['progress'] / $achievement['total']) * 100 : 0;
            ?>
            <div class="ygv-achievement-card <?php echo $is_unlocked ? 'ygv-unlocked' : 'ygv-locked'; ?>">
                <div class="ygv-achievement-icon <?php echo $is_unlocked ? '' : 'ygv-grayscale'; ?>">
                    <?php echo $achievement['icon']; ?>
                    <?php if ($is_unlocked): ?>
                    <span class="ygv-unlocked-check">✓</span>
                    <?php endif; ?>
                </div>
                <div class="ygv-achievement-info">
                    <h4 class="ygv-achievement-name"><?php echo esc_html($achievement['name']); ?></h4>
                    <p class="ygv-achievement-desc"><?php echo esc_html($achievement['description']); ?></p>
                    
                    <?php if ($has_progress && !$is_unlocked): ?>
                    <div class="ygv-achievement-progress">
                        <div class="ygv-progress-bar">
                            <div class="ygv-progress-fill" style="width: <?php echo $progress_pct; ?>%"></div>
                        </div>
                        <span class="ygv-progress-text"><?php echo $achievement['progress']; ?>/<?php echo $achievement['total']; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ygv-achievement-reward">
                        <?php if ($is_unlocked): ?>
                            <span class="ygv-reward-earned">
                                ✅ <?php echo esc_html__('Otključano', 'hello-elementor-child'); ?>
                            </span>
                            <?php if ($achievement['unlocked_at']): ?>
                            <span class="ygv-unlocked-date">
                                <?php echo date_i18n('d.m.Y', strtotime($achievement['unlocked_at'])); ?>
                            </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="ygv-reward-xp">+<?php echo $achievement['xp_reward']; ?> XP</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
</div>
