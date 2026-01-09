<?php
$current_term = get_queried_object();

$subcategories = get_terms([
    'taxonomy'   => 'voting_list_category',
    'parent'     => $current_term->term_id,
    'hide_empty' => false,
]);

if ($subcategories && !is_wp_error($subcategories)) : ?>
    <section class="cs-subcategory-section cs-container">
        <h2 class="cs-section-title">
            Najpopularnije oblasti iz kategorije <?php echo esc_html(single_term_title('', false)); ?>
        </h2>
        <div class="swiper cs-subcategory-carousel">
            <div class="swiper-wrapper">
                <?php foreach ($subcategories as $subcategory) : ?>
                    <div class="swiper-slide">
                        <?php get_template_part('inc/voting/templates/partials/category-card', null, ['term' => $subcategory]); ?>
                    </div> 
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>
<?php endif; ?>
