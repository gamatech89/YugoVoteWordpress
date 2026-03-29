<?php
/**
 * VIP Section Template for Homepage
 * 
 * Displays featured VIP persons in a carousel/grid.
 * Used by the [voting_vip_section] shortcode.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

$vip_persons = get_posts([
    'post_type'      => 'vip_person',
    'posts_per_page' => 10,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

if (empty($vip_persons)) return;
?>

<?php
wp_enqueue_style('ygv-vip-section', get_stylesheet_directory_uri() . '/css/vip-section.css', [], '1.0.0');
?>

<section class="cs-vip-section">
    <div class="cs-vip-section__header">
        <h2 class="cs-vip-section__title">⭐ VIP Liste</h2>
        <p class="cs-vip-section__subtitle">Pogledaj liste poznatih ličnosti</p>
    </div>
    
    <div class="cs-vip-grid">
        <?php foreach ($vip_persons as $vp): 
            $photo = get_the_post_thumbnail_url($vp->ID, 'medium');
            $subtitle = get_post_meta($vp->ID, '_vip_subtitle', true);
            $list_count = count(function_exists('ygv_get_vip_person_lists') ? ygv_get_vip_person_lists($vp->ID) : []);
        ?>
        <a href="<?php echo get_permalink($vp->ID); ?>" class="cs-vip-card">
            <div class="cs-vip-card__banner"></div>
            <div class="cs-vip-card__photo-wrap">
                <?php if ($photo): ?>
                    <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($vp->post_title); ?>" class="cs-vip-card__photo">
                <?php else: ?>
                    <div class="cs-vip-card__photo-placeholder">⭐</div>
                <?php endif; ?>
            </div>
            <div class="cs-vip-card__body">
                <span class="cs-vip-card__badge">⭐ VIP</span>
                <h3 class="cs-vip-card__name"><?php echo esc_html($vp->post_title); ?></h3>
                <?php if ($subtitle): ?>
                    <p class="cs-vip-card__subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
                <span class="cs-vip-card__stat"><strong><?php echo $list_count; ?></strong> lista</span>
                <div><span class="cs-vip-card__cta">Pogledaj liste →</span></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
