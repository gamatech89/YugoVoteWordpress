<?php
/**
 * Achievement Service
 * 
 * Tracks and manages user achievements based on their activity.
 * 
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

class YGV_Achievement_Service {
    
    protected $table_name;
    
    /**
     * Achievement definitions with their unlock conditions
     */
    protected $achievement_definitions = [];
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ygv_user_achievements';
        $this->maybe_install_table();
        $this->load_achievement_definitions();
    }
    
    /**
     * Create achievements table if not exists
     */
    protected function maybe_install_table() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;
        
        $charset = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            achievement_id VARCHAR(50) NOT NULL,
            unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            xp_awarded INT UNSIGNED NOT NULL DEFAULT 0,
            UNIQUE KEY user_achievement (user_id, achievement_id),
            KEY user_id (user_id)
        ) $charset;";
        
        dbDelta($sql);
    }
    
    /**
     * Load all achievement definitions
     */
    protected function load_achievement_definitions() {
        $this->achievement_definitions = [
            // Voting achievements
            'first_vote' => [
                'name' => __('Prvi Glas', 'hello-elementor-child'),
                'description' => __('Glasaj na svojoj prvoj listi', 'hello-elementor-child'),
                'icon' => '🗳️',
                'xp_reward' => 10,
                'category' => 'voting',
                'check_type' => 'vote_count',
                'threshold' => 1,
            ],
            'vote_10' => [
                'name' => __('Glasač', 'hello-elementor-child'),
                'description' => __('Glasaj 10 puta', 'hello-elementor-child'),
                'icon' => '🗳️',
                'xp_reward' => 25,
                'category' => 'voting',
                'check_type' => 'vote_count',
                'threshold' => 10,
            ],
            'vote_50' => [
                'name' => __('Aktivni Glasač', 'hello-elementor-child'),
                'description' => __('Glasaj 50 puta', 'hello-elementor-child'),
                'icon' => '🎯',
                'xp_reward' => 50,
                'category' => 'voting',
                'check_type' => 'vote_count',
                'threshold' => 50,
            ],
            'vote_100' => [
                'name' => __('Profesionalni Glasač', 'hello-elementor-child'),
                'description' => __('Glasaj 100 puta', 'hello-elementor-child'),
                'icon' => '🏅',
                'xp_reward' => 100,
                'category' => 'voting',
                'check_type' => 'vote_count',
                'threshold' => 100,
            ],
            'vote_500' => [
                'name' => __('Glasački Veteran', 'hello-elementor-child'),
                'description' => __('Glasaj 500 puta', 'hello-elementor-child'),
                'icon' => '🎖️',
                'xp_reward' => 250,
                'category' => 'voting',
                'check_type' => 'vote_count',
                'threshold' => 500,
            ],
            
            // Quiz achievements
            'first_quiz' => [
                'name' => __('Kviz Početnik', 'hello-elementor-child'),
                'description' => __('Završi svoj prvi kviz', 'hello-elementor-child'),
                'icon' => '🧠',
                'xp_reward' => 15,
                'category' => 'quiz',
                'check_type' => 'quiz_count',
                'threshold' => 1,
            ],
            'quiz_10' => [
                'name' => __('Kviz Entuzijasta', 'hello-elementor-child'),
                'description' => __('Završi 10 kvizova', 'hello-elementor-child'),
                'icon' => '📚',
                'xp_reward' => 50,
                'category' => 'quiz',
                'check_type' => 'quiz_count',
                'threshold' => 10,
            ],
            'quiz_perfect' => [
                'name' => __('Perfektan Rezultat', 'hello-elementor-child'),
                'description' => __('Završi kviz sa 100% tačnih odgovora', 'hello-elementor-child'),
                'icon' => '💯',
                'xp_reward' => 30,
                'category' => 'quiz',
                'check_type' => 'quiz_perfect',
                'threshold' => 1,
            ],
            
            // List creation achievements
            'first_list' => [
                'name' => __('Kreator', 'hello-elementor-child'),
                'description' => __('Kreiraj svoju prvu listu', 'hello-elementor-child'),
                'icon' => '📝',
                'xp_reward' => 50,
                'category' => 'creation',
                'check_type' => 'list_count',
                'threshold' => 1,
            ],
            'list_5' => [
                'name' => __('Aktivni Kreator', 'hello-elementor-child'),
                'description' => __('Kreiraj 5 listi', 'hello-elementor-child'),
                'icon' => '✍️',
                'xp_reward' => 100,
                'category' => 'creation',
                'check_type' => 'list_count',
                'threshold' => 5,
            ],
            
            // Level achievements
            'level_5' => [
                'name' => __('Učenik', 'hello-elementor-child'),
                'description' => __('Dostani globalni nivo 5', 'hello-elementor-child'),
                'icon' => '⭐',
                'xp_reward' => 25,
                'category' => 'level',
                'check_type' => 'overall_level',
                'threshold' => 5,
            ],
            'level_10' => [
                'name' => __('Znalac', 'hello-elementor-child'),
                'description' => __('Dostani globalni nivo 10', 'hello-elementor-child'),
                'icon' => '🌟',
                'xp_reward' => 50,
                'category' => 'level',
                'check_type' => 'overall_level',
                'threshold' => 10,
            ],
            'level_25' => [
                'name' => __('Ekspert', 'hello-elementor-child'),
                'description' => __('Dostani globalni nivo 25', 'hello-elementor-child'),
                'icon' => '💫',
                'xp_reward' => 150,
                'category' => 'level',
                'check_type' => 'overall_level',
                'threshold' => 25,
            ],
            'level_50' => [
                'name' => __('Legenda', 'hello-elementor-child'),
                'description' => __('Dostani globalni nivo 50', 'hello-elementor-child'),
                'icon' => '🏆',
                'xp_reward' => 500,
                'category' => 'level',
                'check_type' => 'overall_level',
                'threshold' => 50,
            ],
            
            // Category level achievements
            'category_level_10' => [
                'name' => __('Specijalista', 'hello-elementor-child'),
                'description' => __('Dostani nivo 10 u bilo kojoj kategoriji', 'hello-elementor-child'),
                'icon' => '🎓',
                'xp_reward' => 75,
                'category' => 'level',
                'check_type' => 'any_category_level',
                'threshold' => 10,
            ],
            'category_level_25' => [
                'name' => __('Master', 'hello-elementor-child'),
                'description' => __('Dostani nivo 25 u bilo kojoj kategoriji', 'hello-elementor-child'),
                'icon' => '🧙',
                'xp_reward' => 200,
                'category' => 'level',
                'check_type' => 'any_category_level',
                'threshold' => 25,
            ],
            
            // Streak achievements (for future)
            'streak_3' => [
                'name' => __('Početak Serije', 'hello-elementor-child'),
                'description' => __('Glasaj 3 dana zaredom', 'hello-elementor-child'),
                'icon' => '🔥',
                'xp_reward' => 20,
                'category' => 'streak',
                'check_type' => 'voting_streak',
                'threshold' => 3,
            ],
            'streak_7' => [
                'name' => __('Nedeljna Serija', 'hello-elementor-child'),
                'description' => __('Glasaj 7 dana zaredom', 'hello-elementor-child'),
                'icon' => '🔥',
                'xp_reward' => 50,
                'category' => 'streak',
                'check_type' => 'voting_streak',
                'threshold' => 7,
            ],
            'streak_30' => [
                'name' => __('Mesečna Serija', 'hello-elementor-child'),
                'description' => __('Glasaj 30 dana zaredom', 'hello-elementor-child'),
                'icon' => '💎',
                'xp_reward' => 200,
                'category' => 'streak',
                'check_type' => 'voting_streak',
                'threshold' => 30,
            ],
        ];
    }
    
    /**
     * Get all achievement definitions
     */
    public function get_definitions(): array {
        return $this->achievement_definitions;
    }
    
    /**
     * Get user's unlocked achievements
     */
    public function get_user_achievements(int $user_id): array {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT achievement_id, unlocked_at, xp_awarded FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);
        
        $unlocked = [];
        foreach ($results as $row) {
            $unlocked[$row['achievement_id']] = [
                'unlocked_at' => $row['unlocked_at'],
                'xp_awarded' => (int)$row['xp_awarded'],
            ];
        }
        
        return $unlocked;
    }
    
    /**
     * Get full achievement data for display (definitions + user progress)
     */
    public function get_achievements_for_user(int $user_id): array {
        $definitions = $this->achievement_definitions;
        $unlocked = $this->get_user_achievements($user_id);
        $stats = $this->get_user_stats($user_id);
        
        $achievements = [];
        
        foreach ($definitions as $id => $def) {
            $is_unlocked = isset($unlocked[$id]);
            $progress = $this->get_progress_for_achievement($def, $stats);
            
            $achievements[] = [
                'id' => $id,
                'name' => $def['name'],
                'description' => $def['description'],
                'icon' => $def['icon'],
                'xp_reward' => $def['xp_reward'],
                'category' => $def['category'],
                'unlocked' => $is_unlocked,
                'unlocked_at' => $is_unlocked ? $unlocked[$id]['unlocked_at'] : null,
                'progress' => $progress['current'],
                'total' => $progress['total'],
            ];
        }
        
        // Sort: unlocked first, then by progress percentage
        usort($achievements, function($a, $b) {
            if ($a['unlocked'] && !$b['unlocked']) return -1;
            if (!$a['unlocked'] && $b['unlocked']) return 1;
            
            // Both locked - sort by progress percentage
            $a_pct = $a['total'] > 0 ? $a['progress'] / $a['total'] : 0;
            $b_pct = $b['total'] > 0 ? $b['progress'] / $b['total'] : 0;
            return $b_pct <=> $a_pct;
        });
        
        return $achievements;
    }
    
    /**
     * Get user statistics for achievement checking
     */
    protected function get_user_stats(int $user_id): array {
        global $wpdb;
        
        $stats = [
            'vote_count' => 0,
            'quiz_count' => 0,
            'quiz_perfect_count' => 0,
            'list_count' => 0,
            'overall_level' => 1,
            'max_category_level' => 1,
            'voting_streak' => 0,
        ];
        
        // Vote count
        $votes_table = $wpdb->prefix . 'voting_list_votes';
        $stats['vote_count'] = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$votes_table} WHERE user_id = %d",
            $user_id
        ));
        
        // Quiz count and perfect quizzes
        $quiz_table = $wpdb->prefix . 'ygv_user_quiz_progress';
        $quiz_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total, SUM(CASE WHEN best_percent = 100 THEN 1 ELSE 0 END) as perfect 
             FROM {$quiz_table} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);
        $stats['quiz_count'] = (int)($quiz_stats['total'] ?? 0);
        $stats['quiz_perfect_count'] = (int)($quiz_stats['perfect'] ?? 0);
        
        // Lists created by user
        $stats['list_count'] = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'voting_list' AND post_author = %d AND post_status = 'publish'",
            $user_id
        ));
        
        // Overall level
        $overall_table = $wpdb->prefix . 'ygv_user_overall_progress';
        $stats['overall_level'] = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT overall_level FROM {$overall_table} WHERE user_id = %d",
            $user_id
        )) ?: 1;
        
        // Max category level
        $cat_table = $wpdb->prefix . 'ygv_user_category_progress';
        $stats['max_category_level'] = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT MAX(level) FROM {$cat_table} WHERE user_id = %d",
            $user_id
        )) ?: 1;
        
        // Voting streak (simplified - days with votes)
        $stats['voting_streak'] = $this->calculate_voting_streak($user_id);
        
        return $stats;
    }
    
    /**
     * Calculate user's current voting streak
     */
    protected function calculate_voting_streak(int $user_id): int {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'voting_list_votes';
        
        // Get distinct dates user voted (last 60 days)
        $dates = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT DATE(created_at) as vote_date 
             FROM {$votes_table} 
             WHERE user_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
             ORDER BY vote_date DESC",
            $user_id
        ));
        
        if (empty($dates)) return 0;
        
        $streak = 0;
        $today = gmdate('Y-m-d');
        $yesterday = gmdate('Y-m-d', strtotime('-1 day'));
        
        // Check if streak is active (voted today or yesterday)
        if ($dates[0] !== $today && $dates[0] !== $yesterday) {
            return 0; // Streak broken
        }
        
        // Count consecutive days
        $expected_date = $dates[0];
        foreach ($dates as $date) {
            if ($date === $expected_date) {
                $streak++;
                $expected_date = gmdate('Y-m-d', strtotime($expected_date . ' -1 day'));
            } else {
                break; // Gap found
            }
        }
        
        return $streak;
    }
    
    /**
     * Get progress for a specific achievement
     */
    protected function get_progress_for_achievement(array $def, array $stats): array {
        $check_type = $def['check_type'];
        $threshold = $def['threshold'];
        
        $current = 0;
        switch ($check_type) {
            case 'vote_count':
                $current = $stats['vote_count'];
                break;
            case 'quiz_count':
                $current = $stats['quiz_count'];
                break;
            case 'quiz_perfect':
                $current = $stats['quiz_perfect_count'];
                break;
            case 'list_count':
                $current = $stats['list_count'];
                break;
            case 'overall_level':
                $current = $stats['overall_level'];
                break;
            case 'any_category_level':
                $current = $stats['max_category_level'];
                break;
            case 'voting_streak':
                $current = $stats['voting_streak'];
                break;
        }
        
        return [
            'current' => min($current, $threshold), // Cap at threshold for display
            'total' => $threshold,
        ];
    }
    
    /**
     * Check and unlock new achievements for user
     * Call this after actions like voting, completing quizzes, etc.
     * 
     * @return array Newly unlocked achievements
     */
    public function check_and_unlock(int $user_id): array {
        $definitions = $this->achievement_definitions;
        $already_unlocked = $this->get_user_achievements($user_id);
        $stats = $this->get_user_stats($user_id);
        
        $newly_unlocked = [];
        
        foreach ($definitions as $id => $def) {
            // Skip if already unlocked
            if (isset($already_unlocked[$id])) continue;
            
            // Check if threshold is met
            $progress = $this->get_progress_for_achievement($def, $stats);
            if ($progress['current'] >= $progress['total']) {
                // Unlock it!
                $this->unlock_achievement($user_id, $id, $def['xp_reward']);
                
                $newly_unlocked[] = [
                    'id' => $id,
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'icon' => $def['icon'],
                    'xp_reward' => $def['xp_reward'],
                ];
            }
        }
        
        return $newly_unlocked;
    }
    
    /**
     * Unlock a specific achievement
     */
    protected function unlock_achievement(int $user_id, string $achievement_id, int $xp_reward): bool {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'achievement_id' => $achievement_id,
                'unlocked_at' => current_time('mysql', true),
                'xp_awarded' => $xp_reward,
            ],
            ['%d', '%s', '%s', '%d']
        );
        
        // Award XP for the achievement (to overall, no specific category)
        // We add to a "meta" category or overall directly
        if ($result && $xp_reward > 0) {
            // Get first category the user has progress in, or use 0 for overall only
            $cat_table = $wpdb->prefix . 'ygv_user_category_progress';
            $first_cat = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT category_term_id FROM {$cat_table} WHERE user_id = %d ORDER BY xp DESC LIMIT 1",
                $user_id
            ));
            
            if ($first_cat > 0 && class_exists('YGV_Progress_Service')) {
                $progress = new YGV_Progress_Service();
                $progress->add_xp($user_id, $first_cat, $xp_reward);
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Get achievement statistics for user
     */
    public function get_stats(int $user_id): array {
        $unlocked = $this->get_user_achievements($user_id);
        $total = count($this->achievement_definitions);
        $unlocked_count = count($unlocked);
        
        $total_xp_earned = 0;
        foreach ($unlocked as $data) {
            $total_xp_earned += $data['xp_awarded'];
        }
        
        return [
            'unlocked' => $unlocked_count,
            'total' => $total,
            'percentage' => $total > 0 ? round(($unlocked_count / $total) * 100) : 0,
            'xp_earned' => $total_xp_earned,
        ];
    }
}
