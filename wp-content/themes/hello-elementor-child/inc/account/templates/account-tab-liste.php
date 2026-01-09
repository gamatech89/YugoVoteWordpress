<?php if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();

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
                <span class="ygv-info-icon">🔒</span>
                <div class="ygv-info-content">
                    <strong><?php echo esc_html__('Kreiranje lista je zaključano', 'hello-elementor-child'); ?></strong>
                    <p><?php echo esc_html($can_create['reason']); ?></p>
                    <p class="ygv-info-hint"><?php echo esc_html__('Rešavaj kvizove i glasaj da zaradiš XP i otključaš ovu funkciju!', 'hello-elementor-child'); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (empty($user_lists)): ?>
            <div class="ygv-empty-state">
                <span class="ygv-empty-icon">📋</span>
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
                            <span class="ygv-list-votes">🗳️ <?php echo number_format($vote_count); ?> <?php echo esc_html__('glasova', 'hello-elementor-child'); ?></span>
                            <span class="ygv-list-date"><?php echo esc_html(human_time_diff(strtotime($list->post_date), current_time('timestamp'))); ?> <?php echo esc_html__('pre', 'hello-elementor-child'); ?></span>
                        </div>
                    </div>
                    <div class="ygv-list-actions">
                        <a href="<?php echo esc_url(get_edit_post_link($list->ID)); ?>" class="ygv-btn-small" title="<?php echo esc_attr__('Uredi', 'hello-elementor-child'); ?>">✏️</a>
                        <a href="<?php echo esc_url(get_permalink($list->ID)); ?>" class="ygv-btn-small" title="<?php echo esc_attr__('Pogledaj', 'hello-elementor-child'); ?>">👁️</a>
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
                        ✅ <?php echo esc_html__('Otključano', 'hello-elementor-child'); ?>
                    <?php else: ?>
                        🔒 <?php printf(esc_html__('Nivo %d/%d', 'hello-elementor-child'), $user_cat_level, $required_level); ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
</div>
