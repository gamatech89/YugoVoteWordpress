<?php
/**
 * Shortcode: [quiz_slider]
 *
 * Attributes:
 *   count      - number of quizzes (default 8)
 *   autoplay   - 'true'|'false' (default 'true')
 *   category   - quiz_category slug to filter (default '')
 *   difficulty - quiz_levels post ID to filter (default 0)
 *   orderby    - latest|popular|random (default 'latest')
 *   ids        - comma-separated post IDs for spotlight/handpicked mode
 *   guest_only - 'true' to show only quizzes with _allow_guest_play = '1'
 */

if (!function_exists('ygv_quiz_slider_shortcode')) {
    function ygv_quiz_slider_shortcode($atts) {
        $atts = shortcode_atts([
            'count'      => 8,
            'autoplay'   => 'true',
            'category'   => '',
            'difficulty' => 0,
            'orderby'    => 'latest',
            'ids'        => '',
            'guest_only' => 'false',
        ], $atts, 'quiz_slider');

        $count      = max(1, intval($atts['count']));
        $autoplay   = $atts['autoplay'] === 'true' || $atts['autoplay'] === '1';
        $category   = sanitize_text_field($atts['category']);
        $difficulty = intval($atts['difficulty']);
        $orderby    = sanitize_key($atts['orderby']);
        $guest_only = $atts['guest_only'] === 'true' || $atts['guest_only'] === '1';

        // Parse handpicked IDs
        $ids = array_filter(array_map('intval', explode(',', $atts['ids'])));

        $query_args = [
            'post_type'      => 'quiz',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
        ];

        if (!empty($ids)) {
            // Spotlight/handpicked mode — respect given order
            $query_args['post__in'] = $ids;
            $query_args['orderby']  = 'post__in';
        } else {
            switch ($orderby) {
                case 'popular':
                    $query_args['meta_key'] = '_quiz_play_count';
                    $query_args['orderby']  = 'meta_value_num';
                    $query_args['order']    = 'DESC';
                    break;
                case 'random':
                    $query_args['orderby'] = 'rand';
                    break;
                default:
                    $query_args['orderby'] = 'date';
                    $query_args['order']   = 'DESC';
            }
        }

        if ($category) {
            $query_args['tax_query'] = [[
                'taxonomy' => 'quiz_category',
                'field'    => 'slug',
                'terms'    => $category,
            ]];
        }

        $meta_query = [];

        if ($difficulty) {
            $meta_query[] = [
                'key'     => '_quiz_difficulty',
                'value'   => $difficulty,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ];
        }

        if ($guest_only) {
            $meta_query[] = [
                'key'     => '_allow_guest_play',
                'value'   => '1',
                'compare' => '=',
            ];
        }

        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }

        $query = new WP_Query($query_args);
        if (!$query->have_posts()) return '';

        wp_enqueue_style(
            'ygv-templates',
            get_stylesheet_directory_uri() . '/css/templates.css',
            [],
            HELLO_ELEMENTOR_CHILD_VERSION
        );
        wp_enqueue_style(
            'ygv-quiz-slider',
            get_stylesheet_directory_uri() . '/css/quiz-slider.css',
            ['ygv-templates'],
            HELLO_ELEMENTOR_CHILD_VERSION
        );
        wp_enqueue_script(
            'swiper',
            'https://unpkg.com/swiper@10/swiper-bundle.min.js',
            [],
            '10',
            true
        );

        set_query_var('ygv_quiz_slider_query',    $query);
        set_query_var('ygv_quiz_slider_autoplay', $autoplay);

        ob_start();
        $tpl = get_stylesheet_directory() . '/inc/quizzes/templates/quiz-slider.php';
        if (file_exists($tpl)) include $tpl;
        wp_reset_postdata();
        return ob_get_clean();
    }
    add_shortcode('quiz_slider', 'ygv_quiz_slider_shortcode');
}
