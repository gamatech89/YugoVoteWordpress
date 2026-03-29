<?php
/**
 * VIP Lists Section Template for Homepage
 * 
 * Displays VIP-curated voting lists in a grid.
 * Used by the [voting_vip_lists] shortcode.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

$vip_lists = get_posts([
    'post_type'      => 'voting_list',
    'posts_per_page' => 12,
    'post_status'    => 'publish',
    'meta_query'     => [
        [
            'key'   => '_is_vip_list',
            'value' => '1',
        ]
    ],
    'orderby' => 'date',
    'order'   => 'DESC',
]);

if (empty($vip_lists)) return;

wp_enqueue_style('ygv-vip-lists-section', get_stylesheet_directory_uri() . '/css/vip-lists-section.css', [], '1.0.0');
?>

<section class="cs-vip-lists-section">
    <div class="cs-vip-lists-section__header">
        <h2 class="cs-vip-lists-section__title">⭐ VIP Liste</h2>
        <p class="cs-vip-lists-section__subtitle">Liste kreirane od strane poznatih ličnosti</p>
    </div>
    
    <div class="cs-vip-lists-grid">
        <?php foreach ($vip_lists as $list): 
            $list_img = get_the_post_thumbnail_url($list->ID, 'medium');
            $list_excerpt = get_the_excerpt($list) ?: wp_trim_words(strip_tags($list->post_content), 15);
            $items_count = count(get_post_meta($list->ID, '_voting_items', true) ?: []);
            $list_score = function_exists('get_total_score_for_voting_list') ? get_total_score_for_voting_list($list->ID) : 0;
            
            // Get VIP person info
            $vip_person = function_exists('ygv_get_vip_person') ? ygv_get_vip_person($list->ID) : null;
        ?>
        <a href="<?php echo get_permalink($list->ID); ?>" class="cs-vip-lcard">
            <div class="cs-vip-lcard__image-wrap">
                <?php if ($list_img): ?>
                    <img src="<?php echo esc_url($list_img); ?>" alt="" class="cs-vip-lcard__image">
                <?php else: ?>
                    <div class="cs-vip-lcard__image-placeholder">📋</div>
                <?php endif; ?>
                
                <?php if ($vip_person && $vip_person['photo_url']): ?>
                <div class="cs-vip-lcard__person">
                    <img src="<?php echo esc_url($vip_person['photo_url']); ?>" alt="<?php echo esc_attr($vip_person['name']); ?>" class="cs-vip-lcard__person-photo">
                </div>
                <?php endif; ?>
            </div>
            
            <div class="cs-vip-lcard__body">
                <?php if ($vip_person): ?>
                <span class="cs-vip-lcard__vip-tag">⭐ <?php echo esc_html($vip_person['name']); ?></span>
                <?php endif; ?>
                <h3 class="cs-vip-lcard__title"><?php echo esc_html($list->post_title); ?></h3>
                <?php if ($list_excerpt): ?>
                    <p class="cs-vip-lcard__desc"><?php echo esc_html($list_excerpt); ?></p>
                <?php endif; ?>
                <div class="cs-vip-lcard__stats">
                    <span class="cs-vip-lcard__stat"><strong><?php echo $items_count; ?></strong> stavki</span>
                    <span class="cs-vip-lcard__stat"><strong><?php echo number_format($list_score); ?></strong> poena</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
