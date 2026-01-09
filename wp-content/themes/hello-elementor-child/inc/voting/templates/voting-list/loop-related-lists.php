<?php
/**
 * Template for displaying related lists for a voting item.
 * Expects $lists_query (WP_Query object of voting_list posts)
 * Expects $current_item_id (ID of the main voting item being viewed on the single page)
 */

if (!empty($lists_query) && $lists_query->have_posts()) : 
    // Get the title of the main "Voting Item" post.
    // $current_item_id is available from the shortcode function's scope.
    $main_item_title = '';
    if (isset($current_item_id) && !empty($current_item_id)) {
        $main_item_title = get_the_title($current_item_id);
    }
?>
    <div class="cs-related-lists">
               <h2><?php 
                // Translators: %s is the title of the current voting item.
                printf(
                    esc_html__('Liste na kojima se "%s" pojavljuje', 'your-text-domain'), 
                    esc_html($main_item_title)
                ); 
            ?></h2>
        <?php
        // $current_item_id should be available from the lists_for_current_item_shortcode scope.
    
        while ($lists_query->have_posts()) : $lists_query->the_post();
            $list_id_in_loop = get_the_ID();
            $list_title = get_the_title();
            $list_permalink = get_permalink();

            // 1. Get List Category
            $term_slug = 'default'; 
            $category_name = '';
            $categories = get_the_terms($list_id_in_loop, 'voting_list_category'); 
            if (!empty($categories) && !is_wp_error($categories)) {
                $first_category = array_shift($categories); 
                $category_name = $first_category->name;
                $term_slug = $first_category->slug;
            }

            // 2. Get Rank of the Specific (Main) Item Within This List
            $item_rank_in_this_list = null;
            if (isset($current_item_id) && function_exists('get_item_rank_in_list')) {
                $item_rank_in_this_list = get_item_rank_in_list($current_item_id, $list_id_in_loop);
            }

            // 3. Get Number of Votes for the Specific (Main) Item Within This List
            $item_votes_in_this_list = 0;
            if (isset($current_item_id) && function_exists('get_votes_for_item_in_list')) {
                $item_votes_in_this_list = get_votes_for_item_in_list($current_item_id, $list_id_in_loop);
            }
            ?>
            <article class="cs-related-list-card">
                <a href="<?php echo esc_url($list_permalink); ?>" class="cs-related-list-card__inner">
                    <div class="cs-related-list-card__thumb">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium');  ?>
                        <?php else : ?>
                            <div class="cs-related-list-card__placeholder-thumb"></div>
                        <?php endif; ?>
                   
                        
                        <?php if ($item_rank_in_this_list !== null) : ?>
                            <p class="cs-related-list-card__rank cs-bg--<?php echo esc_attr($term_slug); ?>">
                                <?php echo '#' . esc_attr($item_rank_in_this_list); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="cs-related-list-card__info">
                        <?php if (!empty($category_name)) : ?>
                            <div class="cs-related-list-card__category cs-bg--<?php echo esc_attr($term_slug); ?>">
                                <?php echo esc_html($category_name); ?>
                            </div>
                        <?php endif; ?>
                        <h3 class="cs-related-list-card__title">
                            <?php echo esc_html($list_title); ?>
                        </h3>
    
                        <?php if (isset($current_item_id)) : ?>
                        <p class="cs-voting-card__item-score cs-color--<?php echo esc_attr($term_slug); ?>">
                           <?php echo  esc_attr($item_votes_in_this_list); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </a>
            </article>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    </div>
<?php else : ?>
    <p>Nema povezanih lista.</p>
<?php endif; ?>