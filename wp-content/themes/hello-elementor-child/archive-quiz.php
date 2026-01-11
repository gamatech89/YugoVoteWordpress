<?php
/**
 * Pure PHP Template for Quiz Archive
 * Lists all quizzes with filtering by category
 * Bypasses Elementor completely
 * 
 * @package HelloElementorChild
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

$current_user_id = get_current_user_id();

// Get all quiz categories
$quiz_categories = get_terms([
    'taxonomy' => 'quiz_category',
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC',
]);
if (is_wp_error($quiz_categories)) $quiz_categories = [];

// Get filter from URL
$active_category = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : '';

// Stats
$total_quizzes = wp_count_posts('quiz')->publish;

// User stats if logged in
$user_completed = 0;
$user_total_xp = 0;
if ($current_user_id) {
    global $wpdb;
    $progress_table = $wpdb->prefix . 'ygv_user_quiz_progress';
    
    $user_completed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT quiz_id) FROM {$progress_table} WHERE user_id = %d AND attempts > 0",
        $current_user_id
    ));
    
    $user_total_xp = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(awarded_xp) FROM {$progress_table} WHERE user_id = %d",
        $current_user_id
    ));
}

// Query quizzes
$paged = max(1, get_query_var('paged'));
$query_args = [
    'post_type' => 'quiz',
    'posts_per_page' => 12,
    'paged' => $paged,
    'post_status' => 'publish',
];

if ($active_category) {
    $query_args['tax_query'] = [[
        'taxonomy' => 'quiz_category',
        'field' => 'slug',
        'terms' => $active_category,
    ]];
}

$quizzes_query = new WP_Query($query_args);

// Enqueue styles
wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], '1.0.0');

get_header();
?>

<div class="ygv-page ygv-quiz-archive-page">
    
    <!-- ========== HERO SECTION ========== -->
    <section class="ygv-quiz-hero">
        <div class="ygv-container">
            <h1 class="ygv-quiz-hero__title">🧠 Testiraj svoje znanje</h1>
            <p class="ygv-quiz-hero__desc">
                Izaberi kviz, odgovori na pitanja i osvoji XP poene. Takmičite se sa prijateljima!
            </p>
            
            <?php if ($current_user_id): ?>
            <div style="display: flex; gap: 20px; justify-content: center; margin-top: 24px;">
                <div style="background: rgba(255,255,255,0.2); padding: 12px 24px; border-radius: 12px; text-align: center;">
                    <span style="font-size: 24px; font-weight: 800; display: block;"><?php echo intval($user_completed); ?>/<?php echo $total_quizzes; ?></span>
                    <span style="font-size: 12px; text-transform: uppercase; opacity: 0.8;">Završeno</span>
                </div>
                <div style="background: rgba(255,255,255,0.2); padding: 12px 24px; border-radius: 12px; text-align: center;">
                    <span style="font-size: 24px; font-weight: 800; display: block;"><?php echo number_format(intval($user_total_xp)); ?></span>
                    <span style="font-size: 12px; text-transform: uppercase; opacity: 0.8;">Ukupno XP</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- ========== CATEGORY FILTERS ========== -->
    <?php if (!empty($quiz_categories)): ?>
    <section style="padding: 0 20px; margin-top: -40px; position: relative; z-index: 10;">
        <div class="ygv-container" style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
            <a href="<?php echo remove_query_arg('cat'); ?>" 
               class="ygv-filter-btn <?php echo empty($active_category) ? 'active' : ''; ?>">
                Sve kategorije
            </a>
            <?php foreach ($quiz_categories as $cat):
                $cat_color = get_term_meta($cat->term_id, 'category_color', true) ?: '#6366f1';
            ?>
            <a href="<?php echo add_query_arg('cat', $cat->slug); ?>" 
               class="ygv-filter-btn <?php echo $active_category === $cat->slug ? 'active' : ''; ?>"
               style="<?php echo $active_category === $cat->slug ? "background: {$cat_color}; border-color: {$cat_color};" : ''; ?>">
                <?php echo esc_html($cat->name); ?>
                <span style="opacity: 0.7; margin-left: 4px;">(<?php echo $cat->count; ?>)</span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- ========== QUIZ GRID ========== -->
    <?php if ($quizzes_query->have_posts()): ?>
    <section style="padding: 40px 20px 60px;">
        <div class="ygv-quiz-grid">
            <?php while ($quizzes_query->have_posts()): $quizzes_query->the_post();
                $quiz_id = get_the_ID();
                
                // Quiz meta
                $num_questions = get_post_meta($quiz_id, '_num_questions', true) ?: 10;
                $time_per_question = get_post_meta($quiz_id, '_time_per_question', true) ?: 10;
                $quiz_difficulty_id = get_post_meta($quiz_id, '_quiz_difficulty', true);
                $quiz_difficulty = '';
                if ($quiz_difficulty_id) {
                    $difficulty_post = get_post($quiz_difficulty_id);
                    if ($difficulty_post) $quiz_difficulty = $difficulty_post->post_title;
                }
                
                // Category
                $terms = get_the_terms($quiz_id, 'quiz_category');
                $cat_name = 'Opšte';
                $cat_color = '#6366f1';
                if ($terms && !is_wp_error($terms)) {
                    $cat_name = $terms[0]->name;
                    $cat_color = get_term_meta($terms[0]->term_id, 'category_color', true) ?: '#6366f1';
                }
                
                // Time
                $total_time = ceil(($num_questions * $time_per_question) / 60);
                
                // User progress
                $best_percent = 0;
                $attempts = 0;
                $is_completed = false;
                if ($current_user_id) {
                    global $wpdb;
                    $progress_table = $wpdb->prefix . 'ygv_user_quiz_progress';
                    $progress = $wpdb->get_row($wpdb->prepare(
                        "SELECT best_percent, attempts FROM {$progress_table} WHERE user_id = %d AND quiz_id = %d",
                        $current_user_id, $quiz_id
                    ));
                    if ($progress) {
                        $best_percent = intval($progress->best_percent);
                        $attempts = intval($progress->attempts);
                        $is_completed = $attempts > 0;
                    }
                }
            ?>
            <article class="ygv-quiz-card" data-quiz-id="<?php echo $quiz_id; ?>">
                <a href="<?php the_permalink(); ?>" style="text-decoration: none; color: inherit;">
                    <div class="ygv-quiz-card__image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium_large'); ?>
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, <?php echo esc_attr($cat_color); ?> 0%, #1e2a5e 100%); display: flex; align-items: center; justify-content: center;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="rgba(255,255,255,0.3)">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Category Badge -->
                        <span style="position: absolute; top: 12px; left: 12px; padding: 4px 12px; background: <?php echo esc_attr($cat_color); ?>; color: white; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px;">
                            <?php echo esc_html($cat_name); ?>
                        </span>
                        
                        <!-- Difficulty Badge -->
                        <?php if ($quiz_difficulty): 
                            $diff_class = strtolower($quiz_difficulty);
                            $diff_colors = [
                                'lako' => '#10b981',
                                'easy' => '#10b981',
                                'srednje' => '#f59e0b',
                                'medium' => '#f59e0b',
                                'teško' => '#ef4444',
                                'hard' => '#ef4444',
                            ];
                            $diff_color = $diff_colors[$diff_class] ?? '#6366f1';
                        ?>
                        <span class="ygv-quiz-card__difficulty" style="background: <?php echo esc_attr($diff_color); ?>;">
                            <?php echo esc_html($quiz_difficulty); ?>
                        </span>
                        <?php endif; ?>
                        
                        <!-- Completed Indicator -->
                        <?php if ($is_completed): ?>
                        <div style="position: absolute; bottom: 12px; left: 12px; padding: 6px 12px; background: rgba(16, 185, 129, 0.9); color: white; font-size: 12px; font-weight: 600; border-radius: 6px; display: flex; align-items: center; gap: 4px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            <?php echo $best_percent; ?>%
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ygv-quiz-card__body">
                        <h3 class="ygv-quiz-card__title"><?php the_title(); ?></h3>
                        
                        <div class="ygv-quiz-card__meta">
                            <span class="ygv-quiz-card__meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M4 10h5V5H4v5zm6 9h5v-5h-5v5zm-6 0h5v-5H4v5zm0-6h5v-5H4v5zm6 0h5v-5h-5v5zm6-4v5h5v-5h-5zm-6-4v5h5V5h-5zm6 4h5V5h-5v4zm0 9h5v-5h-5v5z"/></svg>
                                <?php echo $num_questions; ?> pitanja
                            </span>
                            <span class="ygv-quiz-card__meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.2 14.2L11 13V7h1.5v5.2l4.5 2.7-.8 1.3z"/></svg>
                                ~<?php echo $total_time; ?> min
                            </span>
                            <?php if ($attempts > 0): ?>
                            <span class="ygv-quiz-card__meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                                <?php echo $attempts; ?>x odigrano
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </article>
            <?php endwhile; ?>
        </div>
        
        <?php
        // Pagination
        $total_pages = $quizzes_query->max_num_pages;
        if ($total_pages > 1):
        ?>
        <nav class="ygv-pagination" style="max-width: 1200px; margin: 40px auto 0; display: flex; justify-content: center; gap: 8px;">
            <?php
            echo paginate_links([
                'total' => $total_pages,
                'current' => $paged,
                'prev_text' => '‹ Prethodna',
                'next_text' => 'Sledeća ›',
                'type' => 'list',
            ]);
            ?>
        </nav>
        <style>
            .ygv-pagination ul { list-style: none; padding: 0; margin: 0; display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
            .ygv-pagination li { display: inline; }
            .ygv-pagination a, .ygv-pagination span { padding: 10px 16px; background: white; border: 1px solid var(--ygv-border); border-radius: 8px; text-decoration: none; color: var(--ygv-text); font-weight: 500; transition: all 0.2s; }
            .ygv-pagination a:hover { background: var(--ygv-primary); color: white; border-color: var(--ygv-primary); }
            .ygv-pagination .current { background: var(--ygv-primary); color: white; border-color: var(--ygv-primary); }
        </style>
        <?php endif; ?>
        
    </section>
    <?php 
    wp_reset_postdata();
    else: 
    ?>
    <section style="padding: 80px 20px; text-align: center;">
        <div class="ygv-container">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="var(--ygv-text-muted)" style="margin-bottom: 24px; opacity: 0.3;">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/>
            </svg>
            <h2 style="font-size: 24px; font-weight: 700; margin: 0 0 8px; color: var(--ygv-text);">Nema kvizova</h2>
            <p style="font-size: 16px; color: var(--ygv-text-muted); margin: 0;">
                <?php echo $active_category ? 'Nema kvizova u ovoj kategoriji.' : 'Kvizovi će uskoro biti dostupni!'; ?>
            </p>
        </div>
    </section>
    <?php endif; ?>
    
</div>

<?php get_footer(); ?>
