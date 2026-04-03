<?php
/**
 * Pure PHP Template for Single Voting Item
 * Displays item details and all lists containing this item
 * Bypasses Elementor completely
 * 
 * @package HelloElementorChild
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

$item_id = get_the_ID();
$item_title = get_the_title();
$item_description = get_the_content();
$item_excerpt = get_the_excerpt();
$item_thumbnail = get_the_post_thumbnail_url($item_id, 'large');

// Custom Fields
$item_meta = [
    'youtube_link' => get_post_meta($item_id, 'youtube_link', true),
    'spotify_link' => get_post_meta($item_id, 'spotify_link', true),
    'external_link' => get_post_meta($item_id, 'external_link', true),
];

// Calculate stats from votes table
global $wpdb;
$votes_table = $wpdb->prefix . 'voting_list_votes';

// Total votes across all lists for this item
$total_votes = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(vote_value) FROM $votes_table WHERE voting_item_id = %d", 
    $item_id
));
$total_votes = intval($total_votes);

// Number of lists this item appears in
$lists_with_item = $wpdb->get_col($wpdb->prepare(
    "SELECT DISTINCT voting_list_id FROM $votes_table WHERE voting_item_id = %d",
    $item_id
));

// Also find lists through meta (item might be in list but have no votes yet)
$all_lists = get_posts([
    'post_type' => 'voting_list',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => '_voting_items',
            'value' => serialize(strval($item_id)),
            'compare' => 'LIKE',
        ],
    ],
]);

// Alternative: search in serialized array
if (empty($all_lists)) {
    $all_lists = get_posts([
        'post_type' => 'voting_list',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);
    
    // Filter to only lists containing this item
    $all_lists = array_filter($all_lists, function($list) use ($item_id) {
        $items = get_post_meta($list->ID, '_voting_items', true);
        if (!is_array($items)) return false;
        return in_array($item_id, $items) || in_array(strval($item_id), $items);
    });
}

$lists_count = count($all_lists);

// Average vote value
$avg_vote = 0;
if ($total_votes > 0) {
    $vote_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $votes_table WHERE voting_item_id = %d",
        $item_id
    ));
    if ($vote_count > 0) {
        $avg_vote = $total_votes / $vote_count;
    }
}

// Enqueue styles
wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], '1.0.0');

get_header();
?>

<div class="ygv-page ygv-item-page">
    
    <!-- ========== HERO SECTION ========== -->
    <section class="ygv-item-hero">
        <div class="ygv-item-hero__inner">
            
            <div class="ygv-item-hero__image">
                <?php if ($item_thumbnail): ?>
                    <img src="<?php echo esc_url($item_thumbnail); ?>" alt="<?php echo esc_attr($item_title); ?>">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="rgba(255,255,255,0.3)">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="ygv-item-hero__content">
                <h1 class="ygv-item-hero__title"><?php echo esc_html($item_title); ?></h1>
                
                <?php if ($item_description): ?>
                <div class="ygv-item-hero__desc">
                    <?php echo wp_kses_post($item_description); ?>
                </div>
                <?php elseif ($item_excerpt): ?>
                <p class="ygv-item-hero__desc">
                    <?php echo esc_html($item_excerpt); ?>
                </p>
                <?php endif; ?>
                
                <div class="ygv-item-hero__stats">
                    <div class="ygv-item-stat">
                        <span class="ygv-item-stat__value"><?php echo number_format($total_votes); ?></span>
                        <span class="ygv-item-stat__label">Ukupno glasova</span>
                    </div>
                    <div class="ygv-item-stat">
                        <span class="ygv-item-stat__value"><?php echo $lists_count; ?></span>
                        <span class="ygv-item-stat__label"><?php echo $lists_count === 1 ? 'Lista' : 'Lista'; ?></span>
                    </div>
                    <?php if ($avg_vote > 0): ?>
                    <div class="ygv-item-stat">
                        <span class="ygv-item-stat__value"><?php echo number_format($avg_vote, 1); ?></span>
                        <span class="ygv-item-stat__label">Prosek ocene</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- External Links -->
                <?php if (!empty(array_filter($item_meta))): ?>
                <div class="ygv-item-links" style="margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap;">
                    <?php if ($item_meta['youtube_link']): ?>
                    <a href="<?php echo esc_url($item_meta['youtube_link']); ?>" 
                       target="_blank" 
                       rel="noopener"
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #FF0000; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        YouTube
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($item_meta['spotify_link']): ?>
                    <a href="<?php echo esc_url($item_meta['spotify_link']); ?>" 
                       target="_blank" 
                       rel="noopener"
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #1DB954; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                        </svg>
                        Spotify
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($item_meta['external_link']): ?>
                    <a href="<?php echo esc_url($item_meta['external_link']); ?>" 
                       target="_blank" 
                       rel="noopener"
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: rgba(255,255,255,0.2); color: white; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        Web stranica
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </section>
    
    <!-- ========== LISTS CONTAINING THIS ITEM ========== -->
    <?php if (!empty($all_lists)): ?>
    <section class="ygv-item-lists">
        <div class="ygv-item-lists__header ygv-container--narrow" style="max-width: 1000px;">
            <h2 class="ygv-item-lists__title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 8px; color: var(--ygv-primary);">
                    <path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/>
                </svg>
                Liste na kojima se nalazi
            </h2>
        </div>
        
        <div class="ygv-lists-grid" style="max-width: 1000px; margin: 0 auto; padding: 0 20px 60px;">
            <?php foreach ($all_lists as $list):
                $list_id = $list->ID;
                
                // Get items count from meta
                $voting_items = get_post_meta($list_id, '_voting_items', true);
                $items_count = is_array($voting_items) ? count($voting_items) : 0;
                
                // Calculate total score for this list from DB
                $list_score = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(vote_value) FROM $votes_table WHERE voting_list_id = %d",
                    $list_id
                ));
                $list_score = intval($list_score);
                
                // Get this item's rank in the list based on votes
                $item_rank = 0;
                if (is_array($voting_items) && !empty($voting_items)) {
                    // Get all items with their scores in this list
                    $items_scores = [];
                    foreach ($voting_items as $vid) {
                        $score = $wpdb->get_var($wpdb->prepare(
                            "SELECT SUM(vote_value) FROM $votes_table WHERE voting_list_id = %d AND voting_item_id = %d",
                            $list_id, $vid
                        ));
                        $items_scores[$vid] = intval($score);
                    }
                    // Sort by score DESC
                    arsort($items_scores);
                    // Find this item's rank
                    $rank = 1;
                    foreach ($items_scores as $vid => $score) {
                        if (intval($vid) === intval($item_id)) {
                            $item_rank = $rank;
                            break;
                        }
                        $rank++;
                    }
                }
                
                // Get item's score in this specific list
                $item_score_in_list = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(vote_value) FROM $votes_table WHERE voting_list_id = %d AND voting_item_id = %d",
                    $list_id, $item_id
                ));
                $item_score_in_list = intval($item_score_in_list);
                
                // Get list's category color
                $list_terms = get_the_terms($list_id, 'voting_list_category');
                $list_color = '#2D3A8C';
                if ($list_terms && !is_wp_error($list_terms)) {
                    $list_term = $list_terms[0];
                    $list_color = get_term_meta($list_term->term_id, 'category_color', true) ?: '#2D3A8C';
                }
            ?>
            <a href="<?php echo get_permalink($list_id); ?>" class="ygv-list-card" style="--ygv-list-color: <?php echo esc_attr($list_color); ?>;">
                <div class="ygv-list-card__image">
                    <?php if (has_post_thumbnail($list_id)): ?>
                        <?php echo get_the_post_thumbnail($list_id, 'medium_large'); ?>
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, <?php echo esc_attr($list_color); ?> 0%, #1e2a5e 100%); display: flex; align-items: center; justify-content: center;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="rgba(255,255,255,0.3)">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($item_rank > 0): ?>
                    <span class="ygv-list-card__badge" style="background: <?php echo $item_rank <= 3 ? ($item_rank === 1 ? 'var(--ygv-gold)' : ($item_rank === 2 ? 'var(--ygv-silver)' : 'var(--ygv-bronze)')) : 'var(--ygv-primary)'; ?>; color: <?php echo $item_rank <= 3 ? '#1a1a1a' : 'white'; ?>;">
                        #<?php echo $item_rank; ?> na listi
                    </span>
                    <?php endif; ?>
                    
                    <div class="ygv-list-card__score">
                        <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <?php echo number_format($item_score_in_list); ?> gl.
                    </div>
                </div>
                
                <div class="ygv-list-card__body">
                    <h3 class="ygv-list-card__title"><?php echo get_the_title($list_id); ?></h3>
                    
                    <div class="ygv-list-card__meta">
                        <span class="ygv-list-card__meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                            <?php echo $items_count; ?> itema
                        </span>
                        <span class="ygv-list-card__meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            <?php echo number_format($list_score); ?> ukupno
                        </span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php else: ?>
    <section class="ygv-item-lists" style="padding: 60px 20px; text-align: center;">
        <div class="ygv-container--narrow">
            <p style="font-size: 18px; color: var(--ygv-text-muted);">
                Ova stavka trenutno nije na nijednoj listi.
            </p>
        </div>
    </section>
    <?php endif; ?>
    
</div>

<?php get_footer(); ?>
