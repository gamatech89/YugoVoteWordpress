<?php
/**
 * Template Part: Top Categories with Top 3 Lists
 * Carousel with manual vote sorting
 * 
 * @package YugoVote
 */

if (!defined('ABSPATH')) exit;

// Fetch all top-level categories
$parent_categories = get_terms([
    'taxonomy'   => 'voting_list_category',
    'parent'     => 0,
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

if (empty($parent_categories) || is_wp_error($parent_categories)) {
    return;
}

/**
 * Calculate total votes for entire category (using get_total_score_for_voting_list)
 */
function yuv_get_category_total_votes($term_id) {
    // Get all lists in this category (including children)
    $args = [
        'post_type'      => 'voting_list',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => '_is_tournament_match',
                'compare' => 'NOT EXISTS',
            ],
        ],
        'tax_query'      => [
            [
                'taxonomy'         => 'voting_list_category',
                'field'            => 'term_id',
                'terms'            => $term_id,
                'include_children' => true,
            ],
        ],
    ];
    
    $list_ids = get_posts($args);
    $total_votes = 0;
    
    foreach ($list_ids as $list_id) {
        // Use the same function as trending section
        if (function_exists('get_total_score_for_voting_list')) {
            $total_votes += (int) get_total_score_for_voting_list($list_id);
        } else {
            // Fallback to meta
            $total_votes += (int) get_post_meta($list_id, 'total_score', true);
        }
    }
    
    return $total_votes;
}

/**
 * Get top 3 lists by actual vote count (manual sorting)
 */
function yuv_get_top_3_lists_by_votes($term_id) {
    // Get ALL lists in category (including children)
    $args = [
        'post_type'      => 'voting_list',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => '_is_tournament_match',
                'compare' => 'NOT EXISTS',
            ],
        ],
        'tax_query'      => [
            [
                'taxonomy'         => 'voting_list_category',
                'field'            => 'term_id',
                'terms'            => $term_id,
                'include_children' => true,
            ],
        ],
    ];
    
    $list_ids = get_posts($args);
    
    if (empty($list_ids)) {
        return [];
    }
    
    // Build array with votes
    $lists_with_votes = [];
    foreach ($list_ids as $list_id) {
        $vote_count = 0;
        
        if (function_exists('get_total_score_for_voting_list')) {
            $vote_count = (int) get_total_score_for_voting_list($list_id);
        } else {
            $vote_count = (int) get_post_meta($list_id, 'total_score', true);
        }
        
        $lists_with_votes[] = [
            'id'    => $list_id,
            'votes' => $vote_count,
        ];
    }
    
    // Sort by votes DESC
    usort($lists_with_votes, function($a, $b) {
        return $b['votes'] - $a['votes'];
    });
    
    // Return top 3
    return array_slice($lists_with_votes, 0, 3);
}
?>

<section class="yuv-top-categories-section">
    <div class="cs-container">
        
        <!-- Category Cards Carousel -->
        <div class="yuv-categories-carousel">
            <button class="yuv-carousel-btn yuv-carousel-prev" aria-label="Prethodna">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18L9 12L15 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            
            <div class="yuv-carousel-track">
            <?php foreach ($parent_categories as $term): 
                $term_id = $term->term_id;
                $term_slug = $term->slug;
                $term_name = $term->name;
                $term_link = get_term_link($term);
                $term_color = get_term_meta($term_id, 'category_color', true) ?: '#4355A4';
                $logo_id = get_term_meta($term_id, 'category_logo', true);
                
                // Get top 3 lists sorted by votes
                $top_3_lists = yuv_get_top_3_lists_by_votes($term_id);
                
                // Skip if no lists
                if (empty($top_3_lists)) {
                    continue;
                }
                
                // Calculate total votes
                $total_votes = yuv_get_category_total_votes($term_id);
            ?>
            
            <div class="yuv-cat-card" style="--cat-color: <?php echo esc_attr($term_color); ?>;">
                
                <!-- Mascot -->
                <div class="yuv-cat-mascot">
                    <?php if ($logo_id): ?>
                        <?php echo wp_get_attachment_image($logo_id, 'medium', false, ['class' => 'mascot-img']); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Content -->
                <div class="yuv-cat-content">
                    <!-- Header -->
                    <div class="yuv-cat-header">
                        <h3 class="yuv-cat-name">
                            <?php echo esc_html(strtoupper($term_name)); ?>
                        </h3>
                        <span class="yuv-cat-votes">
                            <?php echo number_format_i18n($total_votes); ?> GLASOVA
                        </span>
                    </div>
                    
                    <!-- Top 3 Lists -->
                    <ul class="yuv-ranking-list">
                        <?php 
                        $rank = 1;
                        foreach ($top_3_lists as $list_data): 
                            $list_id = $list_data['id'];
                            $list_votes = $list_data['votes'];
                            $list_title = get_the_title($list_id);
                            $list_permalink = get_permalink($list_id);
                        ?>
                            <li class="yuv-rank-item">
                                <span class="rank-num">#<?php echo $rank; ?></span>
                                <div class="rank-info">
                                    <a href="<?php echo esc_url($list_permalink); ?>" class="rank-title">
                                        <?php echo esc_html($list_title); ?>
                                    </a>
                                    <span class="rank-votes">
                                        <?php echo number_format_i18n($list_votes); ?> glasova
                                    </span>
                                </div>
                            </li>
                        <?php 
                            $rank++;
                        endforeach; 
                        ?>
                    </ul>
                    
                    <!-- View More Button -->
                    <a href="<?php echo esc_url($term_link); ?>" class="yuv-cat-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="20" viewBox="0 0 27 20" fill="none">
                            <path d="M1.5 9.81774C6.04008 10.4649 16.322 11.371 21.1292 9.81774C27.1381 7.87618 19.1262 6.48935 14.7196 2.8836C10.3131 -0.72216 27.3384 7.7375 25.3354 9.81774C23.3324 11.898 19.1262 16.3358 16.9229 18" stroke="white" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <?php endforeach; ?>
            </div>
            
            <button class="yuv-carousel-btn yuv-carousel-next" aria-label="Sledeća">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18L15 12L9 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
    </div>
</section>
