<?php if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();

// Placeholder achievements - this will be expanded later
$achievements = [
    [
        'id' => 'first_vote',
        'name' => __('Prvi Glas', 'hello-elementor-child'),
        'description' => __('Glasaj na svojoj prvoj listi', 'hello-elementor-child'),
        'icon' => '🗳️',
        'unlocked' => true, // TODO: Check actual status
        'xp_reward' => 10,
    ],
    [
        'id' => 'quiz_master',
        'name' => __('Kviz Master', 'hello-elementor-child'),
        'description' => __('Reši 10 kvizova', 'hello-elementor-child'),
        'icon' => '🧠',
        'unlocked' => false,
        'progress' => 3,
        'total' => 10,
        'xp_reward' => 50,
    ],
    [
        'id' => 'sport_fan',
        'name' => __('Sport Fan', 'hello-elementor-child'),
        'description' => __('Dostani nivo 10 u Sport kategoriji', 'hello-elementor-child'),
        'icon' => '⚽',
        'unlocked' => false,
        'progress' => 2,
        'total' => 10,
        'xp_reward' => 100,
    ],
    [
        'id' => 'list_creator',
        'name' => __('Kreator', 'hello-elementor-child'),
        'description' => __('Kreiraj svoju prvu listu', 'hello-elementor-child'),
        'icon' => '📝',
        'unlocked' => false,
        'xp_reward' => 50,
    ],
    [
        'id' => 'social_butterfly',
        'name' => __('Društvena Mreža', 'hello-elementor-child'),
        'description' => __('Pozovi 5 prijatelja', 'hello-elementor-child'),
        'icon' => '👥',
        'unlocked' => false,
        'progress' => 0,
        'total' => 5,
        'xp_reward' => 200,
    ],
    [
        'id' => 'legend',
        'name' => __('Legenda', 'hello-elementor-child'),
        'description' => __('Dostani globalni nivo 40', 'hello-elementor-child'),
        'icon' => '🏆',
        'unlocked' => false,
        'progress' => 1,
        'total' => 40,
        'xp_reward' => 500,
    ],
];

$unlocked_count = count(array_filter($achievements, fn($a) => $a['unlocked']));
$total_count = count($achievements);
?>

<div class="ygv-achievements">
    
    <div class="ygv-card">
        <div class="ygv-card-header">
            <h3><?php echo esc_html__('Dostignuća', 'hello-elementor-child'); ?></h3>
            <span class="ygv-achievement-count"><?php echo $unlocked_count; ?>/<?php echo $total_count; ?></span>
        </div>
        
        <div class="ygv-coming-soon-banner">
            <span class="ygv-banner-icon">🚧</span>
            <div class="ygv-banner-content">
                <strong><?php echo esc_html__('Dostignuća dolaze uskoro!', 'hello-elementor-child'); ?></strong>
                <p><?php echo esc_html__('Radimo na sistemu dostignuća. Evo pregled onoga što dolazi...', 'hello-elementor-child'); ?></p>
            </div>
        </div>
        
        <div class="ygv-achievements-grid">
            <?php foreach ($achievements as $achievement): 
                $is_unlocked = $achievement['unlocked'];
                $has_progress = isset($achievement['progress']) && isset($achievement['total']);
            ?>
            <div class="ygv-achievement-card <?php echo $is_unlocked ? 'ygv-unlocked' : 'ygv-locked'; ?>">
                <div class="ygv-achievement-icon <?php echo $is_unlocked ? '' : 'ygv-grayscale'; ?>">
                    <?php echo $achievement['icon']; ?>
                </div>
                <div class="ygv-achievement-info">
                    <h4 class="ygv-achievement-name"><?php echo esc_html($achievement['name']); ?></h4>
                    <p class="ygv-achievement-desc"><?php echo esc_html($achievement['description']); ?></p>
                    
                    <?php if ($has_progress && !$is_unlocked): ?>
                    <div class="ygv-achievement-progress">
                        <div class="ygv-progress-bar">
                            <div class="ygv-progress-fill" style="width: <?php echo ($achievement['progress'] / $achievement['total']) * 100; ?>%"></div>
                        </div>
                        <span class="ygv-progress-text"><?php echo $achievement['progress']; ?>/<?php echo $achievement['total']; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ygv-achievement-reward">
                        <?php if ($is_unlocked): ?>
                            <span class="ygv-reward-earned">✅ <?php echo esc_html__('Otključano', 'hello-elementor-child'); ?></span>
                        <?php else: ?>
                            <span class="ygv-reward-xp">+<?php echo $achievement['xp_reward']; ?> XP</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
</div>
