<?php
/**
 * Tournament Bracket Shortcodes
 * Usage: 
 * - [yuv_auto_bracket id="123"] - Display specific tournament bracket
 * - [yuv_active_tournament] - Display currently active tournament (for home page)
 * - [yuv_tournament_archive] - Display list of all past tournaments (for archive page)
 * - [yuv_active_duel] - Display current active duel in hero battle arena format
 */

if (!defined('ABSPATH')) exit;

function yuv_auto_bracket_shortcode($atts) {
    $atts = shortcode_atts(['id' => ''], $atts, 'yuv_auto_bracket');
    
    $tournament_id = intval($atts['id']);
    if (!$tournament_id) {
        return '<p>Nevažeći ID turnira.</p>';
    }

    $bracket_lists = get_post_meta($tournament_id, '_yuv_bracket_lists', true);
    if (empty($bracket_lists)) {
        return '<p>Bracket još nije kreiran za ovaj turnir.</p>';
    }

    ob_start();
    ?>
    <div class="yuv-tournament-bracket">
        <style>
            .yuv-tournament-bracket { max-width: 1200px; margin: 40px auto; }
            .yuv-bracket-round { margin-bottom: 40px; }
            .yuv-bracket-round h3 { text-align: center; font-size: 24px; margin-bottom: 20px; }
            .yuv-bracket-matches { display: grid; gap: 20px; }
            .yuv-bracket-matches.qf { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
            .yuv-bracket-matches.sf { grid-template-columns: repeat(2, 1fr); max-width: 800px; margin: 0 auto; }
            .yuv-bracket-matches.final { max-width: 400px; margin: 0 auto; }
            .yuv-match-card { background: white; border: 2px solid #ddd; border-radius: 12px; padding: 20px; transition: all 0.3s; }
            .yuv-match-card.completed { border-color: #28a745; }
            .yuv-match-card.active { border-color: #2271b1; box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3); }
            .yuv-match-card.pending { opacity: 0.6; }
            .yuv-match-title { font-weight: 700; font-size: 16px; margin-bottom: 15px; text-align: center; }
            .yuv-match-items { display: flex; flex-direction: column; gap: 12px; }
            .yuv-match-item { display: flex; align-items: center; gap: 12px; padding: 10px; background: #f9f9f9; border-radius: 8px; }
            .yuv-match-item.winner { background: #d4edda; border: 2px solid #28a745; }
            .yuv-match-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
            .yuv-match-item-name { flex: 1; font-weight: 600; }
            .yuv-match-item-score { font-size: 18px; font-weight: 700; }
            .yuv-match-status { text-align: center; margin-top: 12px; font-size: 13px; }
            .yuv-match-status.completed { color: #28a745; }
            .yuv-match-status.active { color: #2271b1; }
            .yuv-tie-icon { display: inline-block; margin-left: 5px; font-size: 16px; }
        </style>

        <!-- Quarterfinals -->
        <div class="yuv-bracket-round">
            <h3>⚔️ Četvrtfinale</h3>
            <div class="yuv-bracket-matches qf">
                <?php foreach ($bracket_lists['qf'] as $list_id): ?>
                    <?php echo yuv_render_match_card($list_id); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Semifinals -->
        <div class="yuv-bracket-round">
            <h3>🏆 Polufinale</h3>
            <div class="yuv-bracket-matches sf">
                <?php foreach ($bracket_lists['sf'] as $list_id): ?>
                    <?php echo yuv_render_match_card($list_id); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Final -->
        <div class="yuv-bracket-round">
            <h3>👑 FINALE</h3>
            <div class="yuv-bracket-matches final">
                <?php echo yuv_render_match_card($bracket_lists['final'][0]); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('yuv_auto_bracket', 'yuv_auto_bracket_shortcode');

/**
 * Active Tournament Shortcode (for home page)
 * Displays the currently active tournament
 */
function yuv_active_tournament_shortcode($atts) {
    // Save and clear global post to avoid conflicts
    global $post;
    $original_post = $post;
    
    try {
        // Query for active tournaments (published, bracket created, not completed)
        $args = array(
            'post_type' => 'yuv_tournament',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_yuv_bracket_created',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            wp_reset_postdata();
            $post = $original_post; // Restore original post
            return '<div class="yuv-no-tournament"><p>Trenutno nema aktivnih turnira.</p></div>';
        }

        $tournament = $query->posts[0];
        $tournament_id = $tournament->ID;
        
        // Check if tournament is actually still active (has uncompleted matches)
        $bracket_lists = get_post_meta($tournament_id, '_yuv_bracket_lists', true);
        $winner_id = get_post_meta($tournament_id, '_yuv_winner_id', true);
        
        wp_reset_postdata();
        $post = $original_post; // Restore original post
        
        if ($winner_id) {
            return '<div class="yuv-no-tournament"><p>Trenutno nema aktivnih turnira.</p></div>';
        }

        // Build output without relying on global post context
        ob_start();
        ?>
        <div class="yuv-active-tournament-wrapper">
            <div class="yuv-tournament-header">
                <h2 class="yuv-tournament-title"><?php echo esc_html($tournament->post_title); ?></h2>
                <?php if ($tournament->post_content): ?>
                    <div class="yuv-tournament-description">
                        <?php echo wpautop($tournament->post_content); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php 
            // Directly render bracket without shortcode to avoid context issues
            if (!empty($bracket_lists)) {
                echo yuv_render_tournament_bracket($tournament_id, $bracket_lists);
            }
            ?>
        </div>
        <style>
            .yuv-active-tournament-wrapper { margin: 40px 0; }
            .yuv-tournament-header { text-align: center; margin-bottom: 40px; }
            .yuv-tournament-title { font-size: 32px; font-weight: 700; margin-bottom: 15px; }
            .yuv-tournament-description { font-size: 16px; color: #666; max-width: 800px; margin: 0 auto; }
            .yuv-no-tournament { text-align: center; padding: 60px 20px; background: #f5f5f5; border-radius: 12px; }
            .yuv-no-tournament p { font-size: 18px; color: #666; margin: 0; }
        </style>
        <?php
        return ob_get_clean();
    } catch (Exception $e) {
        $post = $original_post; // Restore original post on error
        error_log('YUV Active Tournament Error: ' . $e->getMessage());
        return '<div class="yuv-no-tournament"><p>Greška pri učitavanju turnira.</p></div>';
    }
}
add_shortcode('yuv_active_tournament', 'yuv_active_tournament_shortcode');

/**
 * Helper function to render bracket HTML
 */
function yuv_render_tournament_bracket($tournament_id, $bracket_lists) {
    ob_start();
    ?>
    <div class="yuv-tournament-bracket">
        <style>
            .yuv-tournament-bracket { max-width: 1200px; margin: 40px auto; }
            .yuv-bracket-round { margin-bottom: 40px; }
            .yuv-bracket-round h3 { text-align: center; font-size: 24px; margin-bottom: 20px; }
            .yuv-bracket-matches { display: grid; gap: 20px; }
            .yuv-bracket-matches.qf { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
            .yuv-bracket-matches.sf { grid-template-columns: repeat(2, 1fr); max-width: 800px; margin: 0 auto; }
            .yuv-bracket-matches.final { max-width: 400px; margin: 0 auto; }
            .yuv-match-card { background: white; border: 2px solid #ddd; border-radius: 12px; padding: 20px; transition: all 0.3s; }
            .yuv-match-card.completed { border-color: #28a745; }
            .yuv-match-card.active { border-color: #2271b1; box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3); }
            .yuv-match-card.pending { opacity: 0.6; }
            .yuv-match-title { font-weight: 700; font-size: 16px; margin-bottom: 15px; text-align: center; }
            .yuv-match-items { display: flex; flex-direction: column; gap: 12px; }
            .yuv-match-item { display: flex; align-items: center; gap: 12px; padding: 10px; background: #f9f9f9; border-radius: 8px; }
            .yuv-match-item.winner { background: #d4edda; border: 2px solid #28a745; }
            .yuv-match-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
            .yuv-match-item-name { flex: 1; font-weight: 600; }
            .yuv-match-item-score { font-size: 18px; font-weight: 700; }
            .yuv-match-status { text-align: center; margin-top: 12px; font-size: 13px; }
            .yuv-match-status.completed { color: #28a745; }
            .yuv-match-status.active { color: #2271b1; }
            .yuv-tie-icon { display: inline-block; margin-left: 5px; font-size: 16px; }
        </style>

        <!-- Quarterfinals -->
        <div class="yuv-bracket-round">
            <h3>⚔️ Četvrtfinale</h3>
            <div class="yuv-bracket-matches qf">
                <?php foreach ($bracket_lists['qf'] as $list_id): ?>
                    <?php echo yuv_render_match_card($list_id); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Semifinals -->
        <div class="yuv-bracket-round">
            <h3>🏆 Polufinale</h3>
            <div class="yuv-bracket-matches sf">
                <?php foreach ($bracket_lists['sf'] as $list_id): ?>
                    <?php echo yuv_render_match_card($list_id); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Final -->
        <div class="yuv-bracket-round">
            <h3>👑 FINALE</h3>
            <div class="yuv-bracket-matches final">
                <?php echo yuv_render_match_card($bracket_lists['final'][0]); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Active Duel Shortcode - Random Access + Auto-Swipe Navigation
 * Usage: [yuv_active_duel]
 * Supports: ?match_id=123 for direct access OR auto-swipe to first unvoted
 */
function yuv_active_duel_shortcode($atts) {
    global $wpdb;
    
    $current_time = current_time('timestamp');
    $user_id = get_current_user_id();
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Step 1: Find active tournament
    $active_tournament = $wpdb->get_var(
        "SELECT ID FROM {$wpdb->posts} 
        WHERE post_type = 'yuv_tournament' 
        AND post_status = 'publish' 
        ORDER BY post_date DESC 
        LIMIT 1"
    );
    
    if (!$active_tournament) {
        return yuv_render_no_duel();
    }
    
    $tournament_id = $active_tournament;
    $tournament_title = get_the_title($tournament_id);
    $bracket_lists = get_post_meta($tournament_id, '_yuv_bracket_lists', true);
    
    if (empty($bracket_lists)) {
        return yuv_render_no_duel();
    }
    
    // Step 2: Get ALL active matches for this tournament
    $all_match_ids = [];
    foreach ($bracket_lists as $stage => $match_ids) {
        $all_match_ids = array_merge($all_match_ids, $match_ids);
    }
    
    if (empty($all_match_ids)) {
        return yuv_render_no_duel();
    }
    
    // Step 3: Filter to only PUBLISHED matches that haven't ended
    $active_matches = [];
    foreach ($all_match_ids as $match_id) {
        $post_status = get_post_status($match_id);
        $end_time = (int) get_post_meta($match_id, '_yuv_end_time', true);
        $completed = get_post_meta($match_id, '_yuv_match_completed', true);
        
        if ($post_status === 'publish' && $end_time > $current_time && !$completed) {
            $active_matches[] = $match_id;
        }
    }
    
    if (empty($active_matches)) {
        return yuv_render_stage_complete();
    }
    
    // Step 4: RANDOM ACCESS - Check if ?match_id is provided
    $target_match_id = isset($_GET['match_id']) ? (int) $_GET['match_id'] : null;
    
    if ($target_match_id && in_array($target_match_id, $active_matches, true)) {
        // Direct access to specific match
        return yuv_render_arena($target_match_id, $tournament_id, $tournament_title, $active_matches, $user_id, $user_ip);
    }
    
    // Step 5: AUTO-SWIPE - Find FIRST match where user hasn't voted
    $first_unvoted_match = null;
    
    foreach ($active_matches as $match_id) {
        // Check if user voted in this match
        if ($user_id > 0) {
            $user_vote = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}voting_list_votes 
                WHERE voting_list_id = %d AND user_id = %d",
                $match_id,
                $user_id
            ));
        } else {
            $user_vote = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}voting_list_votes 
                WHERE voting_list_id = %d AND user_id = 0 AND ip_address = %s",
                $match_id,
                $user_ip
            ));
        }
        
        if (!$user_vote) {
            $first_unvoted_match = $match_id;
            break; // Found it!
        }
    }
    
    // If all matches voted, show the first one with results
    if (!$first_unvoted_match) {
        $first_unvoted_match = $active_matches[0];
    }
    
    // Step 6: Render the selected match
    return yuv_render_arena($first_unvoted_match, $tournament_id, $tournament_title, $active_matches, $user_id, $user_ip);
}

/**
 * Render arena for a specific match with new UI
 * Returns HTML string for both shortcode and AJAX
 */
function yuv_render_arena($match_id, $tournament_id, $tournament_title, $all_matches, $user_id, $user_ip) {
    $html = yuv_render_arena_html($match_id, $tournament_id, $tournament_title, $all_matches, $user_id, $user_ip);
    return $html;
}

/**
 * Extracted HTML rendering logic for arena (reusable for AJAX)
 * FIX 3: Added timer badge
 * FIX 4: Added vote counts to result overlay
 * FIX 5: Fixed navigation thumbnails with data-match-id
 */
function yuv_render_arena_html($match_id, $tournament_id, $tournament_title, $all_matches, $user_id, $user_ip) {
    global $wpdb;
    
    $stage = get_post_meta($match_id, '_yuv_stage', true);
    $match_number = get_post_meta($match_id, '_yuv_match_number', true);
    $end_time = (int) get_post_meta($match_id, '_yuv_end_time', true);
    $items = get_post_meta($match_id, '_voting_items', true) ?: [];
    
    // Check if user voted - PERSISTENT STATE
    $has_voted = false;
    $winning_item_id = null;
    
    if ($user_id > 0) {
        $user_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT voting_item_id FROM {$wpdb->prefix}voting_list_votes 
            WHERE voting_list_id = %d AND user_id = %d",
            $match_id,
            $user_id
        ));
    } else {
        $user_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT voting_item_id FROM {$wpdb->prefix}voting_list_votes 
            WHERE voting_list_id = %d AND user_id = 0 AND ip_address = %s",
            $match_id,
            $user_ip
        ));
    }
    
    if (!empty($user_vote)) {
        $has_voted = true;
        $winning_item_id = (int) $user_vote;
    }
    
    // Get contenders
    $contenders = [];
    foreach ($items as $item_id) {
        $item = get_post($item_id);
        if (!$item) continue;

        $votes_table = $wpdb->prefix . 'voting_list_votes';
        $vote_count = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(vote_value) FROM {$votes_table} 
            WHERE voting_list_id = %d AND voting_item_id = %d",
            $match_id,
            $item_id
        ));

        $image = get_post_meta($item_id, '_custom_image_url', true);
        if (!$image) {
            $image = get_the_post_thumbnail_url($item_id, 'large');
        }

        $contenders[] = [
            'id' => $item_id,
            'name' => $item->post_title,
            'bio' => get_post_meta($item_id, '_short_description', true) ?: wp_trim_words($item->post_content, 20),
            'image' => $image,
            'votes' => (int) $vote_count,
        ];
    }

    // Calculate percentages
    $total_votes = array_sum(array_column($contenders, 'votes'));
    foreach ($contenders as &$c) {
        $c['percent'] = $total_votes > 0 ? round(($c['votes'] / $total_votes) * 100) : 50;
        $c['is_winner'] = ($c['id'] == $winning_item_id);
    }

    // Stage labels
    $stage_labels = [
        'r16' => 'OSMINA FINALA',
        'qf' => 'ČETVRTFINALE',
        'sf' => 'POLUFINALE',
        'final' => 'FINALE'
    ];
    $stage_label = $stage_labels[$stage] ?? 'DUEL';

    ob_start();
    ?>
    
    <!-- FIX 3: Added id="yuv-arena" anchor for scroll positioning -->
    <div id="yuv-arena" class="yuv-arena-wrapper <?php echo $has_voted ? 'yuv-show-results' : ''; ?>">
        
        <!-- Arena Header NEW - FIX 3: Added Timer -->
        <div class="yuv-arena-header-new">
            <span class="yuv-stage-pill"><?php echo esc_html($stage_label); ?></span>
            <h2 class="yuv-tournament-name"><?php echo esc_html($tournament_title); ?></h2>
            <h3 class="yuv-match-names">
                <?php if (!empty($contenders[0]) && !empty($contenders[1])): ?>
                    <?php echo esc_html($contenders[0]['name'] . ' vs ' . $contenders[1]['name']); ?>
                <?php endif; ?>
            </h3>
            
            <?php if (!$has_voted && $end_time): ?>
                <div class="yuv-timer-badge">
                    Preostalo: <span id="yuv-duel-timer" data-end="<?php echo esc_attr($end_time); ?>">--:--:--</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Duel Arena (Split Screen) - FIX 1: Added data attributes for JS -->
        <div class="yuv-duel-arena"
             data-match-id="<?php echo esc_attr($match_id); ?>"
             data-tournament-id="<?php echo esc_attr($tournament_id); ?>"
             data-end-time="<?php echo esc_attr($end_time); ?>">
            
            <!-- Left Contender -->
            <?php if (!empty($contenders[0])): $left = $contenders[0]; ?>
                <div class="yuv-contender yuv-left <?php echo $left['is_winner'] ? 'is-winner' : ''; ?>" 
                     data-contender-id="<?php echo esc_attr($left['id']); ?>">
                    
                    <div class="yuv-contender-img" style="background-image: url('<?php echo esc_url($left['image']); ?>');">
                        <div class="yuv-img-overlay"></div>
                    </div>

                    <div class="yuv-contender-info">
                        <h2 class="yuv-contender-name"><?php echo esc_html($left['name']); ?></h2>
                        <?php if (!empty($left['bio'])): ?>
                            <p class="yuv-contender-bio"><?php echo esc_html($left['bio']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!$has_voted): ?>
                            <button class="yuv-vote-btn" data-item-id="<?php echo esc_attr($left['id']); ?>">
                                <span class="yuv-vote-icon">⚡</span>
                                <span class="yuv-vote-text">GLASAJ</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- FIX 4: Added vote count to result overlay -->
                    <div class="yuv-result-overlay">
                        <div class="yuv-result-bar" style="width: <?php echo esc_attr($left['percent']); ?>%;"></div>
                        <span class="yuv-percent"><?php echo esc_html($left['percent']); ?>%</span>
                        <span class="yuv-vote-count"><?php echo esc_html(number_format($left['votes'])); ?> glasova</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- VS Badge -->
            <div class="yuv-vs-badge">
                <span>VS</span>
            </div>

            <!-- Right Contender -->
            <?php if (!empty($contenders[1])): $right = $contenders[1]; ?>
                <div class="yuv-contender yuv-right <?php echo $right['is_winner'] ? 'is-winner' : ''; ?>" 
                     data-contender-id="<?php echo esc_attr($right['id']); ?>">
                    
                    <div class="yuv-contender-img" style="background-image: url('<?php echo esc_url($right['image']); ?>');">
                        <div class="yuv-img-overlay"></div>
                    </div>

                    <div class="yuv-contender-info">
                        <h2 class="yuv-contender-name"><?php echo esc_html($right['name']); ?></h2>
                        <?php if (!empty($right['bio'])): ?>
                            <p class="yuv-contender-bio"><?php echo esc_html($right['bio']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!$has_voted): ?>
                            <button class="yuv-vote-btn" data-item-id="<?php echo esc_attr($right['id']); ?>">
                                <span class="yuv-vote-icon">⚡</span>
                                <span class="yuv-vote-text">GLASAJ</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- FIX 4: Added vote count to result overlay -->
                    <div class="yuv-result-overlay">
                        <div class="yuv-result-bar" style="width: <?php echo esc_attr($right['percent']); ?>%;"></div>
                        <span class="yuv-percent"><?php echo esc_html($right['percent']); ?>%</span>
                        <span class="yuv-vote-count"><?php echo esc_html(number_format($right['votes'])); ?> glasova</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navigation Strip (Footer with Links) -->
        <div class="yuv-nav-strip">
            <?php foreach ($all_matches as $strip_match_id): 
                $is_current = ($strip_match_id == $match_id);
                $strip_items = get_post_meta($strip_match_id, '_voting_items', true) ?: [];
                
                // Check if voted
                if ($user_id > 0) {
                    $strip_voted = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}voting_list_votes 
                        WHERE voting_list_id = %d AND user_id = %d",
                        $strip_match_id,
                        $user_id
                    ));
                } else {
                    $strip_voted = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}voting_list_votes 
                        WHERE voting_list_id = %d AND user_id = 0 AND ip_address = %s",
                        $strip_match_id,
                        $user_ip
                    ));
                }
                
                $strip_class = $is_current ? 'current' : ($strip_voted ? 'voted' : '');
                
                // Get first two images
                $img1 = '';
                $img2 = '';
                if (!empty($strip_items[0])) {
                    $img1 = get_post_meta($strip_items[0], '_custom_image_url', true) ?: get_the_post_thumbnail_url($strip_items[0], 'thumbnail');
                }
                if (!empty($strip_items[1])) {
                    $img2 = get_post_meta($strip_items[1], '_custom_image_url', true) ?: get_the_post_thumbnail_url($strip_items[1], 'thumbnail');
                }
                
                // Use data attribute for match_id (AJAX navigation, no URL changes)
            ?>
                <div class="yuv-nav-item <?php echo esc_attr($strip_class); ?>" data-match-id="<?php echo esc_attr($strip_match_id); ?>">
                    <div class="yuv-nav-thumbs">
                        <?php if ($img1): ?>
                            <img src="<?php echo esc_url($img1); ?>" alt="" class="yuv-nav-img">
                        <?php endif; ?>
                        <span class="yuv-nav-vs">VS</span>
                        <?php if ($img2): ?>
                            <img src="<?php echo esc_url($img2); ?>" alt="" class="yuv-nav-img">
                        <?php endif; ?>
                    </div>
                    <?php if ($strip_voted): ?>
                        <span class="yuv-nav-check"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <?php
    return ob_get_clean();
}

/**
 * No duel message
 */
function yuv_render_no_duel() {
    return '<div class="yuv-no-duel">
        <div class="yuv-no-duel-icon">⚔️</div>
        <h3>Trenutno nema aktivnih duela</h3>
        <p>Pratite nas za najave novih turnira!</p>
    </div>';
}

/**
 * Stage complete message
 */
function yuv_render_stage_complete() {
    return '<div class="yuv-stage-complete">
        <div class="yuv-complete-icon">🏆</div>
        <h2>Završili ste sve trenutne duelove!</h2>
        <p>Vratite se kasnije za sledeću rundu.</p>
        <a href="' . home_url() . '" class="yuv-btn-primary">Nazad na početnu</a>
    </div>';
}

/**
 * Active Duel Shortcode - Hero Battle Arena (DEPRECATED - keeping for compatibility)
 * Usage: [yuv_active_duel_old]
 */
function yuv_active_duel_old_shortcode($atts) {
    global $wpdb;
    
    // Find currently active match
    $current_time = current_time('timestamp');
    
    // Find active match - prioritize matches user hasn't voted in
    $user_id = get_current_user_id();
    
    if ($user_id > 0) {
        // For logged-in users, prioritize matches they haven't voted in
        $active_match = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yuv_match_completed'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_end_time'
            LEFT JOIN {$wpdb->prefix}voting_list_votes v ON p.ID = v.voting_list_id AND v.user_id = %d
            WHERE p.post_type = 'voting_list'
            AND p.post_status = 'publish'
            AND (pm1.meta_value = '0' OR pm1.meta_value = '')
            AND pm2.meta_value > %d
            ORDER BY 
                CASE WHEN v.id IS NULL THEN 0 ELSE 1 END,
                pm2.meta_value ASC
            LIMIT 1",
            $user_id,
            $current_time
        ));
    } else {
        // For guests, just get earliest active match
        $active_match = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yuv_match_completed'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_end_time'
            WHERE p.post_type = 'voting_list'
            AND p.post_status = 'publish'
            AND (pm1.meta_value = '0' OR pm1.meta_value = '')
            AND pm2.meta_value > %d
            ORDER BY pm2.meta_value ASC
            LIMIT 1",
            $current_time
        ));
    }
    
    if (!$active_match) {
        return '<div class="yuv-no-duel">
            <div class="yuv-no-duel-icon">⚔️</div>
            <h3>Trenutno nema aktivnih duela</h3>
            <p>Pratite nas za najave novih turnira!</p>
        </div>';
    }

    // Get match data
    $match_id = $active_match;
    $tournament_id = get_post_meta($match_id, '_yuv_tournament_id', true);
    
    // If tournament doesn't exist, try to find any active tournament and update the match
    $tournament_exists = get_post_status($tournament_id);
    if (!$tournament_exists || $tournament_exists === false) {
        // Find any active tournament
        $active_tournament = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'yuv_tournament' 
            AND post_status = 'publish' 
            ORDER BY post_date DESC 
            LIMIT 1"
        );
        
        if ($active_tournament) {
            // Update this match to point to the active tournament
            update_post_meta($match_id, '_yuv_tournament_id', $active_tournament);
            $tournament_id = $active_tournament;
        } else {
            return '<div class="yuv-no-duel">
                <div class="yuv-no-duel-icon">⚔️</div>
                <h3>Trenutno nema aktivnih duela</h3>
                <p>Pratite nas za najave novih turnira!</p>
            </div>';
        }
    }
    
    $match_title = get_the_title($match_id);
    $stage = get_post_meta($match_id, '_yuv_stage', true);
    $match_number = get_post_meta($match_id, '_yuv_match_number', true);
    $end_time = (int) get_post_meta($match_id, '_yuv_end_time', true);
    $items = get_post_meta($match_id, '_voting_items', true) ?: [];

    // Check if user voted
    $has_voted = false;
    if ($user_id > 0) {
        $votes_table = $wpdb->prefix . 'voting_list_votes';
        $user_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT voting_item_id FROM {$votes_table} 
            WHERE voting_list_id = %d AND user_id = %d",
            $match_id,
            $user_id
        ));
        $has_voted = !empty($user_vote);
    }

    // Get contenders data
    $contenders = [];
    foreach ($items as $item_id) {
        $item = get_post($item_id);
        if (!$item) continue;

        // Get vote count
        $votes_table = $wpdb->prefix . 'voting_list_votes';
        $vote_count = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(vote_value) FROM {$votes_table} 
            WHERE voting_list_id = %d AND voting_item_id = %d",
            $match_id,
            $item_id
        ));

        $image = get_post_meta($item_id, '_custom_image_url', true);
        if (!$image) {
            $image = get_the_post_thumbnail_url($item_id, 'full');
        }
        
        // If custom image URL contains a WordPress thumbnail size, get the full size
        if ($image && preg_match('/(-\d+x\d+)\.(jpg|jpeg|png|gif|webp)$/i', $image, $matches)) {
            $image = str_replace($matches[1] . '.' . $matches[2], '.' . $matches[2], $image);
        }

        $contenders[] = [
            'id' => $item_id,
            'name' => $item->post_title,
            'bio' => get_post_meta($item_id, '_short_description', true) ?: wp_trim_words($item->post_content, 20),
            'image' => $image,
            'votes' => (int) $vote_count,
        ];
    }

    // Calculate percentages
    $total_votes = array_sum(array_column($contenders, 'votes'));
    foreach ($contenders as &$c) {
        $c['percent'] = $total_votes > 0 ? round(($c['votes'] / $total_votes) * 100) : 50;
    }

    // Get ALL remaining matches for this tournament (enhanced timeline)
    $all_future_matches = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, pm1.meta_value as end_time, pm2.meta_value as stage, pm3.meta_value as match_num
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yuv_end_time'
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_stage'
        INNER JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_yuv_match_number'
        INNER JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_yuv_tournament_id' AND pm4.meta_value = %d
        WHERE p.post_type = 'voting_list'
        AND p.post_status IN ('publish', 'future')
        AND p.ID != %d
        AND pm1.meta_value > %d
        ORDER BY pm1.meta_value ASC
        LIMIT 5",
        $tournament_id,
        $match_id,
        $current_time
    ));

    // Stage labels
    $stage_labels = [
        'of' => 'Osmina finala',
        'qf' => 'Četvrtfinale',
        'sf' => 'Polufinale',
        'final' => 'FINALE'
    ];
    $stage_label = $stage_labels[$stage] ?? 'Meč';
    
    // Get all matches in current stage to show progress
    $total_stage_matches = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yuv_stage'
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
        WHERE p.post_type = 'voting_list'
        AND pm1.meta_value = %s
        AND pm2.meta_value = %d",
        $stage,
        $tournament_id
    ));
    
    // Count how many matches user voted in this stage
    $user_votes_in_stage = 0;
    if ($user_id > 0) {
        $user_votes_in_stage = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT v.voting_list_id)
            FROM {$wpdb->prefix}voting_list_votes v
            INNER JOIN {$wpdb->postmeta} pm1 ON v.voting_list_id = pm1.post_id AND pm1.meta_key = '_yuv_stage'
            INNER JOIN {$wpdb->postmeta} pm2 ON v.voting_list_id = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
            WHERE v.user_id = %d
            AND pm1.meta_value = %s
            AND pm2.meta_value = %d",
            $user_id,
            $stage,
            $tournament_id
        ));
    }

    // Get tournament title
    $tournament_title = get_the_title($tournament_id);
    
    // Check if user voted in all matches of this stage
    $all_stage_votes_complete = ($user_votes_in_stage >= $total_stage_matches);
    
    ob_start();
    ?>
    
    <div class="yuv-duel-arena" 
         data-tournament-id="<?php echo esc_attr($tournament_id); ?>"
         data-match-id="<?php echo esc_attr($match_id); ?>"
         data-stage="<?php echo esc_attr($stage); ?>"
         data-total-matches="<?php echo esc_attr($total_stage_matches); ?>"
         data-voted-matches="<?php echo esc_attr($user_votes_in_stage); ?>"
         data-user-voted="<?php echo $has_voted ? 'true' : 'false'; ?>"
         data-end-time="<?php echo esc_attr($end_time); ?>"
         data-all-votes-complete="<?php echo $all_stage_votes_complete ? 'true' : 'false'; ?>">
        
        <!-- Arena Header -->
        <div class="yuv-arena-header">
            <div class="yuv-match-info">
                <span class="yuv-tournament-badge">🏆 <?php echo esc_html($tournament_title); ?></span>
                <h2 class="yuv-match-title">
                    <?php echo esc_html($stage_label . ' ' . $match_number); ?>
                </h2>
            </div>
            <div class="yuv-countdown-timer">
                <span class="yuv-timer-label">⏱️ Preostalo vreme:</span>
                <div class="yuv-timer-display" id="duel-timer">
                    <span class="yuv-time-segment"><span id="timer-hours">00</span>h</span>
                    <span class="yuv-time-segment"><span id="timer-minutes">00</span>m</span>
                    <span class="yuv-time-segment"><span id="timer-seconds">00</span>s</span>
                </div>
            </div>
        </div>
        
        <!-- Progress Bar (inside arena) -->
        <div class="yuv-progress-container">
            <div id="yuv-progress-bar" class="yuv-progress-bar">
                <?php
                $progress_percent = $total_stage_matches > 0 ? ($user_votes_in_stage / $total_stage_matches) * 100 : 0;
                ?>
                <div class="yuv-progress-fill" style="width: <?php echo esc_attr($progress_percent); ?>%"></div>
            </div>
            <div id="yuv-progress-text" class="yuv-progress-text"><?php echo esc_html($user_votes_in_stage); ?>/<?php echo esc_html($total_stage_matches); ?> duels completed</div>
        </div>

        <!-- Battle Arena -->
        <div class="yuv-battle-arena">\n            \n            <!-- Left Contender -->
            <?php if (!empty($contenders[0])): $left = $contenders[0]; ?>
                <div class="yuv-contender yuv-left <?php echo $has_voted ? 'voted' : ''; ?>" 
                     data-contender-id="<?php echo esc_attr($left['id']); ?>">
                    
                    <div class="yuv-contender-img" style="background-image: url('<?php echo esc_url($left['image'] ?: get_template_directory_uri() . '/assets/images/default-avatar.jpg'); ?>');">
                        <div class="yuv-img-overlay"></div>
                    </div>

                    <div class="yuv-contender-info">
                        <h2 class="yuv-contender-name"><?php echo esc_html($left['name']); ?></h2>
                        <?php if ($left['bio']): ?>
                            <p class="yuv-contender-bio"><?php echo esc_html($left['bio']); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($has_voted): ?>
                        <div class="yuv-result-bar">
                            <div class="yuv-bar-fill" style="height: <?php echo esc_attr($left['percent']); ?>%"></div>
                            <span class="yuv-percent"><?php echo esc_html($left['percent']); ?>%</span>
                            <span class="yuv-vote-count"><?php echo esc_html($left['votes']); ?> glasova</span>
                        </div>
                    <?php else: ?>
                        <button class="yuv-vote-btn" data-item-id="<?php echo esc_attr($left['id']); ?>">
                            <span class="yuv-vote-icon">⚡</span>
                            <span class="yuv-vote-text">GLASAJ</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- VS Badge -->
            <div class="yuv-vs-badge">
                <svg width="100" height="100" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="48" fill="rgba(255,255,255,0.95)" stroke="#FF6B35" stroke-width="3"/>
                    <text x="50" y="60" font-size="32" font-weight="900" fill="#FF6B35" text-anchor="middle">VS</text>
                </svg>
                <div class="yuv-lightning">⚡</div>
            </div>

            <!-- Right Contender -->
            <?php if (!empty($contenders[1])): $right = $contenders[1]; ?>
                <div class="yuv-contender yuv-right <?php echo $has_voted ? 'voted' : ''; ?>" 
                     data-contender-id="<?php echo esc_attr($right['id']); ?>">
                    
                    <div class="yuv-contender-img" style="background-image: url('<?php echo esc_url($right['image'] ?: get_template_directory_uri() . '/assets/images/default-avatar.jpg'); ?>');">
                        <div class="yuv-img-overlay"></div>
                    </div>

                    <div class="yuv-contender-info">
                        <h2 class="yuv-contender-name"><?php echo esc_html($right['name']); ?></h2>
                        <?php if ($right['bio']): ?>
                            <p class="yuv-contender-bio"><?php echo esc_html($right['bio']); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($has_voted): ?>
                        <div class="yuv-result-bar">
                            <div class="yuv-bar-fill" style="height: <?php echo esc_attr($right['percent']); ?>%"></div>
                            <span class="yuv-percent"><?php echo esc_html($right['percent']); ?>%</span>
                            <span class="yuv-vote-count"><?php echo esc_html($right['votes']); ?> glasova</span>
                        </div>
                    <?php else: ?>
                        <button class="yuv-vote-btn" data-item-id="<?php echo esc_attr($right['id']); ?>">
                            <span class="yuv-vote-icon">⚡</span>
                            <span class="yuv-vote-text">GLASAJ</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- View Bracket Button (shown after voting in all stage matches) -->
        <?php if ($all_stage_votes_complete): ?>
            <div class="yuv-bracket-cta">
                <p class="yuv-completion-message">🎉 Završili ste glasanje u svim mečevima ove faze!</p>
                <a href="#bracket-results" class="yuv-view-bracket-btn" onclick="document.getElementById('bracket-results').scrollIntoView({behavior: 'smooth'}); return false;">
                    📊 Pogledaj Trenutni Bracket
                </a>
            </div>
        <?php endif; ?>

        <!-- Vote Confirmation Toast -->
        <div id="yuv-vote-toast" class="yuv-vote-toast" style="display: none;">
            <div class="yuv-toast-icon">✓</div>
            <div class="yuv-toast-message">Tvoj glas je zabeležen!</div>
        </div>

    </div>
    
    <!-- Bracket Visualization (if user completed all votes in stage) -->
    <?php if ($all_stage_votes_complete): ?>
        <div id="bracket-results" style="margin-top: 40px;">
            <h3 style="text-align: center; color: #4355a4; margin-bottom: 20px;">📊 Trenutni Bracket</h3>
            <?php echo do_shortcode('[yuv_auto_bracket tournament_id="' . $tournament_id . '"]'); ?>
        </div>
    <?php endif; ?>

    <?php
    return ob_get_clean();
}
add_shortcode('yuv_active_duel', 'yuv_active_duel_shortcode');

/**
 * Tournament Archive Shortcode
 * Displays list of all past tournaments
 */
function yuv_tournament_archive_shortcode($atts) {
    $atts = shortcode_atts(['per_page' => 12], $atts, 'yuv_tournament_archive');
    
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    
    $args = array(
        'post_type' => 'yuv_tournament',
        'post_status' => 'publish',
        'posts_per_page' => intval($atts['per_page']),
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<div class="yuv-no-tournaments"><p>Nema dostupnih turnira.</p></div>';
    }

    ob_start();
    ?>
    <div class="yuv-tournament-archive">
        <style>
            .yuv-tournament-archive { max-width: 1200px; margin: 0 auto; }
            .yuv-tournament-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px; }
            .yuv-tournament-card { background: white; border: 2px solid #ddd; border-radius: 12px; padding: 25px; transition: all 0.3s; cursor: pointer; }
            .yuv-tournament-card:hover { border-color: #2271b1; box-shadow: 0 6px 20px rgba(34, 113, 177, 0.2); transform: translateY(-5px); }
            .yuv-tournament-card.active { border-color: #28a745; background: linear-gradient(135deg, #f0f9f4 0%, white 100%); }
            .yuv-tournament-card.completed { border-color: #6c757d; opacity: 0.85; }
            .yuv-tournament-card-header { margin-bottom: 15px; }
            .yuv-tournament-card-title { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: #333; }
            .yuv-tournament-card-date { font-size: 13px; color: #666; }
            .yuv-tournament-card-status { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 15px; }
            .yuv-tournament-card-status.active { background: #28a745; color: white; }
            .yuv-tournament-card-status.completed { background: #6c757d; color: white; }
            .yuv-tournament-card-winner { margin-top: 15px; padding-top: 15px; border-top: 2px solid #e9ecef; }
            .yuv-tournament-card-winner-label { font-size: 12px; color: #666; margin-bottom: 8px; text-transform: uppercase; }
            .yuv-tournament-card-winner-info { display: flex; align-items: center; gap: 12px; }
            .yuv-tournament-card-winner-info img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
            .yuv-tournament-card-winner-name { font-weight: 700; font-size: 16px; }
            .yuv-tournament-card-meta { font-size: 13px; color: #666; margin-top: 12px; }
            .yuv-tournament-archive-pagination { text-align: center; margin-top: 40px; }
        </style>

        <div class="yuv-tournament-grid">
            <?php while ($query->have_posts()): $query->the_post();
                $tournament_id = get_the_ID();
                $bracket_created = get_post_meta($tournament_id, '_yuv_bracket_created', true);
                $winner_id = get_post_meta($tournament_id, '_yuv_winner_id', true);
                $start_date = get_post_meta($tournament_id, '_yuv_start_date', true);
                $bracket_lists = get_post_meta($tournament_id, '_yuv_bracket_lists', true);
                
                $is_active = $bracket_created && !$winner_id;
                $is_completed = $bracket_created && $winner_id;
                
                $status_class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                $status_text = $is_active ? '🔴 U toku' : ($is_completed ? '✅ Završeno' : '⏳ Predstoji');
            ?>
                <div class="yuv-tournament-card <?php echo esc_attr($status_class); ?>" 
                     onclick="window.location.href='<?php echo get_permalink(); ?>'">
                    
                    <div class="yuv-tournament-card-header">
                        <h3 class="yuv-tournament-card-title"><?php the_title(); ?></h3>
                        <?php if ($start_date): ?>
                            <div class="yuv-tournament-card-date">
                                📅 <?php echo date('d.m.Y', strtotime($start_date)); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <span class="yuv-tournament-card-status <?php echo $is_active ? 'active' : ($is_completed ? 'completed' : ''); ?>">
                        <?php echo esc_html($status_text); ?>
                    </span>

                    <?php if ($is_completed && $winner_id): 
                        $winner = get_post($winner_id);
                        $winner_image = get_post_meta($winner_id, '_custom_image_url', true);
                    ?>
                        <div class="yuv-tournament-card-winner">
                            <div class="yuv-tournament-card-winner-label">👑 Pobednik</div>
                            <div class="yuv-tournament-card-winner-info">
                                <?php if ($winner_image): ?>
                                    <img src="<?php echo esc_url($winner_image); ?>" 
                                         alt="<?php echo esc_attr($winner->post_title); ?>">
                                <?php endif; ?>
                                <span class="yuv-tournament-card-winner-name">
                                    <?php echo esc_html($winner->post_title); ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($bracket_created && !empty($bracket_lists)): 
                        $total_matches = count($bracket_lists['qf']) + count($bracket_lists['sf']) + count($bracket_lists['final']);
                        $completed_matches = 0;
                        foreach (['qf', 'sf', 'final'] as $stage) {
                            foreach ($bracket_lists[$stage] as $list_id) {
                                if (get_post_meta($list_id, '_yuv_match_completed', true)) {
                                    $completed_matches++;
                                }
                            }
                        }
                    ?>
                        <div class="yuv-tournament-card-meta">
                            ⚔️ Mečeva: <?php echo $completed_matches; ?>/<?php echo $total_matches; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($query->max_num_pages > 1): ?>
            <div class="yuv-tournament-archive-pagination">
                <?php
                echo paginate_links(array(
                    'total' => $query->max_num_pages,
                    'current' => $paged,
                    'prev_text' => '← Prethodna',
                    'next_text' => 'Sledeća →'
                ));
                ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('yuv_tournament_archive', 'yuv_tournament_archive_shortcode');

/**
 * Render single match card
 */
function yuv_render_match_card($list_id) {
    global $wpdb;

    $post = get_post($list_id);
    
    if (!$post) {
        return '<div class="yuv-match-card"><p>Meč nije pronađen</p></div>';
    }
    
    $completed = get_post_meta($list_id, '_yuv_match_completed', true);
    $winner_id = get_post_meta($list_id, '_yuv_winner_id', true);
    $tie_breaker = get_post_meta($list_id, '_yuv_tie_breaker_used', true);
    $final_scores = get_post_meta($list_id, '_yuv_final_scores', true) ?: [];
    $items = get_post_meta($list_id, '_voting_items', true) ?: [];
    $end_time = get_post_meta($list_id, '_yuv_end_time', true);

    $status_class = $post->post_status === 'publish' 
        ? ($completed ? 'completed' : 'active') 
        : 'pending';

    ob_start();
    ?>
    <div class="yuv-match-card <?php echo esc_attr($status_class); ?>">
        <div class="yuv-match-title">
            <?php echo esc_html($post->post_title); ?>
        </div>

        <div class="yuv-match-items">
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item_id): 
                    $item_post = get_post($item_id);
                    if (!$item_post) continue;

                    $is_winner = ($completed && $winner_id == $item_id);
                    $score = $final_scores[$item_id] ?? 0;
                    $image = get_post_meta($item_id, '_custom_image_url', true);
                ?>
                    <div class="yuv-match-item <?php echo $is_winner ? 'winner' : ''; ?>">
                        <?php if ($image): ?>
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($item_post->post_title); ?>">
                        <?php endif; ?>
                        <span class="yuv-match-item-name"><?php echo esc_html($item_post->post_title); ?></span>
                        <?php if ($completed): ?>
                            <span class="yuv-match-item-score"><?php echo esc_html($score); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="yuv-match-item">
                    <span class="yuv-match-item-name">TBD</span>
                </div>
                <div class="yuv-match-item">
                    <span class="yuv-match-item-name">TBD</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="yuv-match-status <?php echo esc_attr($status_class); ?>">
            <?php if ($completed): ?>
                ✅ Završeno
                <?php if ($tie_breaker): ?>
                    <span class="yuv-tie-icon" title="Pobednik određen izvlačenjem">🎲</span>
                <?php endif; ?>
            <?php elseif ($post->post_status === 'publish'): ?>
                🔴 U toku - Završava: <?php echo $end_time ? date('d.m.Y H:i', intval($end_time)) : 'N/A'; ?>
            <?php else: ?>
                ⏳ Čeka pobednike
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
