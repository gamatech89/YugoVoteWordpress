<?php
// Ensure this file is accessed within WordPress
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: Display Voting List
 * Usage: [voting_list id="1975"]
 */
function voting_list_shortcode($atts) {
    $atts = shortcode_atts(['id' => ''], $atts, 'voting_list');

    if (empty($atts['id'])) {
        return "Invalid voting list ID.";
    }

    // Pass the voting list ID to the template
    set_query_var('voting_list_id', intval($atts['id']));

    // Start output buffering
    ob_start();
    get_template_part('inc/voting/templates/voting-list-template');
    return ob_get_clean();
}
add_shortcode('voting_list', 'voting_list_shortcode');


/**
 * Shortcode: Display Voting List based on the current post ID
 * Usage: [voting_list_single] or [voting_list_single layout="grid|compact|classic" version="v2"]
 */
function single_voting_list($atts) {
    $atts = shortcode_atts([
        'layout'  => '', // grid, compact, classic (empty = use post meta or default)
        'version' => '', // v2 to use new template, empty for legacy
    ], $atts, 'voting_list_single');
    
    // Get the current post ID
    $post_id = get_the_ID();

    // Check if we are inside the loop
    if (!$post_id) {
        return "No post ID found.";
    }

    // Pass the current post ID to the template
    set_query_var('voting_list_id', $post_id);
    
    // Pass layout preference (URL param > shortcode attr > post meta)
    $layout = isset($_GET['layout']) ? sanitize_text_field($_GET['layout']) : $atts['layout'];
    if ($layout) {
        set_query_var('voting_layout_override', $layout);
    }

    // Start output buffering
    ob_start();
    
    // Use v2 template if specified or if URL param set
    $use_v2 = $atts['version'] === 'v2' || isset($_GET['v2']);
    if ($use_v2) {
        get_template_part('inc/voting/templates/voting-list/voting-list-template-v2');
    } else {
        get_template_part('inc/voting/templates/voting-list/voting-list-template');
    }
    
    return ob_get_clean();
}
add_shortcode('voting_list_single', 'single_voting_list');

/**
 * Shortcode: Display Voting List with V2 template (for testing)
 * Usage: [voting_list_v2] or [voting_list_v2 layout="compact"]
 */
function voting_list_v2_shortcode($atts) {
    $atts = shortcode_atts([
        'layout'       => '', // grid, compact, classic
        'show_switcher' => 'true', // Show layout switcher UI
    ], $atts, 'voting_list_v2');
    
    $post_id = get_the_ID();
    if (!$post_id) {
        return "No post ID found.";
    }

    set_query_var('voting_list_id', $post_id);
    
    // Pass layout preference
    $layout = isset($_GET['layout']) ? sanitize_text_field($_GET['layout']) : $atts['layout'];
    if ($layout) {
        set_query_var('voting_layout_override', $layout);
    }
    
    set_query_var('show_layout_switcher', $atts['show_switcher'] === 'true');

    ob_start();
    get_template_part('inc/voting/templates/voting-list/voting-list-template-v2');
    return ob_get_clean();
}
add_shortcode('voting_list_v2', 'voting_list_v2_shortcode');

/**
 * Shortcode: Full Page Voting List Template
 * Complete redesigned page with hero, list, and floating "My Votes" panel
 * Usage: [voting_list_fullpage]
 */
function voting_list_fullpage_shortcode($atts) {
    $post_id = get_the_ID();
    if (!$post_id) {
        return "No post ID found.";
    }

    set_query_var('voting_list_id', $post_id);

    // Enqueue the fullpage CSS
    wp_enqueue_style(
        'voting-fullpage-css',
        get_stylesheet_directory_uri() . '/css/voting-fullpage.css',
        [],
        HELLO_ELEMENTOR_CHILD_VERSION
    );

    ob_start();
    get_template_part('inc/voting/templates/voting-list/voting-list-fullpage');
    return ob_get_clean();
}
add_shortcode('voting_list_fullpage', 'voting_list_fullpage_shortcode');

function shortcode_voting_list_total_score($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $voting_list_id = intval($atts['id']) ?: get_the_ID();

    $score = get_total_score_for_voting_list($voting_list_id);
    return '<span class="cs-list-total__score">' . $score . '</span>';
}

add_shortcode('voting_list_total_score', 'shortcode_voting_list_total_score');



/**
 * Shortcode: Display lists that contain the current 'voting_items' post.
 *
 * This shortcode is intended for use on the single page of a 'voting_items' CPT.
 * It queries 'voting_list' posts to find those where the current item's ID
 * is present in the '_voting_items' post meta field (a serialized array).
 *
 * Shortcode: [lists_with_this_item]
 *
 * @param array $atts Shortcode attributes (not currently used in the logic).
 * @return string HTML output of related lists or a 'no lists found' message.
 */
function lists_for_current_item_shortcode($atts) {
    if (!is_singular('voting_items')) {
        return '';
    }

    $current_item_id = (string) get_the_ID();

    if (!$current_item_id) {
        return '<p>Could not determine the current item ID.</p>';
    }

    $args = [
        'post_type'      => 'voting_list',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => '_voting_items',
                'value'   => '"' . $current_item_id . '"',
                'compare' => 'LIKE'
            ],
        ]
    ];

    $lists_query = new WP_Query($args);

    ob_start();
    if ($lists_query->have_posts()) {
        include get_stylesheet_directory() . '/inc/voting/templates/voting-list/loop-related-lists.php';
    } else {
        echo '<p>Nema lista za ovaj predmet.</p>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('lists_with_this_item', 'lists_for_current_item_shortcode');

/**
 * Get the rank of a specific item within a specific voting list based on vote scores.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $item_id_to_rank The ID of the voting item whose rank is needed.
 * @param int $list_id         The ID of the voting list.
 * @return int|null            The rank of the item (e.g., 1, 2, 3), or null if not found or not applicable.
 * Rank is 1-based. Items with the same score get the same rank (standard ranking).
 */
function get_item_rank_in_list($item_id_to_rank, $list_id) {
    global $wpdb;

    $item_id_to_rank = intval($item_id_to_rank);
    $list_id = intval($list_id);

    // 1. Get all item IDs for this list from its post meta

    $list_item_ids_meta = get_post_meta($list_id, '_voting_items', true);

    if (empty($list_item_ids_meta) || !is_array($list_item_ids_meta)) {
        return null; // List has no items or meta format is unexpected
    }
    // Ensure item IDs are integers if they were stored as strings
    $list_item_ids = array_map('intval', $list_item_ids_meta);

    // Check if the item we want to rank is actually in this list's meta
    if (!in_array($item_id_to_rank, $list_item_ids)) {
         return null; 
    }

    $item_scores = [];
    // 2. Get scores for each item in this list using your existing helper
    if (!function_exists('get_votes_for_item_in_list')) {
        // If this crucial helper is missing, we can't calculate ranks.
        // You should have added this function in a previous step.
        error_log('Error: get_votes_for_item_in_list() function is missing.');
        return null;
    }

    foreach ($list_item_ids as $item_id_from_list) {
        $item_scores[$item_id_from_list] = get_votes_for_item_in_list($item_id_from_list, $list_id);
    }

    // 3. Sort items by score, descending. Higher score = better rank.
    arsort($item_scores); // Sorts an array by values in reverse order, maintaining keys (item IDs)

    // 4. Find the rank of $item_id_to_rank
    $rank = 0;
    $current_position = 0; // How many items processed
    $previous_score = -1;  // Initialize with an impossible score

    foreach ($item_scores as $id => $score) {
        $current_position++;
        if ($score !== $previous_score) { // If score is different from previous, it's a new rank
            $rank = $current_position;
            $previous_score = $score;
        }
        // If current iteration is the item we're looking for, return its determined rank
        if ($id === $item_id_to_rank) {
            return $rank;
        }
    }

    return null; // Should have been found if it was in $list_item_ids and had a score.
}

if (!function_exists('shortcode_assigned_child_list_categories')) { // Renamed
    /**
     * Shortcode to display 'voting_list_category' terms that are:
     * 1. Directly assigned to the current 'voting_list' post.
     * 2. Are themselves child categories (i.e., they have a parent).
     * The container div is styled based on the slug of the parent of the 
     * first such assigned child category found.
     *
     * Usage: [assigned_child_list_categories] (Intended for use on single 'voting_list' pages)
     *
     * @param array  $atts Shortcode attributes (currently not used).
     * @return string HTML output.
     */
    function shortcode_assigned_child_list_categories($atts) { // Renamed
        $current_voting_list_id = get_the_ID();

        if (!$current_voting_list_id || get_post_type($current_voting_list_id) !== 'voting_list') {
            if (current_user_can('edit_posts')) {
                return '';
            }
            return '';
        }

        $assigned_terms = get_the_terms($current_voting_list_id, 'voting_list_category');

        if (empty($assigned_terms) || is_wp_error($assigned_terms)) {
            return '';
        }

        $assigned_child_categories = [];
        foreach ($assigned_terms as $term) {
            if (isset($term->parent) && $term->parent != 0) {
                $assigned_child_categories[$term->term_id] = $term;
            }
        }

        if (empty($assigned_child_categories)) {
            return '';
        }

        uasort($assigned_child_categories, function($a, $b) {
            return strcmp(strtolower($a->name), strtolower($b->name));
        });

        $parent_slug_for_styling = 'default';
        $first_assigned_child_term = reset($assigned_child_categories); 

        if ($first_assigned_child_term && isset($first_assigned_child_term->parent) && $first_assigned_child_term->parent != 0) {
            // Use the renamed helper function if you defined it, or get slug directly
            if (function_exists('get_term_slug_or_default')) {
                 $parent_slug_for_styling = get_term_slug_or_default($first_assigned_child_term->parent, 'voting_list_category');
            } else {
                // Fallback if helper doesn't exist (less robust)
                $parent_term = get_term($first_assigned_child_term->parent, 'voting_list_category');
                if ($parent_term && !is_wp_error($parent_term) && isset($parent_term->slug)) {
                    $parent_slug_for_styling = $parent_term->slug;
                }
            }
        }
        
     ob_start();
        ?> 
        <div class="cs-category-list">
            <?php foreach ($assigned_child_categories as $child_term_to_display) : ?>
                <?php
                $child_category_link = get_term_link($child_term_to_display, 'voting_list_category');
                
                if (is_wp_error($child_category_link)) {
                    // Fallback: Output name as plain text (inside a span for consistency if needed, or just text)
                    echo '<span class="cs-bg--' . esc_attr($parent_slug_for_styling) . '">' . esc_html($child_term_to_display->name) . '</span>';
                } else {
                    // Output the child category name as a direct link
                    echo '<a href="' . esc_url($child_category_link) . '" class="cs-category-list__link cs-bg--' . esc_attr($parent_slug_for_styling) . '">' . esc_html($child_term_to_display->name) . '</a>';
                }
                ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    // Register the shortcode with the new function name
    add_shortcode('assigned_child_list_categories', 'shortcode_assigned_child_list_categories');
}

if (!function_exists('cs_voting_mega_menu_shortcode')) {
    function cs_voting_mega_menu_shortcode() {
        // Try cached menu first (invalidated on vote changes)
        $cached = get_transient('ygv_mega_menu_html');
        if ($cached !== false) {
            return $cached;
        }

        $parent_terms = get_terms([
            'taxonomy'   => 'voting_list_category',
            'parent'     => 0,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (empty($parent_terms) || is_wp_error($parent_terms)) {
            return '';
        }

        // Provera trenutnog objekta za aktivnu klasu
        $current_object = get_queried_object();
        $current_term_id = 0;
        if (isset($current_object->term_id)) {
            $current_term_id = $current_object->term_id;
        }

        ob_start();
        ?>
        <div class="cs-mega-menu-wrapper">
            <button class="cs-mobile-menu-toggle" aria-label="Otvori Meni">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <nav class="cs-mega-menu-nav">
                <button class="cs-mobile-menu-close" aria-label="Zatvori Meni">&times;</button>
                
                <div class="cs-mobile-logo-area">
                    <span class="cs-menu-title">MENI</span>
                </div>

                <ul class="cs-menu-list">
                    <?php 
                    // Add Top Navigation links to mobile menu
                    $top_menu_items = wp_get_nav_menu_items(13);
                    if ($top_menu_items) :
                        foreach ($top_menu_items as $item) : ?>
                            <li class="cs-menu-item cs-menu-item--topbar desktop-hide" style="--cat-color: var(--ygv-primary);">
                                <div class="cs-mobile-link-wrapper">
                                    <a href="<?php echo esc_url($item->url); ?>" class="cs-menu-link">
                                        <span class="cs-link-text"><?php echo esc_html($item->title); ?></span>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <li class="cs-menu-divider desktop-hide"></li>
                    <?php endif; ?>

                    <?php foreach ($parent_terms as $parent) : ?>
                        <?php
                        $parent_link = get_term_link($parent);
                        $term_id = $parent->term_id;
                        $color = get_term_meta($term_id, 'category_color', true) ?: '#333';
                        $logo_id = get_term_meta($term_id, 'category_logo', true);
                        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : ''; 

                        $child_terms = get_terms([
                            'taxonomy'   => 'voting_list_category',
                            'parent'     => $term_id,
                            'hide_empty' => false,
                        ]);

                        $has_children = !empty($child_terms) && !is_wp_error($child_terms);

                        // Get trending lists from this category
                        $trending_lists = [];
                        if ($has_children) {
                            global $wpdb;
                            $votes_table = $wpdb->prefix . 'voting_list_votes';
                            
                            // Get lists with most votes in this category
                            $category_term_ids = array_merge([$term_id], array_map(fn($c) => $c->term_id, $child_terms));
                            $placeholders = implode(',', array_fill(0, count($category_term_ids), '%d'));
                            
                            $top_voted_lists = $wpdb->get_results($wpdb->prepare(
                                "SELECT p.ID, p.post_title, COUNT(v.id) as vote_count
                                FROM {$wpdb->posts} p
                                INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                                LEFT JOIN {$votes_table} v ON p.ID = v.voting_list_id
                                WHERE p.post_type = 'voting_list' 
                                AND p.post_status = 'publish'
                                AND tr.term_taxonomy_id IN ($placeholders)
                                GROUP BY p.ID
                                ORDER BY vote_count DESC, p.post_date DESC
                                LIMIT 3",
                                ...$category_term_ids
                            ));
                            $trending_lists = $top_voted_lists;
                        }

                        // Provera da li je ova kategorija (ili njeno dete) aktivna
                        $is_active = ($term_id === $current_term_id);
                        if (!$is_active && $has_children && $current_term_id) {
                            foreach ($child_terms as $child) {
                                if ($child->term_id === $current_term_id) {
                                    $is_active = true;
                                    break;
                                }
                            }
                        }
                        
                        // Use wp_count_posts equivalent via taxonomy count
                        $total_lists = 0;
                        if ($has_children) {
                            $all_child_ids = array_merge([$term_id], wp_list_pluck($child_terms, 'term_id'));
                            foreach ($all_child_ids as $tid) {
                                $t = get_term($tid, 'voting_list_category');
                                if ($t && !is_wp_error($t)) {
                                    $total_lists += $t->count;
                                }
                            }
                        } else {
                            $t = get_term($term_id, 'voting_list_category');
                            $total_lists = ($t && !is_wp_error($t)) ? $t->count : 0;
                        }
                        ?>

                        <li class="cs-menu-item <?php echo $has_children ? 'has-mega-menu' : ''; ?> <?php echo $is_active ? 'current-menu-item' : ''; ?>" 
                            style="--cat-color: <?php echo esc_attr($color); ?>">
                            
                            <div class="cs-mobile-link-wrapper">
                                <a href="<?php echo esc_url($parent_link); ?>" class="cs-menu-link">
                                    <span class="cs-link-text"><?php echo esc_html(strtoupper($parent->name)); ?></span>
                                </a>
                                <?php if ($has_children) : ?>
                                    <span class="cs-mobile-dropdown-trigger">▼</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($has_children) : ?>
                                <div class="cs-mega-menu-dropdown">
                                    <div class="cs-mega-menu-inner">
                                        <div class="cs-dropdown-brand" style="background-color: <?php echo esc_attr($color); ?>">
                                            <div class="cs-brand-content">
                                                <?php if ($logo_url) : ?>
                                                    <div class="cs-mascot-circle">
                                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($parent->name); ?>">
                                                    </div>
                                                <?php endif; ?>
                                                <h3><?php echo esc_html($parent->name); ?></h3>
                                                
                                                <div class="cs-brand-stats">
                                                    <div class="cs-brand-stat">
                                                        <span class="cs-brand-stat__value"><?php echo $total_lists; ?></span>
                                                        <span class="cs-brand-stat__label">Lista</span>
                                                    </div>
                                                    <div class="cs-brand-stat">
                                                        <span class="cs-brand-stat__value"><?php echo count($child_terms); ?></span>
                                                        <span class="cs-brand-stat__label">Kategorija</span>
                                                    </div>
                                                </div>
                                                
                                                <a href="<?php echo esc_url($parent_link); ?>" class="cs-brand-btn">
                                                    Istraži Sve
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                                </a>
                                            </div>
                                            <div class="cs-brand-curve"></div>
                                        </div>

                                        <div class="cs-dropdown-content">
                                            <!-- Subcategories as Cards -->
                                            <div class="cs-subcats-section">
                                                <span class="cs-section-label">KATEGORIJE</span>
                                                <div class="cs-subcats-grid">
                                                    <?php foreach ($child_terms as $child) : 
                                                        // Use term count instead of expensive WP_Query
                                                        $list_count = $child->count;
                                                    ?>
                                                        <a href="<?php echo esc_url(get_term_link($child)); ?>" class="cs-subcat-card">
                                                            <span class="cs-subcat-name"><?php echo esc_html($child->name); ?></span>
                                                            <span class="cs-subcat-count"><?php echo $list_count; ?></span>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($trending_lists)) : ?>
                                            <!-- Top Voted Lists -->
                                            <div class="cs-trending-section">
                                                <span class="cs-section-label">🏆 NAJVIŠE GLASOVA</span>
                                                <div class="cs-trending-list">
                                                    <?php foreach ($trending_lists as $tlist) : 
                                                        $thumb = get_the_post_thumbnail_url($tlist->ID, 'thumbnail');
                                                        $votes = intval($tlist->vote_count);
                                                    ?>
                                                        <a href="<?php echo get_permalink($tlist->ID); ?>" class="cs-trending-item">
                                                            <div class="cs-trending-thumb" style="<?php echo $thumb ? 'background-image:url('.esc_url($thumb).')' : 'background:'.esc_attr($color).'20'; ?>">
                                                                <?php if (!$thumb): ?>
                                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="<?php echo esc_attr($color); ?>"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="cs-trending-info">
                                                                <span class="cs-trending-title"><?php echo esc_html(wp_trim_words($tlist->post_title, 4)); ?></span>
                                                                <span class="cs-trending-votes"><?php echo number_format($votes); ?> glasova</span>
                                                            </div>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div class="cs-mobile-overlay"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.cs-mobile-menu-toggle').click(function() {
                $('.cs-mega-menu-nav').addClass('active');
                $('.cs-mobile-overlay').addClass('active');
                $('body').css('overflow', 'hidden');
            });

            $('.cs-mobile-menu-close, .cs-mobile-overlay').click(function() {
                $('.cs-mega-menu-nav').removeClass('active');
                $('.cs-mobile-overlay').removeClass('active');
                $('body').css('overflow', '');
            });

            $('.cs-mobile-dropdown-trigger').click(function(e) {
                e.preventDefault();
                $(this).toggleClass('open');
                $(this).closest('.cs-menu-item').find('.cs-mega-menu-dropdown').slideToggle(300);
            });
        });
        </script>
        <?php
        $output = ob_get_clean();
        
        // Cache for 15 minutes (invalidated on vote changes)
        set_transient('ygv_mega_menu_html', $output, 15 * MINUTE_IN_SECONDS);
        
        return $output;
    }
    add_shortcode('voting_mega_menu', 'cs_voting_mega_menu_shortcode');
}


if (!function_exists('cs_homepage_categories_slider_shortcode')) {
    function cs_homepage_categories_slider_shortcode() {
        
        // 1. Uzmi sve glavne kategorije
        $categories = get_terms([
            'taxonomy'   => 'voting_list_category',
            'parent'     => 0,
            'hide_empty' => false,
            'orderby'    => 'id', 
            'order'      => 'ASC',
        ]);

        if (empty($categories) || is_wp_error($categories)) {
            return '';
        }

        // 2. Prosledi podatke template-u
        set_query_var('cs_home_categories', $categories);

        ob_start();
        
        // 3. Učitaj Template fajl
        $template_path = get_stylesheet_directory() . '/inc/voting/templates/global/hero-section.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo "";
        }

        return ob_get_clean();
    }
    add_shortcode('homepage_categories_slider', 'cs_homepage_categories_slider_shortcode');
}
if (!function_exists('cs_voting_trending_shortcode')) {
    function cs_voting_trending_shortcode($atts) {
        $atts = shortcode_atts([
            'count' => 9, // Preporuka: 9 za pun grid (1 velika + 8 malih)
        ], $atts);

        // Use batch-fetched scores (single query + transient)
        // instead of calling get_total_score_for_voting_list() 491 times
        $all_scores = function_exists('ygv_get_all_list_scores') 
            ? ygv_get_all_list_scores() 
            : [];

        if (empty($all_scores)) {
            return '';
        }

        // Get non-tournament list IDs
        $all_lists_args = [
            'post_type'      => 'voting_list',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        $all_list_ids = get_posts($all_lists_args);
        if (empty($all_list_ids)) return '';

        // Filter scores to only non-tournament lists
        $list_scores = [];
        foreach ($all_list_ids as $list_id) {
            $list_scores[$list_id] = $all_scores[$list_id] ?? 0;
        }

        // Sort by score descending
        arsort($list_scores);

        // Take top X
        $top_ids = array_slice(array_keys($list_scores), 0, $atts['count']);

        if (empty($top_ids)) return '';

        $final_args = [
            'post_type' => 'voting_list',
            'post__in'  => $top_ids,
            'orderby'   => 'post__in',
        ];

        $query = new WP_Query($final_args);

        // Pass data to template
        set_query_var('cs_trending_data', [
            'query' => $query
        ]);

        ob_start();
        
        // Load Template
        $template_path = get_stylesheet_directory() . '/inc/voting/templates/global/trending-section.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
             // Fallback
             $fallback_path = get_stylesheet_directory() . '/inc/voting/templates-part/home/trending-section.php';
             if (file_exists($fallback_path)) include $fallback_path;
        }

        wp_reset_postdata();
        return ob_get_clean();
    }
    add_shortcode('voting_trending', 'cs_voting_trending_shortcode');
}
/**
 * Shortcode: Display Top Categories with Zigzag Layout
 * Usage: [voting_top_categories]
 * 
 * Shows all top-level categories with:
 * - Large circular mascot/logo
 * - Top voting list per category
 * - Alternating left/right layout
 */
if (!function_exists('cs_voting_top_categories_shortcode')) {
    function cs_voting_top_categories_shortcode() {
        ob_start();
        
        $template_path = get_stylesheet_directory() . '/inc/voting/templates/partials/archive-top-categories.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        }
        
        return ob_get_clean();
    }
    add_shortcode('voting_top_categories', 'cs_voting_top_categories_shortcode');
}

/**
 * Shortcode: VIP Section for Homepage
 * Usage: [voting_vip_section]
 * Displays featured VIP persons with their curated list counts.
 */
function cs_voting_vip_section_shortcode() {
    ob_start();

    $template_path = get_stylesheet_directory() . '/inc/voting/templates/global/vip-section.php';
    if (file_exists($template_path)) {
        include $template_path;
    }

    return ob_get_clean();
}
add_shortcode('voting_vip_section', 'cs_voting_vip_section_shortcode');

/**
 * Shortcode: VIP Lists for Homepage
 * Usage: [voting_vip_lists]
 * Displays VIP-curated voting lists in a grid with VIP person info.
 */
function cs_voting_vip_lists_shortcode() {
    ob_start();

    $template_path = get_stylesheet_directory() . '/inc/voting/templates/global/vip-lists-section.php';
    if (file_exists($template_path)) {
        include $template_path;
    }

    return ob_get_clean();
}
add_shortcode('voting_vip_lists', 'cs_voting_vip_lists_shortcode');
