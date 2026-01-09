<?php
namespace HelloElementorChild\Quizzes\Services;

if (!defined('ABSPATH')) {
    exit();
}

class ProgressService {
    protected string $t_quiz;
    protected string $t_cat;
    protected string $t_over;

    public function __construct() {
        global $wpdb;
        $p = $wpdb->prefix;
        $this->t_quiz = $p . 'ygv_user_quiz_progress';
        $this->t_cat  = $p . 'ygv_user_category_progress';
        $this->t_over = $p . 'ygv_user_overall_progress';
        $this->maybe_install_tables();
    }

    protected function maybe_install_tables(): void {
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

    public function get_thresholds($scope = 'category'): array {
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

        return [1=>0, 2=>50, 3=>120, 4=>210, 5=>320, 6=>450, 7=>620, 8=>830, 9=>1080, 10=>1370];
    }

    public function xp_to_level(int $xp, string $scope = 'category'): array {
        $thr = $this->get_thresholds($scope);
        $max_level = function_exists('ygv_get_level_config') ? (ygv_get_level_config()['max_level'] ?? 100) : 100;

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

    public function get_vote_bonus(int $user_id, int $category_term_id): array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT xp, level FROM {$this->t_cat} WHERE user_id = %d AND category_term_id = %d",
            $user_id,
            $category_term_id
        ), ARRAY_A);

        $xp = (int)($row['xp'] ?? 0);
        $level = (int)($row['level'] ?? 1);

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

    public function add_xp(int $user_id, int $term_id, int $xp): array {
        global $wpdb;
        if ($xp <= 0) return ['awarded'=>0];

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->t_cat} WHERE user_id=%d AND category_term_id=%d",
            $user_id, $term_id
        ), ARRAY_A);

        $old_cat_level = 1;
        if (!$row) {
            $row = ['user_id'=>$user_id,'category_term_id'=>$term_id,'xp'=>0,'level'=>1,'streak'=>0,'last_attempt_at'=>current_time('mysql', true)];
            $wpdb->insert($this->t_cat, $row, ['%d','%d','%d','%d','%d','%s']);
        } else {
            $old_cat_level = (int)$row['level'];
        }

        $new_xp = (int)$row['xp'] + $xp;
        $lev = $this->xp_to_level($new_xp, 'category');
        $new_cat_level = (int)$lev['level'];

        $wpdb->update($this->t_cat, [
            'xp' => $new_xp,
            'level' => $new_cat_level,
            'last_attempt_at' => current_time('mysql', true),
        ], ['user_id'=>$user_id, 'category_term_id'=>$term_id], ['%d','%d','%s'], ['%d','%d']);

        $sum = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(xp),0) FROM {$this->t_cat} WHERE user_id=%d", $user_id
        ));
        $levO = $this->xp_to_level($sum, 'overall');

        $old_overall_level = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT overall_level FROM {$this->t_over} WHERE user_id=%d", $user_id
        )) ?: 1;
        $new_overall_level = (int)$levO['level'];

        $exists_over = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->t_over} WHERE user_id=%d", $user_id
        ));
        if ($exists_over) {
            $wpdb->update($this->t_over, [
                'overall_xp' => $sum,
                'overall_level' => $new_overall_level,
                'last_updated' => current_time('mysql', true),
            ], ['user_id'=>$user_id], ['%d','%d','%s'], ['%d']);
        } else {
            $wpdb->insert($this->t_over, [
                'user_id'=>$user_id,
                'overall_xp'=>$sum,
                'overall_level'=>$new_overall_level,
                'last_updated'=>current_time('mysql', true),
            ], ['%d','%d','%d','%s']);
        }

        $level_ups = [];

        if ($new_cat_level > $old_cat_level && $term_id > 0) {
            $term = get_term($term_id, 'voting_list_category');
            $term_name = $term ? $term->name : 'Kategorija';
            $mascot_id = get_term_meta($term_id, 'category_logo', true);
            $mascot_url = $mascot_id ? wp_get_attachment_image_url($mascot_id, 'medium') : '';
            $color = get_term_meta($term_id, 'category_color', true) ?: '#4457A5';

            $level_ups[] = [
                'type' => 'category',
                'category_name' => $term_name,
                'old_level' => $old_cat_level,
                'new_level' => $new_cat_level,
                'mascot_url' => $mascot_url,
                'color' => $color,
                'title' => $this->get_level_title($new_cat_level),
            ];
        }

        if ($new_overall_level > $old_overall_level) {
            $level_ups[] = [
                'type' => 'overall',
                'old_level' => $old_overall_level,
                'new_level' => $new_overall_level,
                'title' => $this->get_level_title($new_overall_level),
            ];
        }

        return [
            'awarded'=>$xp,
            'category'=>['xp'=>$new_xp,'level'=>$new_cat_level],
            'overall' =>['xp'=>$sum,'level'=>$new_overall_level],
            'level_ups' => $level_ups,
        ];
    }

    public function get_overall_progress(int $user_id): array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT overall_xp, overall_level FROM {$this->t_over} WHERE user_id=%d",
            $user_id
        ), ARRAY_A);
        if (!$row) {
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

    public function record_attempt(int $user_id, array $args): array {
        global $wpdb;

        $quiz_id  = (int)($args['quiz_id'] ?? 0);
        $cat_id   = (int)($args['category'] ?? 0);
        $correct  = max(0, (int)($args['correct'] ?? 0));
        $total    = max(1, (int)($args['total'] ?? 1));

        $base = (int) get_post_meta($quiz_id, '_quiz_xp_value', true);
        if ($base <= 0) $base = 20;

        $percent = (int) round(($correct / $total) * 100);
        $potential = (int) round($base * ($percent / 100));

        $thresholds = $this->get_thresholds('quiz');
        $max_level = function_exists('ygv_get_level_config') ? (ygv_get_level_config()['max_level'] ?? 100) : 100;

        $level = 1;
        foreach ($thresholds as $lvl => $xp_needed) {
            if ($lvl > $max_level) break;
            if ($potential >= $xp_needed) {
                $level = $lvl;
            }
        }

        $bonus = 0;
        if ($cat_id > 0 && function_exists('ygv_get_level_config')) {
            $config = ygv_get_level_config();
            foreach ($config['tiers'] as $tier) {
                if ($level >= $tier['min_level'] && $level <= $tier['max_level']) {
                    $bonus = (int)$tier['xp_bonus'];
                    break;
                }
            }
        }

        $awarded_xp = $potential + $bonus;

        $attempt_row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->t_quiz} WHERE user_id=%d AND quiz_id=%d",
            $user_id, $quiz_id
        ), ARRAY_A);

        $best_percent = (int)($attempt_row['best_percent'] ?? 0);
        $awarded_before = (int)($attempt_row['awarded_xp'] ?? 0);
        $attempts = (int)($attempt_row['attempts'] ?? 0) + 1;

        $best_percent = max($best_percent, $percent);
        $awarded_xp   = max($awarded_xp, $awarded_before);

        if ($attempt_row) {
            $wpdb->update($this->t_quiz, [
                'best_percent' => $best_percent,
                'awarded_xp' => $awarded_xp,
                'attempts' => $attempts,
                'last_attempt_at' => current_time('mysql', true),
            ], ['user_id'=>$user_id, 'quiz_id'=>$quiz_id], ['%d','%d','%d','%s'], ['%d','%d']);
        } else {
            $wpdb->insert($this->t_quiz, [
                'user_id'=>$user_id,
                'quiz_id'=>$quiz_id,
                'best_percent'=>$best_percent,
                'awarded_xp'=>$awarded_xp,
                'attempts'=>$attempts,
                'last_attempt_at'=>current_time('mysql', true),
            ], ['%d','%d','%d','%d','%d','%s']);
        }

        $result = $this->add_xp($user_id, $cat_id, $awarded_xp);
        $result['awarded_percent'] = $percent;
        return $result;
    }
}
