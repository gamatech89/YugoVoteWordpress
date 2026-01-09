<?php
/**
 * Template Part: Single Quiz Card with User Progress
 */

if (!defined('ABSPATH')) exit;

global $post;
$quiz_id = get_the_ID();
$current_user_id = get_current_user_id();

// Fetch quiz meta
$num_questions = get_post_meta($quiz_id, '_num_questions', true) ?: 10;
$time_per_question = get_post_meta($quiz_id, '_time_per_question', true) ?: 10;
$quiz_difficulty_id = get_post_meta($quiz_id, '_quiz_difficulty', true);
$quiz_difficulty = '';

if (!empty($quiz_difficulty_id)) {
    $difficulty_post = get_post($quiz_difficulty_id);
    if ($difficulty_post) {
        $quiz_difficulty = $difficulty_post->post_title;
    }
}

// Get category info
$category_color = function_exists('ygv_get_quiz_category_color') 
    ? ygv_get_quiz_category_color($quiz_id) 
    : '#6A0DAD';
$category_name = function_exists('ygv_get_quiz_category_name') 
    ? ygv_get_quiz_category_name($quiz_id) 
    : 'Opšte';

// Get category slug for filtering
$terms = wp_get_object_terms($quiz_id, 'quiz_category', ['fields' => 'slugs']);
$category_slug = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : 'uncategorized';

// Featured image
$featured_image = get_the_post_thumbnail_url($quiz_id, 'medium');
$excerpt = get_the_excerpt($quiz_id);

// Calculate total time
$total_time = $num_questions * $time_per_question;
$total_time_minutes = ceil($total_time / 60);

// ✅ User Progress (if logged in)
$user_progress = null;
$best_percent = 0;
$attempts = 0;
$is_completed = false;
$is_new = true;

if ($current_user_id > 0) {
    global $wpdb;
    $progress_table = $wpdb->prefix . 'ygv_user_quiz_progress';
    
    $user_progress = $wpdb->get_row($wpdb->prepare(
        "SELECT best_percent, attempts, awarded_xp FROM {$progress_table} WHERE user_id = %d AND quiz_id = %d",
        $current_user_id,
        $quiz_id
    ), ARRAY_A);
    
    if ($user_progress) {
        $best_percent = (int) $user_progress['best_percent'];
        $attempts = (int) $user_progress['attempts'];
        $is_completed = $attempts > 0;
        $is_new = false;
    }
}

// Determine status badge
$status_badge = '';
$status_class = '';
if ($current_user_id > 0) {
    if ($is_completed) {
        if ($best_percent >= 70) {
            $status_badge = 'Odličan rezultat';
            $status_class = 'excellent';
        } elseif ($best_percent >= 40) {
            $status_badge = 'Dobar rezultat';
            $status_class = 'good';
        } else {
            $status_badge = 'Probaj ponovo';
            $status_class = 'retry';
        }
    } else {
        $status_badge = 'Nije igrano';
        $status_class = 'new';
    }
}
?>

<article class="yuv-quiz-card-entry <?php echo $is_completed ? 'is-completed' : ''; ?> <?php echo $is_new ? 'is-new' : ''; ?>" 
         data-category="<?php echo esc_attr($category_slug); ?>" 
         data-quiz-id="<?php echo esc_attr($quiz_id); ?>"
         style="--quiz-card-color: <?php echo esc_attr($category_color); ?>">
    
    <a href="#" class="yuv-quiz-card-link" data-quiz-id="<?php echo esc_attr($quiz_id); ?>">
        
        <!-- Featured Image -->
        <div class="yuv-quiz-card__image">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
            <?php else: ?>
                <div class="yuv-quiz-card__placeholder" style="background: linear-gradient(135deg, <?php echo esc_attr($category_color); ?> 0%, rgba(0,0,0,0.3) 100%);"></div>
            <?php endif; ?>
            
            <!-- Category Badge -->
            <span class="yuv-quiz-card__badge" style="background-color: <?php echo esc_attr($category_color); ?>;">
                <?php echo esc_html($category_name); ?>
            </span>

            <!-- ✅ User Status Badge -->
            <?php if ($current_user_id > 0 && $status_badge): ?>
                <div class="yuv-quiz-card__status-badge <?php echo esc_attr($status_class); ?>">
                    <?php if ($is_completed): ?>
                        <i class="ri-checkbox-circle-fill"></i>
                    <?php else: ?>
                        <i class="ri-star-line"></i>
                    <?php endif; ?>
                    <span><?php echo esc_html($status_badge); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Card Body -->
        <div class="yuv-quiz-card__body">
            <h3 class="yuv-quiz-card__title"><?php echo esc_html(get_the_title()); ?></h3>
            
            <?php if ($excerpt): ?>
                <p class="yuv-quiz-card__excerpt"><?php echo esc_html(wp_trim_words($excerpt, 15)); ?></p>
            <?php endif; ?>

            <!-- Meta Row -->
            <div class="yuv-quiz-card__meta">
                <div class="yuv-quiz-card__meta-item">
                    <i class="ri-file-list-3-line"></i>
                    <span><?php echo esc_html($num_questions); ?> pitanja</span>
                </div>
                <div class="yuv-quiz-card__meta-item">
                    <i class="ri-time-line"></i>
                    <span>~<?php echo esc_html($total_time_minutes); ?> min</span>
                </div>
                <?php if ($quiz_difficulty): ?>
                    <div class="yuv-quiz-card__meta-item">
                        <i class="ri-star-line"></i>
                        <span><?php echo esc_html($quiz_difficulty); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ✅ User Progress Section (always shown for consistent layout) -->
            <div class="yuv-quiz-card__progress">
                <?php if ($is_completed): 
                    // Determine color class based on percentage
                    $progress_class = '';
                    if ($best_percent >= 80) {
                        $progress_class = 'excellent';
                    } elseif ($best_percent >= 50) {
                        $progress_class = 'good';
                    } elseif ($best_percent >= 30) {
                        $progress_class = 'average';
                    } else {
                        $progress_class = 'low';
                    }
                ?>
                    <div class="yuv-progress-bar">
                        <div class="yuv-progress-fill <?php echo esc_attr($progress_class); ?>" style="width: <?php echo esc_attr($best_percent); ?>%;"></div>
                    </div>
                    <div class="yuv-progress-text">
                        <span class="yuv-progress-score <?php echo esc_attr($progress_class); ?>"><?php echo esc_html($best_percent); ?>%</span>
                        <span class="yuv-progress-label">najbolji rezultat</span>
                    </div>
                <?php else: ?>
                    <!-- Not started yet -->
                    <div class="yuv-progress-placeholder">
                        <i class="ri-play-circle-line"></i>
                        <span>Još nisi igrao ovaj kviz</span>
                    </div>
                <?php endif; ?>
                
                <!-- Attempts count (if any) -->
                <?php if ($attempts > 0): ?>
                    <div class="yuv-progress-attempts">
                        <i class="ri-refresh-line"></i>
                        <span><?php echo esc_html($attempts); ?>x pokušaja</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Footer -->
        <div class="yuv-quiz-card__footer">
            <span class="yuv-quiz-card__button" style="color: <?php echo esc_attr($category_color); ?>; background: <?php echo esc_attr($category_color); ?>1A;">
                <?php if ($is_completed): ?>
                    <i class="ri-refresh-line"></i>
                    Igraj Ponovo
                <?php else: ?>
                    <i class="ri-play-circle-line"></i>
                    Započni Kviz
                <?php endif; ?>
            </span>
        </div>
    </a>
</article>
