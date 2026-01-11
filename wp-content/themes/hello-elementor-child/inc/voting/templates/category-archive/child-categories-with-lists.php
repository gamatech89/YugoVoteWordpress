<?php
/**
 * Child Categories Section with Top Lists
 * Similar to "Najpopularnije objave po kategorijama" layout
 * Shows each child category with its top 3 lists
 */

$current_term = get_queried_object();
if (!$current_term || $current_term->parent !== 0) return; // Only for parent categories

$subcategories = get_terms([
    'taxonomy'   => 'voting_list_category',
    'parent'     => $current_term->term_id,
    'hide_empty' => true,
    'orderby'    => 'count',
    'order'      => 'DESC',
]);

if (empty($subcategories) || is_wp_error($subcategories)) return;
?>

<section class="ygv-child-cats-section cs-container">
    <h2 class="ygv-section-title">
        Najpopularnije liste po kategorijama
    </h2>
    
    <div class="ygv-child-cats-carousel">
        <button class="ygv-carousel-btn ygv-carousel-btn--prev" aria-label="Previous">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
        
        <div class="ygv-child-cats-track">
            <?php foreach ($subcategories as $subcategory) : 
                $cat_id = $subcategory->term_id;
                $cat_name = $subcategory->name;
                $cat_color = get_term_meta($cat_id, 'category_color', true) ?: '#2D3A8C';
                $cat_logo_id = get_term_meta($cat_id, 'category_logo', true);
                $cat_logo_url = $cat_logo_id ? wp_get_attachment_url($cat_logo_id) : '';
                $cat_link = get_term_link($subcategory);
                
                // Get total votes for this category
                $total_votes = get_term_meta($cat_id, '_total_category_votes', true) ?: 0;
                
                // Get top 3 lists in this category
                $top_lists = get_posts([
                    'post_type' => 'voting_list',
                    'posts_per_page' => 3,
                    'meta_key' => '_vote_cache_total_score',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC',
                    'tax_query' => [[
                        'taxonomy' => 'voting_list_category',
                        'field' => 'term_id',
                        'terms' => $cat_id,
                    ]],
                    'meta_query' => [
                        [
                            'key' => '_is_tournament_match',
                            'compare' => 'NOT EXISTS',
                        ],
                    ],
                ]);
            ?>
            <div class="ygv-child-cat-card" style="--cat-color: <?php echo esc_attr($cat_color); ?>;">
                <div class="ygv-child-cat-card__header">
                    <?php if ($cat_logo_url): ?>
                    <div class="ygv-child-cat-card__logo">
                        <img src="<?php echo esc_url($cat_logo_url); ?>" alt="<?php echo esc_attr($cat_name); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <h3 class="ygv-child-cat-card__name" style="color: <?php echo esc_attr($cat_color); ?>;">
                        <?php echo esc_html(strtoupper($cat_name)); ?>
                    </h3>
                    
                    <div class="ygv-child-cat-card__stats">
                        <span class="ygv-child-cat-card__votes"><?php echo number_format($total_votes); ?></span>
                        <span class="ygv-child-cat-card__votes-label">GLASOVA</span>
                    </div>
                </div>
                
                <div class="ygv-child-cat-card__lists">
                    <?php foreach ($top_lists as $index => $list_post): 
                        $list_id = $list_post->ID;
                        $list_title = get_the_title($list_post);
                        $list_score = get_post_meta($list_id, '_vote_cache_total_score', true) ?: 0;
                        $list_link = get_permalink($list_post);
                    ?>
                    <a href="<?php echo esc_url($list_link); ?>" class="ygv-child-cat-card__list-item">
                        <span class="ygv-child-cat-card__list-rank" style="background: <?php echo esc_attr($cat_color); ?>;">
                            #<?php echo $index + 1; ?>
                        </span>
                        <span class="ygv-child-cat-card__list-title"><?php echo esc_html($list_title); ?></span>
                        <span class="ygv-child-cat-card__list-score"><?php echo number_format($list_score); ?> glasova</span>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <a href="<?php echo esc_url($cat_link); ?>" class="ygv-child-cat-card__more" style="background: <?php echo esc_attr($cat_color); ?>;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <button class="ygv-carousel-btn ygv-carousel-btn--next" aria-label="Next">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>
    </div>
</section>

<style>
/* Child Categories with Top Lists Section */
.ygv-child-cats-section {
    padding: 48px 0;
}

.ygv-section-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 32px;
}

.ygv-child-cats-carousel {
    position: relative;
    display: flex;
    align-items: center;
    gap: 16px;
}

.ygv-carousel-btn {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s;
    flex-shrink: 0;
}

.ygv-carousel-btn:hover {
    background: #e2e8f0;
    color: #1a1a1a;
}

.ygv-carousel-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.ygv-child-cats-track {
    display: flex;
    gap: 24px;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding: 8px 0;
}

.ygv-child-cats-track::-webkit-scrollbar {
    display: none;
}

.ygv-child-cat-card {
    flex: 0 0 320px;
    scroll-snap-align: start;
    background: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    position: relative;
}

.ygv-child-cat-card__header {
    text-align: center;
    margin-bottom: 20px;
}

.ygv-child-cat-card__logo {
    width: 80px;
    height: 80px;
    margin: 0 auto 16px;
    padding: 12px;
    background: #fff7ed;
    border-radius: 50%;
}

.ygv-child-cat-card__logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.ygv-child-cat-card__name {
    margin: 0 0 8px;
    font-size: 1.1rem;
    font-weight: 800;
    letter-spacing: 0.5px;
}

.ygv-child-cat-card__stats {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.ygv-child-cat-card__votes {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--cat-color);
}

.ygv-child-cat-card__votes-label {
    font-size: 11px;
    color: #94a3b8;
    letter-spacing: 0.5px;
}

.ygv-child-cat-card__lists {
    display: flex;
    flex-direction: column;
    gap: 12px;
    flex: 1;
}

.ygv-child-cat-card__list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}

.ygv-child-cat-card__list-item:hover {
    transform: translateX(4px);
}

.ygv-child-cat-card__list-rank {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.ygv-child-cat-card__list-title {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    color: #1a1a1a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ygv-child-cat-card__list-score {
    font-size: 12px;
    color: #94a3b8;
    white-space: nowrap;
}

.ygv-child-cat-card__more {
    position: absolute;
    bottom: 16px;
    right: 16px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border-radius: 50%;
    transition: all 0.2s;
}

.ygv-child-cat-card__more:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Responsive */
@media (max-width: 768px) {
    .ygv-carousel-btn {
        display: none;
    }
    
    .ygv-child-cat-card {
        flex: 0 0 280px;
    }
    
    .ygv-section-title {
        font-size: 1.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const section = document.querySelector('.ygv-child-cats-section');
    if (!section) return;
    
    const track = section.querySelector('.ygv-child-cats-track');
    const prevBtn = section.querySelector('.ygv-carousel-btn--prev');
    const nextBtn = section.querySelector('.ygv-carousel-btn--next');
    
    if (!track || !prevBtn || !nextBtn) return;
    
    const cardWidth = 320 + 24; // card width + gap
    
    prevBtn.addEventListener('click', function() {
        track.scrollBy({ left: -cardWidth, behavior: 'smooth' });
    });
    
    nextBtn.addEventListener('click', function() {
        track.scrollBy({ left: cardWidth, behavior: 'smooth' });
    });
    
    // Update button states
    function updateButtons() {
        prevBtn.disabled = track.scrollLeft <= 0;
        nextBtn.disabled = track.scrollLeft >= track.scrollWidth - track.clientWidth - 10;
    }
    
    track.addEventListener('scroll', updateButtons);
    updateButtons();
});
</script>
