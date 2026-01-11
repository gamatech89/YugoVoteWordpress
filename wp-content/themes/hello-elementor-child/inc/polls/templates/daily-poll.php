<?php
if (!defined('ABSPATH')) exit;
// Promenljive dostupne ovde: $poll_id, $title, $description, $image_url
?>
<div class="ygv-daily-poll">
    <!-- Left Side: Image & Info -->
    <div class="ygv-daily-poll__left">
        <?php if ($image_url) : ?>
            <div class="ygv-daily-poll__image">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
            </div>
        <?php else : ?>
            <div class="ygv-daily-poll__image ygv-daily-poll__image--placeholder">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </div>
        <?php endif; ?>
        
        <div class="ygv-daily-poll__info">
            <span class="ygv-daily-poll__badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                </svg>
                Anketa Dana
            </span>
            <?php if ($description) : ?>
                <p class="ygv-daily-poll__desc"><?php echo wp_kses_post($description); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Side: Poll Form -->
    <div class="ygv-daily-poll__right">
        <h3 class="ygv-daily-poll__title"><?php echo esc_html($title); ?></h3>
        <div class="ygv-daily-poll__widget">
            <?php echo do_shortcode('[voting_poll id="' . $poll_id . '"]'); ?>
        </div>
    </div>
</div>