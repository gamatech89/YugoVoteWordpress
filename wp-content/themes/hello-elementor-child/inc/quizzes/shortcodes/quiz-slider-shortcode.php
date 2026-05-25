<?php
/**
 * Shortcode: [quiz_slider]
 *
 * Attributes:
 *   count      - number of quizzes (default 8)
 *   autoplay   - 'true'|'false' (default 'true')
 *   category   - quiz_category slug to filter (default '')
 *   difficulty - quiz_levels post ID to filter (default 0)
 *   title      - section heading (default 'Kvizovi')
 *   orderby    - latest|popular|random (default 'latest')
 */

if (!function_exists('ygv_quiz_slider_shortcode')) {
    function ygv_quiz_slider_shortcode($atts) {
        $atts = shortcode_atts([
            'count'      => 8,
            'autoplay'   => 'true',
            'category'   => '',
            'difficulty' => 0,
            'title'      => 'Kvizovi',
            'orderby'    => 'latest',
        ], $atts, 'quiz_slider');

        $count      = max(1, intval($atts['count']));
        $autoplay   = $atts['autoplay'] === 'true' || $atts['autoplay'] === '1';
        $category   = sanitize_text_field($atts['category']);
        $difficulty = intval($atts['difficulty']);
        $title      = sanitize_text_field($atts['title']);
        $orderby    = sanitize_key($atts['orderby']);

        $query_args = [
            'post_type'      => 'quiz',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
        ];

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

        if ($category) {
            $query_args['tax_query'] = [[
                'taxonomy' => 'quiz_category',
                'field'    => 'slug',
                'terms'    => $category,
            ]];
        }

        if ($difficulty) {
            $query_args['meta_query'] = [[
                'key'     => '_quiz_difficulty',
                'value'   => $difficulty,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ]];
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
        set_query_var('ygv_quiz_slider_title',    $title);

        ob_start();
        $tpl = get_stylesheet_directory() . '/inc/quizzes/templates/quiz-slider.php';
        if (file_exists($tpl)) include $tpl;
        wp_reset_postdata();
        return ob_get_clean();
    }
    add_shortcode('quiz_slider', 'ygv_quiz_slider_shortcode');
}
