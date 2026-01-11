<?php if (!defined('ABSPATH')) exit;

// Ensure icons are loaded
if (!function_exists('ygv_icon_e')) {
    require_once get_stylesheet_directory() . '/inc/icons.php';
}

$user_id = get_current_user_id();
global $wpdb;

// Get lists created by this user
$user_lists = get_posts([
    'post_type' => 'voting_list',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => ['publish', 'pending', 'draft'],
    'orderby' => 'date',
    'order' => 'DESC',
]);

// Get level config to check if user can create lists
$can_create = ['can_create' => false, 'reason' => ''];
if (function_exists('ygv_can_user_create_list')) {
    $can_create = ygv_can_user_create_list($user_id);
}

// Get all parent categories for the category filter
$parent_categories = get_terms([
    'taxonomy' => 'voting_list_category',
    'parent' => 0,
    'hide_empty' => false,
]);

// Get voting history - lists where user has voted
$voted_lists_query = $wpdb->prepare(
    "SELECT DISTINCT v.voting_list_id, MAX(v.created_at) as last_vote_date, COUNT(*) as vote_count
     FROM {$wpdb->prefix}voting_list_votes v
     WHERE v.user_id = %d
     GROUP BY v.voting_list_id
     ORDER BY last_vote_date DESC
     LIMIT 50",
    $user_id
);
$voted_list_data = $wpdb->get_results($voted_lists_query, ARRAY_A);

// Get the list post objects
$voted_lists = [];
if (!empty($voted_list_data)) {
    $list_ids = wp_list_pluck($voted_list_data, 'voting_list_id');
    $voted_list_map = array_column($voted_list_data, null, 'voting_list_id');
    
    $list_posts = get_posts([
        'post_type' => 'voting_list',
        'post__in' => $list_ids,
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'post__in',
    ]);
    
    foreach ($list_posts as $list) {
        $voted_lists[] = [
            'post' => $list,
            'vote_count' => $voted_list_map[$list->ID]['vote_count'] ?? 0,
            'last_vote' => $voted_list_map[$list->ID]['last_vote_date'] ?? '',
        ];
    }
}

// Calculate voting stats
$total_votes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}voting_list_votes WHERE user_id = %d",
    $user_id
));

$total_lists_voted = count($voted_list_data);

// Get votes by category
$votes_by_category = $wpdb->get_results($wpdb->prepare(
    "SELECT t.name as category_name, t.term_id, COUNT(v.id) as vote_count
     FROM {$wpdb->prefix}voting_list_votes v
     JOIN {$wpdb->prefix}posts p ON v.voting_list_id = p.ID
     JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
     JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
     JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
     WHERE v.user_id = %d AND tt.taxonomy = 'voting_list_category' AND tt.parent = 0
     GROUP BY t.term_id
     ORDER BY vote_count DESC",
    $user_id
), ARRAY_A);

// Get unique items voted on
$unique_items_voted = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT voting_item_id) FROM {$wpdb->prefix}voting_list_votes WHERE user_id = %d",
    $user_id
));

// Get total points (sum of all vote values)
$total_points = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(vote_value), 0) FROM {$wpdb->prefix}voting_list_votes WHERE user_id = %d",
    $user_id
));

// Get voting streak (consecutive days)
$voting_days = $wpdb->get_col($wpdb->prepare(
    "SELECT DISTINCT DATE(created_at) as vote_date 
     FROM {$wpdb->prefix}voting_list_votes 
     WHERE user_id = %d 
     ORDER BY vote_date DESC 
     LIMIT 30",
    $user_id
));
$voting_streak = 0;
if (!empty($voting_days)) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    // Only count streak if voted today or yesterday
    if ($voting_days[0] === $today || $voting_days[0] === $yesterday) {
        $voting_streak = 1;
        for ($i = 1; $i < count($voting_days); $i++) {
            $expected = date('Y-m-d', strtotime($voting_days[$i-1] . ' -1 day'));
            if ($voting_days[$i] === $expected) {
                $voting_streak++;
            } else {
                break;
            }
        }
    }
}
?>

<div class="ygv-my-lists">
    
    <div class="ygv-card">
        <div class="ygv-card-header">
            <h3><?php echo esc_html__('Moje Liste', 'hello-elementor-child'); ?></h3>
            <?php if ($can_create['can_create']): ?>
                <a href="<?php echo esc_url(ygv_account_page_url(['tab' => 'kreiraj-listu'])); ?>" class="ygv-btn ygv-btn-primary ygv-btn-auto">
                    + <?php echo esc_html__('Kreiraj Novu Listu', 'hello-elementor-child'); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (!$can_create['can_create']): ?>
            <div class="ygv-info-box">
                <span class="ygv-info-icon"><?php ygv_icon_e('lock', 24); ?></span>
                <div class="ygv-info-content">
                    <strong><?php echo esc_html__('Kreiranje lista je zaključano', 'hello-elementor-child'); ?></strong>
                    <p><?php echo esc_html($can_create['reason']); ?></p>
                    <p class="ygv-info-hint"><?php echo esc_html__('Rešavaj kvizove i glasaj da zaradiš XP i otključaš ovu funkciju!', 'hello-elementor-child'); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (empty($user_lists)): ?>
            <div class="ygv-empty-state">
                <span class="ygv-empty-icon"><?php ygv_icon_e('clipboard-list', 48); ?></span>
                <h4><?php echo esc_html__('Još nemaš nijednu listu', 'hello-elementor-child'); ?></h4>
                <p><?php echo esc_html__('Kada dostigneš potreban nivo, moći ćeš da kreiraš svoje Top 10 liste.', 'hello-elementor-child'); ?></p>
            </div>
        <?php else: ?>
            <?php
            // ✅ PERFORMANCE: Prefetch all terms for all lists to avoid N+1 queries
            $list_ids = wp_list_pluck($user_lists, 'ID');
            $list_ids = array_map('intval', $list_ids); // Ensure integers for security
            update_object_term_cache($list_ids, 'voting_list');
            
            // ✅ PERFORMANCE: Batch fetch vote counts for all lists in one query
            global $wpdb;
            $list_ids_placeholders = implode(',', array_fill(0, count($list_ids), '%d'));
            $vote_counts_query = $wpdb->prepare(
                "SELECT voting_list_id, COUNT(*) as vote_count 
                 FROM {$wpdb->prefix}voting_list_votes 
                 WHERE voting_list_id IN ($list_ids_placeholders) 
                 GROUP BY voting_list_id",
                ...$list_ids
            );
            $vote_counts_results = $wpdb->get_results($vote_counts_query, OBJECT_K);
            ?>
            <div class="ygv-lists-grid">
                <?php foreach ($user_lists as $list): 
                    $categories = wp_get_object_terms($list->ID, 'voting_list_category', ['fields' => 'names']);
                    $category_name = !empty($categories) ? $categories[0] : '';
                    $status = $list->post_status;
                    $status_labels = [
                        'publish' => __('Objavljeno', 'hello-elementor-child'),
                        'pending' => __('Na čekanju', 'hello-elementor-child'),
                        'draft' => __('Nacrt', 'hello-elementor-child'),
                    ];
                    $status_label = $status_labels[$status] ?? $status;
                    $status_class = 'ygv-status-' . $status;
                    
                    // ✅ PERFORMANCE: Use pre-fetched vote count
                    $vote_count = isset($vote_counts_results[$list->ID]) ? $vote_counts_results[$list->ID]->vote_count : 0;
                    
                    // Get thumbnail
                    $thumbnail = get_the_post_thumbnail_url($list->ID, 'medium');
                    if (!$thumbnail) {
                        $thumbnail = get_stylesheet_directory_uri() . '/assets/images/list-placeholder.jpg';
                    }
                ?>
                <div class="ygv-list-card">
                    <div class="ygv-list-thumb" style="background-image: url('<?php echo esc_url($thumbnail); ?>')">
                        <span class="ygv-list-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                    </div>
                    <div class="ygv-list-content">
                        <h4 class="ygv-list-title">
                            <a href="<?php echo esc_url(get_permalink($list->ID)); ?>"><?php echo esc_html($list->post_title); ?></a>
                        </h4>
                        <?php if ($category_name): ?>
                            <span class="ygv-list-category"><?php echo esc_html($category_name); ?></span>
                        <?php endif; ?>
                        <div class="ygv-list-meta">
                            <span class="ygv-list-votes"><?php ygv_icon_e('vote', 14); ?> <?php echo number_format($vote_count); ?> <?php echo esc_html__('glasova', 'hello-elementor-child'); ?></span>
                            <span class="ygv-list-date"><?php echo esc_html(human_time_diff(strtotime($list->post_date), current_time('timestamp'))); ?> <?php echo esc_html__('pre', 'hello-elementor-child'); ?></span>
                        </div>
                    </div>
                    <div class="ygv-list-actions">
                        <a href="<?php echo esc_url(get_edit_post_link($list->ID)); ?>" class="ygv-btn-small" title="<?php echo esc_attr__('Uredi', 'hello-elementor-child'); ?>"><?php ygv_icon_e('edit', 16); ?></a>
                        <a href="<?php echo esc_url(get_permalink($list->ID)); ?>" class="ygv-btn-small" title="<?php echo esc_attr__('Pogledaj', 'hello-elementor-child'); ?>"><?php ygv_icon_e('eye', 16); ?></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="ygv-lists-summary">
                <p>
                    <?php printf(
                        esc_html__('Ukupno %d lista', 'hello-elementor-child'),
                        count($user_lists)
                    ); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Voting Statistics -->
    <div class="ygv-card ygv-voting-stats-card">
        <h3><?php ygv_icon_e('chart-bar', 20); ?> <?php echo esc_html__('Statistika Glasanja', 'hello-elementor-child'); ?></h3>
        
        <?php 
        // Build chart data for SVG donut
        $chart_data = [];
        $chart_total = 0;
        if (!empty($votes_by_category)) {
            foreach ($votes_by_category as $cat) {
                $cat_term = get_term_by('name', $cat['category_name'], 'voting_list_category');
                $cat_color = $cat_term && function_exists('ygv_get_category_color_by_term_id') 
                    ? ygv_get_category_color_by_term_id($cat_term->term_id) 
                    : '#4f46e5';
                $chart_data[] = [
                    'name' => $cat['category_name'],
                    'count' => (int) $cat['vote_count'],
                    'color' => $cat_color
                ];
                $chart_total += (int) $cat['vote_count'];
            }
        }
        ?>
        
        <div class="ygv-stats-donut-layout">
            <!-- Donut Chart -->
            <?php if (!empty($chart_data)): ?>
            <div class="ygv-donut-container">
                <svg viewBox="0 0 100 100" class="ygv-donut-chart">
                    <?php
                    $cumulative = 0;
                    $radius = 35;
                    $circumference = 2 * M_PI * $radius;
                    foreach ($chart_data as $segment):
                        $percent = $chart_total > 0 ? ($segment['count'] / $chart_total) * 100 : 0;
                        $dash = ($percent / 100) * $circumference;
                        $gap = $circumference - $dash;
                        $offset = -($cumulative / 100) * $circumference + ($circumference / 4); // Start from top
                    ?>
                    <circle 
                        cx="50" cy="50" r="<?php echo $radius; ?>"
                        fill="none"
                        stroke="<?php echo esc_attr($segment['color']); ?>"
                        stroke-width="12"
                        stroke-dasharray="<?php echo $dash; ?> <?php echo $gap; ?>"
                        stroke-dashoffset="<?php echo $offset; ?>"
                        class="ygv-donut-segment"
                    />
                    <?php 
                        $cumulative += $percent;
                    endforeach; 
                    ?>
                </svg>
                <div class="ygv-donut-center">
                    <span class="ygv-donut-number"><?php echo number_format($chart_total); ?></span>
                    <span class="ygv-donut-label"><?php echo esc_html__('Glasova', 'hello-elementor-child'); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Stats & Legend -->
            <div class="ygv-stats-legend">
                <!-- Mini Stats Row -->
                <div class="ygv-mini-stats-row">
                    <div class="ygv-mini-stat">
                        <span class="ygv-mini-stat-value"><?php echo number_format($total_lists_voted); ?></span>
                        <span class="ygv-mini-stat-label"><?php echo esc_html__('Lista', 'hello-elementor-child'); ?></span>
                    </div>
                    <div class="ygv-mini-stat">
                        <span class="ygv-mini-stat-value"><?php echo number_format($unique_items_voted); ?></span>
                        <span class="ygv-mini-stat-label"><?php echo esc_html__('Stavki', 'hello-elementor-child'); ?></span>
                    </div>
                    <?php if ($total_points > 0): ?>
                    <div class="ygv-mini-stat">
                        <span class="ygv-mini-stat-value"><?php echo number_format($total_points); ?></span>
                        <span class="ygv-mini-stat-label"><?php echo esc_html__('Poena', 'hello-elementor-child'); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($voting_streak >= 2): ?>
                    <div class="ygv-mini-stat ygv-mini-stat--streak">
                        <span class="ygv-mini-stat-value">🔥 <?php echo $voting_streak; ?></span>
                        <span class="ygv-mini-stat-label"><?php echo esc_html__('Dana', 'hello-elementor-child'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($chart_data)): ?>
                <!-- Favorite Category Highlight -->
                <?php 
                $fav_cat = $chart_data[0]; // First is highest
                $fav_cat_term = get_term_by('name', $fav_cat['name'], 'voting_list_category');
                $fav_mascot = $fav_cat_term ? get_term_meta($fav_cat_term->term_id, 'category_mascot', true) : '';
                ?>
                <div class="ygv-fav-category" style="--cat-color: <?php echo esc_attr($fav_cat['color']); ?>;">
                    <?php if ($fav_mascot): ?>
                    <img src="<?php echo esc_url($fav_mascot); ?>" alt="" class="ygv-fav-mascot">
                    <?php endif; ?>
                    <div class="ygv-fav-info">
                        <span class="ygv-fav-label">⭐ <?php echo esc_html__('Omiljena kategorija', 'hello-elementor-child'); ?></span>
                        <span class="ygv-fav-name"><?php echo esc_html($fav_cat['name']); ?></span>
                    </div>
                </div>
                
                <!-- Category Legend -->
                <div class="ygv-donut-legend">
                    <?php foreach ($chart_data as $item): 
                        $percent = $chart_total > 0 ? round(($item['count'] / $chart_total) * 100) : 0;
                    ?>
                    <div class="ygv-legend-item">
                        <span class="ygv-legend-dot" style="background: <?php echo esc_attr($item['color']); ?>"></span>
                        <span class="ygv-legend-name"><?php echo esc_html($item['name']); ?></span>
                        <span class="ygv-legend-value"><?php echo number_format($item['count']); ?> <small>(<?php echo $percent; ?>%)</small></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Motivational Message -->
        <?php if ($total_votes > 0): ?>
        <div class="ygv-stats-motivation">
            <?php
            // Generate fun message based on total points (votes are 1-10 each)
            if ($total_points >= 5000) {
                $message = __('🏆 Legendarni glasač! Tvoji glasovi oblikuju liste.', 'hello-elementor-child');
            } elseif ($total_points >= 2000) {
                $message = __('💪 Pravi ekspert! Još malo do 5000 poena.', 'hello-elementor-child');
            } elseif ($total_points >= 500) {
                $message = __('🌟 Aktivni glasač! Nastavi da rangiraš favorite.', 'hello-elementor-child');
            } elseif ($total_points >= 100) {
                $message = __('🚀 Dobar početak! Istraži više lista i glasaj.', 'hello-elementor-child');
            } else {
                $message = __('👋 Dobrodošao! Glasaj na listama i skupljaj poene.', 'hello-elementor-child');
            }
            ?>
            <span><?php echo esc_html($message); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Voting History -->
    <div class="ygv-card">
        <h3><?php ygv_icon_e('history', 20); ?> <?php echo esc_html__('Istorija Glasanja', 'hello-elementor-child'); ?></h3>
        <p class="ygv-card-subtitle"><?php echo esc_html__('Liste na kojima si glasao/la', 'hello-elementor-child'); ?></p>
        
        <?php if (empty($voted_lists)): ?>
            <div class="ygv-empty-state">
                <span class="ygv-empty-icon"><?php ygv_icon_e('vote', 48); ?></span>
                <h4><?php echo esc_html__('Još nisi glasao/la', 'hello-elementor-child'); ?></h4>
                <p><?php echo esc_html__('Kada glasaš na listama, tvoja istorija će se prikazati ovde.', 'hello-elementor-child'); ?></p>
                <a href="<?php echo esc_url(home_url('/liste/')); ?>" class="ygv-btn ygv-btn-primary">
                    <?php echo esc_html__('Istraži Liste', 'hello-elementor-child'); ?>
                </a>
            </div>
        <?php else: ?>
            <?php
            // ✅ PERFORMANCE: Prefetch terms for voted lists
            $voted_list_posts = array_column($voted_lists, 'post');
            $voted_list_ids = wp_list_pluck($voted_list_posts, 'ID');
            if (!empty($voted_list_ids)) {
                update_object_term_cache($voted_list_ids, 'voting_list');
            }
            ?>
            <div class="ygv-voting-history-list" id="voting-history-container">
                <?php 
                $items_per_page = 10;
                $total_items = count($voted_lists);
                $show_more = $total_items > $items_per_page;
                $display_items = array_slice($voted_lists, 0, $items_per_page);
                
                foreach ($display_items as $item): 
                    $list = $item['post'];
                    $user_votes = $item['vote_count'];
                    $last_vote = $item['last_vote'];
                    
                    $categories = wp_get_object_terms($list->ID, 'voting_list_category', ['fields' => 'names']);
                    $category_name = !empty($categories) ? $categories[0] : '';
                    
                    // Get thumbnail
                    $thumbnail = get_the_post_thumbnail_url($list->ID, 'thumbnail');
                    if (!$thumbnail) {
                        $thumbnail = get_stylesheet_directory_uri() . '/assets/images/list-placeholder.jpg';
                    }
                    
                    // Calculate time ago
                    $time_ago = human_time_diff(strtotime($last_vote), current_time('timestamp'));
                ?>
                <div class="ygv-history-item">
                    <div class="ygv-history-thumb" style="background-image: url('<?php echo esc_url($thumbnail); ?>')"></div>
                    <div class="ygv-history-content">
                        <h4 class="ygv-history-title">
                            <a href="<?php echo esc_url(get_permalink($list->ID)); ?>"><?php echo esc_html($list->post_title); ?></a>
                        </h4>
                        <?php if ($category_name): ?>
                            <span class="ygv-history-category"><?php echo esc_html($category_name); ?></span>
                        <?php endif; ?>
                        <div class="ygv-history-meta">
                            <span class="ygv-history-votes" title="<?php echo esc_attr__('Tvoji glasovi na ovoj listi', 'hello-elementor-child'); ?>">
                                <?php ygv_icon_e('check', 14); ?> <?php echo $user_votes; ?> <?php echo $user_votes === 1 ? esc_html__('glas', 'hello-elementor-child') : esc_html__('glasova', 'hello-elementor-child'); ?>
                            </span>
                            <span class="ygv-history-date"><?php echo esc_html($time_ago); ?> <?php echo esc_html__('pre', 'hello-elementor-child'); ?></span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url(get_permalink($list->ID)); ?>" class="ygv-history-action" title="<?php echo esc_attr__('Pogledaj listu', 'hello-elementor-child'); ?>">
                        <?php ygv_icon_e('arrow-right', 18); ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($show_more): ?>
            <button type="button" class="ygv-btn ygv-btn-secondary ygv-btn-block ygv-load-more-btn" 
                    data-loaded="<?php echo $items_per_page; ?>" 
                    data-total="<?php echo $total_items; ?>"
                    onclick="ygvLoadMoreHistory(this)">
                <?php printf(esc_html__('Prikaži još (%d preostalo)', 'hello-elementor-child'), $total_items - $items_per_page); ?>
            </button>
            <script>
            function ygvLoadMoreHistory(btn) {
                const container = document.getElementById('voting-history-container');
                const hiddenItems = container.querySelectorAll('.ygv-history-item.ygv-hidden');
                let shown = 0;
                hiddenItems.forEach(item => {
                    if (shown < 10) {
                        item.classList.remove('ygv-hidden');
                        shown++;
                    }
                });
                const remaining = container.querySelectorAll('.ygv-history-item.ygv-hidden').length;
                if (remaining === 0) {
                    btn.style.display = 'none';
                } else {
                    btn.textContent = 'Prikaži još (' + remaining + ' preostalo)';
                }
            }
            </script>
            <?php endif; ?>
            
            <!-- Hidden items for lazy load -->
            <?php 
            $hidden_items = array_slice($voted_lists, $items_per_page);
            foreach ($hidden_items as $item): 
                $list = $item['post'];
                $user_votes = $item['vote_count'];
                $last_vote = $item['last_vote'];
                $categories = wp_get_object_terms($list->ID, 'voting_list_category', ['fields' => 'names']);
                $category_name = !empty($categories) ? $categories[0] : '';
                $thumbnail = get_the_post_thumbnail_url($list->ID, 'thumbnail');
                if (!$thumbnail) $thumbnail = get_stylesheet_directory_uri() . '/assets/images/list-placeholder.jpg';
                $time_ago = human_time_diff(strtotime($last_vote), current_time('timestamp'));
            ?>
            <div class="ygv-history-item ygv-hidden">
                <div class="ygv-history-thumb" style="background-image: url('<?php echo esc_url($thumbnail); ?>')"></div>
                <div class="ygv-history-content">
                    <h4 class="ygv-history-title">
                        <a href="<?php echo esc_url(get_permalink($list->ID)); ?>"><?php echo esc_html($list->post_title); ?></a>
                    </h4>
                    <?php if ($category_name): ?>
                        <span class="ygv-history-category"><?php echo esc_html($category_name); ?></span>
                    <?php endif; ?>
                    <div class="ygv-history-meta">
                        <span class="ygv-history-votes">
                            <?php ygv_icon_e('check', 14); ?> <?php echo $user_votes; ?> <?php echo $user_votes === 1 ? 'glas' : 'glasova'; ?>
                        </span>
                        <span class="ygv-history-date"><?php echo esc_html($time_ago); ?> pre</span>
                    </div>
                </div>
                <a href="<?php echo esc_url(get_permalink($list->ID)); ?>" class="ygv-history-action">
                    <?php ygv_icon_e('arrow-right', 18); ?>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Categories where user can create lists -->
    <?php if ($can_create['can_create'] && !empty($parent_categories)): ?>
    <div class="ygv-card">
        <h3><?php echo esc_html__('Kategorije za Kreiranje', 'hello-elementor-child'); ?></h3>
        <p class="ygv-card-subtitle"><?php echo esc_html__('Otključaj nivo 10 u kategoriji da kreiraš liste', 'hello-elementor-child'); ?></p>
        
        <div class="ygv-category-mascot-grid">
            <?php 
            global $wpdb;
            $t_cat = $wpdb->prefix . 'ygv_user_category_progress';
            $level_config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : null;
            $required_level = $level_config['list_creation_category_level'] ?? 10;
            
            // ✅ PERFORMANCE: Batch fetch all category levels in one query
            $category_ids = wp_list_pluck($parent_categories, 'term_id');
            $category_ids = array_map('intval', $category_ids);
            $category_ids_placeholders = implode(',', array_fill(0, count($category_ids), '%d'));
            $category_levels_query = $wpdb->prepare(
                "SELECT category_term_id, level FROM {$t_cat} 
                 WHERE user_id = %d AND category_term_id IN ($category_ids_placeholders)",
                $user_id,
                ...$category_ids
            );
            $category_levels_results = $wpdb->get_results($category_levels_query, OBJECT_K);
            
            foreach ($parent_categories as $category): 
                $user_cat_level = isset($category_levels_results[$category->term_id]) 
                    ? (int) $category_levels_results[$category->term_id]->level 
                    : 1;
                
                $can_create_in_cat = $user_cat_level >= $required_level;
                
                // Get category color and mascot
                $cat_color = function_exists('ygv_get_category_color_by_term_id') 
                    ? ygv_get_category_color_by_term_id($category->term_id) 
                    : '#4f46e5';
                $mascot_url = get_term_meta($category->term_id, 'category_mascot', true);
                if (!$mascot_url) {
                    $mascot_url = get_stylesheet_directory_uri() . '/assets/images/mascot-default.png';
                }
                
                $progress = min(100, ($user_cat_level / $required_level) * 100);
            ?>
            <div class="ygv-cat-mascot-card <?php echo $can_create_in_cat ? 'ygv-unlocked' : 'ygv-locked'; ?>" style="--cat-color: <?php echo esc_attr($cat_color); ?>;">
                <div class="ygv-cat-mascot-img">
                    <img src="<?php echo esc_url($mascot_url); ?>" alt="<?php echo esc_attr($category->name); ?>" loading="lazy">
                    <?php if ($can_create_in_cat): ?>
                    <span class="ygv-cat-mascot-badge"><?php ygv_icon_e('check', 14); ?></span>
                    <?php endif; ?>
                </div>
                <div class="ygv-cat-mascot-info">
                    <span class="ygv-cat-mascot-name"><?php echo esc_html($category->name); ?></span>
                    <?php if ($can_create_in_cat): ?>
                        <span class="ygv-cat-mascot-status ygv-status-unlocked">
                            <?php echo esc_html__('Otključano', 'hello-elementor-child'); ?>
                        </span>
                    <?php else: ?>
                        <div class="ygv-cat-mascot-progress">
                            <div class="ygv-progress-mini">
                                <div class="ygv-progress-mini-fill" style="width: <?php echo $progress; ?>%;"></div>
                            </div>
                            <span class="ygv-cat-mascot-lvl">Lvl <?php echo $user_cat_level; ?>/<?php echo $required_level; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
</div>
