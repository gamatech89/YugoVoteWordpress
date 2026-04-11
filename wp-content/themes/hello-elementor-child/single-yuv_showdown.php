<?php
/**
 * Single template for yuv_showdown CPT
 * Renders the showdown arena/results for a specific showdown post.
 */
if (!defined('ABSPATH')) exit;

get_header();
?>
<main class="site-main" role="main">
    <div style="max-width: 1200px; margin: 0 auto;">
        <?php echo do_shortcode('[yuv_showdown id="' . intval(get_the_ID()) . '"]'); ?>
    </div>
</main>
<?php
get_footer();
