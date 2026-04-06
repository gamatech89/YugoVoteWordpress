<?php
/**
 * Showdown Shortcodes — Redesigned UI
 * [yuv_showdown] - Main voting arena
 * [yuv_showdown_archive] - Past showdowns 
 * [yuv_showdown_widget] - Homepage widget
 */

if (!defined('ABSPATH')) exit;

/**
 * [yuv_showdown] — Main Showdown Arena
 * Shows the active showdown voting experience
 */
function yuv_showdown_shortcode($atts) {
    $manager = new YUV_Showdown_Manager();
    
    // Try to get the active showdown
    $showdown = $manager->get_active_showdown();
    
    if (!$showdown) {
        $showdown = $manager->get_latest_completed();
        if (!$showdown) {
            return '<div class="showdown-wrap"><div class="sd-empty"><div class="sd-empty__icon"><i class="ri-sword-line"></i></div><h3 class="sd-empty__title">Nema aktivnih Showdown-ova</h3><p class="sd-empty__desc">Trenutno nema aktivnih takmičenja. Navrati ponovo uskoro!</p></div></div>';
        }
    }

    $showdown_id = $showdown->ID;
    $items = $manager->get_items($showdown_id);
    $status = get_post_meta($showdown_id, '_yuv_showdown_status', true);
    
    if (count($items) < 2) {
        return '<div class="showdown-wrap"><div class="sd-empty"><div class="sd-empty__icon"><i class="ri-error-warning-line"></i></div><h3 class="sd-empty__title">Nedovoljno učesnika</h3><p class="sd-empty__desc">Showdown nema dovoljno učesnika za početak.</p></div></div>';
    }

    $user_id = get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $has_played = $manager->has_user_played($showdown_id, $user_id, $ip);
    $leaderboard = $has_played || $status === 'completed' ? $manager->get_leaderboard($showdown_id) : [];
    $total_players = $manager->get_session_count($showdown_id);

    ob_start();
    ?>
    <div class="sd-page" 
         id="yuv-showdown-arena"
         data-showdown-id="<?php echo esc_attr($showdown_id); ?>"
         data-status="<?php echo esc_attr($status); ?>"
         data-has-played="<?php echo $has_played ? '1' : '0'; ?>"
         data-items='<?php echo esc_attr(wp_json_encode($items)); ?>'
         style="<?php echo ($has_played || $status === 'completed') ? 'display:none' : ''; ?>"
         >
        
        <!-- Header (hidden when showing results) -->
        <header class="showdown-header" id="sd-header" style="<?php echo ($has_played || $status === 'completed') ? 'display:none' : ''; ?>">
            <div class="showdown-badge">
                <i class="ri-sword-line"></i>
                Može biti samo jedan
            </div>
            <h1 class="showdown-title"><?php echo esc_html($showdown->post_title); ?></h1>
            <?php if ($showdown->post_content): ?>
                <p class="showdown-subtitle"><?php echo esc_html(wp_trim_words($showdown->post_content, 25)); ?></p>
            <?php else: ?>
                <p class="showdown-subtitle">Biraj svog favorita — gubitnik ispada, novi izazivač ulazi u arenu</p>
            <?php endif; ?>
        </header>

        <!-- Progress -->
        <div class="sd-progress" id="sd-progress" style="<?php echo ($has_played || $status === 'completed') ? 'display:none' : ''; ?>">
            <div class="sd-progress__info">
                <span class="sd-progress__round" id="sd-progress-round">Runda 1 od <?php echo count($items) - 1; ?></span>
                <span class="sd-progress__remaining">Preostalo: <strong id="sd-progress-remaining"><?php echo count($items); ?></strong> učesnika</span>
            </div>
            <div class="sd-progress__bar">
                <div class="sd-progress__fill" id="sd-progress-fill" style="width: 0%"></div>
            </div>
        </div>

        <!-- Arena (two cards side by side) -->
        <div class="sd-arena" id="sd-arena" style="<?php echo ($has_played || $status === 'completed') ? 'display:none' : ''; ?>">
            
            <!-- Fighter A -->
            <div class="sd-fighter" id="sd-fighter-a" data-side="left">
                <div class="sd-fighter__img-wrap">
                    <img class="sd-fighter__img" id="sd-fighter-a-img" src="" alt="">
                    <div class="sd-fighter__img-overlay"></div>
                </div>
                <div class="sd-fighter__content">
                    <h2 class="sd-fighter__name" id="sd-fighter-a-name"></h2>
                    <p class="sd-fighter__desc" id="sd-fighter-a-desc"></p>
                    <button class="sd-fighter__pick" data-side="left">
                        <i class="ri-trophy-line"></i>
                        Izaberi
                    </button>
                </div>
            </div>

            <!-- VS -->
            <div class="sd-vs" id="sd-vs">VS</div>

            <!-- Fighter B -->
            <div class="sd-fighter" id="sd-fighter-b" data-side="right">
                <div class="sd-fighter__img-wrap">
                    <img class="sd-fighter__img" id="sd-fighter-b-img" src="" alt="">
                    <div class="sd-fighter__img-overlay"></div>
                </div>
                <div class="sd-fighter__content">
                    <h2 class="sd-fighter__name" id="sd-fighter-b-name"></h2>
                    <p class="sd-fighter__desc" id="sd-fighter-b-desc"></p>
                    <button class="sd-fighter__pick" data-side="right">
                        <i class="ri-trophy-line"></i>
                        Izaberi
                    </button>
                </div>
            </div>

        </div>

        <!-- Hint -->
        <p class="sd-hint" id="sd-hint" style="<?php echo ($has_played || $status === 'completed') ? 'display:none' : ''; ?>">
            <i class="ri-cursor-line"></i>
            Klikni na karticu ili dugme da izabereš pobednika
        </p>

    </div><!-- /.sd-page -->

    <!-- Results Screen (fixed overlay, light theme, matching mockup) -->
    <div id="sd-results" class="sd-results-screen <?php echo ($has_played || $status === 'completed') ? 'sd-results-screen--visible' : ''; ?>">
            
            <?php if ($has_played || $status === 'completed'): ?>
                <!-- Results Header -->
                <header class="showdown-header sd-results-header">
                    <div class="showdown-badge">
                        <i class="ri-trophy-fill"></i>
                        Rezultati
                    </div>
                    <h1 class="showdown-title"><?php echo esc_html($showdown->post_title); ?></h1>
                    <p class="showdown-subtitle">Showdown završen — evo konačnog poretka</p>
                </header>

                <?php if (!empty($leaderboard)): ?>
                    <!-- Podium (top 3) -->
                    <section class="sd-podium">
                        <?php 
                        $podium_order = ['silver' => 1, 'gold' => 0, 'bronze' => 2];
                        foreach ($podium_order as $class => $idx):
                            if (!isset($leaderboard[$idx])) continue;
                            $entry = $leaderboard[$idx];
                        ?>
                            <div class="sd-podium__item sd-podium__item--<?php echo $class; ?>">
                                <div class="sd-podium__avatar-wrap">
                                    <?php if ($class === 'gold'): ?>
                                        <span class="sd-podium__crown">👑</span>
                                    <?php endif; ?>
                                    <span class="sd-podium__medal"><?php echo $idx + 1; ?></span>
                                    <?php if (!empty($entry['image'])): ?>
                                        <img class="sd-podium__avatar" src="<?php echo esc_url($entry['image']); ?>" alt="<?php echo esc_attr($entry['name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <h3 class="sd-podium__name"><?php echo esc_html($entry['name']); ?></h3>
                                <p class="sd-podium__wins">Pobede: <strong><?php echo $entry['wins']; ?></strong></p>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <!-- Ranked list (4th and below) -->
                    <?php if (count($leaderboard) > 3): ?>
                        <section class="sd-ranked">
                            <h2 class="sd-ranked__title">Ostali plasmani</h2>
                            <div class="sd-ranked__list">
                                <?php foreach (array_slice($leaderboard, 3) as $rank => $entry): ?>
                                    <div class="sd-ranked__item">
                                        <span class="sd-ranked__rank"><?php echo $rank + 4; ?></span>
                                        <?php if (!empty($entry['image'])): ?>
                                            <img class="sd-ranked__avatar" src="<?php echo esc_url($entry['image']); ?>" alt="">
                                        <?php endif; ?>
                                        <div class="sd-ranked__info">
                                            <h4 class="sd-ranked__name"><?php echo esc_html($entry['name']); ?></h4>
                                            <?php if (!empty($entry['description'])): ?>
                                                <p class="sd-ranked__desc"><?php echo esc_html(wp_trim_words($entry['description'], 10)); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="sd-ranked__stats">Pobede: <strong><?php echo $entry['wins']; ?></strong></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>


                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php
    return ob_get_clean();
}
add_shortcode('yuv_showdown', 'yuv_showdown_shortcode');


/**
 * [yuv_showdown_archive] — Past Showdowns
 */
function yuv_showdown_archive_shortcode($atts) {
    $atts = shortcode_atts(['count' => 20], $atts);
    
    $manager = new YUV_Showdown_Manager();
    $archive = $manager->get_archive(intval($atts['count']));

    if (empty($archive)) {
        return '<div class="showdown-wrap"><div class="sd-empty"><div class="sd-empty__icon"><i class="ri-archive-line"></i></div><h3 class="sd-empty__title">Nema prethodnih Showdown-ova</h3><p class="sd-empty__desc">Još uvek nema završenih takmičenja.</p></div></div>';
    }

    ob_start();
    ?>
    <div class="showdown-wrap sd-dark">
        <!-- Archive Header -->
        <header class="showdown-header sd-archive-header">
            <div class="showdown-badge">
                <i class="ri-archive-line"></i>
                Arhiva
            </div>
            <h1 class="showdown-title">Prethodni Showdown-ovi</h1>
            <p class="showdown-subtitle">Pogledaj rezultate završenih takmičenja</p>
        </header>

        <!-- Grid -->
        <div class="sd-archive-grid">
            <?php foreach ($archive as $item): ?>
                <article class="sd-archive-card">
                    <div class="sd-archive-card__top">
                        <span class="sd-archive-card__cat">Showdown</span>
                        <span class="sd-archive-card__date"><?php echo esc_html($item['date']); ?></span>
                    </div>
                    <h3 class="sd-archive-card__title"><?php echo esc_html($item['title']); ?></h3>
                    
                    <?php if (!empty($item['top3'])): ?>
                        <div class="sd-archive-podium">
                            <?php 
                            $podium_map = [1 => '2nd', 0 => '1st', 2 => '3rd'];
                            foreach ($podium_map as $idx => $pos):
                                if (!isset($item['top3'][$idx])) continue;
                                $entry = $item['top3'][$idx];
                            ?>
                                <div class="sd-archive-winner sd-archive-winner--<?php echo $pos; ?>">
                                    <div class="sd-archive-winner__avatar-wrap">
                                        <?php if (!empty($entry['image'])): ?>
                                            <img class="sd-archive-winner__avatar" src="<?php echo esc_url($entry['image']); ?>" alt="">
                                        <?php endif; ?>
                                        <span class="sd-archive-winner__badge"><?php echo $idx + 1; ?></span>
                                    </div>
                                    <span class="sd-archive-winner__name"><?php echo esc_html($entry['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="sd-archive-card__footer">
                        <span class="sd-archive-card__participants">
                            <i class="ri-group-line"></i>
                            <?php echo $item['total_sessions']; ?> glasača
                        </span>
                        <a href="<?php echo esc_url(get_permalink($item['id'])); ?>" class="sd-archive-card__play">
                            Pogledaj <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('yuv_showdown_archive', 'yuv_showdown_archive_shortcode');


/**
 * [yuv_showdown_widget] — Homepage Widget
 */
function yuv_showdown_widget_shortcode($atts) {
    $manager = new YUV_Showdown_Manager();
    
    $showdown = $manager->get_active_showdown();
    $is_active = ($showdown !== null);
    
    if (!$showdown) {
        $showdown = $manager->get_latest_completed();
    }
    
    if (!$showdown) {
        return '';
    }

    $showdown_id = $showdown->ID;
    $items = $manager->get_items($showdown_id);
    $total_players = $manager->get_session_count($showdown_id);
    
    $user_id = get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $has_played = $manager->has_user_played($showdown_id, $user_id, $ip);

    ob_start();
    ?>
    <div class="sd-widget sd-dark">
        <div class="sd-widget__badge">
            <i class="<?php echo $is_active ? 'ri-fire-line' : 'ri-flag-line'; ?>"></i>
            <?php echo $is_active ? 'Aktivan Showdown' : 'Poslednji Showdown'; ?>
        </div>
        <h3 class="sd-widget__title"><?php echo esc_html($showdown->post_title); ?></h3>
        <p class="sd-widget__desc">
            <?php echo count($items); ?> učesnika · <?php echo $total_players; ?> glasača
        </p>
        <?php if ($is_active && !$has_played): ?>
            <a href="<?php echo get_permalink($showdown_id); ?>" class="sd-widget__cta">
                <i class="ri-sword-line"></i>
                Igraj Sada
            </a>
        <?php elseif ($is_active && $has_played): ?>
            <a href="<?php echo get_permalink($showdown_id); ?>" class="sd-widget__cta">
                <i class="ri-bar-chart-2-line"></i>
                Pogledaj Rezultate
            </a>
        <?php else: ?>
            <a href="<?php echo get_permalink($showdown_id); ?>" class="sd-widget__cta">
                <i class="ri-trophy-line"></i>
                Pogledaj Pobednika
            </a>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('yuv_showdown_widget', 'yuv_showdown_widget_shortcode');
