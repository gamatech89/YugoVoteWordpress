<?php
$term = $args['term'] ?? null;
if (!$term) return;

$term_id = $term->term_id;
$slug = sanitize_title($term->slug);

$description = term_description($term_id, 'voting_list_category');
$total_posts = get_voting_list_count_by_category($term_id);
$link = get_term_link($term);
$image_id = get_term_meta($term_id, 'thumbnail_id', true);
$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
?>

<a href="<?php echo esc_url($link); ?>"
   class="cs-subcategory-card cs-border--<?php echo esc_attr($slug); ?> cs-bg-hover--<?php echo esc_attr($slug); ?>">
    <?php if ($image_url): ?>
        <div class="cs-subcategory-icon" style="background-image: url('<?php echo esc_url($image_url); ?>');"></div>
    <?php endif; ?>

    <div class="cs-subcategory-card__content">
        <h3 class="cs-color--<?php echo esc_attr($slug); ?> cs-color-hover--<?php echo esc_attr($slug); ?>">
            <?php echo esc_html($term->name); ?>
        </h3>
        <div class="cs-subcategory-card__description"><?php echo wp_kses_post($description); ?></div>
        <p class="cs-subcategory-card__meta cs-bg--<?php echo esc_attr($slug); ?>">
            <?php echo esc_html($total_posts); ?> liste
        </p>
    </div>
</a>
