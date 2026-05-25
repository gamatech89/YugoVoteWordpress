<?php
/**
 * Pure PHP Template for Quiz Archive
 * Lists all quizzes with filtering by category
 * Bypasses Elementor completely
 * 
 * @package HelloElementorChild
 */

// Prevent direct access
if (!defined('ABSPATH'))
    exit;

$current_user_id = get_current_user_id();

// Get all quiz categories
$quiz_categories = get_terms([
    'taxonomy' => 'quiz_category',
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC',
]);
if (is_wp_error($quiz_categories))
    $quiz_categories = [];

// Get filter from URL
$active_category   = isset($_GET['cat'])  ? sanitize_text_field($_GET['cat'])  : '';
$active_difficulty = isset($_GET['diff']) ? intval($_GET['diff'])               : 0;

// Get quiz levels for difficulty filter
$quiz_levels = get_posts([
    'post_type'   => 'quiz_levels',
    'numberposts' => -1,
    'post_status' => 'publish',
    'orderby'     => 'menu_order title',
    'order'       => 'ASC',
]);

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
    $query_args['tax_query'] = [
        [
            'taxonomy' => 'quiz_category',
            'field'    => 'slug',
            'terms'    => $active_category,
        ]
    ];
}

if ($active_difficulty) {
    $query_args['meta_query'] = [
        [
            'key'     => '_quiz_difficulty',
            'value'   => $active_difficulty,
            'compare' => '=',
            'type'    => 'NUMERIC',
        ]
    ];
}

$quizzes_query = new WP_Query($query_args);

// Enqueue styles
wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], '1.0.0');

get_header();
?>

<div class="ygv-page ygv-quiz-archive-page">

    <!-- ========== HERO SECTION ========== -->
    <section class="ygv-page-hero ygv-page-hero--quiz">
        <div class="ygv-page-hero__inner">
            <div class="ygv-page-hero__label">
                🧠 Kvizovi
            </div>
            <h1 class="ygv-page-hero__title">Pokaži šta znaš</h1>
            <p class="ygv-page-hero__desc">
                Registruj se i igraj. Dobrim rezultatima ostvaruješ benefite a tvoj glas vredi daleko više. Napokon.
            </p>
            <div class="ygv-page-hero__stats">
                <div class="ygv-page-hero__stat">
                    <span class="ygv-page-hero__stat-value"><?php echo intval($total_quizzes); ?></span>
                    <span class="ygv-page-hero__stat-label">Kvizova</span>
                </div>
                <?php if ($current_user_id): ?>
                    <div class="ygv-page-hero__stat">
                        <span
                            class="ygv-page-hero__stat-value"><?php echo intval($user_completed); ?>/<?php echo $total_quizzes; ?></span>
                        <span class="ygv-page-hero__stat-label">Završeno</span>
                    </div>
                    <div class="ygv-page-hero__stat">
                        <span class="ygv-page-hero__stat-value"><?php echo number_format(intval($user_total_xp)); ?></span>
                        <span class="ygv-page-hero__stat-label">Ukupno XP</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="ygv-page-hero__wave">
            <svg viewBox="0 0 1440 48" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,48 C360,0 1080,0 1440,48 L1440,48 L0,48 Z" fill="#ffffff" />
            </svg>
        </div>
    </section>

    <!-- ========== CATEGORY FILTERS ========== -->
    <?php if (!empty($quiz_categories)): ?>
        <section class="ygv-quiz-filters">
            <div class="ygv-container">
                <div class="ygv-filter-row"
                    id="ygv-archive-filters"
                    data-nonce="<?php echo wp_create_nonce('ygv_archive_filter'); ?>">
                    <div class="ygv-filter-buttons">
                        <a href="#"
                            class="ygv-filter-btn <?php echo empty($active_category) ? 'active' : ''; ?>"
                            data-cat="">
                            <?php ygv_icon_e('grid', 16); ?>
                            Svi Kvizovi
                        </a>
                        <?php foreach ($quiz_categories as $cat):
                            $cat_color = function_exists('ygv_get_unified_category_color')
                                ? ygv_get_unified_category_color($cat->term_id)
                                : '#6366f1';
                            $is_active = $active_category === $cat->slug;
                            ?>
                            <a href="#"
                                class="ygv-filter-btn <?php echo $is_active ? 'active' : ''; ?>"
                                data-cat="<?php echo esc_attr($cat->slug); ?>"
                                data-color="<?php echo esc_attr($cat_color); ?>"
                                style="<?php echo $is_active ? "background:{$cat_color};border-color:{$cat_color};" : ''; ?>">
                                <?php echo esc_html($cat->name); ?>
                                <span class="ygv-filter-count"><?php echo $cat->count; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($quiz_levels)): ?>
                    <div class="ygv-diff-filter">
                        <select id="ygv-diff-select" class="ygv-diff-select">
                            <option value="">Sve težine</option>
                            <?php foreach ($quiz_levels as $level): ?>
                            <option value="<?php echo $level->ID; ?>" <?php selected($active_difficulty, $level->ID); ?>>
                                <?php echo esc_html($level->post_title); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ========== QUIZ GRID ========== -->
    <?php if ($quizzes_query->have_posts()): ?>
        <section class="ygv-quiz-section" id="ygv-quiz-section">
            <div class="ygv-container">
                <div class="ygv-quiz-grid" id="ygv-archive-grid">
                    <?php while ($quizzes_query->have_posts()):
                        $quizzes_query->the_post();
                        $quiz_id = get_the_ID();

                        // Quiz meta
                        $num_questions = get_post_meta($quiz_id, '_num_questions', true) ?: 10;
                        $time_per_question = get_post_meta($quiz_id, '_time_per_question', true) ?: 10;
                        $quiz_difficulty_id = get_post_meta($quiz_id, '_quiz_difficulty', true);
                        $quiz_difficulty = '';
                        $quiz_difficulty_slug = 'beginner';
                        if ($quiz_difficulty_id) {
                            $difficulty_post = get_post($quiz_difficulty_id);
                            if ($difficulty_post) {
                                $quiz_difficulty = $difficulty_post->post_title;
                                $quiz_difficulty_slug = $difficulty_post->post_name;
                            }
                        }

                        // Category - use unified color helper
                        $terms = get_the_terms($quiz_id, 'quiz_category');
                        $cat_name = 'GENERAL';
                        $cat_color = '#6366f1';
                        if ($terms && !is_wp_error($terms)) {
                            $cat_name = strtoupper($terms[0]->name);
                            // Use ygv_get_quiz_category_color for proper color mapping
                            $cat_color = function_exists('ygv_get_quiz_category_color')
                                ? ygv_get_quiz_category_color($quiz_id)
                                : '#6366f1';
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
                                $current_user_id,
                                $quiz_id
                            ));
                            if ($progress) {
                                $best_percent = intval($progress->best_percent);
                                $attempts = intval($progress->attempts);
                                $is_completed = $attempts > 0;
                            }
                        }

                        // Difficulty colors
                        $diff_colors = [
                            'beginner' => '#10b981',
                            'easy' => '#10b981',
                            'lako' => '#10b981',
                            'intermediate' => '#f59e0b',
                            'medium' => '#f59e0b',
                            'srednje' => '#f59e0b',
                            'expert' => '#ef4444',
                            'hard' => '#ef4444',
                            'teško' => '#ef4444',
                        ];
                        $diff_color = $diff_colors[strtolower($quiz_difficulty_slug)] ?? '#6366f1';

                        // Lock state
                        $is_locked = !$current_user_id
                            && get_post_meta($quiz_id, '_allow_guest_play', true) !== '1';

                        // Status badge
                        $status_badge = '';
                        $status_class = '';
                        if ($is_locked) {
                            $status_badge = '🔒 Prijavi se';
                            $status_class = 'ygv-status-locked';
                        } elseif ($is_completed && $best_percent >= 80) {
                            $status_badge = '✓ Odličan rezultat';
                            $status_class = 'ygv-status-excellent';
                        } elseif ($is_completed) {
                            $status_badge = '✓ Odigrano';
                            $status_class = 'ygv-status-played';
                        } else {
                            $status_badge = '☆ Nije igrano';
                            $status_class = 'ygv-status-new';
                        }
                        ?>
                        <article class="ygv-quiz-card <?php echo $is_locked ? 'ygv-quiz-card--locked' : ''; ?>"
                            style="--card-color: <?php echo esc_attr($cat_color); ?>;">
                            <div class="ygv-quiz-card__image">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('medium_large'); ?>
                                <?php else: ?>
                                    <div class="ygv-quiz-card__placeholder"
                                        style="background: linear-gradient(135deg, <?php echo esc_attr($cat_color); ?> 0%, #1e2a5e 100%);">
                                        <?php ygv_icon_e('brain', 48); ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Category Badge (top left) -->
                                <span class="ygv-quiz-card__category" style="background: <?php echo esc_attr($cat_color); ?>;">
                                    <?php echo esc_html($cat_name); ?>
                                </span>

                                <!-- Status Badge (top right) -->
                                <span class="ygv-quiz-card__status <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($status_badge); ?>
                                </span>

                                <?php if ($is_locked): ?>
                                    <div class="ygv-quiz-card__lock-overlay">
                                        <?php ygv_icon_e('lock', 28); ?>
                                    </div>
                                <?php elseif ($is_completed): ?>
                                    <div class="ygv-quiz-card__overlay">
                                        <div class="ygv-quiz-card__checkmark">
                                            <?php ygv_icon_e('check', 32); ?>
                                        </div>
                                        <span class="ygv-quiz-card__percent"><?php echo $best_percent; ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="ygv-quiz-card__body">
                                <h3 class="ygv-quiz-card__title"><?php the_title(); ?></h3>

                                <div class="ygv-quiz-card__meta">
                                    <span class="ygv-quiz-card__meta-item">
                                        <?php ygv_icon_e('file-list', 14); ?>
                                        <?php echo $num_questions; ?> pitanja
                                    </span>
                                    <?php if ($quiz_difficulty): ?>
                                        <span class="ygv-quiz-card__meta-item ygv-quiz-card__difficulty"
                                            style="color: <?php echo esc_attr($diff_color); ?>;">
                                            <?php ygv_icon_e('star', 14); ?>
                                            <?php echo esc_html($quiz_difficulty); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($is_completed): ?>
                                    <div class="ygv-quiz-card__result">
                                        <span class="ygv-quiz-card__score-badge"
                                            style="color: <?php echo $best_percent >= 80 ? '#10b981' : ($best_percent >= 50 ? '#f59e0b' : '#ef4444'); ?>;">
                                            <?php echo $best_percent; ?>% <small>NAJBOLJI REZULTAT</small>
                                        </span>
                                        <span class="ygv-quiz-card__attempts"><?php ygv_icon_e('refresh', 12); ?>
                                            <?php echo $attempts; ?>x pokušaja</span>
                                    </div>
                                <?php else: ?>
                                    <div class="ygv-quiz-card__new-badge">
                                        <?php ygv_icon_e('sparkles', 14); ?>
                                        Novi kviz za tebe!
                                    </div>
                                <?php endif; ?>

                                <button type="button"
                                    class="ygv-quiz-card__btn ygv-quiz-start-btn <?php echo $is_locked ? 'ygv-quiz-btn--locked' : ''; ?>"
                                    data-quiz-id="<?php echo $quiz_id; ?>"
                                    <?php echo $is_locked ? 'data-locked="true"' : ''; ?>>
                                    <?php if ($is_locked): ?>
                                        <?php ygv_icon_e('lock', 16); ?>
                                        <span>Prijavi se da igraš</span>
                                    <?php else: ?>
                                        <?php ygv_icon_e($is_completed ? 'refresh' : 'play', 16); ?>
                                        <span><?php echo $is_completed ? 'Igraj Ponovo' : 'Započni Kviz'; ?></span>
                                    <?php endif; ?>
                                </button>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            </div>

            <div id="ygv-archive-pagination"></div>

        </section>
        <?php
        wp_reset_postdata();
    else:
        ?>
        <section style="padding: 80px 20px; text-align: center;">
            <div class="ygv-container">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="var(--ygv-text-muted)"
                    style="margin-bottom: 24px; opacity: 0.3;">
                    <path
                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z" />
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