<?php
/**
 * The template for displaying archive pages for the Showdown custom post type.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="site-main ygv-sd-single" role="main">
    <div class="ygv-sd-single__inner">

        <?php
        // Show active dvoboj at the top if one exists
        $manager = new YUV_Showdown_Manager();
        $active  = $manager->get_active_showdown();
        if ($active):
        ?>
        <div class="showdown-wrap sd-dark" style="margin-bottom: 40px; border-radius: var(--sd-radius);">
            <?php echo do_shortcode('[yuv_showdown id="' . intval($active->ID) . '"]'); ?>
        </div>
        <?php endif; ?>

        <?php echo do_shortcode('[yuv_showdown_archive]'); ?>

    </div>
</main>
<?php
get_footer();
