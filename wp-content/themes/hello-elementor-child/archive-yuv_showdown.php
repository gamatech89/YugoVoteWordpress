<?php
/**
 * The template for displaying archive pages for the Showdown custom post type.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

get_header();

// We can just use the built-in shortcode to display the archive correctly
?>
<main class="site-main" role="main">
    <header class="page-header" style="text-align: center; padding: 40px 20px;">
        <h1 class="page-title">Showdown Arena</h1>
        <p>Glasaj za svog favorita u duelima 1 na 1!</p>
    </header>

    <div class="page-content" style="max-width: 1200px; margin: 0 auto; padding: 0 20px 60px;">
        <?php echo do_shortcode('[yuv_showdown_archive]'); ?>
    </div>
</main>

<?php
get_footer();
