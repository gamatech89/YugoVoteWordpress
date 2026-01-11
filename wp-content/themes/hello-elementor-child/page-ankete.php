<?php
/**
 * Template Name: Ankete Arhiva
 * Description: Stranica za prikaz arhive svih anketa
 */

if (!defined('ABSPATH')) exit;

get_header();
?>

<main class="ygv-page ygv-page--ankete">
    <div class="ygv-container">
        
        <?php 
        // Prikaži najnoviju anketu na vrhu
        $latest = new WP_Query([
            'post_type'      => 'voting_poll',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        
        if ($latest->have_posts()) : 
            $latest->the_post();
            $poll_id = get_the_ID();
            $title = get_the_title();
            $image_url = get_the_post_thumbnail_url($poll_id, 'large');
            $description = get_the_content();
            
            // Uključi daily poll template
            include get_stylesheet_directory() . '/inc/polls/templates/daily-poll.php';
            wp_reset_postdata();
        endif;
        ?>
        
        <!-- Sekcija: Arhiva Starih Anketa -->
        <section class="ygv-section ygv-section--archive">
            <?php echo do_shortcode('[voting_poll_archive]'); ?>
        </section>
        
    </div>
</main>

<?php get_footer(); ?>
