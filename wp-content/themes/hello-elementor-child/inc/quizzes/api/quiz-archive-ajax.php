<?php
/**
 * AJAX handler for quiz archive filtering (category + difficulty, no page reload)
 */

if (!defined('ABSPATH')) exit;

function ygv_archive_filter_ajax() {
    if (!check_ajax_referer('ygv_archive_filter', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    $cat     = sanitize_text_field($_POST['cat']   ?? '');
    $diff    = intval($_POST['diff']               ?? 0);
    $paged   = max(1, intval($_POST['paged']       ?? 1));
    $user_id = get_current_user_id();

    $args = [
        'post_type'      => 'quiz',
        'posts_per_page' => 12,
        'paged'          => $paged,
        'post_status'    => 'publish',
    ];

    if ($cat) {
        $args['tax_query'] = [[
            'taxonomy' => 'quiz_category',
            'field'    => 'slug',
            'terms'    => $cat,
        ]];
    }

    if ($diff) {
        $args['meta_query'] = [[
            'key'     => '_quiz_difficulty',
            'value'   => $diff,
            'compare' => '=',
            'type'    => 'NUMERIC',
        ]];
    }

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) {
        $diff_colors = [
            'beginner'     => '#10b981', 'easy'  => '#10b981', 'lako'    => '#10b981',
            'intermediate' => '#f59e0b', 'medium' => '#f59e0b', 'srednje' => '#f59e0b',
            'expert'       => '#ef4444', 'hard'   => '#ef4444', 'tesko'   => '#ef4444', 'teško' => '#ef4444',
        ];

        while ($query->have_posts()):
            $query->the_post();
            $quiz_id = get_the_ID();

            $num_questions      = get_post_meta($quiz_id, '_num_questions', true)     ?: 10;
            $time_per_question  = get_post_meta($quiz_id, '_time_per_question', true) ?: 10;
            $quiz_difficulty_id = get_post_meta($quiz_id, '_quiz_difficulty', true);
            $quiz_difficulty    = '';
            $diff_slug          = 'beginner';

            if ($quiz_difficulty_id) {
                $dp = get_post($quiz_difficulty_id);
                if ($dp) { $quiz_difficulty = $dp->post_title; $diff_slug = $dp->post_name; }
            }

            $terms     = get_the_terms($quiz_id, 'quiz_category');
            $cat_name  = 'GENERAL';
            $cat_color = '#6366f1';
            if ($terms && !is_wp_error($terms)) {
                $cat_name  = strtoupper($terms[0]->name);
                $cat_color = function_exists('ygv_get_quiz_category_color')
                    ? ygv_get_quiz_category_color($quiz_id) : '#6366f1';
            }

            $diff_color = $diff_colors[strtolower($diff_slug)] ?? '#6366f1';

            $best_percent = 0; $attempts = 0; $is_completed = false;
            if ($user_id) {
                global $wpdb;
                $row = $wpdb->get_row($wpdb->prepare(
                    "SELECT best_percent, attempts FROM {$wpdb->prefix}ygv_user_quiz_progress WHERE user_id=%d AND quiz_id=%d",
                    $user_id, $quiz_id
                ));
                if ($row) {
                    $best_percent = intval($row->best_percent);
                    $attempts     = intval($row->attempts);
                    $is_completed = $attempts > 0;
                }
            }

            if ($is_completed && $best_percent >= 80) {
                $status_badge = '✓ Odličan rezultat'; $status_class = 'ygv-status-excellent';
            } elseif ($is_completed) {
                $status_badge = '✓ Odigrano';         $status_class = 'ygv-status-played';
            } else {
                $status_badge = '☆ Nije igrano';      $status_class = 'ygv-status-new';
            }
            ?>
            <article class="ygv-quiz-card" style="--card-color: <?php echo esc_attr($cat_color); ?>;">
                <div class="ygv-quiz-card__image">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('medium_large'); ?>
                    <?php else: ?>
                        <div class="ygv-quiz-card__placeholder"
                            style="background:linear-gradient(135deg,<?php echo esc_attr($cat_color); ?> 0%,#1e2a5e 100%);">
                            <?php ygv_icon_e('brain', 48); ?>
                        </div>
                    <?php endif; ?>
                    <span class="ygv-quiz-card__category" style="background:<?php echo esc_attr($cat_color); ?>;">
                        <?php echo esc_html($cat_name); ?>
                    </span>
                    <span class="ygv-quiz-card__status <?php echo esc_attr($status_class); ?>">
                        <?php echo esc_html($status_badge); ?>
                    </span>
                    <?php if ($is_completed): ?>
                        <div class="ygv-quiz-card__overlay">
                            <div class="ygv-quiz-card__checkmark"><?php ygv_icon_e('check', 32); ?></div>
                            <span class="ygv-quiz-card__percent"><?php echo $best_percent; ?>%</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="ygv-quiz-card__body">
                    <h3 class="ygv-quiz-card__title"><?php the_title(); ?></h3>
                    <div class="ygv-quiz-card__meta">
                        <span class="ygv-quiz-card__meta-item">
                            <?php ygv_icon_e('file-list', 14); ?> <?php echo $num_questions; ?> pitanja
                        </span>
                        <?php if ($quiz_difficulty): ?>
                            <span class="ygv-quiz-card__meta-item ygv-quiz-card__difficulty"
                                style="color:<?php echo esc_attr($diff_color); ?>;">
                                <?php ygv_icon_e('star', 14); ?> <?php echo esc_html($quiz_difficulty); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($is_completed): ?>
                        <div class="ygv-quiz-card__result">
                            <span class="ygv-quiz-card__score-badge"
                                style="color:<?php echo $best_percent >= 80 ? '#10b981' : ($best_percent >= 50 ? '#f59e0b' : '#ef4444'); ?>;">
                                <?php echo $best_percent; ?>% <small>NAJBOLJI REZULTAT</small>
                            </span>
                            <span class="ygv-quiz-card__attempts">
                                <?php ygv_icon_e('refresh', 12); ?> <?php echo $attempts; ?>x pokušaja
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="ygv-quiz-card__new-badge">
                            <?php ygv_icon_e('sparkles', 14); ?> Novi kviz za tebe!
                        </div>
                    <?php endif; ?>
                    <button type="button" class="ygv-quiz-card__btn ygv-quiz-start-btn"
                        data-quiz-id="<?php echo $quiz_id; ?>">
                        <?php ygv_icon_e($is_completed ? 'refresh' : 'play', 16); ?>
                        <span><?php echo $is_completed ? 'Igraj Ponovo' : 'Započni Kviz'; ?></span>
                    </button>
                </div>
            </article>
            <?php
        endwhile;
        wp_reset_postdata();
    } else {
        echo '<div class="ygv-quiz-empty" style="grid-column:1/-1;text-align:center;padding:60px 20px;">';
        echo '<p style="color:var(--ygv-text-muted);font-size:16px;">Nema kvizova za izabrane filtere.</p>';
        echo '</div>';
    }

    $cards_html = ob_get_clean();

    wp_send_json_success([
        'html'        => $cards_html,
        'total_pages' => (int) $query->max_num_pages,
        'current'     => $paged,
        'found'       => (int) $query->found_posts,
    ]);
}

add_action('wp_ajax_ygv_archive_filter',        'ygv_archive_filter_ajax');
add_action('wp_ajax_nopriv_ygv_archive_filter', 'ygv_archive_filter_ajax');
