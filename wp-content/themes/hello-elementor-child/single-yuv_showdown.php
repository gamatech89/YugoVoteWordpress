<?php
/**
 * Single template for yuv_showdown CPT
 * Renders the showdown arena/results for a specific showdown post.
 */
if (!defined('ABSPATH')) exit;

get_header();
?>
<main class="site-main" role="main">
    <div style="margin-top: 60px; margin-bottom: 60px;">
        <div style="max-width: 1200px; margin: 0 auto; background: #4456a6; padding: 60px 40px; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.15);">
            <?php echo do_shortcode('[yuv_showdown id="' . intval(get_the_ID()) . '"]'); ?>
        </div>
    </div>
</main>
<?php
get_footer();
