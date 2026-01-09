<?php
/**
 * Quiz Grid Shortcode with AJAX Loading & Filters
 * Usage: [yuv_quiz_grid per_page="9" category=""]
 */

if (!defined('ABSPATH')) exit;

function yuv_quiz_grid_shortcode($atts) {
    $atts = shortcode_atts([
        'per_page' => 9,
        'category' => '',
    ], $atts, 'yuv_quiz_grid');

    $per_page = intval($atts['per_page']);
    $category_slug = sanitize_text_field($atts['category']);
    
    // Initial page load
    $paged = 1;

    // Build query args
    $args = yuv_build_quiz_query_args($per_page, $paged, $category_slug, 'latest');
    $quiz_query = new WP_Query($args);

    // Get all categories for filter tabs
    $categories = get_terms([
        'taxonomy' => 'quiz_category',
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    // Get total counts
    $total_quizzes = $quiz_query->found_posts;
    $max_pages = $quiz_query->max_num_pages;

    ob_start();
    ?>

    <div class="yuv-quiz-archive" 
         data-per-page="<?php echo esc_attr($per_page); ?>"
         data-category="<?php echo esc_attr($category_slug); ?>"
         data-paged="1"
         data-max-pages="<?php echo esc_attr($max_pages); ?>">
        
        <!-- Filter Bar -->
        <div class="yuv-quiz-filter-bar">
            
            <!-- Category Tabs -->
            <?php if (!empty($categories)): ?>
                <div class="yuv-quiz-filters">
                    <button class="yuv-filter-btn active" data-filter="all">
                        <i class="ri-layout-grid-line"></i>
                        Svi Kvizovi
                    </button>
                    <?php foreach ($categories as $term): ?>
                        <?php
                        $term_color = get_term_meta($term->term_id, 'quiz_category_color', true) ?: '#6A0DAD';
                        $term_count = $term->count;
                        ?>
                        <button 
                            class="yuv-filter-btn" 
                            data-filter="<?php echo esc_attr($term->slug); ?>"
                            style="--filter-color: <?php echo esc_attr($term_color); ?>;">
                            <?php echo esc_html($term->name); ?>
                            <span class="yuv-filter-count"><?php echo esc_html($term_count); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Sort Dropdown -->
            <div class="yuv-quiz-sort">
                <label for="yuv-sort-select">
                    <i class="ri-sort-desc"></i>
                    <span>Sortiraj:</span>
                </label>
                <select id="yuv-sort-select" class="yuv-sort-select">
                    <option value="latest">Najnovije</option>
                    <option value="popular">Najpopularnije</option>
                    <option value="hardest">Najteže</option>
                    <option value="most_questions">Najviše Pitanja</option>
                </select>
            </div>
        </div>

        <!-- Loading Spinner (Hidden by default) -->
        <div class="yuv-quiz-loader" style="display: none;">
            <div class="yuv-spinner"></div>
            <p>Učitavanje kvizova...</p>
        </div>

        <!-- Quiz Grid -->
        <div class="yuv-quiz-grid" id="yuv-quiz-grid">
            <?php
            if ($quiz_query->have_posts()) {
                while ($quiz_query->have_posts()) {
                    $quiz_query->the_post();
                    get_template_part('inc/quizzes/templates/content-quiz-card');
                }
                wp_reset_postdata();
            } else {
                echo '<div class="yuv-quiz-empty">
                    <i class="ri-survey-line" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
                    <p>Nema dostupnih kvizova.</p>
                </div>';
            }
            ?>
        </div>

        <!-- Result Count & Load More -->
        <?php if ($quiz_query->have_posts()): ?>
            <div class="yuv-quiz-footer">
                <div class="yuv-quiz-results">
                    Prikazano 
                    <span id="yuv-visible-count"><?php echo $quiz_query->post_count; ?></span> 
                    od 
                    <span id="yuv-total-count"><?php echo $total_quizzes; ?></span> 
                    kvizova
                </div>

                <?php if ($max_pages > 1): ?>
                    <div class="yuv-quiz-load-more">
                        <button class="yuv-load-more-btn" id="yuv-load-more-btn">
                            <i class="ri-refresh-line"></i>
                            Učitaj još kvizova
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('yuv_quiz_grid', 'yuv_quiz_grid_shortcode');

/**
 * Helper: Build Quiz Query Args
 */
function yuv_build_quiz_query_args($per_page, $paged, $category_slug = '', $sort_by = 'latest') {
    $args = [
        'post_type' => 'quiz',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $paged,
    ];

    // Category filter
    if (!empty($category_slug)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'quiz_category',
                'field' => 'slug',
                'terms' => $category_slug,
            ],
        ];
    }

    // Sorting logic
    switch ($sort_by) {
        case 'popular':
            $args['meta_key'] = '_yuv_quiz_attempts_count';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;

        case 'hardest':
            // Order by difficulty meta or default to date
            $args['meta_key'] = '_quiz_difficulty';
            $args['orderby'] = 'meta_value';
            $args['order'] = 'DESC';
            break;

        case 'most_questions':
            $args['meta_key'] = '_num_questions';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;

        case 'latest':
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }

    return $args;
}
