<?php
/**
 * Template: Quiz Slider — [quiz_slider]
 */

$query    = get_query_var('ygv_quiz_slider_query');
$autoplay = get_query_var('ygv_quiz_slider_autoplay');

if (!$query || !$query->have_posts()) return;

$current_user_id = get_current_user_id();
$slider_id       = 'ygv-quiz-slider-' . uniqid();

?>

<section class="ygv-quiz-slider-section">

    <div class="ygv-spotlight-carousel-wrap">
        <div class="swiper ygv-quiz-slider-swiper" id="<?php echo esc_attr($slider_id); ?>">
            <div class="swiper-wrapper">

                <?php while ($query->have_posts()):
                    $query->the_post(); ?>
                    <div class="swiper-slide">
                        <?php ygv_render_quiz_card(get_the_ID(), $current_user_id); ?>
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
