<?php
// inc/quizzes/services/class-ygv-progress-service.php
if (!defined('ABSPATH')) exit;

class YGV_Progress_Service {
    protected $t_quiz;
    protected $t_cat;
    protected $t_over;

    public function __construct() {
        global $wpdb;
        $p = $wpdb->prefix;
        $this->t_quiz = $p . 'ygv_user_quiz_progress';
        $this->t_cat  = $p . 'ygv_user_category_progress';
        $this->t_over = $p . 'ygv_user_overall_progress';
        $this->maybe_install_tables();
    }

    /** ---------------- INSTALL ---------------- */
    protected function maybe_install_tables() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $sql_quiz = "CREATE TABLE {$this->t_quiz} (
            user_id BIGINT UNSIGNED NOT NULL,
            quiz_id BIGINT UNSIGNED NOT NULL,
            best_percent SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            awarded_xp INT UNSIGNED NOT NULL DEFAULT 0,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            last_attempt_at DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
            PRIMARY KEY (user_id, quiz_id)
        ) $charset;";

        $sql_cat = "CREATE TABLE {$this->t_cat} (
            user_id BIGINT UNSIGNED NOT NULL,
            category_term_id BIGINT UNSIGNED NOT NULL,
            xp INT UNSIGNED NOT NULL DEFAULT 0,
            level SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            streak SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            last_attempt_at DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
            PRIMARY KEY (user_id, category_term_id)
        ) $charset;";

        $sql_over = "CREATE TABLE {$this->t_over} (
            user_id BIGINT UNSIGNED NOT NULL,
            overall_xp INT UNSIGNED NOT NULL DEFAULT 0,
            overall_level SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            last_updated DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
            PRIMARY KEY (user_id)
        ) $charset;";

        dbDelta($sql_quiz);
        dbDelta($sql_cat);
        dbDelta($sql_over);
    }

    /** ---------------- LEVELS / THRESHOLDS ---------------- */
    public function get_thresholds($scope = 'category'): array {
        // Load from configurable settings if available
        if (function_exists('ygv_get_level_config')) {
            $config = ygv_get_level_config();
            $thresholds = $config['xp_thresholds'] ?? [];
            $xp_per_level = $config['xp_per_level_after_10'] ?? 300;
            $max_level = $config['max_level'] ?? 100;
            
            // Build thresholds for levels 11+
            $last_threshold = $thresholds[10] ?? 1250;
            for ($lvl = 11; $lvl <= $max_level; $lvl++) {
                $last_threshold += $xp_per_level;
                $thresholds[$lvl] = $last_threshold;
            }
            
            return $thresholds;
        }
        
        // Fallback default: L1=0, L2=50, L3=120, L4=210, L5=320 ...
        return [1=>0, 2=>50, 3=>120, 4=>210, 5=>320, 6=>450, 7=>620, 8=>830, 9=>1080, 10=>1370];
    }

    /** returns ['level'=>X, 'next_xp'=>N | null] based on xp */
    public function xp_to_level(int $xp, string $scope = 'category'): array {
        $thr = $this->get_thresholds($scope);
        $max_level = function_exists('ygv_get_level_config') 
            ? (ygv_get_level_config()['max_level'] ?? 100) 
            : 100;
        
        $lvl = 1; 
        $next_xp = null;
        
        foreach ($thr as $L => $need) {
            if ($L > $max_level) break;
            if ($xp >= $need) { 
                $lvl = $L; 
            } else { 
                $next_xp = $need; 
                break; 
            }
        }
        
        return ['level'=>$lvl, 'next_xp'=>$next_xp];
    }
    
    /**
     * Get vote bonus info for a user in a specific category
     * 
     * @param int $user_id
     * @param int $category_term_id Parent category term ID
     * @return array ['bonus' => int, 'title' => string, 'level' => int, 'xp' => int]
     */
    public function get_vote_bonus(int $user_id, int $category_term_id): array {
        global $wpdb;
        
        // Get user's XP and level in this category
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT xp, level FROM {$this->t_cat} WHERE user_id = %d AND category_term_id = %d",
            $user_id,
            $category_term_id
        ), ARRAY_A);
        
        $xp = (int)($row['xp'] ?? 0);
        $level = (int)($row['level'] ?? 1);
        
        // Get bonus from config
        $bonus = 0;
        $title = 'Rookie';
        
        if (function_exists('ygv_get_level_config')) {
            $config = ygv_get_level_config();
            foreach ($config['tiers'] as $tier) {
                if ($level >= $tier['min_level'] && $level <= $tier['max_level']) {
                    $bonus = (int)$tier['vote_bonus'];
                    $title = $tier['title'];
                    break;
                }
            }
        }
        
        return [
            'bonus' => $bonus,
            'title' => $title,
            'level' => $level,
            'xp' => $xp
        ];
    }
    
    /**
     * Get user's level title for a category
     * 
     * @param int $level
     * @return string
     */
    public function get_level_title(int $level): string {
        if (function_exists('ygv_get_level_config')) {
            $config = ygv_get_level_config();
            foreach ($config['tiers'] as $tier) {
                if ($level >= $tier['min_level'] && $level <= $tier['max_level']) {
                    return $tier['title'];
                }
            }
        }
        return 'Rookie';
    }

    /** ---------------- CATEGORY & OVERALL ---------------- */
    public function add_xp(int $user_id, int $term_id, int $xp): array {
        global $wpdb;
        if ($xp <= 0) return ['awarded'=>0];

        // Category row
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->t_cat} WHERE user_id=%d AND category_term_id=%d",
            $user_id, $term_id
        ), ARRAY_A);
        if (!$row) {
            $row = ['user_id'=>$user_id,'category_term_id'=>$term_id,'xp'=>0,'level'=>1,'streak'=>0,'last_attempt_at'=>current_time('mysql', true)];
            $wpdb->insert($this->t_cat, $row, ['%d','%d','%d','%d','%d','%s']);
        }

        $new_xp = (int)$row['xp'] + $xp;
        $lev = $this->xp_to_level($new_xp, 'category');
        $wpdb->update($this->t_cat, [
            'xp' => $new_xp,
            'level' => (int)$lev['level'],
            'last_attempt_at' => current_time('mysql', true),
        ], ['user_id'=>$user_id, 'category_term_id'=>$term_id], ['%d','%d','%s'], ['%d','%d']);

        // Overall = sum of all cat xp
        $sum = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(xp),0) FROM {$this->t_cat} WHERE user_id=%d", $user_id
        ));
        $levO = $this->xp_to_level($sum, 'overall');

        $exists_over = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->t_over} WHERE user_id=%d", $user_id
        ));
        if ($exists_over) {
            $wpdb->update($this->t_over, [
                'overall_xp' => $sum,
                'overall_level' => (int)$levO['level'],
                'last_updated' => current_time('mysql', true),
            ], ['user_id'=>$user_id], ['%d','%d','%s'], ['%d']);
        } else {
            $wpdb->insert($this->t_over, [
                'user_id'=>$user_id,
                'overall_xp'=>$sum,
                'overall_level'=>(int)$levO['level'],
                'last_updated'=>current_time('mysql', true),
            ], ['%d','%d','%d','%s']);
        }

        return [
            'awarded'=>$xp,
            'category'=>['xp'=>$new_xp,'level'=>(int)$lev['level']],
            'overall' =>['xp'=>$sum,'level'=>(int)$levO['level']],
        ];
    }

    public function get_overall_progress(int $user_id): array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT overall_xp, overall_level FROM {$this->t_over} WHERE user_id=%d",
            $user_id
        ), ARRAY_A);
        if (!$row) {
            // bootstrap
            $this->add_xp($user_id, 0, 0);
            $row = ['overall_xp'=>0,'overall_level'=>1];
        }
        return $row;
    }

    public function get_category_levels(int $user_id): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT category_term_id, xp, level FROM {$this->t_cat} WHERE user_id=%d ORDER BY xp DESC",
            $user_id
        ), ARRAY_A) ?: [];
    }

    /** ---------------- QUIZ ATTEMPTS (delta XP model) ---------------- */
    public function record_attempt(int $user_id, array $args): array {
        global $wpdb;

        $quiz_id  = (int)($args['quiz_id'] ?? 0);
        $cat_id   = (int)($args['category'] ?? 0);
        $correct  = max(0, (int)($args['correct'] ?? 0));
        $total    = max(1, (int)($args['total'] ?? 1));

        // Base XP – default 20
        $base = (int) get_post_meta($quiz_id, '_quiz_xp_value', true);
        if ($base <= 0) $base = 20;

        $percent = (int) round(($correct / $total) * 100);
        $potential = (int) round($base * ($percent / 100));

        // Fetch best for this quiz
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->t_quiz} WHERE user_id=%d AND quiz_id=%d",
            $user_id, $quiz_id
        ), ARRAY_A);

        $prev_awarded = $row ? (int)$row['awarded_xp'] : 0;
        $award = max(0, $potential - $prev_awarded);

        if ($row) {
            $wpdb->update($this->t_quiz, [
                'best_percent'   => max((int)$row['best_percent'], $percent),
                'awarded_xp'     => max($prev_awarded, $potential),
                'attempts'       => ((int)$row['attempts']) + 1,
                'last_attempt_at'=> current_time('mysql', true),
            ], ['user_id'=>$user_id, 'quiz_id'=>$quiz_id],
               ['%d','%d','%d','%s'], ['%d','%d']);
        } else {
            $wpdb->insert($this->t_quiz, [
                'user_id'        => $user_id,
                'quiz_id'        => $quiz_id,
                'best_percent'   => $percent,
                'awarded_xp'     => $potential,
                'attempts'       => 1,
                'last_attempt_at'=> current_time('mysql', true),
            ], ['%d','%d','%d','%d','%d','%s']);
        }

        $result = ['awarded_xp'=>$award];
        if ($award > 0 && $cat_id > 0) {
            $result = array_merge($result, $this->add_xp($user_id, $cat_id, $award));
        } else {
            // still return overall snapshot
            $result['overall'] = $this->get_overall_progress($user_id);
        }
        return $result;
    }
    
    /**
     * Award XP for voting on a list
     * 
     * Rules:
     * - 2 XP per vote (configurable)
     * - Maximum 50 votes per day = 100 XP max/day from voting
     * - XP goes to the parent category of the voting list
     * 
     * @param int $user_id
     * @param int $voting_list_id
     * @return array ['awarded_xp' => int, 'votes_today' => int, 'limit_reached' => bool]
     */
    public function award_voting_xp(int $user_id, int $voting_list_id): array {
        global $wpdb;
        
        // Get config
        $config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : [];
        $xp_per_vote = (int)($config['xp_per_vote'] ?? 2);
        $daily_vote_limit = (int)($config['daily_vote_limit'] ?? 50);
        
        // Count votes today by this user
        $votes_table = $wpdb->prefix . 'voting_list_votes';
        $today_start = gmdate('Y-m-d 00:00:00');
        
        $votes_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$votes_table} 
             WHERE user_id = %d AND created_at >= %s",
            $user_id,
            $today_start
        ));
        
        // Check if limit reached (subtract 1 since we just added a vote)
        $votes_before_this = max(0, $votes_today - 1);
        $limit_reached = $votes_before_this >= $daily_vote_limit;
        
        $result = [
            'awarded_xp' => 0,
            'votes_today' => $votes_today,
            'daily_limit' => $daily_vote_limit,
            'limit_reached' => $limit_reached,
        ];
        
        // If limit reached, no XP
        if ($limit_reached) {
            return $result;
        }
        
        // Get parent category for this voting list
        $category_term_id = 0;
        if (function_exists('ygv_get_list_parent_category')) {
            $category_term_id = ygv_get_list_parent_category($voting_list_id);
        }
        
        // Award XP
        if ($category_term_id > 0) {
            $xp_result = $this->add_xp($user_id, $category_term_id, $xp_per_vote);
            $result['awarded_xp'] = $xp_per_vote;
            $result['category'] = $xp_result['category'] ?? null;
            $result['overall'] = $xp_result['overall'] ?? null;
        }
        
        return $result;
    }
    
    /**
     * Award XP for creating a voting list
     * 
     * Rules:
     * - 50 XP per list created (configurable)
     * - XP goes to the category the list was created in
     * 
     * @param int $user_id
     * @param int $category_term_id
     * @return array
     */
    public function award_list_creation_xp(int $user_id, int $category_term_id): array {
        $config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : [];
        $xp_for_list = (int)($config['xp_for_list_creation'] ?? 50);
        
        $result = $this->add_xp($user_id, $category_term_id, $xp_for_list);
        $result['awarded_xp'] = $xp_for_list;
        
        return $result;
    }
}
