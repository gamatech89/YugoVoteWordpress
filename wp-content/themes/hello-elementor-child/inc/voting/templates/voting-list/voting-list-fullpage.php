<?php
/**
 * Voting List Full Page Template
 * 
 * Complete page with:
 * - Hero section (title, description, stats, category)
 * - Voting list items with always-visible voting
 * - Floating "My Votes" sidebar panel
 * 
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

// Get the voting list ID
$voting_list_id = get_query_var('voting_list_id');
if (!$voting_list_id) {
    echo "<p>No voting list ID found.</p>";
    return;
}

$list_post = get_post($voting_list_id);
if (!$list_post || $list_post->post_type !== 'voting_list') {
    echo "<p>Invalid voting list.</p>";
    return;
}

// ========================================
// GATHER LIST DATA
// ========================================

$list_title = get_the_title($voting_list_id);
$list_content = apply_filters('the_content', $list_post->post_content);
$list_excerpt = get_the_excerpt($voting_list_id) ?: wp_trim_words(strip_tags($list_post->post_content), 30);
$list_thumbnail = get_the_post_thumbnail_url($voting_list_id, 'large');
$voting_scale = get_post_meta($voting_list_id, '_voting_scale', true) ?: 10;
$is_featured = get_post_meta($voting_list_id, '_is_featured', true);

// Get total score and vote count
global $wpdb;
$votes_table = $wpdb->prefix . 'voting_list_votes';
$total_score = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(vote_value) FROM $votes_table WHERE voting_list_id = %d", $voting_list_id
)) ?: 0;
$total_votes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT CONCAT(voting_item_id, '-', COALESCE(user_id, ip_address))) FROM $votes_table WHERE voting_list_id = %d", $voting_list_id
)) ?: 0;
$unique_voters = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT COALESCE(user_id, ip_address)) FROM $votes_table WHERE voting_list_id = %d", $voting_list_id
)) ?: 0;

// Get category info
$list_categories = get_the_terms($voting_list_id, 'voting_list_category');
$primary_category = null;
$category_color = '#2D3A8C';
$category_name = '';
$category_link = '';

if (!empty($list_categories) && !is_wp_error($list_categories)) {
    // Find the deepest (most specific) category
    $primary_category = $list_categories[0];
    foreach ($list_categories as $cat) {
        if ($cat->parent !== 0) {
            $primary_category = $cat;
            break;
        }
    }
    
    $category_name = $primary_category->name;
    $category_link = get_term_link($primary_category);
    
    // Get category color
    if (function_exists('ygv_get_category_color')) {
        $category_color = ygv_get_category_color($primary_category->slug);
    }
    
    // Get parent category for breadcrumb
    if ($primary_category->parent !== 0) {
        $parent_cat = get_term($primary_category->parent, 'voting_list_category');
    }
}

// Get voting items
$voting_items_ids = get_post_meta($voting_list_id, '_voting_items', true);
if (!is_array($voting_items_ids)) $voting_items_ids = [];

$items_count = count($voting_items_ids);

// Current user's votes on this list
$current_user_id = get_current_user_id();
$user_ip = $_SERVER['REMOTE_ADDR'];

$user_votes = [];
if ($current_user_id) {
    $user_votes_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT voting_item_id, vote_value FROM $votes_table WHERE voting_list_id = %d AND user_id = %d",
        $voting_list_id, $current_user_id
    ), ARRAY_A);
} else {
    $user_votes_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT voting_item_id, vote_value FROM $votes_table WHERE voting_list_id = %d AND ip_address = %s AND user_id IS NULL",
        $voting_list_id, $user_ip
    ), ARRAY_A);
}

foreach ($user_votes_raw as $vote) {
    $user_votes[$vote['voting_item_id']] = $vote['vote_value'];
}

$user_votes_count = count($user_votes);
$user_completion_percent = $items_count > 0 ? round(($user_votes_count / $items_count) * 100) : 0;

// ========================================
// USER VOTE BONUS (for logged-in users)
// ========================================
$user_vote_bonus = 0;
$user_expert_title = '';
$user_category_level = 1;
$parent_category_id = 0;

if ($current_user_id && function_exists('ygv_get_list_parent_category') && function_exists('ygv_get_user_vote_bonus')) {
    $parent_category_id = ygv_get_list_parent_category($voting_list_id);
    if ($parent_category_id) {
        $bonus_info = ygv_get_user_vote_bonus($current_user_id, $parent_category_id);
        $user_vote_bonus = (int) $bonus_info['bonus'];
        $user_expert_title = $bonus_info['title'];
        $user_category_level = (int) $bonus_info['level'];
    }
}

// ========================================
// VIP LIST DATA
// ========================================
$is_vip_list = function_exists('ygv_is_vip_list') ? ygv_is_vip_list($voting_list_id) : false;
$vip_person = null;
$vip_ranks = [];

if ($is_vip_list) {
    $vip_person = function_exists('ygv_get_vip_person') ? ygv_get_vip_person($voting_list_id) : null;
    $vip_ranks = function_exists('ygv_get_vip_ranks') ? ygv_get_vip_ranks($voting_list_id) : [];
}

// ========================================
// PREPARE ITEMS DATA
// ========================================

$items = [];
$relations_table = $wpdb->prefix . 'voting_list_item_relations';

if (!empty($voting_items_ids)) {
    $items_query = new WP_Query([
        'post_type'      => 'voting_items',
        'post_status'    => 'publish',
        'post__in'       => $voting_items_ids,
        'posts_per_page' => -1,
        'orderby'        => 'post__in'
    ]);
    
    $ranking = 0;
    while ($items_query->have_posts()) {
        $items_query->the_post();
        $ranking++;
        $item_id = get_the_ID();
        
        // Get item score
        $item_score = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(vote_value) FROM $votes_table WHERE voting_list_id = %d AND voting_item_id = %d",
            $voting_list_id, $item_id
        )) ?: 0;
        
        // Get pivot data (custom desc/image for this list)
        $pivot_data = $wpdb->get_row($wpdb->prepare(
            "SELECT short_description, custom_image_url, url FROM $relations_table WHERE voting_list_id = %d AND voting_item_id = %d",
            $voting_list_id, $item_id
        ), ARRAY_A);
        
        // Get how many other published lists this item appears in
        $other_lists_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT pivot.voting_list_id)
             FROM $relations_table pivot
             INNER JOIN {$wpdb->posts} wpp ON wpp.ID = pivot.voting_list_id AND wpp.post_status = 'publish'
             WHERE pivot.voting_item_id = %d AND pivot.voting_list_id != %d",
            $item_id, $voting_list_id
        )) ?: 0;
        
        $default_image = get_the_post_thumbnail_url($item_id, 'medium');
        $default_desc = get_post_meta($item_id, '_short_description', true);
        
        $items[] = [
            'id'          => $item_id,
            'title'       => get_the_title(),
            'permalink'   => get_permalink(),
            'image'       => (!empty($pivot_data['custom_image_url']) ? $pivot_data['custom_image_url'] : $default_image) ?: get_stylesheet_directory_uri() . '/assets/images/placeholder.jpg',
            'short_desc'  => !empty($pivot_data['short_description']) ? $pivot_data['short_description'] : $default_desc,
            'video_url'   => !empty($pivot_data['url']) ? $pivot_data['url'] : get_post_meta($item_id, '_item_url', true),
            'score'       => floatval($item_score),
            'ranking'     => $ranking,
            'user_vote'   => $user_votes[$item_id] ?? null,
            'vip_rank'    => $vip_ranks[$item_id] ?? null,
            'other_lists_count' => (int) $other_lists_count,
        ];
    }
    wp_reset_postdata();
    
    // Sort by score for ranking
    usort($items, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    // Update rankings after sort
    foreach ($items as $index => &$item) {
        $item['ranking'] = $index + 1;
    }
    unset($item);
}
?>

<style>
:root {
    --vlp-primary: <?php echo esc_attr($category_color); ?>;
    --vlp-primary-light: <?php echo esc_attr($category_color); ?>15;
    --vlp-gold: #FFD700;
    --vlp-silver: #C0C0C0;
    --vlp-bronze: #CD7F32;
}

/* Jockey font for titles */
.vlp-hero__title,
.vlp-item__title,
.vlp-list-header__title {
    font-family: var(--heading-font, 'Jockey One', sans-serif) !important;
}

/* Video popup */
.vlp-video-popup {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 10000;
    align-items: center;
    justify-content: center;
}
.vlp-video-popup.vlp-video-popup--open {
    display: flex;
}
.vlp-video-popup__overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    cursor: pointer;
}
.vlp-video-popup__content {
    position: relative;
    width: 90%;
    max-width: 900px;
    aspect-ratio: 16 / 9;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}
.vlp-video-popup__iframe {
    width: 100%;
    height: 100%;
    border: none;
}
.vlp-video-popup__close {
    position: absolute;
    top: -40px;
    right: 0;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    transition: background 0.2s;
    padding: 0 !important;
}
.vlp-video-popup__close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Disabled vote button (value already used) */
.vlp-vote-btn--disabled {
    opacity: 0.3;
    cursor: not-allowed;
    pointer-events: none;
    background: #e5e7eb;
    border-color: #d1d5db;
    color: #9ca3af;
}

/* Item reorder animation */
.vlp-item {
    transition: box-shadow 0.3s ease;
}
.vlp-item--moved {
    box-shadow: 0 0 0 3px var(--vlp-primary), 0 8px 30px rgba(0,0,0,0.15) !important;
    z-index: 10;
}

/* Panel slide out animation */
@keyframes vlpSlideOut {
    from { opacity: 1; transform: translateX(0); }
    to { opacity: 0; transform: translateX(20px); }
}

/* Panel remove button - ensure clickable and visible */
.vlp-panel__item-remove {
    position: relative;
    z-index: 10;
    cursor: pointer !important;
    pointer-events: auto !important;
    min-width: 36px;
    min-height: 36px;
    padding: 0 !important;
}
.vlp-panel__item-remove svg {
    pointer-events: none;
    stroke-width: 2.5;
}

/* Panel close button - larger SVG */
.vlp-panel__close {
    padding: 0 !important;
}
.vlp-panel__close svg {
    width: 22px;
    height: 22px;
    stroke-width: 2.5;
}

/* ========================================
   TOAST NOTIFICATIONS
   ======================================== */
.vlp-toast {
    position: fixed;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 14px 24px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.4);
    z-index: 10001;
    opacity: 0;
    transition: all 0.3s ease;
}
.vlp-toast--visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
.vlp-toast strong {
    color: #fef08a;
}

/* ========================================
   LEVEL UP POPUP
   ======================================== */
.vlp-levelup-popup {
    position: fixed;
    inset: 0;
    z-index: 10002;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}
.vlp-levelup-popup--visible {
    opacity: 1;
    visibility: visible;
}
.vlp-levelup-popup__overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.7);
}
.vlp-levelup-popup__content {
    position: relative;
    background: white;
    padding: 40px;
    border-radius: 24px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    transform: scale(0.8);
    transition: transform 0.3s ease;
}
.vlp-levelup-popup--visible .vlp-levelup-popup__content {
    transform: scale(1);
}
.vlp-levelup__confetti {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 40px;
}
.vlp-levelup__confetti::before {
    content: '🎉';
}
.vlp-levelup__mascot {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: contain;
    margin: 0 auto 15px;
    background: #f8f9fa;
    border: 3px solid var(--vlp-primary);
}
.vlp-levelup__mascot-placeholder {
    font-size: 60px;
    margin-bottom: 15px;
}
.vlp-levelup__title {
    font-size: 28px;
    font-weight: 800;
    color: var(--vlp-primary);
    margin: 0 0 8px;
}
.vlp-levelup__category {
    font-size: 16px;
    color: #666;
    margin: 0 0 15px;
}
.vlp-levelup__level {
    font-size: 48px;
    font-weight: 800;
    color: #fbbf24;
    text-shadow: 2px 2px 0 #f59e0b;
}
.vlp-levelup__rank {
    font-size: 18px;
    color: #10b981;
    font-weight: 600;
    margin: 10px 0;
}
.vlp-levelup__bonus {
    font-size: 14px;
    color: #8b5cf6;
    background: #ede9fe;
    padding: 8px 16px;
    border-radius: 20px;
    margin: 15px 0 0;
}
.vlp-levelup__close {
    margin-top: 20px;
    padding: 12px 32px;
    background: var(--vlp-primary);
    color: white;
    border: none;
    border-radius: 25px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s;
}
.vlp-levelup__close:hover {
    transform: scale(1.05);
}

/* ========================================
   ACHIEVEMENT POPUP
   ======================================== */
.vlp-achievement-popup {
    position: fixed;
    inset: 0;
    z-index: 10002;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}
.vlp-achievement-popup--visible {
    opacity: 1;
    visibility: visible;
}
.vlp-achievement-popup__overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.7);
}
.vlp-achievement-popup__content {
    position: relative;
    background: linear-gradient(135deg, #1e1b4b, #312e81);
    padding: 40px;
    border-radius: 24px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    transform: scale(0.8);
    transition: transform 0.3s ease;
}
.vlp-achievement-popup--visible .vlp-achievement-popup__content {
    transform: scale(1);
}
.vlp-achievement__icon {
    font-size: 70px;
    margin-bottom: 15px;
}
.vlp-achievement__title {
    font-size: 20px;
    color: #a5b4fc;
    margin: 0 0 10px;
    text-transform: uppercase;
    letter-spacing: 2px;
}
.vlp-achievement__name {
    font-size: 26px;
    font-weight: 800;
    color: #fbbf24;
    margin: 0 0 10px;
}
.vlp-achievement__desc {
    font-size: 14px;
    color: #c7d2fe;
    margin: 0;
}
.vlp-achievement__close {
    margin-top: 25px;
    padding: 12px 32px;
    background: #fbbf24;
    color: #1e1b4b;
    border: none;
    border-radius: 25px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s;
}
.vlp-achievement__close:hover {
    transform: scale(1.05);
}

/* Expert Vote Bonus Badge */
.vlp-expert-bonus {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 16px;
    padding: 12px 16px;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.15), rgba(255, 215, 0, 0.05));
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 12px;
    font-size: 14px;
}
.vlp-expert-bonus__icon {
    font-size: 24px;
}
.vlp-expert-bonus__text {
    color: #1f2937;
    line-height: 1.4;
}
.vlp-expert-bonus__text strong {
    color: #b8860b;
}
.vlp-expert-bonus--locked {
    background: linear-gradient(135deg, rgba(107, 114, 128, 0.1), rgba(107, 114, 128, 0.05));
    border-color: rgba(107, 114, 128, 0.2);
}
.vlp-expert-bonus--locked .vlp-expert-bonus__text strong {
    color: var(--vlp-primary);
}
.vlp-expert-bonus__level {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #6b7280;
}

/* Expert bonus in list header */
.vlp-list-header .vlp-expert-bonus {
    margin-top: 16px;
    justify-content: center;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.12), rgba(255, 215, 0, 0.04));
}
.vlp-list-header .vlp-expert-bonus--locked {
    background: linear-gradient(135deg, rgba(107, 114, 128, 0.08), rgba(107, 114, 128, 0.03));
}

/* Toast XP & Bonus styling */
.vlp-toast__xp {
    display: inline-block;
    margin-left: 8px;
    padding: 3px 10px;
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
}
.vlp-toast__bonus {
    color: #fff !important;
    font-weight: 600;
}
.vlp-toast__limit {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.7);
    margin-left: 6px;
}

/* ========================================
   VIP LIST STYLES
   ======================================== */
.vlp-vip-badge {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 193, 7, 0.1));
    border: 1px solid rgba(255, 215, 0, 0.4);
    border-radius: 50px;
    padding: 8px 20px 8px 8px;
    margin-top: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
}
.vlp-vip-badge:hover {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.3), rgba(255, 193, 7, 0.15));
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
}
.vlp-vip-badge__photo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #FFD700;
}
.vlp-vip-badge__info {
    display: flex;
    flex-direction: column;
}
.vlp-vip-badge__label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 600;
}
.vlp-vip-badge__name {
    font-size: 15px;
    font-weight: 700;
    color: #FFD700;
}
.vlp-vip-badge__subtitle {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
}
/* VIP rank on item card */
.vlp-vip-rank {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: linear-gradient(135deg, #FFF8E1, #FFF3C4);
    border: 1px solid #FFD54F;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: #F57F17;
    margin-top: 6px;
}
.vlp-vip-rank__icon {
    font-size: 14px;
}
.vlp-vip-rank__value {
    font-weight: 800;
    color: #E65100;
}
</style>

<div class="vlp-page" data-list-id="<?php echo esc_attr($voting_list_id); ?>">
    
    <!-- ========================================
         HERO SECTION
         ======================================== -->
    <section class="vlp-hero" <?php if ($list_thumbnail): ?>style="--hero-bg: url('<?php echo esc_url($list_thumbnail); ?>')"<?php endif; ?>>
        <div class="vlp-hero__overlay"></div>
        <div class="vlp-hero__content">
            
            <!-- Breadcrumb -->
            <nav class="vlp-breadcrumb">
                <a href="<?php echo home_url(); ?>">Početna</a>
                <span class="vlp-breadcrumb__sep">›</span>
                <?php if (isset($parent_cat)): ?>
                    <a href="<?php echo esc_url(get_term_link($parent_cat)); ?>"><?php echo esc_html($parent_cat->name); ?></a>
                    <span class="vlp-breadcrumb__sep">›</span>
                <?php endif; ?>
                <?php if ($category_name): ?>
                    <a href="<?php echo esc_url($category_link); ?>"><?php echo esc_html($category_name); ?></a>
                    <span class="vlp-breadcrumb__sep">›</span>
                <?php endif; ?>
                <span class="vlp-breadcrumb__current"><?php echo esc_html($list_title); ?></span>
            </nav>
            
            <!-- Category Badge -->
            <?php if ($category_name): ?>
            <a href="<?php echo esc_url($category_link); ?>" class="vlp-category-badge">
                <?php echo esc_html($category_name); ?>
            </a>
            <?php endif; ?>
            
            <?php if (false && $is_vip_list && $vip_person): // Temporarily hidden ?>
            <!-- VIP Person Badge -->
            <a href="<?php echo esc_url($vip_person['permalink']); ?>" class="vlp-vip-badge">
                <?php if ($vip_person['photo_url']): ?>
                    <img src="<?php echo esc_url($vip_person['photo_url']); ?>" alt="<?php echo esc_attr($vip_person['name']); ?>" class="vlp-vip-badge__photo">
                <?php endif; ?>
                <span class="vlp-vip-badge__info">
                    <span class="vlp-vip-badge__label">⭐ VIP Lista</span>
                    <span class="vlp-vip-badge__name"><?php echo esc_html($vip_person['name']); ?></span>
                    <?php if ($vip_person['subtitle']): ?>
                        <span class="vlp-vip-badge__subtitle"><?php echo esc_html($vip_person['subtitle']); ?></span>
                    <?php endif; ?>
                </span>
            </a>
            <?php endif; ?>
            
            <h1 class="vlp-hero__title"><?php echo esc_html($list_title); ?></h1>
            
            <?php 
            // Get full description text
            $full_description = $list_post->post_content;
            
            if ($full_description): 
            ?>
            <div class="vlp-hero__desc-wrapper">
                <div class="vlp-hero__desc">
                    <?php echo wp_kses_post($full_description); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Stats Row -->
            <div class="vlp-stats">
                <div class="vlp-stat">
                    <span class="vlp-stat__value"><?php echo number_format($items_count); ?></span>
                    <span class="vlp-stat__label">Itema</span>
                </div>
                <div class="vlp-stat">
                    <span class="vlp-stat__value"><?php echo number_format($total_score); ?></span>
                    <span class="vlp-stat__label">Ukupno poena</span>
                </div>
                <!-- Voter count hidden until numbers are respectable
                <div class="vlp-stat">
                    <span class="vlp-stat__value"><?php echo number_format($unique_voters); ?></span>
                    <span class="vlp-stat__label">Glasača</span>
                </div>
                -->
            </div>
            
            <!-- User Progress -->
            <div class="vlp-user-progress">
                <div class="vlp-user-progress__bar">
                    <div class="vlp-user-progress__fill" style="width: <?php echo round(($user_votes_count / $voting_scale) * 100); ?>%"></div>
                </div>
                <span class="vlp-user-progress__text">
                    Glasali ste za <strong><?php echo $user_votes_count; ?></strong> od <strong><?php echo $voting_scale; ?></strong> itema
                    (<?php echo round(($user_votes_count / $voting_scale) * 100); ?>%)
                </span>
            </div>
            
        </div>
    </section>
    
    <!-- ========================================
         VOTING LIST SECTION
         ======================================== -->
    <section class="vlp-list-section">
        <div class="vlp-list-container">
            
            <!-- List Header -->
            <div class="vlp-list-header">
                <h2 class="vlp-list-header__title">Glasajte za svoje favorite</h2>
                <p class="vlp-list-header__subtitle">Ocenite svaku stavku od 1 do <?php echo $voting_scale; ?>. Vaši glasovi utiču na rang listu!</p>
                
                <?php if ($current_user_id && $user_vote_bonus > 0): ?>
                <!-- Expert Vote Bonus Badge -->
                <div class="vlp-expert-bonus">
                    <span class="vlp-expert-bonus__icon">⭐</span>
                    <span class="vlp-expert-bonus__text">
                        <strong><?php echo esc_html($user_expert_title); ?></strong> (Nivo <?php echo $user_category_level; ?>)
                        — Vaši glasovi vrede <strong>+<?php echo $user_vote_bonus; ?></strong> bonus poena!
                    </span>
                </div>
                <?php elseif ($current_user_id && $user_category_level < 10): ?>
                <!-- Level up reminder -->
                <div class="vlp-expert-bonus vlp-expert-bonus--locked">
                    <span class="vlp-expert-bonus__icon">🔒</span>
                    <span class="vlp-expert-bonus__text">
                        Dostignite <strong>Nivo 10</strong> u ovoj kategoriji da otključate bonus glasove!
                        <span class="vlp-expert-bonus__level">Trenutni nivo: <?php echo $user_category_level; ?></span>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Voting Items -->
            <div class="vlp-items" data-list-id="<?php echo esc_attr($voting_list_id); ?>">
                <?php foreach ($items as $item): 
                    $is_top3 = $item['ranking'] <= 3;
                    $rank_class = $is_top3 ? 'vlp-item--rank-' . $item['ranking'] : '';
                    $has_voted = $item['user_vote'] !== null;
                ?>
                <article class="vlp-item <?php echo $rank_class; ?> <?php echo $has_voted ? 'vlp-item--voted' : ''; ?>" 
                         data-item-id="<?php echo esc_attr($item['id']); ?>">
                    
                    <!-- Image with Rank Badge -->
                    <div class="vlp-item__media">
                        <img src="<?php echo esc_url($item['image']); ?>" 
                             alt="<?php echo esc_attr($item['title']); ?>" 
                             class="vlp-item__img" loading="lazy">
                        <?php if ($item['video_url']): ?>
                        <button class="vlp-item__play" data-video-url="<?php echo esc_url($item['video_url']); ?>" aria-label="Play video">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Watermark Rank -->
                    <div class="vlp-item__rank">
                        <span class="vlp-item__rank-num"><?php echo $item['ranking']; ?></span>
                    </div>
                    
                    <!-- Info -->
                    <div class="vlp-item__info">
                        <h3 class="vlp-item__title">
                            <a href="<?php echo esc_url($item['permalink']); ?>"><?php echo esc_html($item['title']); ?></a>
                        </h3>
                        <?php if ($item['short_desc']): ?>
                        <div class="vlp-item__desc-wrapper">
                            <p class="vlp-item__desc"><?php echo wp_kses_post($item['short_desc']); ?></p>
                            <?php if ($item['other_lists_count'] > 0): ?>
                            <div style="margin-top:8px;">
                                <a href="<?php echo esc_url($item['permalink']); ?>" class="vlp-item__other-lists" style="font-weight: 700; font-size: 14px; color: var(--vlp-primary); text-decoration: underline;">pojavljuje se u jos <?php echo $item['other_lists_count']; ?> lista</a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php elseif ($item['other_lists_count'] > 0): ?>
                        <div class="vlp-item__desc-wrapper">
                            <div style="margin-top:8px;">
                                <a href="<?php echo esc_url($item['permalink']); ?>" class="vlp-item__other-lists" style="font-weight: 700; font-size: 14px; color: var(--vlp-primary); text-decoration: underline;">pojavljuje se u jos <?php echo $item['other_lists_count']; ?> lista</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Score Display -->
                        <div class="vlp-item__score">
                            <span class="vlp-item__score-value" data-score="<?php echo intval($item['score']); ?>">
                                <?php echo number_format(intval($item['score'])); ?>
                            </span>
                            <span class="vlp-item__score-label">poena</span>
                        </div>
                        
                        <?php if (false && $is_vip_list && $vip_person && $item['vip_rank'] !== null): // Temporarily hidden ?>
                        <!-- VIP Rank Badge -->
                        <div class="vlp-vip-rank">
                            <span class="vlp-vip-rank__icon">⭐</span>
                            <span>#<span class="vlp-vip-rank__value"><?php echo intval($item['vip_rank']); ?></span></span>
                            <span>po <?php echo esc_html(explode(' ', $vip_person['name'])[0]); ?>u</span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Vote Buttons - moved here under score -->
                        <div class="vlp-item__buttons">
                            <?php for ($i = 1; $i <= $voting_scale; $i++): 
                                $is_active = $item['user_vote'] == $i;
                                // Check if this value is used by another item
                                $is_used_by_other = false;
                                if (!$is_active) {
                                    foreach ($user_votes as $voted_item_id => $voted_value) {
                                        if ($voted_value == $i && $voted_item_id != $item['id']) {
                                            $is_used_by_other = true;
                                            break;
                                        }
                                    }
                                }
                                $btn_classes = 'vlp-vote-btn';
                                if ($is_active) $btn_classes .= ' vlp-vote-btn--active';
                                if ($is_used_by_other) $btn_classes .= ' vlp-vote-btn--disabled';
                            ?>
                            <button class="<?php echo $btn_classes; ?>" 
                                    data-value="<?php echo $i; ?>"
                                    <?php echo $is_used_by_other ? 'disabled' : ''; ?>
                                    aria-label="Vote <?php echo $i; ?>">
                                <?php echo $i; ?>
                            </button>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                </article>
                <?php endforeach; ?>
            </div>
            
        </div>
    </section>
    
    <!-- ========================================
         FLOATING MY VOTES BUTTON & PANEL
         ======================================== -->
    <button class="vlp-fab" id="vlpFabButton" aria-label="My votes">
        <span class="vlp-fab__icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 12l2 2 4-4"/>
                <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9c1.94 0 3.74.62 5.2 1.67"/>
            </svg>
        </span>
        <span class="vlp-fab__count" id="vlpFabCount"><?php echo $user_votes_count; ?></span>
        <span class="vlp-fab__label">Moji glasovi</span>
    </button>
    
    <!-- Overlay -->
    <div class="vlp-panel-overlay" id="vlpPanelOverlay"></div>
    
    <!-- Side Panel -->
    <aside class="vlp-panel" id="vlpPanel">
        <header class="vlp-panel__header">
            <h3 class="vlp-panel__title">Moji glasovi</h3>
            <button class="vlp-panel__close" id="vlpPanelClose" aria-label="Close panel">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </header>
        
        <div class="vlp-panel__progress">
            <div class="vlp-panel__progress-bar">
                <div class="vlp-panel__progress-fill" id="vlpPanelProgress" style="width: <?php echo round(($user_votes_count / $voting_scale) * 100); ?>%"></div>
            </div>
            <span class="vlp-panel__progress-text">
                <span id="vlpPanelVoted"><?php echo $user_votes_count; ?></span> / <?php echo $voting_scale; ?> glasova
            </span>
        </div>
        
        <div class="vlp-panel__list" id="vlpPanelList">
            <?php 
            // Show voted items, sorted by vote value (highest first)
            $voted_items = array_filter($items, fn($i) => $i['user_vote'] !== null);
            usort($voted_items, fn($a, $b) => $b['user_vote'] <=> $a['user_vote']);
            
            if (empty($voted_items)): ?>
                <div class="vlp-panel__empty">
                    <p>Još niste glasali za nijednu stavku.</p>
                    <p>Glasajte za svoje favorite!</p>
                </div>
            <?php else: ?>
                <?php foreach ($voted_items as $item): ?>
                <div class="vlp-panel__item" data-item-id="<?php echo esc_attr($item['id']); ?>" data-vote-value="<?php echo esc_attr($item['user_vote']); ?>">
                    <img src="<?php echo esc_url($item['image']); ?>" alt="" class="vlp-panel__item-img">
                    <div class="vlp-panel__item-info">
                        <span class="vlp-panel__item-title"><?php echo esc_html(wp_trim_words($item['title'], 5)); ?></span>
                        <span class="vlp-panel__item-vote">Ocena: <strong><?php echo $item['user_vote']; ?></strong></span>
                    </div>
                    <button class="vlp-panel__item-remove" data-item-id="<?php echo esc_attr($item['id']); ?>" aria-label="Remove vote">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <footer class="vlp-panel__footer">
            <p class="vlp-panel__tip">💡 Kliknite X pored stavke da uklonite glas</p>
        </footer>
    </aside>
    
    <!-- Video Popup -->
    <div class="vlp-video-popup" id="vlpVideoPopup">
        <div class="vlp-video-popup__overlay" id="vlpVideoOverlay"></div>
        <div class="vlp-video-popup__content">
            <button class="vlp-video-popup__close" id="vlpVideoClose" aria-label="Close video">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <iframe class="vlp-video-popup__iframe" id="vlpVideoIframe" allowfullscreen allow="autoplay"></iframe>
        </div>
    </div>
    
</div>

<script>
(function() {
    // Define voting_list_vars if not already defined (for standalone use)
    if (typeof voting_list_vars === 'undefined') {
        window.voting_list_vars = {
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('voting_list_actions_nonce'); ?>'
        };
    }
    
    const listId = <?php echo $voting_list_id; ?>;
    const votingScale = <?php echo $voting_scale; ?>;
    const totalItems = <?php echo $items_count; ?>;
    
    // Track which vote values are used by which items
    const usedVoteValues = {}; // { voteValue: itemId }
    const itemVotes = {}; // { itemId: voteValue }
    
    // Initialize from current state
    <?php foreach ($user_votes as $item_id => $vote_value): ?>
    usedVoteValues[<?php echo $vote_value; ?>] = <?php echo $item_id; ?>;
    itemVotes[<?php echo $item_id; ?>] = <?php echo $vote_value; ?>;
    <?php endforeach; ?>
    
    // ========================================
    // TOAST & NOTIFICATION SYSTEM
    // ========================================
    function showToast(message, type = 'success', duration = 3500) {
        // Remove existing toasts
        document.querySelectorAll('.vlp-toast').forEach(t => t.remove());
        
        const toast = document.createElement('div');
        toast.className = 'vlp-toast vlp-toast--' + type;
        toast.innerHTML = '<span class="vlp-toast__message">' + message + '</span>';
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('vlp-toast--visible'), 10);
        setTimeout(() => {
            toast.classList.remove('vlp-toast--visible');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    
    function buildVoteFeedbackMessage(data) {
        let msg = '✓ Glas zabeležen';
        
        // Show expert bonus if applied (vote value increase)
        if (data.expert_bonus && data.expert_bonus > 0) {
            msg += ` <strong class="vlp-toast__bonus">(+${data.expert_bonus} bonus)</strong>`;
        }
        
        // Show XP awarded (field is xp_awarded from API)
        if (data.xp_awarded && data.xp_awarded > 0) {
            msg += ` <span class="vlp-toast__xp">+${data.xp_awarded} XP</span>`;
        } else if (data.xp_limit_reached) {
            msg += ' <span class="vlp-toast__limit">(dnevni limit XP)</span>';
        }
        
        return msg;
    }
    
    function showLevelUpPopup(levelUp) {
        document.querySelectorAll('.vlp-levelup-popup').forEach(p => p.remove());
        
        // Determine if this is category or overall level-up
        const isCategory = levelUp.type === 'category';
        const categoryLabel = isCategory ? levelUp.category_name : 'Ukupno';
        
        const mascotHtml = levelUp.mascot_url 
            ? '<img src="' + levelUp.mascot_url + '" alt="" class="vlp-levelup__mascot">'
            : '<div class="vlp-levelup__mascot-placeholder">🏆</div>';
        
        const popup = document.createElement('div');
        popup.className = 'vlp-levelup-popup';
        popup.innerHTML = `
            <div class="vlp-levelup-popup__overlay"></div>
            <div class="vlp-levelup-popup__content">
                <div class="vlp-levelup__confetti"></div>
                ${mascotHtml}
                <h2 class="vlp-levelup__title">Novi Nivo!</h2>
                <p class="vlp-levelup__category">${categoryLabel}</p>
                <div class="vlp-levelup__level">Nivo ${levelUp.new_level}</div>
                <p class="vlp-levelup__rank">${levelUp.title || ''}</p>
                ${levelUp.new_level >= 10 && isCategory ? '<p class="vlp-levelup__bonus">🎉 Otključali ste bonus glasove!</p>' : ''}
                <button class="vlp-levelup__close">Super!</button>
            </div>
        `;
        document.body.appendChild(popup);
        
        setTimeout(() => popup.classList.add('vlp-levelup-popup--visible'), 10);
        
        popup.querySelector('.vlp-levelup__close').addEventListener('click', () => {
            popup.classList.remove('vlp-levelup-popup--visible');
            setTimeout(() => popup.remove(), 300);
        });
        popup.querySelector('.vlp-levelup-popup__overlay').addEventListener('click', () => {
            popup.classList.remove('vlp-levelup-popup--visible');
            setTimeout(() => popup.remove(), 300);
        });
    }
    
    function showAchievementPopup(achievement) {
        document.querySelectorAll('.vlp-achievement-popup').forEach(p => p.remove());
        
        const popup = document.createElement('div');
        popup.className = 'vlp-achievement-popup';
        popup.innerHTML = `
            <div class="vlp-achievement-popup__overlay"></div>
            <div class="vlp-achievement-popup__content">
                <div class="vlp-achievement__icon">${achievement.icon || '🏅'}</div>
                <h2 class="vlp-achievement__title">Dostignuće otključano!</h2>
                <p class="vlp-achievement__name">${achievement.name}</p>
                <p class="vlp-achievement__desc">${achievement.description || ''}</p>
                <button class="vlp-achievement__close">Odlično!</button>
            </div>
        `;
        document.body.appendChild(popup);
        
        setTimeout(() => popup.classList.add('vlp-achievement-popup--visible'), 10);
        
        popup.querySelector('.vlp-achievement__close').addEventListener('click', () => {
            popup.classList.remove('vlp-achievement-popup--visible');
            setTimeout(() => popup.remove(), 300);
        });
        popup.querySelector('.vlp-achievement-popup__overlay').addEventListener('click', () => {
            popup.classList.remove('vlp-achievement-popup--visible');
            setTimeout(() => popup.remove(), 300);
        });
    }
    
    function handleNotifications(data) {
        // Show XP toast
        const feedbackMsg = buildVoteFeedbackMessage(data);
        showToast(feedbackMsg, 'success', 3500);
        
        // Handle level-ups
        if (data.level_ups && data.level_ups.length > 0) {
            setTimeout(() => {
                data.level_ups.forEach((levelUp, i) => {
                    setTimeout(() => showLevelUpPopup(levelUp), i * 4000);
                });
            }, 1000);
        }
        
        // Handle achievements
        if (data.achievements && data.achievements.length > 0) {
            const levelUpDelay = data.level_ups && data.level_ups.length > 0 
                ? data.level_ups.length * 4000 + 1500 
                : 1500;
            setTimeout(() => {
                data.achievements.forEach((ach, i) => {
                    setTimeout(() => showAchievementPopup(ach), i * 4500);
                });
            }, levelUpDelay);
        }
    }
    
    // ========================================
    // VIDEO POPUP
    // ========================================
    const videoPopup = document.getElementById('vlpVideoPopup');
    const videoIframe = document.getElementById('vlpVideoIframe');
    const videoOverlay = document.getElementById('vlpVideoOverlay');
    const videoClose = document.getElementById('vlpVideoClose');
    
    function convertToEmbed(url) {
        try {
            const parsedUrl = new URL(url);
            let videoId = null;
            let startSeconds = 0;
            
            if (parsedUrl.hostname.includes('youtube.com')) {
                videoId = parsedUrl.searchParams.get('v');
            } else if (parsedUrl.hostname.includes('youtu.be')) {
                videoId = parsedUrl.pathname.slice(1);
            }
            
            const tParam = parsedUrl.searchParams.get('t');
            if (tParam) {
                const match = tParam.match(/(?:(\d+)m)?(?:(\d+)s)?|(\d+)/);
                if (match) {
                    const minutes = parseInt(match[1] || 0, 10);
                    const seconds = parseInt(match[2] || match[3] || 0, 10);
                    startSeconds = minutes * 60 + seconds;
                }
            }
            
            if (videoId) {
                let embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1&mute=0&rel=0&modestbranding=1`;
                if (startSeconds > 0) embedUrl += `&start=${startSeconds}`;
                return embedUrl;
            }
        } catch (e) {}
        return url;
    }
    
    function openVideo(url) {
        videoIframe.src = convertToEmbed(url);
        videoPopup.classList.add('vlp-video-popup--open');
        document.body.style.overflow = 'hidden';
    }
    
    function closeVideo() {
        videoIframe.src = '';
        videoPopup.classList.remove('vlp-video-popup--open');
        document.body.style.overflow = '';
    }
    
    // Video play buttons
    document.querySelectorAll('.vlp-item__play').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const videoUrl = this.dataset.videoUrl;
            if (videoUrl) openVideo(videoUrl);
        });
    });
    
    videoOverlay?.addEventListener('click', closeVideo);
    videoClose?.addEventListener('click', closeVideo);
    
    // ========================================
    // HERO READ MORE TOGGLE
    // ========================================
    const heroReadMoreBtn = document.querySelector('.vlp-hero__read-more');
    if (heroReadMoreBtn) {
        heroReadMoreBtn.addEventListener('click', function() {
            const wrapper = this.closest('.vlp-hero__desc-wrapper');
            const descEl = wrapper.querySelector('.vlp-hero__desc');
            const isExpanded = descEl.classList.contains('vlp-hero__desc--expanded');
            
            if (isExpanded) {
                descEl.textContent = descEl.dataset.short + ' [...]';
                descEl.classList.remove('vlp-hero__desc--expanded');
                this.textContent = 'Pročitaj više';
            } else {
                descEl.textContent = descEl.dataset.full;
                descEl.classList.add('vlp-hero__desc--expanded');
                this.textContent = 'Prikaži manje';
            }
        });
    }
    
    // ========================================
    // PANEL TOGGLE
    // ========================================
    const fab = document.getElementById('vlpFabButton');
    const panel = document.getElementById('vlpPanel');
    const panelOverlay = document.getElementById('vlpPanelOverlay');
    const closeBtn = document.getElementById('vlpPanelClose');
    
    function openPanel() {
        panel.classList.add('vlp-panel--open');
        panelOverlay.classList.add('vlp-panel-overlay--visible');
        document.body.style.overflow = 'hidden';
    }
    
    function closePanel() {
        panel.classList.remove('vlp-panel--open');
        panelOverlay.classList.remove('vlp-panel-overlay--visible');
        document.body.style.overflow = '';
    }
    
    fab?.addEventListener('click', openPanel);
    closeBtn?.addEventListener('click', closePanel);
    panelOverlay?.addEventListener('click', closePanel);
    
    // Escape key closes both
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closePanel();
            closeVideo();
        }
    });
    
    // ========================================
    // VOTING LOGIC WITH EXCLUSIVE VALUES
    // ========================================
    
    function refreshAllButtonStates() {
        // Update all vote buttons based on current state
        document.querySelectorAll('.vlp-item').forEach(itemEl => {
            const itemId = parseInt(itemEl.dataset.itemId);
            const buttons = itemEl.querySelectorAll('.vlp-vote-btn');
            const currentVote = itemVotes[itemId];
            
            buttons.forEach(btn => {
                const value = parseInt(btn.dataset.value);
                const usedByItem = usedVoteValues[value];
                
                // Remove all state classes first
                btn.classList.remove('vlp-vote-btn--active', 'vlp-vote-btn--disabled');
                btn.disabled = false;
                
                if (currentVote === value) {
                    // This is the active vote for this item
                    btn.classList.add('vlp-vote-btn--active');
                } else if (usedByItem && usedByItem !== itemId) {
                    // This value is used by another item - disable it
                    btn.classList.add('vlp-vote-btn--disabled');
                    btn.disabled = true;
                }
            });
        });
    }
    
    // Vote button clicks
    document.querySelectorAll('.vlp-vote-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.classList.contains('vlp-vote-btn--disabled')) return;
            
            const item = this.closest('.vlp-item');
            const itemId = parseInt(item.dataset.itemId);
            const value = parseInt(this.dataset.value);
            
            // Check if clicking the currently active button (deselect)
            if (itemVotes[itemId] === value) {
                removeVote(itemId);
                return;
            }
            
            submitVote(itemId, value, item, this);
        });
    });
    
    // Remove vote clicks from panel (using event delegation)
    const panelList = document.getElementById('vlpPanelList');
    if (panelList) {
        panelList.addEventListener('click', function(e) {
            // Check if clicked element or any of its parents is the remove button
            const removeBtn = e.target.closest('.vlp-panel__item-remove');
            if (removeBtn) {
                e.preventDefault();
                e.stopPropagation();
                const itemId = parseInt(removeBtn.dataset.itemId);
                if (itemId) {
                    removeVote(itemId);
                }
            }
        });
    }
    
    function submitVote(itemId, value, itemEl, btnEl) {
        // Store old vote value if changing
        const oldValue = itemVotes[itemId];
        
        // Optimistic UI update
        if (oldValue !== undefined) {
            delete usedVoteValues[oldValue];
        }
        usedVoteValues[value] = itemId;
        itemVotes[itemId] = value;
        refreshAllButtonStates();
        
        // Disable buttons during request
        const buttons = itemEl.querySelectorAll('.vlp-vote-btn');
        buttons.forEach(b => b.style.pointerEvents = 'none');
        btnEl.classList.add('vlp-vote-btn--loading');
        
        fetch(voting_list_vars.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'submit_vote',
                nonce: voting_list_vars.nonce,
                voting_list_id: listId,
                voting_item_id: itemId,
                vote_value: value
            })
        })
        .then(res => res.json())
        .then(data => {
            buttons.forEach(b => b.style.pointerEvents = '');
            btnEl.classList.remove('vlp-vote-btn--loading');
            
            if (data.success) {
                // Mark as voted
                itemEl.classList.add('vlp-item--voted');
                
                // Update score
                const scoreEl = itemEl.querySelector('.vlp-item__score-value');
                if (data.data.new_total_score !== undefined) {
                    const newScore = Math.round(parseFloat(data.data.new_total_score));
                    scoreEl.textContent = newScore.toLocaleString();
                    scoreEl.dataset.score = newScore;
                    
                    // Reorder items based on new scores
                    reorderItems();
                }
                
                // Update panel
                updatePanel(itemId, value, itemEl, oldValue === undefined);
                
                // Show XP toast and handle level-ups/achievements
                handleNotifications(data.data);
            } else {
                // Revert optimistic update on error
                if (oldValue !== undefined) {
                    usedVoteValues[oldValue] = itemId;
                    itemVotes[itemId] = oldValue;
                } else {
                    delete usedVoteValues[value];
                    delete itemVotes[itemId];
                }
                refreshAllButtonStates();
                showFeedback(btnEl, 'error', data.data?.message || 'Greška');
            }
        })
        .catch(err => {
            console.error(err);
            buttons.forEach(b => b.style.pointerEvents = '');
            btnEl.classList.remove('vlp-vote-btn--loading');
            
            // Revert optimistic update
            if (oldValue !== undefined) {
                usedVoteValues[oldValue] = itemId;
                itemVotes[itemId] = oldValue;
            } else {
                delete usedVoteValues[value];
                delete itemVotes[itemId];
            }
            refreshAllButtonStates();
            showFeedback(btnEl, 'error', 'Network error');
        });
    }
    
    function removeVote(itemId) {
        const oldValue = itemVotes[itemId];
        if (oldValue === undefined) return;
        
        // Optimistic update
        delete usedVoteValues[oldValue];
        delete itemVotes[itemId];
        refreshAllButtonStates();
        
        const itemEl = document.querySelector('.vlp-item[data-item-id="' + itemId + '"]');
        
        fetch(voting_list_vars.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'remove_vote',
                nonce: voting_list_vars.nonce,
                list_id: listId,
                item_id: itemId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (itemEl) {
                    itemEl.classList.remove('vlp-item--voted');
                    
                    // Update score
                    const scoreEl = itemEl.querySelector('.vlp-item__score-value');
                    if (data.data.new_total_score !== undefined) {
                        const newScore = Math.round(parseFloat(data.data.new_total_score));
                        scoreEl.textContent = newScore.toLocaleString();
                        scoreEl.dataset.score = newScore;
                        
                        // Reorder items based on new scores
                        reorderItems();
                    }
                }
                
                // Remove from panel
                const panelItem = document.querySelector('.vlp-panel__item[data-item-id="' + itemId + '"]');
                if (panelItem) {
                    panelItem.style.animation = 'vlpSlideOut 0.3s ease forwards';
                    setTimeout(() => panelItem.remove(), 300);
                }
                
                updateCounts(-1);
            } else {
                // Revert
                usedVoteValues[oldValue] = itemId;
                itemVotes[itemId] = oldValue;
                refreshAllButtonStates();
            }
        })
        .catch(err => {
            console.error(err);
            // Revert
            usedVoteValues[oldValue] = itemId;
            itemVotes[itemId] = oldValue;
            refreshAllButtonStates();
        });
    }
    
    function updatePanel(itemId, value, itemEl, isNewVote) {
        const panelList = document.getElementById('vlpPanelList');
        
        // Remove empty state
        const emptyState = panelList.querySelector('.vlp-panel__empty');
        if (emptyState) emptyState.remove();
        
        // Check if item already in panel
        let panelItem = panelList.querySelector('.vlp-panel__item[data-item-id="' + itemId + '"]');
        
        if (panelItem) {
            // Update existing
            panelItem.dataset.voteValue = value;
            panelItem.querySelector('.vlp-panel__item-vote strong').textContent = value;
        } else {
            // Add new
            const img = itemEl.querySelector('.vlp-item__img').src;
            const title = itemEl.querySelector('.vlp-item__title a').textContent;
            
            const newItem = document.createElement('div');
            newItem.className = 'vlp-panel__item';
            newItem.dataset.itemId = itemId;
            newItem.dataset.voteValue = value;
            newItem.innerHTML = `
                <img src="${img}" alt="" class="vlp-panel__item-img">
                <div class="vlp-panel__item-info">
                    <span class="vlp-panel__item-title">${title.substring(0, 30)}${title.length > 30 ? '...' : ''}</span>
                    <span class="vlp-panel__item-vote">Ocena: <strong>${value}</strong></span>
                </div>
                <button class="vlp-panel__item-remove" data-item-id="${itemId}" aria-label="Remove vote">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            `;
            panelList.appendChild(newItem);
            
            if (isNewVote) updateCounts(1);
        }
        
        // Re-sort panel items by vote value (highest first)
        sortPanelItems();
    }
    
    function sortPanelItems() {
        const panelList = document.getElementById('vlpPanelList');
        const items = Array.from(panelList.querySelectorAll('.vlp-panel__item'));
        
        items.sort((a, b) => {
            const aVal = parseInt(a.dataset.voteValue) || 0;
            const bVal = parseInt(b.dataset.voteValue) || 0;
            return bVal - aVal; // Descending
        });
        
        items.forEach(item => panelList.appendChild(item));
    }
    
    // ========================================
    // REORDER ITEMS WITH ANIMATION
    // ========================================
    function reorderItems() {
        const container = document.querySelector('.vlp-items');
        if (!container) return;
        
        const items = Array.from(container.querySelectorAll('.vlp-item'));
        if (items.length < 2) return;
        
        // Store current positions
        const oldPositions = new Map();
        items.forEach(item => {
            const rect = item.getBoundingClientRect();
            oldPositions.set(item, { top: rect.top, left: rect.left });
        });
        
        // Sort by score (descending)
        items.sort((a, b) => {
            const aScore = parseInt(a.querySelector('.vlp-item__score-value')?.dataset.score) || 0;
            const bScore = parseInt(b.querySelector('.vlp-item__score-value')?.dataset.score) || 0;
            return bScore - aScore;
        });
        
        // Check if order changed
        const currentOrder = Array.from(container.querySelectorAll('.vlp-item'));
        let orderChanged = false;
        for (let i = 0; i < items.length; i++) {
            if (items[i] !== currentOrder[i]) {
                orderChanged = true;
                break;
            }
        }
        
        if (!orderChanged) return;
        
        // Reorder DOM
        items.forEach(item => container.appendChild(item));
        
        // Update rankings
        items.forEach((item, index) => {
            const rank = index + 1;
            const rankNum = item.querySelector('.vlp-item__rank-num');
            if (rankNum) rankNum.textContent = '#' + rank;
            
            // Update medal
            const rankMedal = item.querySelector('.vlp-item__rank-medal');
            if (rank <= 3) {
                const medals = ['🥇', '🥈', '🥉'];
                if (rankMedal) {
                    rankMedal.textContent = medals[rank - 1];
                } else {
                    const medalSpan = document.createElement('span');
                    medalSpan.className = 'vlp-item__rank-medal';
                    medalSpan.textContent = medals[rank - 1];
                    item.querySelector('.vlp-item__rank').prepend(medalSpan);
                }
                item.classList.remove('vlp-item--rank-1', 'vlp-item--rank-2', 'vlp-item--rank-3');
                item.classList.add('vlp-item--rank-' + rank);
            } else {
                if (rankMedal) rankMedal.remove();
                item.classList.remove('vlp-item--rank-1', 'vlp-item--rank-2', 'vlp-item--rank-3');
            }
        });
        
        // Animate to new positions
        items.forEach(item => {
            const oldPos = oldPositions.get(item);
            const newRect = item.getBoundingClientRect();
            
            const deltaY = oldPos.top - newRect.top;
            const deltaX = oldPos.left - newRect.left;
            
            if (Math.abs(deltaY) > 5 || Math.abs(deltaX) > 5) {
                // Start from old position
                item.style.transform = `translate(${deltaX}px, ${deltaY}px)`;
                item.style.transition = 'none';
                
                // Force reflow
                item.offsetHeight;
                
                // Animate to new position
                item.style.transition = 'transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                item.style.transform = '';
                
                // Add highlight effect for moved items
                item.classList.add('vlp-item--moved');
                setTimeout(() => item.classList.remove('vlp-item--moved'), 600);
            }
        });
    }
    
    function updateCounts(delta) {
        const fabCount = document.getElementById('vlpFabCount');
        const panelVoted = document.getElementById('vlpPanelVoted');
        const panelProgress = document.getElementById('vlpPanelProgress');
        const heroProgress = document.querySelector('.vlp-user-progress__fill');
        const heroText = document.querySelector('.vlp-user-progress__text');
        
        let current = parseInt(fabCount.textContent) + delta;
        if (current < 0) current = 0;
        
        fabCount.textContent = current;
        panelVoted.textContent = current;
        
        // Both progress bars based on voting scale (max votes available)
        const percent = Math.round((current / votingScale) * 100);
        panelProgress.style.width = percent + '%';
        if (heroProgress) heroProgress.style.width = percent + '%';
        if (heroText) {
            heroText.innerHTML = 'Glasali ste za <strong>' + current + '</strong> od <strong>' + votingScale + '</strong> itema (' + percent + '%)';
        }
    }
    
    function showFeedback(btn, type, message) {
        const feedback = document.createElement('span');
        feedback.className = 'vlp-feedback vlp-feedback--' + type;
        feedback.textContent = type === 'success' ? '✓' : '✕';
        
        btn.parentNode.style.position = 'relative';
        btn.parentNode.appendChild(feedback);
        
        setTimeout(() => feedback.classList.add('vlp-feedback--show'), 10);
        setTimeout(() => {
            feedback.classList.remove('vlp-feedback--show');
            setTimeout(() => feedback.remove(), 200);
        }, 1500);
    }
})();
</script>
