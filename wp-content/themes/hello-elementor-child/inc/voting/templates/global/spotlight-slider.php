<?php
/**
 * Template: Spotlight Slider Widget
 * Modes: spotlight | latest | trending | top
 */

$posts    = get_query_var('ygv_slider_posts');
$mode     = get_query_var('ygv_slider_mode') ?: 'spotlight';
$autoplay = get_query_var('ygv_slider_autoplay');

if (empty($posts)) return;

$default_color  = '#4456A6';
$slider_id      = 'ygv-spotlight-slider-' . uniqid();

$mode_labels = [
    'spotlight' => '',
    'latest'    => 'Najnovije',
    'trending'  => 'Trending',
    'top'       => 'Top liste',
];
$section_label = $mode_labels[$mode] ?? '';
?>

<section class="ygv-spotlight-section">
    <?php if ($section_label) : ?>
    <div class="ygv-spotlight-header cs-container">
        <span class="ygv-spotlight-badge ygv-spotlight-badge--<?php echo esc_attr($mode); ?>">
            <?php if ($mode === 'trending') : ?>
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 10L5 6L8 9L13 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 3H13V6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?php endif; ?>
            <?php echo esc_html($section_label); ?>
        </span>
    </div>
    <?php endif; ?>

    <div class="swiper ygv-spotlight-swiper" id="<?php echo esc_attr($slider_id); ?>">
        <div class="swiper-wrapper">
            <?php foreach ($posts as $post) :
                $list_id   = $post->ID;
                $permalink = get_permalink($list_id);
                $title     = get_the_title($list_id);
                $thumb     = get_the_post_thumbnail_url($list_id, 'large');

                $terms    = get_the_terms($list_id, 'voting_list_category');
                $term     = ($terms && !is_wp_error($terms)) ? $terms[0] : null;
                $cat_name = $term ? esc_html($term->name) : '';
                $cat_color = $term ? get_term_meta($term->term_id, 'category_color', true) : '';
                if (empty($cat_color) || $cat_color === '#ffffff') {
                    $cat_color = $default_color;
                }

                $score = 0;
                if (function_exists('get_total_score_for_voting_list')) {
                    $score = get_total_score_for_voting_list($list_id);
                }

                $recent_score = isset($post->ygv_recent_score) ? $post->ygv_recent_score : null;
            ?>
            <div class="swiper-slide">
                <a href="<?php echo esc_url($permalink); ?>"
                   class="ygv-slide-card"
                   style="--slide-accent: <?php echo esc_attr($cat_color); ?>">

                    <?php if ($thumb) : ?>
                    <div class="ygv-slide-bg">
                        <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>">
                    </div>
                    <?php endif; ?>

                    <div class="ygv-slide-overlay"></div>

                    <div class="ygv-slide-content">
                        <?php if ($cat_name) : ?>
                        <span class="ygv-slide-cat"><?php echo $cat_name; ?></span>
                        <?php endif; ?>

                        <h2 class="ygv-slide-title"><?php echo esc_html($title); ?></h2>

                        <div class="ygv-slide-meta">
                            <span class="ygv-slide-votes">
                                <strong><?php echo number_format_i18n($score); ?></strong> glasova
                            </span>
                            <?php if ($mode === 'trending' && $recent_score !== null && $recent_score > 0) : ?>
                            <span class="ygv-slide-trending-badge">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1 9L4.5 5.5L7 8L11 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                +<?php echo number_format_i18n($recent_score); ?> ovaj tjedan
                            </span>
                            <?php endif; ?>
                        </div>

                        <span class="ygv-slide-cta">Glasaj sada</span>
                    </div>

                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="swiper-button-prev ygv-arrow ygv-arrow--prev">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="18" viewBox="0 0 28 20" fill="none">
                <path d="M26 9.81774C21.4599 10.4649 11.178 11.371 6.37084 9.81774C0.361911 7.87618 8.37381 6.48935 12.7804 2.8836C17.1869 -0.72216 0.161613 7.7375 2.16459 9.81774C4.16756 11.898 8.37381 16.3358 10.5771 18" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="swiper-button-next ygv-arrow ygv-arrow--next">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="18" viewBox="0 0 28 20" fill="none">
                <path d="M2 9.81774C6.54008 10.4649 16.822 11.371 21.6292 9.81774C27.6381 7.87618 19.6262 6.48935 15.2196 2.8836C10.8131 -0.72216 27.8384 7.7375 25.8354 9.81774C23.8324 11.898 19.6262 16.3358 17.4229 18" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>

        <div class="swiper-pagination ygv-spotlight-dots"></div>
    </div>
</section>

<script>
jQuery(document).ready(function($) {
    if (typeof Swiper === 'undefined') return;
    var el = document.getElementById('<?php echo esc_js($slider_id); ?>');
    if (!el) return;
    new Swiper(el, {
        loop: true,
        speed: 700,
        autoplay: <?php echo $autoplay ? '{ delay: 5000, disableOnInteraction: true }' : 'false'; ?>,
        effect: 'slide',
        slidesPerView: 1,
        spaceBetween: 0,
        pagination: {
            el: el.querySelector('.ygv-spotlight-dots'),
            clickable: true,
        },
        navigation: {
            nextEl: el.querySelector('.ygv-arrow--next'),
            prevEl: el.querySelector('.ygv-arrow--prev'),
        },
        breakpoints: {
            768: { slidesPerView: 1 },
        },
    });
});
</script>
