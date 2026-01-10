<?php if (!defined('ABSPATH')) exit;

// Ensure icons are loaded
if (!function_exists('ygv_icon_e')) {
    require_once get_stylesheet_directory() . '/inc/icons.php';
}

$user_id = get_current_user_id();
global $wpdb;

// Get quiz progress table
$t_quiz = $wpdb->prefix . 'ygv_user_quiz_progress';
$t_cat = $wpdb->prefix . 'ygv_user_category_progress';

// Get user's quiz history (all quizzes they've attempted)
$quiz_progress = $wpdb->get_results($wpdb->prepare(
    "SELECT qp.*, p.post_title as quiz_title, 
            GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as category_names
     FROM {$t_quiz} qp
     LEFT JOIN {$wpdb->posts} p ON qp.quiz_id = p.ID
     LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
     LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
     LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
     WHERE qp.user_id = %d 
       AND p.post_status = 'publish'
       AND (tt.taxonomy = 'quiz_category' OR tt.taxonomy IS NULL)
     GROUP BY qp.quiz_id
     ORDER BY qp.last_attempt_at DESC",
    $user_id
), ARRAY_A);

// Calculate stats
$total_quizzes = count($quiz_progress);
$total_attempts = array_sum(array_column($quiz_progress, 'attempts'));
$total_xp = array_sum(array_column($quiz_progress, 'awarded_xp'));
$perfect_scores = count(array_filter($quiz_progress, fn($q) => (int)$q['best_percent'] >= 100));

// Get top categories (by XP)
$category_progress = $wpdb->get_results($wpdb->prepare(
    "SELECT cp.*, t.name as category_name
     FROM {$t_cat} cp
     LEFT JOIN {$wpdb->terms} t ON cp.category_term_id = t.term_id
     WHERE cp.user_id = %d
     ORDER BY cp.xp DESC
     LIMIT 5",
    $user_id
), ARRAY_A);

// Get available quizzes count
$available_quizzes = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'quiz' AND post_status = 'publish'"
);

// Token info - use Token Service for accurate data
$current_tokens = 0;
$max_tokens = 48;
$next_refill = 0;
$regen_interval = 60; // minutes

if (function_exists('ygv_tokens')) {
    $wallet = ygv_tokens()->current_wallet($user_id);
    
    $tokens_in_db = (int)($wallet['tokens'] ?? 0);
    $max_tokens = (int)($wallet['max_tokens'] ?? 48);
    $regen_rate = (int)($wallet['regen_rate'] ?? 2);
    $regen_interval = (int)($wallet['regen_interval_minutes'] ?? 60);
    $refill_anchor = (int)($wallet['refill_anchor'] ?? time());
    
    // Calculate current tokens based on refill
    $now = time();
    $elapsed = $now - $refill_anchor;
    $regen_interval_sec = $regen_interval * 60;
    $intervals_passed = floor($elapsed / $regen_interval_sec);
    $regenerated = $intervals_passed * $regen_rate;
    $current_tokens = min($max_tokens, $tokens_in_db + $regenerated);
    
    // Time until next token
    if ($current_tokens < $max_tokens) {
        $next_refill = $regen_interval_sec - ($elapsed % $regen_interval_sec);
    }
}

// Level config for titles
$level_config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : null;
?>

<div class="ygv-quizzes-dashboard">

    <!-- Token Status Card -->
    <div class="ygv-card ygv-token-card">
        <div class="ygv-token-content">
            <div class="ygv-token-icon"><?php ygv_icon_e('gamepad', 32); ?></div>
            <div class="ygv-token-info">
                <h3 class="ygv-token-title"><?php echo esc_html__('Quiz Tokeni', 'hello-elementor-child'); ?></h3>
                <div class="ygv-token-count">
                    <span class="ygv-token-current"><?php echo $current_tokens; ?></span>
                    <span class="ygv-token-divider">/</span>
                    <span class="ygv-token-max"><?php echo $max_tokens; ?></span>
                </div>
                <?php if ($current_tokens < $max_tokens && $next_refill > 0): ?>
                <div class="ygv-token-refill">
                    <?php echo esc_html__('Sledeći token za', 'hello-elementor-child'); ?>: 
                    <span class="ygv-token-timer" data-seconds="<?php echo $next_refill; ?>">
                        <?php echo gmdate('H:i:s', $next_refill); ?>
                    </span>
                </div>
                <?php elseif ($current_tokens >= $max_tokens): ?>
                <div class="ygv-token-full"><?php echo esc_html__('Tokeni su puni!', 'hello-elementor-child'); ?></div>
                <?php endif; ?>
            </div>
            <div class="ygv-token-bar-wrapper">
                <div class="ygv-token-bar">
                    <div class="ygv-token-bar-fill" style="width: <?php echo ($current_tokens / $max_tokens) * 100; ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quiz Stats Overview -->
    <div class="ygv-card ygv-quiz-stats-card">
        <h3><?php ygv_icon_e('chart-bar', 20); ?> <?php echo esc_html__('Pregled Kvizova', 'hello-elementor-child'); ?></h3>
        
        <div class="ygv-quiz-stats-grid">
            <div class="ygv-quiz-stat-box">
                <span class="ygv-quiz-stat-icon"><?php ygv_icon_e('clipboard-edit', 24); ?></span>
                <span class="ygv-quiz-stat-number"><?php echo number_format($total_quizzes); ?></span>
                <span class="ygv-quiz-stat-label"><?php echo esc_html__('Rešenih Kvizova', 'hello-elementor-child'); ?></span>
            </div>
            <div class="ygv-quiz-stat-box">
                <span class="ygv-quiz-stat-icon"><?php ygv_icon_e('refresh', 24); ?></span>
                <span class="ygv-quiz-stat-number"><?php echo number_format($total_attempts); ?></span>
                <span class="ygv-quiz-stat-label"><?php echo esc_html__('Ukupno Pokušaja', 'hello-elementor-child'); ?></span>
            </div>
            <div class="ygv-quiz-stat-box">
                <span class="ygv-quiz-stat-icon"><?php ygv_icon_e('star', 24); ?></span>
                <span class="ygv-quiz-stat-number"><?php echo number_format($total_xp); ?></span>
                <span class="ygv-quiz-stat-label"><?php echo esc_html__('XP iz Kvizova', 'hello-elementor-child'); ?></span>
            </div>
            <div class="ygv-quiz-stat-box">
                <span class="ygv-quiz-stat-icon"><?php ygv_icon_e('trophy', 24); ?></span>
                <span class="ygv-quiz-stat-number"><?php echo number_format($perfect_scores); ?></span>
                <span class="ygv-quiz-stat-label"><?php echo esc_html__('Savršenih Rezultata', 'hello-elementor-child'); ?></span>
            </div>
        </div>
        
        <div class="ygv-quiz-progress-overview">
            <div class="ygv-quiz-progress-text">
                <?php echo esc_html__('Napredak:', 'hello-elementor-child'); ?> 
                <strong><?php echo $total_quizzes; ?></strong> / <strong><?php echo $available_quizzes; ?></strong> 
                <?php echo esc_html__('kvizova rešeno', 'hello-elementor-child'); ?>
            </div>
            <div class="ygv-progress-unified">
                <div class="ygv-progress-unified__fill" style="width: <?php echo $available_quizzes > 0 ? ($total_quizzes / $available_quizzes) * 100 : 0; ?>%; --cat-color: #f59e0b;"></div>
            </div>
        </div>
    </div>

    <!-- Category Progress -->
    <?php if (!empty($category_progress)): ?>
    <div class="ygv-card">
        <h3><?php ygv_icon_e('medal', 20); ?> <?php echo esc_html__('Top Kategorije', 'hello-elementor-child'); ?></h3>
        <p class="ygv-card-subtitle"><?php echo esc_html__('Tvoj napredak u kategorijama kvizova', 'hello-elementor-child'); ?></p>
        
        <div class="ygv-category-progress-list">
            <?php foreach ($category_progress as $cat): 
                $cat_level = (int)$cat['level'];
                $cat_xp = (int)$cat['xp'];
                $cat_term_id = (int)$cat['category_term_id'];
                
                // Get category color
                $cat_color = function_exists('ygv_get_quiz_category_color') 
                    ? ygv_get_quiz_category_color($cat_term_id) 
                    : '#6db24a';
                
                // Get title for level
                $cat_title = 'Rookie';
                if ($level_config) {
                    foreach ($level_config['tiers'] as $tier) {
                        if ($cat_level >= $tier['min_level'] && $cat_level <= $tier['max_level']) {
                            $cat_title = $tier['title'];
                            break;
                        }
                    }
                }
            ?>
            <div class="ygv-cat-progress-item" style="--cat-color: <?php echo esc_attr($cat_color); ?>;">
                <div class="ygv-cat-progress-level" style="background: var(--cat-color);">
                    <span class="ygv-cat-level-number"><?php echo $cat_level; ?></span>
                </div>
                <div class="ygv-cat-progress-info">
                    <div class="ygv-cat-progress-name"><?php echo esc_html($cat['category_name'] ?? 'Unknown'); ?></div>
                    <div class="ygv-cat-progress-title" style="color: var(--cat-color);"><?php echo esc_html($cat_title); ?></div>
                </div>
                <div class="ygv-cat-progress-xp">
                    <?php echo number_format($cat_xp); ?> XP
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quiz History -->
    <div class="ygv-card">
        <div class="ygv-card-header">
            <h3><?php ygv_icon_e('history', 20); ?> <?php echo esc_html__('Istorija Kvizova', 'hello-elementor-child'); ?></h3>
            <a href="<?php echo esc_url(home_url('/kvizovi/')); ?>" class="ygv-btn ygv-btn-primary ygv-btn-small">
                <?php echo esc_html__('Istraži Kvizove', 'hello-elementor-child'); ?>
            </a>
        </div>
        
        <?php if (empty($quiz_progress)): ?>
            <div class="ygv-empty-state">
                <span class="ygv-empty-icon"><?php ygv_icon_e('brain', 48); ?></span>
                <h4><?php echo esc_html__('Još nisi rešio/la nijedan kviz', 'hello-elementor-child'); ?></h4>
                <p><?php echo esc_html__('Rešavaj kvizove da zaradiš XP i napreduj u kategorijama!', 'hello-elementor-child'); ?></p>
                <a href="<?php echo esc_url(home_url('/kvizovi/')); ?>" class="ygv-btn ygv-btn-primary">
                    <?php echo esc_html__('Počni sa Kvizovima', 'hello-elementor-child'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="ygv-quiz-history-list">
                <?php foreach ($quiz_progress as $quiz): 
                    $quiz_id = (int)$quiz['quiz_id'];
                    $best_percent = (int)$quiz['best_percent'];
                    $attempts = (int)$quiz['attempts'];
                    $awarded_xp = (int)$quiz['awarded_xp'];
                    $last_attempt = $quiz['last_attempt_at'];
                    
                    // Get difficulty
                    $difficulty = get_post_meta($quiz_id, '_quiz_difficulty', true) ?: 'medium';
                    $difficulty_labels = [
                        'easy' => __('Lako', 'hello-elementor-child'),
                        'medium' => __('Srednje', 'hello-elementor-child'),
                        'hard' => __('Teško', 'hello-elementor-child'),
                    ];
                    $difficulty_label = $difficulty_labels[$difficulty] ?? $difficulty;
                    
                    // Time ago
                    $time_ago = human_time_diff(strtotime($last_attempt), current_time('timestamp'));
                    
                    // Score color class
                    $score_class = 'ygv-score-low';
                    if ($best_percent >= 80) $score_class = 'ygv-score-high';
                    elseif ($best_percent >= 50) $score_class = 'ygv-score-medium';
                ?>
                <div class="ygv-quiz-history-item">
                    <div class="ygv-quiz-history-score <?php echo esc_attr($score_class); ?>">
                        <span class="ygv-quiz-score-percent"><?php echo $best_percent; ?>%</span>
                        <?php if ($best_percent >= 100): ?>
                            <span class="ygv-quiz-perfect"><?php ygv_icon_e('trophy', 16); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ygv-quiz-history-content">
                        <h4 class="ygv-quiz-history-title">
                            <a href="<?php echo esc_url(get_permalink($quiz_id)); ?>">
                                <?php echo esc_html($quiz['quiz_title'] ?? 'Quiz #'.$quiz_id); ?>
                            </a>
                        </h4>
                        <?php if (!empty($quiz['category_names'])): ?>
                            <span class="ygv-quiz-history-category"><?php echo esc_html($quiz['category_names']); ?></span>
                        <?php endif; ?>
                        <div class="ygv-quiz-history-meta">
                            <span class="ygv-quiz-meta-difficulty ygv-difficulty-<?php echo esc_attr($difficulty); ?>">
                                <?php echo esc_html($difficulty_label); ?>
                            </span>
                            <span class="ygv-quiz-meta-attempts" title="<?php echo esc_attr__('Broj pokušaja', 'hello-elementor-child'); ?>">
                                <?php ygv_icon_e('refresh', 14); ?> <?php echo $attempts; ?>x
                            </span>
                            <span class="ygv-quiz-meta-xp" title="<?php echo esc_attr__('Zarađen XP', 'hello-elementor-child'); ?>">
                                <?php ygv_icon_e('star', 14); ?> <?php echo number_format($awarded_xp); ?> XP
                            </span>
                            <span class="ygv-quiz-meta-time"><?php echo esc_html($time_ago); ?> <?php echo esc_html__('pre', 'hello-elementor-child'); ?></span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url(get_permalink($quiz_id)); ?>" class="ygv-quiz-history-action" title="<?php echo esc_attr__('Ponovo reši', 'hello-elementor-child'); ?>">
                        <?php if ($best_percent < 100): ?>
                            <?php ygv_icon_e('refresh', 20); ?>
                        <?php else: ?>
                            <?php ygv_icon_e('check-circle', 20); ?>
                        <?php endif; ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
// Token timer countdown
document.addEventListener('DOMContentLoaded', function() {
    const timer = document.querySelector('.ygv-token-timer');
    if (!timer) return;
    
    let seconds = parseInt(timer.dataset.seconds, 10);
    if (isNaN(seconds) || seconds <= 0) return;
    
    const updateTimer = () => {
        if (seconds <= 0) {
            location.reload();
            return;
        }
        
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        
        timer.textContent = 
            String(h).padStart(2, '0') + ':' +
            String(m).padStart(2, '0') + ':' +
            String(s).padStart(2, '0');
        
        seconds--;
    };
    
    setInterval(updateTimer, 1000);
});
</script>