<?php
/**
 * Template part for Homepage Trending Section
 * Layout: 1 Big Left, 4 Small Right (Grid)
 */

$data = get_query_var('cs_trending_data');
$query = $data['query'];

if (!$query || !$query->have_posts()) return;

// Default Primary Color (YugoVote Blue)
$default_color = '#4456A6'; 
?>

<section class="cs-trending-leaderboard">
    <div class="cs-container">
        
        <div class="cs-leaderboard-grid">
            <?php 
            $rank = 0;
            while ($query->have_posts()) : $query->the_post(); 
                $rank++;
                $list_id = get_the_ID();
                
                // 1. GLASOVI (Live Calculation)
                $score = 0;
                if (function_exists('get_total_score_for_voting_list')) {
                    $score = get_total_score_for_voting_list($list_id);
                } else {
                    // Fallback na meta polje ako funkcija ne postoji
                    $score = get_post_meta($list_id, 'total_score', true);
                }
                
                // 2. KATEGORIJA & BOJA
                $terms = get_the_terms($list_id, 'voting_list_category');
                $term = ($terms && !is_wp_error($terms)) ? $terms[0] : null;
                
                $cat_name = $term ? $term->name : 'YugoVote'; // Default ime ako nema kategorije
                
                // Uzmi boju, ako nema -> stavi Default Primary
                $cat_color = $term ? get_term_meta($term->term_id, 'category_color', true) : '';
                if (empty($cat_color) || $cat_color === '#ffffff') {
                    $cat_color = $default_color;
                }

                // Slika
                $thumbnail = get_the_post_thumbnail_url($list_id, 'large');
                
                // Prva kartica je VELIKA, ostale STANDARD
                $is_hero = ($rank === 1);
                $card_class = $is_hero ? 'cs-lb-card--hero' : 'cs-lb-card--standard';
                $excerpt = get_the_excerpt($list_id);
            ?>

            <a href="<?php the_permalink(); ?>" class="cs-lb-card <?php echo esc_attr($card_class); ?>" style="--accent-color: <?php echo esc_attr($cat_color); ?>">
                
                <div class="cs-lb-rank"><?php echo $rank; ?></div>

                <div class="cs-lb-media">
                    <?php if ($thumbnail) : ?>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title(); ?>">
                    <?php endif; ?>
                    <span class="cs-lb-cat-badge"><?php echo esc_html($cat_name); ?></span>
                </div>

                <div class="cs-lb-content">
                    <h3 class="cs-lb-title"><?php the_title(); ?></h3>
                  
                    <div class="cs-lb-meta">
                        <span class="cs-lb-score">
                            <strong><?php echo number_format_i18n((int)$score); ?></strong> glasova
                        </span>
                    </div>
                </div>

                <div class="cs-lb-overlay"></div>
            </a>

            <?php endwhile; wp_reset_postdata(); ?>
        </div>

    </div>
</section>