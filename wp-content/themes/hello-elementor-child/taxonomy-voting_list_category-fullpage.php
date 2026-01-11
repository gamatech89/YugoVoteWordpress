<?php
/**
 * Pure PHP Template for Voting List Categories
 * Design based on [voting_top_categories] shortcode style
 * 
 * Layout for PARENT categories:
 * - Hero section with bigger mascot, title, description, stats in boxes
 * - Subcategories carousel (vertical cards with mascot on top)
 * - Per-subcategory trending grids (1 big LEFT + 4 small RIGHT in 2x2)
 * 
 * Layout for CHILD categories:
 * - Same hero
 * - Lists grid (1 big + 4 small per row)
 * 
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

$current_term = get_queried_object();
$term_id = $current_term->term_id;
$is_parent = $current_term->parent === 0;

// Get category meta
$category_color = get_term_meta($term_id, 'category_color', true) ?: '#4456A6';
$category_logo_id = get_term_meta($term_id, 'category_logo', true);
$category_logo_url = $category_logo_id ? wp_get_attachment_url($category_logo_id) : '';
$category_description = $current_term->description;

// Get parent category info (for breadcrumb on child)
$parent_term = null;
$parent_logo_url = '';
if (!$is_parent) {
    $parent_term = get_term($current_term->parent, 'voting_list_category');
    $parent_logo_id = get_term_meta($parent_term->term_id, 'category_logo', true);
    $parent_logo_url = $parent_logo_id ? wp_get_attachment_url($parent_logo_id) : '';
}

// Stats - calculate real votes
$lists_count = $current_term->count;

// Calculate total votes for this category
$total_votes = 0;
$all_lists_in_cat = get_posts([
    'post_type'      => 'voting_list',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'tax_query'      => [[
        'taxonomy'         => 'voting_list_category',
        'field'            => 'term_id',
        'terms'            => $term_id,
        'include_children' => true,
    ]],
    'meta_query'     => [
        ['key' => '_is_tournament_match', 'compare' => 'NOT EXISTS'],
    ],
]);
foreach ($all_lists_in_cat as $lid) {
    if (function_exists('get_total_score_for_voting_list')) {
        $total_votes += (int) get_total_score_for_voting_list($lid);
    }
}

// Get subcategories (for parent only)
$subcategories = [];
if ($is_parent) {
    $subcategories = get_terms([
        'taxonomy'   => 'voting_list_category',
        'parent'     => $term_id,
        'hide_empty' => false,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ]);
    if (is_wp_error($subcategories)) $subcategories = [];
}

/**
 * Helper: Get top lists by votes for a category (with featured support)
 */
if (!function_exists('ygv_get_top_lists_for_category')) {
    function ygv_get_top_lists_for_category($cat_term_id, $count = 5, $include_children = false) {
        $args = [
            'post_type'      => 'voting_list',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy'         => 'voting_list_category',
                'field'            => 'term_id',
                'terms'            => $cat_term_id,
                'include_children' => $include_children,
            ]],
            'meta_query'     => [
                ['key' => '_is_tournament_match', 'compare' => 'NOT EXISTS'],
            ],
        ];
        
        $list_ids = get_posts($args);
        if (empty($list_ids)) return ['featured' => null, 'items' => []];
        
        $lists_data = [];
        foreach ($list_ids as $list_id) {
            $vote_count = function_exists('get_total_score_for_voting_list') 
                ? (int) get_total_score_for_voting_list($list_id) : 0;
            $is_featured = get_post_meta($list_id, '_is_featured', true) === '1';
            $lists_data[] = ['id' => $list_id, 'votes' => $vote_count, 'featured' => $is_featured];
        }
        
        usort($lists_data, function($a, $b) { return $b['votes'] - $a['votes']; });
        
        $featured_item = null;
        foreach ($lists_data as $key => $item) {
            if ($item['featured']) {
                $featured_item = $item;
                unset($lists_data[$key]);
                break;
            }
        }
        
        if (!$featured_item && !empty($lists_data)) {
            $featured_item = array_shift($lists_data);
        }
        
        return [
            'featured' => $featured_item,
            'items'    => array_slice(array_values($lists_data), 0, $count - 1),
        ];
    }
}

/**
 * Helper: Calculate total votes for a category
 */
function ygv_get_category_votes($cat_term_id) {
    $list_ids = get_posts([
        'post_type'      => 'voting_list',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => [[
            'taxonomy'         => 'voting_list_category',
            'field'            => 'term_id',
            'terms'            => $cat_term_id,
            'include_children' => true,
        ]],
        'meta_query'     => [['key' => '_is_tournament_match', 'compare' => 'NOT EXISTS']],
    ]);
    
    $total = 0;
    foreach ($list_ids as $lid) {
        if (function_exists('get_total_score_for_voting_list')) {
            $total += (int) get_total_score_for_voting_list($lid);
        }
    }
    return $total;
}

/**
 * Helper: Get top 3 voting items from a voting list (by score)
 */
if (!function_exists('ygv_get_top_items_for_list')) {
    function ygv_get_top_items_for_list($list_id, $count = 3) {
        global $wpdb;
        
        // Get items associated with this list
        $item_ids = get_post_meta($list_id, '_voting_items', true);
        if (empty($item_ids) || !is_array($item_ids)) return [];
        
        $votes_table = $wpdb->prefix . 'voting_list_votes';
        
        // Get scores for items in this specific list
        $items_with_scores = [];
        foreach ($item_ids as $item_id) {
            $score = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(vote_value) FROM $votes_table WHERE voting_list_id = %d AND voting_item_id = %d",
                $list_id, $item_id
            ));
            $items_with_scores[] = [
                'id' => $item_id,
                'score' => (int) $score,
                'title' => get_the_title($item_id),
            ];
        }
        
        // Sort by score DESC
        usort($items_with_scores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return array_slice($items_with_scores, 0, $count);
    }
}

// Enqueue styles
wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], HELLO_ELEMENTOR_CHILD_VERSION);

get_header();
?>

<div class="ygv-page ygv-category-page">
    
    <!-- ========== HERO SECTION ========== -->
    <section class="ygv-cat-hero" style="--ygv-cat-color: <?php echo esc_attr($category_color); ?>;">
        <div class="ygv-cat-hero__inner">
            
            <?php if ($category_logo_url): ?>
            <div class="ygv-cat-hero__logo">
                <img src="<?php echo esc_url($category_logo_url); ?>" alt="<?php echo esc_attr($current_term->name); ?>">
            </div>
            <?php endif; ?>
            
            <div class="ygv-cat-hero__content">
                
                <!-- Breadcrumb -->
                <nav class="ygv-cat-hero__breadcrumb">
                    <a href="<?php echo home_url('/'); ?>">Početna</a>
                    <span>›</span>
                    <?php if (!$is_parent && $parent_term): ?>
                        <a href="<?php echo esc_url(get_term_link($parent_term)); ?>"><?php echo esc_html($parent_term->name); ?></a>
                        <span>›</span>
                    <?php endif; ?>
                    <span><?php echo esc_html($current_term->name); ?></span>
                </nav>
                
                <h1 class="ygv-cat-hero__title"><?php echo esc_html($current_term->name); ?></h1>
                
                <?php if ($category_description): ?>
                <p class="ygv-cat-hero__desc"><?php echo esc_html($category_description); ?></p>
                <?php endif; ?>
                
                <div class="ygv-cat-hero__stats">
                    <div class="ygv-cat-hero__stat-box">
                        <span class="ygv-cat-hero__stat-value"><?php echo number_format($lists_count); ?></span>
                        <span class="ygv-cat-hero__stat-label">LISTA</span>
                    </div>
                    <div class="ygv-cat-hero__stat-box">
                        <span class="ygv-cat-hero__stat-value"><?php echo number_format($total_votes); ?></span>
                        <span class="ygv-cat-hero__stat-label">GLASOVA</span>
                    </div>
                    <?php if ($is_parent && !empty($subcategories)): ?>
                    <div class="ygv-cat-hero__stat-box">
                        <span class="ygv-cat-hero__stat-value"><?php echo count($subcategories); ?></span>
                        <span class="ygv-cat-hero__stat-label">PODKATEGORIJA</span>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </section>
    
    <?php if ($is_parent && !empty($subcategories)): ?>
    
    <!-- ========== SUBCATEGORIES CAROUSEL (Vertical Cards like image-2) ========== -->
    <section class="ygv-subcats-carousel-section">
        <div class="ygv-container">
            <div class="ygv-subcats-carousel">
                <button class="ygv-carousel-btn ygv-carousel-prev" aria-label="Prethodna">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                
                <div class="ygv-carousel-track">
                    <?php foreach ($subcategories as $subcat):
                        $sub_id = $subcat->term_id;
                        $sub_color = get_term_meta($sub_id, 'category_color', true) ?: $category_color;
                        $sub_logo_id = get_term_meta($sub_id, 'category_logo', true);
                        $sub_logo_url = $sub_logo_id ? wp_get_attachment_url($sub_logo_id) : $category_logo_url; // Fallback to parent mascot
                        $sub_link = get_term_link($subcat);
                        
                        // Get top 3 lists for this subcategory
                        $top_data = ygv_get_top_lists_for_category($sub_id, 4);
                        $top_lists = [];
                        if ($top_data['featured']) $top_lists[] = $top_data['featured'];
                        $top_lists = array_merge($top_lists, $top_data['items']);
                        $top_lists = array_slice($top_lists, 0, 3);
                        
                        if (empty($top_lists)) continue;
                        
                        // Calculate subcategory total votes
                        $sub_votes = ygv_get_category_votes($sub_id);
                    ?>
                    <div class="ygv-subcat-card-v2" style="--cat-color: <?php echo esc_attr($sub_color); ?>;">
                        
                        <!-- Top Colored Section -->
                        <div class="ygv-subcat-card-v2__top">
                            <div class="ygv-subcat-card-v2__mascot">
                                <?php if ($sub_logo_url): ?>
                                    <img src="<?php echo esc_url($sub_logo_url); ?>" alt="<?php echo esc_attr($subcat->name); ?>">
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" fill="white" opacity="0.5">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <h3 class="ygv-subcat-card-v2__name"><?php echo esc_html(strtoupper($subcat->name)); ?></h3>
                            <span class="ygv-subcat-card-v2__votes"><?php echo number_format($sub_votes); ?> GLASOVA</span>
                        </div>
                        
                        <!-- Bottom White Section -->
                        <div class="ygv-subcat-card-v2__bottom">
                            <ul class="ygv-subcat-card-v2__ranking">
                                <?php $rank = 1; foreach ($top_lists as $list_data): ?>
                                <li class="ygv-rank-item-v2">
                                    <span class="rank-badge" style="background: <?php echo esc_attr($sub_color); ?>;">#<?php echo $rank; ?></span>
                                    <div class="rank-info">
                                        <a href="<?php echo get_permalink($list_data['id']); ?>" class="rank-title">
                                            <?php echo esc_html(get_the_title($list_data['id'])); ?>
                                        </a>
                                        <span class="rank-votes"><?php echo number_format($list_data['votes']); ?> glasova</span>
                                    </div>
                                </li>
                                <?php $rank++; endforeach; ?>
                            </ul>
                            
                            <a href="<?php echo esc_url($sub_link); ?>" class="ygv-subcat-card-v2__link" style="background: <?php echo esc_attr($sub_color); ?>;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button class="ygv-carousel-btn ygv-carousel-next" aria-label="Sledeća">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </section>
    
    <!-- ========== TRENDING GRIDS PER SUBCATEGORY (1 big LEFT + 4 small RIGHT 2x2) ========== -->
    <?php foreach ($subcategories as $subcat):
        $sub_id = $subcat->term_id;
        $sub_color = get_term_meta($sub_id, 'category_color', true) ?: $category_color;
        $sub_link = get_term_link($subcat);
        
        // Get 5 lists (1 featured big + 4 small)
        $lists_data = ygv_get_top_lists_for_category($sub_id, 5);
        if (!$lists_data['featured'] && empty($lists_data['items'])) continue;
    ?>
    <section class="ygv-subcat-trending" style="--cat-color: <?php echo esc_attr($sub_color); ?>;">
        <div class="ygv-container">
            
            <div class="ygv-subcat-trending__header">
                <h2 class="ygv-subcat-trending__title"><?php echo esc_html($subcat->name); ?></h2>
                <a href="<?php echo esc_url($sub_link); ?>" class="ygv-subcat-trending__viewall-btn">
                    pogledaj više
                    <svg width="20" height="14" viewBox="0 0 27 20" fill="none">
                        <path d="M1.5 9.81774C6.04008 10.4649 16.322 11.371 21.1292 9.81774C27.1381 7.87618 19.1262 6.48935 14.7196 2.8836C10.3131 -0.72216 27.3384 7.7375 25.3354 9.81774C23.3324 11.898 19.1262 16.3358 16.9229 18" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </a>
            </div>
            
            <div class="ygv-trending-grid-v2">
                <?php 
                // Featured (big) card on LEFT
                if ($lists_data['featured']):
                    $list_id = $lists_data['featured']['id'];
                    $votes = $lists_data['featured']['votes'];
                    $thumbnail = get_the_post_thumbnail_url($list_id, 'large');
                ?>
                <a href="<?php echo get_permalink($list_id); ?>" class="ygv-trend-card ygv-trend-card--big">
                    <div class="ygv-trend-card__media">
                        <?php if ($thumbnail): ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title($list_id)); ?>">
                        <?php else: ?>
                            <div class="ygv-trend-card__placeholder"></div>
                        <?php endif; ?>
                    </div>
                    <div class="ygv-trend-card__content">
                        <h3 class="ygv-trend-card__title"><?php echo esc_html(get_the_title($list_id)); ?></h3>
                        <span class="ygv-trend-card__votes"><strong><?php echo number_format($votes); ?></strong> GLASOVA</span>
                    </div>
                </a>
                <?php endif; ?>
                
                <!-- 4 Small cards on RIGHT (2x2 grid) -->
                <div class="ygv-trending-grid-v2__small">
                    <?php foreach ($lists_data['items'] as $item):
                        $list_id = $item['id'];
                        $votes = $item['votes'];
                        $thumbnail = get_the_post_thumbnail_url($list_id, 'medium');
                    ?>
                    <a href="<?php echo get_permalink($list_id); ?>" class="ygv-trend-card ygv-trend-card--small">
                        <div class="ygv-trend-card__media">
                            <?php if ($thumbnail): ?>
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title($list_id)); ?>">
                            <?php else: ?>
                                <div class="ygv-trend-card__placeholder"></div>
                            <?php endif; ?>
                        </div>
                        <div class="ygv-trend-card__content">
                            <h3 class="ygv-trend-card__title"><?php echo esc_html(get_the_title($list_id)); ?></h3>
                            <span class="ygv-trend-card__votes"><strong><?php echo number_format($votes); ?></strong> GLASOVA</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
        </div>
    </section>
    <?php endforeach; ?>
    
    <?php else: // CHILD CATEGORY - Show all lists ?>
    
    <!-- ========== ALL LISTS (Grid on desktop, Carousel on mobile) ========== -->
    <?php
    // Get all lists sorted by votes
    $all_list_ids = get_posts([
        'post_type'      => 'voting_list',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => [[
            'taxonomy' => 'voting_list_category',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ]],
        'meta_query'     => [['key' => '_is_tournament_match', 'compare' => 'NOT EXISTS']],
    ]);
    
    $lists_with_votes = [];
    foreach ($all_list_ids as $lid) {
        $vote_count = function_exists('get_total_score_for_voting_list') 
            ? (int) get_total_score_for_voting_list($lid) : 0;
        $lists_with_votes[] = ['id' => $lid, 'votes' => $vote_count];
    }
    usort($lists_with_votes, function($a, $b) { return $b['votes'] - $a['votes']; });
    
    if (!empty($lists_with_votes)):
    ?>
    <section class="ygv-lists-section">
        <div class="ygv-container">
            <div class="ygv-lists-section__header">
                <h2 class="ygv-lists-section__title">
                    Sve liste u kategoriji <?php echo esc_html($current_term->name); ?>
                </h2>
            </div>
            
            <div class="ygv-child-lists-scroll">
                <?php foreach ($lists_with_votes as $item):
                    $thumbnail = get_the_post_thumbnail_url($item['id'], 'medium_large');
                    $top_items = ygv_get_top_items_for_list($item['id'], 3);
                ?>
                <a href="<?php echo get_permalink($item['id']); ?>" class="ygv-child-list-card">
                    <div class="ygv-child-list-card__media">
                        <?php if ($thumbnail): ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title($item['id'])); ?>">
                        <?php else: ?>
                            <div class="ygv-child-list-card__placeholder"></div>
                        <?php endif; ?>
                    </div>
                    <div class="ygv-child-list-card__content">
                        <h3 class="ygv-child-list-card__title"><?php echo esc_html(get_the_title($item['id'])); ?></h3>
                        
                        <?php if (!empty($top_items)): ?>
                        <div class="ygv-child-list-card__top3">
                            <?php foreach ($top_items as $rank => $top_item): ?>
                            <div class="ygv-top-item">
                                <span class="ygv-top-item__rank"><?php echo ($rank + 1); ?></span>
                                <span class="ygv-top-item__name"><?php echo esc_html($top_item['title']); ?></span>
                                <span class="ygv-top-item__score"><?php echo number_format($top_item['score']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <span class="ygv-child-list-card__votes"><strong><?php echo number_format($item['votes']); ?></strong> GLASOVA</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <?php endif; ?>
    
</div>

<!-- Carousel JavaScript -->
<script>
(function() {
    const carousel = document.querySelector('.ygv-subcats-carousel');
    if (!carousel) return;
    
    const track = carousel.querySelector('.ygv-carousel-track');
    const prevBtn = carousel.querySelector('.ygv-carousel-prev');
    const nextBtn = carousel.querySelector('.ygv-carousel-next');
    
    if (!track || !prevBtn || !nextBtn) return;
    
    const scrollAmount = 300;
    
    prevBtn.addEventListener('click', () => track.scrollBy({ left: -scrollAmount, behavior: 'smooth' }));
    nextBtn.addEventListener('click', () => track.scrollBy({ left: scrollAmount, behavior: 'smooth' }));
    
    // Touch swipe
    let startX, scrollLeft;
    track.addEventListener('touchstart', (e) => { startX = e.touches[0].pageX; scrollLeft = track.scrollLeft; });
    track.addEventListener('touchmove', (e) => { if (!startX) return; track.scrollLeft = scrollLeft + (startX - e.touches[0].pageX) * 2; });
})();
</script>

<?php get_footer(); ?>
