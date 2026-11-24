<?php
/**
 * AJAX handler for quiz archive filtering (category + difficulty, no page reload)
 */

if (!defined('ABSPATH')) exit;

function ygv_archive_filter_ajax() {
    if (!ygv_public_nonce_ok('ygv_archive_filter')) {
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
        while ($query->have_posts()):
            $query->the_post();
            ygv_render_quiz_card(get_the_ID(), $user_id);
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
