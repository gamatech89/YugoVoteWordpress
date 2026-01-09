<?php
/**
 * Tournament Duel Arena - Clean & Functional PHP
 * Created: 2025-12-28
 */

if (!defined('ABSPATH')) exit;

/**
 * Active Duel Shortcode
 * Display the current active match in hero/battle arena format
 */
function yuv_active_duel_shortcode($atts) {
    global $wpdb;
    
    // Get current user info
    $user_id = get_current_user_id();
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    // Find active tournament
    $tournament = get_posts(array(
        'post_type' => 'yuv_tournament',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => '_yuv_bracket_created',
                'value' => '1'
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if (empty($tournament)) {
        return '<div class="yuv-no-duel"><p>Trenutno nema aktivnih duelova.</p></div>';
    }
    
    $tournament_id = $tournament[0]->ID;
    $tournament_title = $tournament[0]->post_title;
    
    // Get all matches for this tournament
    $all_matches = get_posts(array(
        'post_type' => 'voting_list',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_yuv_tournament_id',
                'value' => $tournament_id
            ),
            array(
                'key' => '_is_tournament_match',
                'value' => '1'
            )
        ),
        'orderby' => 'meta_value_num',
        'meta_key' => '_yuv_match_number',
        'order' => 'ASC'
    ));
    
    if (empty($all_matches)) {
        return '<div class="yuv-no-duel"><p>Nema dostupnih mečeva.</p></div>';
    }
    
    // Find first unvoted match for this user
    $current_match = null;
    foreach ($all_matches as $match) {
        $match_id = $match->ID;
        
        // Check if user already voted
        if ($user_id > 0) {
            $voted = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}voting_list_votes 
                WHERE voting_list_id = %d AND user_id = %d",
                $match_id,
                $user_id
            ));
        } else {
            $voted = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}voting_list_votes 
                WHERE voting_list_id = %d AND user_id = 0 AND ip_address = %s",
                $match_id,
                $user_ip
            ));
        }
        
        if (!$voted) {
            $current_match = $match;
            break;
        }
    }
    
    // If no unvoted match found, show first match
    if (!$current_match) {
        $current_match = $all_matches[0];
    }
    
    $match_id = $current_match->ID;
    
    // Get all match IDs for navigation
    $all_match_ids = array_map(function($m) { return $m->ID; }, $all_matches);
    
    // Render the arena
    return yuv_render_duel_arena($match_id, $tournament_id, $tournament_title, $all_match_ids, $user_id, $user_ip);
}
add_shortcode('yuv_active_duel', 'yuv_active_duel_shortcode');

/**
 * Render the duel arena HTML
 */
function yuv_render_duel_arena($match_id, $tournament_id, $tournament_title, $all_match_ids, $user_id, $user_ip) {
    global $wpdb;
    
    // Get match data
    $stage = get_post_meta($match_id, '_yuv_stage', true);
    $end_time = get_post_meta($match_id, '_yuv_end_time', true);
    $items = get_post_meta($match_id, '_voting_items', true) ?: array();
    
    // Check if user already voted
    $has_voted = false;
    $winning_item_id = null;
    
    if ($user_id > 0) {
        $vote = $wpdb->get_var($wpdb->prepare(
            "SELECT voting_item_id FROM {$wpdb->prefix}voting_list_votes 
            WHERE voting_list_id = %d AND user_id = %d LIMIT 1",
            $match_id,
            $user_id
        ));
        if ($vote) {
            $has_voted = true;
            $winning_item_id = intval($vote);
        }
    } else {
        $vote = $wpdb->get_var($wpdb->prepare(
            "SELECT voting_item_id FROM {$wpdb->prefix}voting_list_votes 
            WHERE voting_list_id = %d AND user_id = 0 AND ip_address = %s LIMIT 1",
            $match_id,
            $user_ip
        ));
        if ($vote) {
            $has_voted = true;
            $winning_item_id = intval($vote);
        }
    }
    
    // Build contenders array
    $contenders = array();
    foreach ($items as $item_id) {
        $item = get_post($item_id);
        if (!$item) continue;
        
        // Get vote count
        $vote_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}voting_list_votes 
            WHERE voting_list_id = %d AND voting_item_id = %d",
            $match_id,
            $item_id
        ));
        
        // Get image
        $image = get_post_meta($item_id, '_custom_image_url', true);
        if (!$image) {
            $image = get_the_post_thumbnail_url($item_id, 'large');
        }
        if (!$image) {
            $image = 'https://via.placeholder.com/800x600?text=' . urlencode($item->post_title);
        }
        
        // Get bio
        $bio = get_post_meta($item_id, '_short_description', true);
        if (!$bio) {
            $bio = wp_trim_words($item->post_content, 20, '...');
        }
        
        $contenders[] = array(
            'id' => $item_id,
            'name' => $item->post_title,
            'bio' => $bio,
            'image' => $image,
            'votes' => intval($vote_count)
        );
    }
    
    // Calculate percentages
    $total_votes = array_sum(array_column($contenders, 'votes'));
    foreach ($contenders as &$c) {
        $c['percent'] = $total_votes > 0 ? round(($c['votes'] / $total_votes) * 100) : 50;
        $c['is_winner'] = ($c['id'] == $winning_item_id);
    }
    
    // Stage labels
    $stage_labels = array(
        'r16' => 'OSMINA FINALA',
        'qf' => 'ČETVRTFINALE',
        'sf' => 'POLUFINALE',
        'final' => 'FINALE'
    );
    $stage_label = isset($stage_labels[$stage]) ? $stage_labels[$stage] : 'DUEL';
    
    // Start output
    ob_start();
    ?>
    
    <div id="yuv-arena" class="yuv-arena-wrapper <?php echo $has_voted ? 'yuv-show-results' : ''; ?>">
        
        <!-- Header -->
        <div class="yuv-arena-header">
            <span class="yuv-stage-badge"><?php echo esc_html($stage_label); ?></span>
            <h2 class="yuv-tournament-title"><?php echo esc_html($tournament_title); ?></h2>
            
            <?php if (!empty($contenders[0]) && !empty($contenders[1])): ?>
                <p class="yuv-match-subtitle">
                    <?php echo esc_html($contenders[0]['name']) . ' vs ' . esc_html($contenders[1]['name']); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($end_time): ?>
                <div class="yuv-timer-display">
                    <span>⏱️ Preostalo:</span>
                    <span class="yuv-timer-value" data-end="<?php echo esc_attr($end_time); ?>">--:--:--</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Duel Arena -->
        <div class="yuv-duel-arena" 
             data-match-id="<?php echo esc_attr($match_id); ?>"
             data-tournament-id="<?php echo esc_attr($tournament_id); ?>">
            
            <?php if (!empty($contenders[0])): $left = $contenders[0]; ?>
            <!-- Left Contender -->
            <div class="yuv-contender <?php echo $left['is_winner'] ? 'is-winner' : ''; ?>" 
                 data-contender-id="<?php echo esc_attr($left['id']); ?>">
                
                <div class="yuv-contender-bg" style="background-image: url('<?php echo esc_url($left['image']); ?>');"></div>
                
                <div class="yuv-contender-content">
                    <div class="yuv-contender-info-panel">
                        <h3 class="yuv-contender-name"><?php echo esc_html($left['name']); ?></h3>
                        
                        <?php if ($left['bio']): ?>
                            <p class="yuv-contender-bio"><?php echo esc_html($left['bio']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!$has_voted): ?>
                            <button class="yuv-vote-btn" data-item-id="<?php echo esc_attr($left['id']); ?>">
                                <span class="yuv-vote-icon">⚡</span>
                                <span class="yuv-vote-text">GLASAJ</span>
                            </button>
                        <?php endif; ?>
                        
                        <!-- Vote Count Only Visible After Voting -->
                        <div class="yuv-vote-count-display">
                            <span class="yuv-vote-percent"><?php echo esc_html($left['percent']); ?>%</span>
                            <span class="yuv-vote-number"><?php echo esc_html(number_format($left['votes'])); ?> glasova</span>
                        </div>
                    </div>
                </div>
                
                <!-- Result Bar for voted state -->
                <?php if ($has_voted): ?>
                <div class="yuv-result-bar-container">
                    <div class="yuv-result-bar" style="width: <?php echo esc_attr($left['percent']); ?>%;"></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($contenders[1])): $right = $contenders[1]; ?>
            <!-- Right Contender -->
            <div class="yuv-contender <?php echo $right['is_winner'] ? 'is-winner' : ''; ?>" 
                 data-contender-id="<?php echo esc_attr($right['id']); ?>">
                
                <div class="yuv-contender-bg" style="background-image: url('<?php echo esc_url($right['image']); ?>');"></div>
                
                <div class="yuv-contender-content">
                    <div class="yuv-contender-info-panel">
                        <h3 class="yuv-contender-name"><?php echo esc_html($right['name']); ?></h3>
                        
                        <?php if ($right['bio']): ?>
                            <p class="yuv-contender-bio"><?php echo esc_html($right['bio']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!$has_voted): ?>
                            <button class="yuv-vote-btn" data-item-id="<?php echo esc_attr($right['id']); ?>">
                                <span class="yuv-vote-icon">⚡</span>
                                <span class="yuv-vote-text">GLASAJ</span>
                            </button>
                        <?php endif; ?>
                        
                        <!-- Vote Count Only Visible After Voting -->
                        <div class="yuv-vote-count-display">
                            <span class="yuv-vote-percent"><?php echo esc_html($right['percent']); ?>%</span>
                            <span class="yuv-vote-number"><?php echo esc_html(number_format($right['votes'])); ?> glasova</span>
                        </div>
                    </div>
                </div>
                
                <!-- Result Bar for voted state -->
                <?php if ($has_voted): ?>
                <div class="yuv-result-bar-container">
                    <div class="yuv-result-bar" style="width: <?php echo esc_attr($right['percent']); ?>%;"></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- VS Badge -->
            <div class="yuv-vs-badge">
                <span>VS</span>
            </div>
        </div>

        <!-- Navigation Strip -->
        <div class="yuv-nav-strip">
            <?php foreach ($all_match_ids as $nav_match_id): 
                $is_current = ($nav_match_id == $match_id);
                $nav_items = get_post_meta($nav_match_id, '_voting_items', true) ?: array();
                
                // Check if user voted on this match
                $nav_voted = false;
                if ($user_id > 0) {
                    $nav_voted = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}voting_list_votes 
                        WHERE voting_list_id = %d AND user_id = %d",
                        $nav_match_id,
                        $user_id
                    )) > 0;
                } else {
                    $nav_voted = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}voting_list_votes 
                        WHERE voting_list_id = %d AND user_id = 0 AND ip_address = %s",
                        $nav_match_id,
                        $user_ip
                    )) > 0;
                }
                
                $nav_class = $is_current ? 'current' : ($nav_voted ? 'voted' : '');
                
                // Get thumbnails
                $thumb1 = '';
                $thumb2 = '';
                if (!empty($nav_items[0])) {
                    $thumb1 = get_post_meta($nav_items[0], '_custom_image_url', true);
                    if (!$thumb1) {
                        $thumb1 = get_the_post_thumbnail_url($nav_items[0], 'thumbnail');
                    }
                }
                if (!empty($nav_items[1])) {
                    $thumb2 = get_post_meta($nav_items[1], '_custom_image_url', true);
                    if (!$thumb2) {
                        $thumb2 = get_the_post_thumbnail_url($nav_items[1], 'thumbnail');
                    }
                }
            ?>
                <div class="yuv-nav-item <?php echo esc_attr($nav_class); ?>" 
                     data-match-id="<?php echo esc_attr($nav_match_id); ?>">
                    <div class="yuv-nav-thumbs">
                        <?php if ($thumb1): ?>
                            <img src="<?php echo esc_url($thumb1); ?>" alt="" class="yuv-nav-img">
                        <?php endif; ?>
                        <span class="yuv-nav-vs">VS</span>
                        <?php if ($thumb2): ?>
                            <img src="<?php echo esc_url($thumb2); ?>" alt="" class="yuv-nav-img">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * AJAX: Load specific match
 */
add_action('wp_ajax_yuv_load_match', 'yuv_ajax_load_match');
add_action('wp_ajax_nopriv_yuv_load_match', 'yuv_ajax_load_match');

function yuv_ajax_load_match() {
    check_ajax_referer('yuv_tournament_nonce', 'nonce');
    
    global $wpdb;
    
    $match_id = intval($_POST['match_id']);
    if (!$match_id) {
        wp_send_json_error(array('message' => 'Nevažeći ID meča'));
    }
    
    $user_id = get_current_user_id();
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    // Get tournament info
    $tournament_id = get_post_meta($match_id, '_yuv_tournament_id', true);
    $tournament = get_post($tournament_id);
    
    if (!$tournament) {
        wp_send_json_error(array('message' => 'Turnir nije pronađen'));
    }
    
    // Get all matches
    $all_matches = get_posts(array(
        'post_type' => 'voting_list',
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key' => '_yuv_tournament_id', 'value' => $tournament_id),
            array('key' => '_is_tournament_match', 'value' => '1')
        ),
        'orderby' => 'meta_value_num',
        'meta_key' => '_yuv_match_number',
        'order' => 'ASC'
    ));
    
    $all_match_ids = array_map(function($m) { return $m->ID; }, $all_matches);
    
    // Render arena
    $html = yuv_render_duel_arena($match_id, $tournament_id, $tournament->post_title, $all_match_ids, $user_id, $user_ip);
    
    wp_send_json_success(array('html' => $html));
}
