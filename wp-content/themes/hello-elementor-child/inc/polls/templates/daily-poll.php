<?php
if (!defined('ABSPATH')) exit;
// Promenljive dostupne ovde: $poll_id, $title, $description, $image_url
?>
<div class="ygv-daily-poll">
    <div class="ygv-daily-poll__header">
        <span class="ygv-daily-poll__badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
            </svg>
            Anketa Dana
        </span>
        <?php if ($image_url) : ?>
            <div class="ygv-daily-poll__image">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
            </div>
        <?php endif; ?>
    </div>
    
    <div class="ygv-daily-poll__content">
        <h3 class="ygv-daily-poll__title"><?php echo esc_html($title); ?></h3>
        
        <?php if ($description) : ?>
            <p class="ygv-daily-poll__desc"><?php echo wp_kses_post($description); ?></p>
        <?php endif; ?>
        
        <div class="ygv-daily-poll__widget">
            <?php echo do_shortcode('[voting_poll id="' . $poll_id . '"]'); ?>
        </div>
    </div>
</div>