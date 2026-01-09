<?php
if (!defined('ABSPATH')) exit;
// Promenljive dostupne ovde: $poll_id, $title, $description, $image_url
?>
<section class="cs-daily-poll-section">
    <div class="cs-container">
        <div class="cs-poll-wrapper-split">
            
            <div class="cs-poll-info">
                <span class="cs-poll-badge">ANKETA DANA</span>
                <h3 class="cs-poll-title"><?php echo esc_html($title); ?></h3>
                
                <?php if ($description) : ?>
                    <div class="cs-poll-desc"><?php echo wp_kses_post($description); ?></div>
                <?php endif; ?>

                <?php if ($image_url) : ?>
                    <div class="cs-poll-image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
                    </div>
                <?php endif; ?>
            </div>

            <div class="cs-poll-action">
                <?php 
                // Ovde pozivamo običan shortcode za samu formu
                echo do_shortcode('[voting_poll id="' . $poll_id . '"]'); 
                ?>
            </div>

        </div>
    </div>
</section>