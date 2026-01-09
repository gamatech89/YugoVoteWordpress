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
        // You can make scopes different later; for now share thresholds.
        // L1=0, L2=50, L3=120, L4=210, L5=320 ...
        return [1=>0, 2=>50, 3=>120, 4=>210, 5=>320, 6=>450, 7=>620, 8=>830, 9=>1080, 10=>1370];
    }

    /** returns ['level'=>X, 'next_xp'=>N | null] based on xp */
    public function xp_to_level(int $xp, string $scope = 'category'): array {
        $thr = $this->get_thresholds($scope);
        $lvl = 1; $next_xp = null;
        foreach ($thr as $L => $need) {
            if ($xp >= $need) { $lvl = $L; }
            else { $next_xp = $need; break; }
        }
        return ['level'=>$lvl, 'next_xp'=>$next_xp];
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
}
