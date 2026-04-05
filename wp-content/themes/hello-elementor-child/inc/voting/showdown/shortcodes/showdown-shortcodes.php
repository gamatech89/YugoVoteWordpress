<?php
/**
 * Showdown — Layout Stability Version (V5)
 * Simplified structure to restore Elementor compatibility
 */

if (!defined('ABSPATH')) exit;

/**
 * [yuv_showdown] — Main Showdown Arena
 */
function yuv_showdown_shortcode($atts) {
    $manager = new YUV_Showdown_Manager();
    $showdown = $manager->get_active_showdown();
    
    if (!$showdown) {
        $showdown = $manager->get_latest_completed();
        if (!$showdown) {
            return '<div class="sd-static-wrap"><div class="sd-empty">Trenutno nema aktivnih takmičenja.</div></div>';
        }
    }

    $showdown_id = $showdown->ID;
    $items = $manager->get_items($showdown_id);
    $status = get_post_meta($showdown_id, '_yuv_showdown_status', true);
    
    if (count($items) < 2) {
        return '<div class="sd-static-wrap"><div class="sd-empty">Nedovoljno učesnika.</div></div>';
    }

    $user_id = get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $has_played = $manager->has_user_played($showdown_id, $user_id, $ip);
    $leaderboard = ($has_played || $status === 'completed') ? $manager->get_leaderboard($showdown_id) : [];
    $total_players = $manager->get_session_count($showdown_id);

    ob_start();
    ?>
    <section class="sd-arena-container" 
             id="yuv-showdown-arena"
             data-showdown-id="<?php echo esc_attr($showdown_id); ?>"
             data-status="<?php echo esc_attr($status); ?>"
             data-has-played="<?php echo $has_played ? '1' : '0'; ?>"
             data-items='<?php echo esc_attr(wp_json_encode($items)); ?>'
             style="<?php echo ($has_played || $status === 'completed') ? 'display:none' : ''; ?>"
             >
        
        <header class="sd-arena-header">
            <div class="sd-badge"><i class="ri-sword-line"></i> Showdown</div>
            <h1 class="sd-title"><?php echo esc_html($showdown->post_title); ?></h1>
            <?php if ($showdown->post_content): ?>
                <p class="sd-subtitle"><?php echo esc_html($showdown->post_content); ?></p>
            <?php endif; ?>
        </header>

        <div class="sd-stats-bar">
            <span>Runda 1 od <?php echo count($items) - 1; ?></span>
            <div class="sd-bar-track">
                <div class="sd-bar-fill" id="sd-progress-fill" style="width: 0%"></div>
            </div>
        </div>

        <div class="sd-main-battle">
            <!-- Left Fighter -->
            <div class="sd-card" id="sd-fighter-a" data-side="left">
                <div class="sd-card__media">
                    <img class="sd-card__image" id="sd-fighter-a-img" src="" alt="">
                </div>
                <div class="sd-card__info">
                    <h2 class="sd-card__name" id="sd-fighter-a-name"></h2>
                    <button class="sd-card__btn" data-side="left">PIK</button>
                </div>
            </div>

            <!-- VS Badge -->
            <div class="sd-vs-circle">VS</div>

            <!-- Right Fighter -->
            <div class="sd-card" id="sd-fighter-b" data-side="right">
                <div class="sd-card__media">
                    <img class="sd-card__image" id="sd-fighter-b-img" src="" alt="">
                </div>
                <div class="sd-card__info">
                    <h2 class="sd-card__name" id="sd-fighter-b-name"></h2>
                    <button class="sd-card__btn" data-side="right">PIK</button>
                </div>
            </div>
        </div>
    </section>

    <section id="sd-results" class="sd-results-view <?php echo ($has_played || $status === 'completed') ? 'sd-results-view--active' : ''; ?>">
        <?php if ($has_played || $status === 'completed'): ?>
            <header class="sd-results-header">
                <h1 class="sd-title"><?php echo esc_html($showdown->post_title); ?></h1>
                <p class="sd-subtitle">Rezultati takmičenja</p>
            </header>

            <?php if (!empty($leaderboard)): ?>
                <div class="sd-podium-grid">
                    <?php 
                    $order = ['silver' => 1, 'gold' => 0, 'bronze' => 2];
                    foreach ($order as $cls => $idx):
                        if (!isset($leaderboard[$idx])) continue;
                        $entry = $leaderboard[$idx];
                        $pct = $total_players > 0 ? round(($entry['wins'] / $total_players) * 100, 1) : 0;
                    ?>
                        <div class="sd-podium-box sd-podium-box--<?php echo $cls; ?>">
                            <div class="sd-podium-avatar">
                                <?php if (!empty($entry['image'])): ?>
                                    <img src="<?php echo esc_url($entry['image']); ?>" alt="">
                                <?php endif; ?>
                                <span class="sd-podium-rank"><?php echo $idx + 1; ?></span>
                            </div>
                            <h3 class="sd-podium-name"><?php echo esc_html($entry['name']); ?></h3>
                            <span class="sd-podium-pct"><?php echo $pct; ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="sd-list-ranked">
                    <?php foreach (array_slice($leaderboard, 3) as $rank => $entry): 
                        $pct = $total_players > 0 ? round(($entry['wins'] / $total_players) * 100, 1) : 0;
                    ?>
                        <div class="sd-list-item">
                            <span class="sd-list-rank">#<?php echo $rank + 4; ?></span>
                            <span class="sd-list-name"><?php echo esc_html($entry['name']); ?></span>
                            <span class="sd-list-pct"><?php echo $pct; ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('yuv_showdown', 'yuv_showdown_shortcode');

/**
 * [yuv_showdown_widget] — Landing Page Widget
 */
function yuv_showdown_widget_shortcode($atts) {
    $manager = new YUV_Showdown_Manager();
    $showdown = $manager->get_active_showdown();
    if (!$showdown) { $showdown = $manager->get_latest_completed(); }
    if (!$showdown) { return ''; }

    ob_start();
    ?>
    <div class="sd-widget-flat">
        <h3 class="sd-widget-flat__title"><?php echo esc_html($showdown->post_title); ?></h3>
        <a href="<?php echo get_permalink($showdown->ID); ?>" class="sd-widget-flat__link">Pogledaj Showdown</a>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('yuv_showdown_widget', 'yuv_showdown_widget_shortcode');

/** [yuv_showdown_archive] - Placeholder fixed */
function yuv_showdown_archive_shortcode($atts) {
    return '<!-- Archive Replaced -->';
}
add_shortcode('yuv_showdown_archive', 'yuv_showdown_archive_shortcode');
