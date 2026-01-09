<?php
/**
 * Leaderboard Shortcode
 * 
 * Displays various leaderboards: overall XP, category XP, votes, achievements
 * 
 * Usage: [ygv_leaderboard type="overall" limit="10"]
 * Types: overall, category, votes, achievements, streak
 * 
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

function ygv_leaderboard_shortcode($atts) {
    $atts = shortcode_atts([
        'type' => 'overall', // overall, category, votes, achievements, streak
        'category' => '', // For category type: category slug or ID
        'limit' => 10,
        'show_current_user' => 'true',
    ], $atts);
    
    $type = sanitize_key($atts['type']);
    $limit = absint($atts['limit']) ?: 10;
    $limit = min($limit, 100); // Cap at 100
    $show_current_user = $atts['show_current_user'] === 'true';
    
    global $wpdb;
    $current_user_id = get_current_user_id();
    
    ob_start();
    ?>
    <div class="ygv-leaderboard" data-type="<?php echo esc_attr($type); ?>">
        
        <!-- Leaderboard Type Tabs -->
        <div class="ygv-lb-tabs">
            <button class="ygv-lb-tab <?php echo $type === 'overall' ? 'active' : ''; ?>" data-type="overall">
                <?php ygv_icon_e('trophy', 16); ?> <?php echo esc_html__('Ukupno', 'hello-elementor-child'); ?>
            </button>
            <button class="ygv-lb-tab <?php echo $type === 'votes' ? 'active' : ''; ?>" data-type="votes">
                <?php ygv_icon_e('vote', 16); ?> <?php echo esc_html__('Glasači', 'hello-elementor-child'); ?>
            </button>
            <button class="ygv-lb-tab <?php echo $type === 'streak' ? 'active' : ''; ?>" data-type="streak">
                <?php ygv_icon_e('flame', 16); ?> <?php echo esc_html__('Streak', 'hello-elementor-child'); ?>
            </button>
            <button class="ygv-lb-tab <?php echo $type === 'achievements' ? 'active' : ''; ?>" data-type="achievements">
                <?php ygv_icon_e('star', 16); ?> <?php echo esc_html__('Dostignuća', 'hello-elementor-child'); ?>
            </button>
        </div>
        
        <!-- Overall XP Leaderboard -->
        <div class="ygv-lb-content <?php echo $type === 'overall' ? 'active' : ''; ?>" data-type="overall">
            <?php echo ygv_render_overall_leaderboard($limit, $current_user_id); ?>
        </div>
        
        <!-- Votes Leaderboard -->
        <div class="ygv-lb-content <?php echo $type === 'votes' ? 'active' : ''; ?>" data-type="votes">
            <?php echo ygv_render_votes_leaderboard($limit, $current_user_id); ?>
        </div>
        
        <!-- Streak Leaderboard -->
        <div class="ygv-lb-content <?php echo $type === 'streak' ? 'active' : ''; ?>" data-type="streak">
            <?php echo ygv_render_streak_leaderboard($limit, $current_user_id); ?>
        </div>
        
        <!-- Achievements Leaderboard -->
        <div class="ygv-lb-content <?php echo $type === 'achievements' ? 'active' : ''; ?>" data-type="achievements">
            <?php echo ygv_render_achievements_leaderboard($limit, $current_user_id); ?>
        </div>
        
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.ygv-lb-tab');
        const contents = document.querySelectorAll('.ygv-lb-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const type = this.dataset.type;
                
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.querySelector(`.ygv-lb-content[data-type="${type}"]`).classList.add('active');
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('ygv_leaderboard', 'ygv_leaderboard_shortcode');

/**
 * Render overall XP leaderboard
 */
function ygv_render_overall_leaderboard($limit, $current_user_id) {
    global $wpdb;
    $t_over = $wpdb->prefix . 'ygv_user_overall_progress';
    
    // Get top users by overall XP
    $leaders = $wpdb->get_results($wpdb->prepare(
        "SELECT op.user_id, op.overall_xp, op.overall_level, u.display_name
         FROM {$t_over} op
         JOIN {$wpdb->users} u ON op.user_id = u.ID
         ORDER BY op.overall_xp DESC
         LIMIT %d",
        $limit
    ), ARRAY_A);
    
    // Get current user's rank if not in top
    $current_user_rank = null;
    $current_user_data = null;
    if ($current_user_id && !in_array($current_user_id, array_column($leaders, 'user_id'))) {
        $current_user_rank = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM {$t_over} WHERE overall_xp > (
                SELECT overall_xp FROM {$t_over} WHERE user_id = %d
            )",
            $current_user_id
        ));
        $current_user_data = $wpdb->get_row($wpdb->prepare(
            "SELECT op.user_id, op.overall_xp, op.overall_level, u.display_name
             FROM {$t_over} op
             JOIN {$wpdb->users} u ON op.user_id = u.ID
             WHERE op.user_id = %d",
            $current_user_id
        ), ARRAY_A);
    }
    
    return ygv_render_leaderboard_list($leaders, $current_user_id, $current_user_rank, $current_user_data, 'xp');
}

/**
 * Render votes leaderboard
 */
function ygv_render_votes_leaderboard($limit, $current_user_id) {
    global $wpdb;
    $votes_table = $wpdb->prefix . 'voting_list_votes';
    
    // Get top voters
    $leaders = $wpdb->get_results($wpdb->prepare(
        "SELECT v.user_id, COUNT(*) as vote_count, u.display_name
         FROM {$votes_table} v
         JOIN {$wpdb->users} u ON v.user_id = u.ID
         WHERE v.user_id IS NOT NULL AND v.user_id > 0
         GROUP BY v.user_id
         ORDER BY vote_count DESC
         LIMIT %d",
        $limit
    ), ARRAY_A);
    
    // Get current user's rank if not in top
    $current_user_rank = null;
    $current_user_data = null;
    if ($current_user_id && !in_array($current_user_id, array_column($leaders, 'user_id'))) {
        $user_vote_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$votes_table} WHERE user_id = %d",
            $current_user_id
        ));
        
        $current_user_rank = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM (
                SELECT user_id, COUNT(*) as cnt FROM {$votes_table}
                WHERE user_id IS NOT NULL AND user_id > 0
                GROUP BY user_id
            ) sub WHERE cnt > %d",
            $user_vote_count
        ));
        
        $user = get_userdata($current_user_id);
        $current_user_data = [
            'user_id' => $current_user_id,
            'vote_count' => $user_vote_count,
            'display_name' => $user ? $user->display_name : 'Unknown',
        ];
    }
    
    return ygv_render_leaderboard_list($leaders, $current_user_id, $current_user_rank, $current_user_data, 'votes');
}

/**
 * Render streak leaderboard
 */
function ygv_render_streak_leaderboard($limit, $current_user_id) {
    global $wpdb;
    $votes_table = $wpdb->prefix . 'voting_list_votes';
    
    // Get all users who have voted
    $all_voters = $wpdb->get_col(
        "SELECT DISTINCT user_id FROM {$votes_table} WHERE user_id IS NOT NULL AND user_id > 0"
    );
    
    // Calculate streak for each user
    $streaks = [];
    foreach ($all_voters as $user_id) {
        $streak = ygv_calculate_user_streak($user_id);
        if ($streak > 0) {
            $user = get_userdata($user_id);
            $streaks[] = [
                'user_id' => $user_id,
                'streak_days' => $streak,
                'display_name' => $user ? $user->display_name : 'Unknown',
            ];
        }
    }
    
    // Sort by streak and limit
    usort($streaks, fn($a, $b) => $b['streak_days'] - $a['streak_days']);
    $leaders = array_slice($streaks, 0, $limit);
    
    // Get current user's rank if not in top
    $current_user_rank = null;
    $current_user_data = null;
    if ($current_user_id && !in_array($current_user_id, array_column($leaders, 'user_id'))) {
        $user_streak = ygv_calculate_user_streak($current_user_id);
        $rank = 1;
        foreach ($streaks as $s) {
            if ($s['streak_days'] > $user_streak) $rank++;
        }
        $current_user_rank = $rank;
        $user = get_userdata($current_user_id);
        $current_user_data = [
            'user_id' => $current_user_id,
            'streak_days' => $user_streak,
            'display_name' => $user ? $user->display_name : 'Unknown',
        ];
    }
    
    return ygv_render_leaderboard_list($leaders, $current_user_id, $current_user_rank, $current_user_data, 'streak');
}

/**
 * Calculate a user's voting streak
 */
function ygv_calculate_user_streak($user_id) {
    global $wpdb;
    $votes_table = $wpdb->prefix . 'voting_list_votes';
    
    $today = gmdate('Y-m-d');
    $yesterday = gmdate('Y-m-d', strtotime('-1 day'));
    
    $dates = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT DATE(created_at) as vote_date 
         FROM {$votes_table} 
         WHERE user_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 120 DAY)
         ORDER BY vote_date DESC",
        $user_id
    ));
    
    if (empty($dates)) return 0;
    
    // If didn't vote today or yesterday, streak is broken
    if ($dates[0] !== $today && $dates[0] !== $yesterday) {
        return 0;
    }
    
    $streak = 0;
    $expected_date = $dates[0];
    
    foreach ($dates as $date) {
        if ($date === $expected_date) {
            $streak++;
            $expected_date = gmdate('Y-m-d', strtotime($expected_date . ' -1 day'));
        } else {
            break;
        }
    }
    
    return $streak;
}

/**
 * Render achievements leaderboard
 */
function ygv_render_achievements_leaderboard($limit, $current_user_id) {
    global $wpdb;
    $achievements_table = $wpdb->prefix . 'ygv_user_achievements';
    
    // Get top users by achievement count
    $leaders = $wpdb->get_results($wpdb->prepare(
        "SELECT a.user_id, COUNT(*) as achievement_count, SUM(a.xp_awarded) as total_xp, u.display_name
         FROM {$achievements_table} a
         JOIN {$wpdb->users} u ON a.user_id = u.ID
         GROUP BY a.user_id
         ORDER BY achievement_count DESC, total_xp DESC
         LIMIT %d",
        $limit
    ), ARRAY_A);
    
    // Get current user's rank if not in top
    $current_user_rank = null;
    $current_user_data = null;
    if ($current_user_id && !in_array($current_user_id, array_column($leaders, 'user_id'))) {
        $user_achievements = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as achievement_count, SUM(xp_awarded) as total_xp
             FROM {$achievements_table} WHERE user_id = %d",
            $current_user_id
        ), ARRAY_A);
        
        $user_count = $user_achievements['achievement_count'] ?? 0;
        $current_user_rank = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM (
                SELECT user_id, COUNT(*) as cnt FROM {$achievements_table}
                GROUP BY user_id
            ) sub WHERE cnt > %d",
            $user_count
        ));
        
        $user = get_userdata($current_user_id);
        $current_user_data = [
            'user_id' => $current_user_id,
            'achievement_count' => $user_count,
            'total_xp' => $user_achievements['total_xp'] ?? 0,
            'display_name' => $user ? $user->display_name : 'Unknown',
        ];
    }
    
    return ygv_render_leaderboard_list($leaders, $current_user_id, $current_user_rank, $current_user_data, 'achievements');
}

/**
 * Render the leaderboard list HTML
 */
function ygv_render_leaderboard_list($leaders, $current_user_id, $current_user_rank, $current_user_data, $type) {
    if (empty($leaders)) {
        return '<div class="ygv-lb-empty">' . 
            '<span class="ygv-lb-empty-icon">' . ygv_icon('trophy', 48) . '</span>' .
            '<p>' . esc_html__('Još nema podataka za ovu rang listu.', 'hello-elementor-child') . '</p>' .
        '</div>';
    }
    
    $html = '<div class="ygv-lb-list">';
    
    foreach ($leaders as $rank => $leader) {
        $html .= ygv_render_leaderboard_row($rank + 1, $leader, $current_user_id, $type);
    }
    
    // Show current user if not in top
    if ($current_user_rank && $current_user_data) {
        $html .= '<div class="ygv-lb-divider">···</div>';
        $html .= ygv_render_leaderboard_row($current_user_rank, $current_user_data, $current_user_id, $type);
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render a single leaderboard row
 */
function ygv_render_leaderboard_row($rank, $data, $current_user_id, $type) {
    $user_id = (int)$data['user_id'];
    $is_current_user = $user_id === $current_user_id;
    $display_name = esc_html($data['display_name']);
    
    // Get avatar
    $avatar = get_avatar_url($user_id, ['size' => 48, 'default' => 'mystery']);
    
    // Rank badge styling
    $rank_class = 'ygv-lb-rank';
    if ($rank === 1) $rank_class .= ' ygv-lb-rank-gold';
    elseif ($rank === 2) $rank_class .= ' ygv-lb-rank-silver';
    elseif ($rank === 3) $rank_class .= ' ygv-lb-rank-bronze';
    
    // Format value based on type
    $value_html = '';
    switch ($type) {
        case 'xp':
            $xp = number_format((int)($data['overall_xp'] ?? 0));
            $level = (int)($data['overall_level'] ?? 1);
            $value_html = '<span class="ygv-lb-level">Nivo ' . $level . '</span><span class="ygv-lb-xp">' . $xp . ' XP</span>';
            break;
        case 'votes':
            $votes = number_format((int)($data['vote_count'] ?? 0));
            $value_html = '<span class="ygv-lb-votes">' . ygv_icon('vote', 14) . ' ' . $votes . ' glasova</span>';
            break;
        case 'streak':
            $streak = (int)($data['streak_days'] ?? 0);
            $icon = $streak >= 30 ? ygv_icon('gem', 16) : ($streak >= 7 ? ygv_icon('flame', 16) : ygv_icon('fire', 16));
            $value_html = '<span class="ygv-lb-streak">' . $icon . ' ' . $streak . ' dana</span>';
            break;
        case 'achievements':
            $count = (int)($data['achievement_count'] ?? 0);
            $value_html = '<span class="ygv-lb-achievements">' . ygv_icon('star', 14) . ' ' . $count . ' dostignuća</span>';
            break;
    }
    
    $row_class = 'ygv-lb-row' . ($is_current_user ? ' ygv-lb-row-current' : '');
    
    $html = '<div class="' . esc_attr($row_class) . '">';
    $html .= '<span class="' . esc_attr($rank_class) . '">' . $rank . '</span>';
    $html .= '<img class="ygv-lb-avatar" src="' . esc_url($avatar) . '" alt="">';
    $html .= '<div class="ygv-lb-user">';
    $html .= '<span class="ygv-lb-name">' . $display_name . ($is_current_user ? ' <small>(ti)</small>' : '') . '</span>';
    $html .= '<div class="ygv-lb-value">' . $value_html . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}
