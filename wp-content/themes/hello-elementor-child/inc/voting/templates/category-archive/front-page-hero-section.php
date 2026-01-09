<?php
/**
 * Template part for Homepage Categories Slider
 */

$categories = get_query_var('cs_home_categories');

if (empty($categories)) {
    return;
}
?>

<div class="cs-home-slider-wrapper">
    <div class="swiper cs-home-category-swiper" id="cs-voting-hero-slider">
        <div class="swiper-wrapper">
            
            <?php foreach ($categories as $term) : ?>
                <?php
                $term_id = $term->term_id;
                
                // 1. Meta Data (Boja, Slike)
                $color = get_term_meta($term_id, 'category_color', true) ?: '#4457A5';
                $logo_id = get_term_meta($term_id, 'category_logo', true);
                $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
                $featured_image_id = get_term_meta($term_id, 'category_featured_image', true);
                $featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'large') : '';
                $term_link = get_term_link($term);
                
                // 2. SLOGAN (Naslov) - Iz custom polja 'Hero Slogan'
                $slogan = get_term_meta($term_id, 'category_slogan', true);
                
                // Ako nema slogana, koristi Ime kategorije
                if (empty($slogan)) {
                    $slogan = $term->name; 
                }
                
                // 3. OPIS (Tekst) - Iz standardnog Description polja
                $cat_desc = term_description($term_id);
                // Ako nema opisa, ne prikazujemo ništa ili možeš staviti neki default
                ?>

                <div class="swiper-slide">
                    <div class="cs-home-cat-hero" style="--cat-hero-color: <?php echo esc_attr($color); ?>; margin-bottom: 0;">
                        <div class="cs-home-cat-hero__inner">
                            
                            <div class="cs-home-cat-hero__media-group">
                                <a href="<?php echo esc_url($term_link); ?>" class="cs-home-cat-hero__img-link">
                                    <div class="cs-mascot-wrapper">
                                        <?php if ($logo_url) : ?>
                                            <img src="<?php echo esc_url($logo_url); ?>" alt="Mascot" class="cs-hero-mascot-img">
                                        <?php endif; ?>
                                        <div class="cs-mascot-line"></div>
                                    </div>
                                    <?php if ($featured_image_url) : ?>
                                        <div class="cs-home-cat-hero__featured-img">
                                            <img src="<?php echo esc_url($featured_image_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
                                        </div>
                                    <?php endif; ?>
                                </a>
                            </div>

                            <div class="cs-home-cat-hero__content">
                                <a href="<?php echo esc_url($term_link); ?>" class="cs-home-cat-hero__label-link">
                                    <span class="cs-home-cat-hero__label"><?php echo esc_html($term->name); ?></span>
                                </a>
                                
                                <h2 class="cs-home-cat-hero__title">
                                    <a href="<?php echo esc_url($term_link); ?>">
                                        <?php echo esc_html($slogan); ?>
                                    </a>
                                </h2>

                                <div class="cs-home-cat-hero__footer">
                                    <?php if (!empty($cat_desc)) : ?>
                                        <div class="cs-home-cat-hero__text">
                                            <?php echo wp_kses_post($cat_desc); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="cs-home-cat-hero__stats">
                                        <span class="cs-home-cat-hero__count">
                                            <?php echo esc_html($term->count); ?> LISTA
                                        </span>
                                        <a href="<?php echo esc_url($term_link); ?>" class="cs-home-cat-hero__btn">
                                            istraži
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>
        
        <div class="swiper-button-prev custom-arrow js-hero-prev">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="20" viewBox="0 0 28 20" fill="none">
                <path d="M26 9.81774C21.4599 10.4649 11.178 11.371 6.37084 9.81774C0.361911 7.87618 8.37381 6.48935 12.7804 2.8836C17.1869 -0.72216 0.161613 7.7375 2.16459 9.81774C4.16756 11.898 8.37381 16.3358 10.5771 18" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
            </svg>
        </div>
        <div class="swiper-button-next custom-arrow js-hero-next">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="20" viewBox="0 0 28 20" fill="none">
                <path d="M2 9.81774C6.54008 10.4649 16.822 11.371 21.6292 9.81774C27.6381 7.87618 19.6262 6.48935 15.2196 2.8836C10.8131 -0.72216 27.8384 7.7375 25.8354 9.81774C23.8324 11.898 19.6262 16.3358 17.4229 18" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
            </svg>
        </div>
        
        <div class="swiper-pagination"></div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    if (typeof Swiper !== 'undefined') {
        var $sliderContainer = $('.cs-home-category-swiper');
        $sliderContainer.each(function() {
            var $this = $(this);
            var $nextBtn = $this.find('.js-hero-next')[0];
            var $prevBtn = $this.find('.js-hero-prev')[0];
            var $pagination = $this.find('.swiper-pagination')[0];

            new Swiper($this[0], {
                loop: true,
                speed: 800,
                autoplay: {
                    delay: 6000,
                    disableOnInteraction: true,
                },
                effect: 'fade', 
                fadeEffect: { crossFade: true },
                pagination: {
                    el: $pagination,
                    clickable: true,
                },
                navigation: {
                    nextEl: $nextBtn,
                    prevEl: $prevBtn,
                },
            });
        });
    }
});
</script>