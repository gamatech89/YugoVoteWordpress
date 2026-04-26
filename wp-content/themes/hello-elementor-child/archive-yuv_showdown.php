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

$manager        = new YUV_Showdown_Manager();
$active         = $manager->get_active_showdown();
$total_showdowns = wp_count_posts('yuv_showdown')->publish;
?>
<main class="site-main ygv-sd-archive-page" role="main">

    <!-- ========== HERO ========== -->
    <section class="ygv-page-hero ygv-page-hero--dvoboj">
        <div class="ygv-page-hero__inner">
            <div class="ygv-page-hero__label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.5 17.5L3 6V3h3l11.5 11.5"/><path d="M13 19l6-6"/><path d="M16 16l4 4"/><path d="M19 21l2-2"/></svg>
                Dvoboj
            </div>
            <h1 class="ygv-page-hero__title">Arena Dvoboja</h1>
            <p class="ygv-page-hero__desc">
                Glasaj za svog favorita u duelima 1 na 1 — gubitnik ispada, može biti samo jedan!
            </p>
            <div class="ygv-page-hero__stats">
                <div class="ygv-page-hero__stat">
                    <span class="ygv-page-hero__stat-value"><?php echo intval($total_showdowns); ?></span>
                    <span class="ygv-page-hero__stat-label">Dvoboja</span>
                </div>
                <?php if ($active): ?>
                <div class="ygv-page-hero__stat">
                    <span class="ygv-page-hero__stat-value">⚔️</span>
                    <span class="ygv-page-hero__stat-label">Aktivan dvoboj</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="ygv-page-hero__wave">
            <svg viewBox="0 0 1440 48" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,48 C360,0 1080,0 1440,48 L1440,48 L0,48 Z" fill="#f8fafc"/>
            </svg>
        </div>
    </section>

    <!-- ========== CONTENT ========== -->
    <div class="ygv-sd-archive-page__body">
        <div class="ygv-sd-single__inner">

            <?php if ($active): ?>
            <div class="showdown-wrap sd-dark" style="margin-bottom: 40px; border-radius: var(--sd-radius);">
                <?php echo do_shortcode('[yuv_showdown id="' . intval($active->ID) . '"]'); ?>
            </div>
            <?php endif; ?>

            <?php echo do_shortcode('[yuv_showdown_archive]'); ?>

        </div>
    </div>

</main>
<?php
get_footer();
