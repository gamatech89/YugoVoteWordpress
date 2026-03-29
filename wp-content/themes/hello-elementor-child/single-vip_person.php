<?php
/**
 * Single VIP Person Template
 * 
 * Displays VIP person's profile with photo, bio, and all their curated lists.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

get_header();

$vip_id = get_the_ID();
$vip_name = get_the_title();
$vip_subtitle = get_post_meta($vip_id, '_vip_subtitle', true);
$vip_photo = get_the_post_thumbnail_url($vip_id, 'large');
$vip_bio = apply_filters('the_content', get_the_content());
$vip_instagram = get_post_meta($vip_id, '_vip_instagram', true);
$vip_twitter = get_post_meta($vip_id, '_vip_twitter', true);
$vip_website = get_post_meta($vip_id, '_vip_website', true);

// Get all curated lists
$curated_lists = function_exists('ygv_get_vip_person_lists') ? ygv_get_vip_person_lists($vip_id) : [];
?>

<?php
wp_enqueue_style('ygv-vip-profile', get_stylesheet_directory_uri() . '/css/vip-profile.css', [], '1.0.0');
?>

<div class="vip-profile">
    
    <!-- Hero Section -->
    <section class="vip-hero">
        <div class="vip-hero__photo-wrap">
            <?php if ($vip_photo): ?>
                <img src="<?php echo esc_url($vip_photo); ?>" alt="<?php echo esc_attr($vip_name); ?>" class="vip-hero__photo">
            <?php else: ?>
                <div class="vip-hero__photo-placeholder">⭐</div>
            <?php endif; ?>
        </div>
        
        <div class="vip-hero__info">
            <div class="vip-hero__badge">⭐ VIP Osoba</div>
            <h1 class="vip-hero__name"><?php echo esc_html($vip_name); ?></h1>
            <?php if ($vip_subtitle): ?>
                <p class="vip-hero__subtitle"><?php echo esc_html($vip_subtitle); ?></p>
            <?php endif; ?>
            
            <?php if ($vip_bio && trim(strip_tags($vip_bio))): ?>
                <div class="vip-hero__bio"><?php echo wp_kses_post($vip_bio); ?></div>
            <?php endif; ?>
            
            <?php if ($vip_instagram || $vip_twitter || $vip_website): ?>
            <div class="vip-hero__social">
                <?php if ($vip_instagram): ?>
                    <a href="<?php echo esc_url($vip_instagram); ?>" target="_blank" rel="noopener" aria-label="Instagram">📷</a>
                <?php endif; ?>
                <?php if ($vip_twitter): ?>
                    <a href="<?php echo esc_url($vip_twitter); ?>" target="_blank" rel="noopener" aria-label="Twitter">𝕏</a>
                <?php endif; ?>
                <?php if ($vip_website): ?>
                    <a href="<?php echo esc_url($vip_website); ?>" target="_blank" rel="noopener" aria-label="Website">🌐</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Curated Lists -->
    <section class="vip-lists">
        <div class="vip-lists__header">
            <h2 class="vip-lists__title">Liste od <?php echo esc_html($vip_name); ?></h2>
            <span class="vip-lists__count"><?php echo count($curated_lists); ?> lista</span>
        </div>
        
        <?php if (!empty($curated_lists)): ?>
        <div class="vip-lists__grid">
            <?php foreach ($curated_lists as $list): 
                $list_img = get_the_post_thumbnail_url($list->ID, 'medium');
                $list_excerpt = get_the_excerpt($list) ?: wp_trim_words(strip_tags($list->post_content), 15);
                $list_score = function_exists('get_total_score_for_voting_list') ? get_total_score_for_voting_list($list->ID) : 0;
                $items_count = count(get_post_meta($list->ID, '_voting_items', true) ?: []);
            ?>
            <a href="<?php echo get_permalink($list->ID); ?>" class="vip-list-card">
                <?php if ($list_img): ?>
                    <img src="<?php echo esc_url($list_img); ?>" alt="" class="vip-list-card__image">
                <?php else: ?>
                    <div class="vip-list-card__image-placeholder">📋</div>
                <?php endif; ?>
                <div class="vip-list-card__body">
                    <span class="vip-list-card__vip-badge">⭐ VIP Lista</span>
                    <h3 class="vip-list-card__title"><?php echo esc_html($list->post_title); ?></h3>
                    <?php if ($list_excerpt): ?>
                        <p class="vip-list-card__desc"><?php echo esc_html($list_excerpt); ?></p>
                    <?php endif; ?>
                    <div class="vip-list-card__stats">
                        <span class="vip-list-card__stat"><strong><?php echo $items_count; ?></strong> stavki</span>
                        <span class="vip-list-card__stat"><strong><?php echo number_format($list_score); ?></strong> poena</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="vip-empty">
            <div class="vip-empty__icon">📋</div>
            <p class="vip-empty__text">Još nema listi za ovog VIP korisnika.</p>
        </div>
        <?php endif; ?>
    </section>
    
</div>

<?php get_footer(); ?>
