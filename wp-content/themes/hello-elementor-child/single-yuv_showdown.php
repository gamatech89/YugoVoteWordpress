<?php
/**
 * Single template for yuv_showdown CPT
 * Renders the showdown arena/results for a specific showdown post.
 */
if (!defined('ABSPATH')) exit;

get_header();
?>
<main class="site-main ygv-sd-single" role="main">
    <div class="ygv-sd-single__inner">
        <div class="showdown-wrap sd-dark">
            <?php echo do_shortcode('[yuv_showdown id="' . intval(get_the_ID()) . '"]'); ?>
        </div>

        <div class="sd-all-link">
            <a href="<?php echo esc_url(get_post_type_archive_link('yuv_showdown')); ?>" class="sd-btn--cta">
                <i class="ri-archive-line"></i>
                Vidi sve dvoboje
            </a>
        </div>
    </div>
</main>
<?php
get_footer();
