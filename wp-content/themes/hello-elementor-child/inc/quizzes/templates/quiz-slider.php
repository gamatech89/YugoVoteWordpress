<?php
/**
 * Template: Quiz Slider — [quiz_slider]
 */

$query    = get_query_var('ygv_quiz_slider_query');
$autoplay = get_query_var('ygv_quiz_slider_autoplay');

if (!$query || !$query->have_posts()) return;

$current_user_id = get_current_user_id();
$slider_id       = 'ygv-quiz-slider-' . uniqid();

$diff_colors = [
    'beginner'     => '#10b981',
    'easy'         => '#10b981',
    'lako'         => '#10b981',
    'intermediate' => '#f59e0b',
    'medium'       => '#f59e0b',
    'srednje'      => '#f59e0b',
    'expert'       => '#ef4444',
    'hard'         => '#ef4444',
    'tesko'        => '#ef4444',
    'teško'        => '#ef4444',
];
?>

<section class="ygv-quiz-slider-section">

    <div class="ygv-spotlight-carousel-wrap">
        <div class="swiper ygv-quiz-slider-swiper" id="<?php echo esc_attr($slider_id); ?>">
            <div class="swiper-wrapper">

                <?php while ($query->have_posts()):
                    $query->the_post();
                    $quiz_id = get_the_ID();

                    // Quiz meta
                    $num_questions      = get_post_meta($quiz_id, '_num_questions', true) ?: 10;
                    $time_per_question  = get_post_meta($quiz_id, '_time_per_question', true) ?: 10;
                    $quiz_difficulty_id = get_post_meta($quiz_id, '_quiz_difficulty', true);
                    $quiz_difficulty    = '';
                    $diff_slug          = '';

                    if ($quiz_difficulty_id) {
                        $diff_post = get_post($quiz_difficulty_id);
                        if ($diff_post) {
                            $quiz_difficulty = $diff_post->post_title;
                            $diff_slug       = $diff_post->post_name;
                        }
                    }

                    // Category
                    $terms     = get_the_terms($quiz_id, 'quiz_category');
                    $cat_name  = 'GENERAL';
                    $cat_color = '#6366f1';
                    if ($terms && !is_wp_error($terms)) {
                        $cat_name  = strtoupper($terms[0]->name);
                        $cat_color = function_exists('ygv_get_quiz_category_color')
                            ? ygv_get_quiz_category_color($quiz_id)
                            : '#6366f1';
                    }

                    $total_time = ceil(($num_questions * $time_per_question) / 60);
                    $diff_color = $diff_colors[strtolower($diff_slug)] ?? '#6366f1';

                    // User progress
                    $best_percent = 0;
                    $attempts     = 0;
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
                            $attempts     = intval($progress->attempts);
                            $is_completed = $attempts > 0;
                        }
                    }

                    // Status badge
                    if ($is_completed && $best_percent >= 80) {
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

                    <div class="swiper-slide">
                        <article class="ygv-quiz-card" style="--card-color: <?php echo esc_attr($cat_color); ?>;">
                            <div class="ygv-quiz-card__image">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('medium_large'); ?>
                                <?php else: ?>
                                    <div class="ygv-quiz-card__placeholder"
                                        style="background: linear-gradient(135deg, <?php echo esc_attr($cat_color); ?> 0%, #1e2a5e 100%);">
                                        <?php ygv_icon_e('brain', 48); ?>
                                    </div>
                                <?php endif; ?>

                                <span class="ygv-quiz-card__category" style="background: <?php echo esc_attr($cat_color); ?>;">
                                    <?php echo esc_html($cat_name); ?>
                                </span>

                                <span class="ygv-quiz-card__status <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($status_badge); ?>
                                </span>

                                <?php if ($is_completed): ?>
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
                                        <span class="ygv-quiz-card__attempts">
                                            <?php ygv_icon_e('refresh', 12); ?>
                                            <?php echo $attempts; ?>x pokušaja
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="ygv-quiz-card__new-badge">
                                        <?php ygv_icon_e('sparkles', 14); ?>
                                        Novi kviz za tebe!
                                    </div>
                                <?php endif; ?>

                                <button type="button" class="ygv-quiz-card__btn ygv-quiz-start-btn"
                                    data-quiz-id="<?php echo $quiz_id; ?>">
                                    <?php ygv_icon_e($is_completed ? 'refresh' : 'play', 16); ?>
                                    <span><?php echo $is_completed ? 'Igraj Ponovo' : 'Započni Kviz'; ?></span>
                                </button>
                            </div>
                        </article>
                    </div>

                <?php endwhile; ?>

            </div>
        </div>

        <div class="ygv-sp-arrow ygv-sp-arrow--prev" id="<?php echo esc_attr($slider_id); ?>-prev">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="16" viewBox="0 0 28 20" fill="none">
                <path d="M26 9.81774C21.4599 10.4649 11.178 11.371 6.37084 9.81774C0.361911 7.87618 8.37381 6.48935 12.7804 2.8836C17.1869 -0.72216 0.161613 7.7375 2.16459 9.81774C4.16756 11.898 8.37381 16.3358 10.5771 18" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="ygv-sp-arrow ygv-sp-arrow--next" id="<?php echo esc_attr($slider_id); ?>-next">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="16" viewBox="0 0 28 20" fill="none">
                <path d="M2 9.81774C6.54008 10.4649 16.822 11.371 21.6292 9.81774C27.6381 7.87618 19.6262 6.48935 15.2196 2.8836C10.8131 -0.72216 27.8384 7.7375 25.8354 9.81774C23.8324 11.898 19.6262 16.3358 17.4229 18" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

</section>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swiper === 'undefined') return;
        var el = document.getElementById('<?php echo esc_js($slider_id); ?>');
        if (!el) return;
        new Swiper(el, {
            loop: true,
            loopAdditionalSlides: 4,
            speed: 600,
            autoplay: <?php echo $autoplay ? '{ delay: 5000, disableOnInteraction: true }' : 'false'; ?>,
            slidesPerView: 1.2,
            spaceBetween: 16,
            navigation: {
                nextEl: '#<?php echo esc_js($slider_id); ?>-next',
                prevEl: '#<?php echo esc_js($slider_id); ?>-prev',
            },
            breakpoints: {
                600:  { slidesPerView: 2,   spaceBetween: 20 },
                960:  { slidesPerView: 3,   spaceBetween: 24 },
                1280: { slidesPerView: 4,   spaceBetween: 24 },
            },
        });
    });
})();
</script>
