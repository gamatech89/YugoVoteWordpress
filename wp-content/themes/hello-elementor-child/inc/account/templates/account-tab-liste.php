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
    "SELECT t.name as category_name, COUNT(v.id) as vote_count
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
?>

<div class="ygv-my-lists">
    
    <div class="ygv-card">
        <div class="ygv-card-header">
            <h3><?php echo esc_html__('Moje Liste', 'hello-elementor-child'); ?></h3>
            <?php if ($can_create['can_create']): ?>
                <a href="<?php echo esc_url(ygv_account_page_url(['tab' => 'kreiraj-listu'])); ?>" class="ygv-btn ygv-btn-primary">
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
                    
                    // Get vote count
                    global $wpdb;
                    $vote_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}voting_list_votes WHERE voting_list_id = %d",
                        $list->ID
                    ));
                    
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
        
        <div class="ygv-voting-stats-grid">
            <div class="ygv-stat-box">
                <span class="ygv-stat-number"><?php echo number_format($total_votes); ?></span>
                <span class="ygv-stat-label"><?php echo esc_html__('Ukupno Glasova', 'hello-elementor-child'); ?></span>
            </div>
            <div class="ygv-stat-box">
                <span class="ygv-stat-number"><?php echo number_format($total_lists_voted); ?></span>
                <span class="ygv-stat-label"><?php echo esc_html__('Lista Glasano', 'hello-elementor-child'); ?></span>
            </div>
        </div>
        
        <?php if (!empty($votes_by_category)): ?>
        <div class="ygv-votes-by-category">
            <h4><?php echo esc_html__('Glasovi po Kategoriji', 'hello-elementor-child'); ?></h4>
            <div class="ygv-category-vote-bars">
                <?php 
                $max_votes = max(array_column($votes_by_category, 'vote_count'));
                foreach ($votes_by_category as $cat): 
                    $percent = $max_votes > 0 ? ($cat['vote_count'] / $max_votes) * 100 : 0;
                    
                    // Get category color by name (find term first)
                    $cat_term = get_term_by('name', $cat['category_name'], 'voting_list_category');
                    $cat_color = $cat_term && function_exists('ygv_get_category_color_by_term_id') 
                        ? ygv_get_category_color_by_term_id($cat_term->term_id) 
                        : '#4f46e5';
                ?>
                <div class="ygv-category-vote-item" style="--cat-color: <?php echo esc_attr($cat_color); ?>;">
                    <div class="ygv-cat-vote-header">
                        <span class="ygv-cat-vote-name"><?php echo esc_html($cat['category_name']); ?></span>
                        <span class="ygv-cat-vote-count" style="color: var(--cat-color);"><?php echo number_format($cat['vote_count']); ?></span>
                    </div>
                    <div class="ygv-progress-unified ygv-progress-unified--sm">
                        <div class="ygv-progress-unified__fill" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
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
            <div class="ygv-voting-history-list">
                <?php foreach ($voted_lists as $item): 
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
            
            <?php if (count($voted_lists) >= 50): ?>
            <p class="ygv-show-more-hint"><?php echo esc_html__('Prikazano poslednjih 50 lista', 'hello-elementor-child'); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Categories where user can create lists -->
    <?php if ($can_create['can_create'] && !empty($parent_categories)): ?>
    <div class="ygv-card">
        <h3><?php echo esc_html__('Kategorije za Kreiranje', 'hello-elementor-child'); ?></h3>
        <p class="ygv-card-subtitle"><?php echo esc_html__('Kategorije u kojima možeš kreirati liste (potreban nivo 10)', 'hello-elementor-child'); ?></p>
        
        <div class="ygv-category-create-list">
            <?php 
            global $wpdb;
            $t_cat = $wpdb->prefix . 'ygv_user_category_progress';
            $level_config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : null;
            $required_level = $level_config['list_creation_category_level'] ?? 10;
            
            foreach ($parent_categories as $category): 
                // Get user's level in this category
                $user_cat_level = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT level FROM {$t_cat} WHERE user_id = %d AND category_term_id = %d",
                    $user_id,
                    $category->term_id
                )) ?: 1;
                
                $can_create_in_cat = $user_cat_level >= $required_level;
            ?>
            <div class="ygv-cat-create-item <?php echo $can_create_in_cat ? 'ygv-unlocked' : 'ygv-locked'; ?>">
                <span class="ygv-cat-create-name"><?php echo esc_html($category->name); ?></span>
                <span class="ygv-cat-create-level">
                    <?php if ($can_create_in_cat): ?>
                        <?php ygv_icon_e('check-circle', 16); ?> <?php echo esc_html__('Otključano', 'hello-elementor-child'); ?>
                    <?php else: ?>
                        <?php ygv_icon_e('lock', 16); ?> <?php printf(esc_html__('Nivo %d/%d', 'hello-elementor-child'), $user_cat_level, $required_level); ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
</div>
