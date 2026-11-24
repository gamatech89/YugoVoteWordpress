<?php
/**
 * Stranica ankete — izgled B, par stranici kviza: slika levo, desno naslov, opis i sama anketa.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()):
    the_post();
    $poll_id = get_the_ID();
    $title   = get_the_title($poll_id);
    $content = trim((string) get_post_field('post_content', $poll_id));
    ?>
    <main id="content" class="site-main ygv-landing ygv-landing--poll">

        <?php ygv_landing_hero('Anketa', ygv_breadcrumb_trail_for(get_post($poll_id))); ?>

        <div class="ygv-landing-wrap">
            <article class="ygv-landing-card">
                <div class="ygv-landing-card__media">
                    <div class="ygv-landing-card__img">
                        <?php if (has_post_thumbnail($poll_id)): ?>
                            <?php echo get_the_post_thumbnail($poll_id, 'large', ['alt' => esc_attr(wp_strip_all_tags($title)), 'loading' => 'eager', 'fetchpriority' => 'high']); ?>
                        <?php else: ?>
                            <div class="ygv-landing-card__placeholder">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path d="M9 11l3 3L22 4"/>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ygv-landing-card__body">
                    <h1 class="ygv-landing-card__title"><?php echo esc_html($title); ?></h1>
                    <?php if ($content !== ''): ?>
                        <div class="ygv-landing-card__desc"><?php echo wpautop(wp_kses_post($content)); ?></div>
                    <?php endif; ?>
                    <div class="ygv-landing-card__poll">
                        <?php echo do_shortcode('[voting_poll id="' . (int) $poll_id . '"]'); ?>
                    </div>
                </div>
            </article>
        </div>

        <?php $related = ygv_poll_related_ids($poll_id); ?>
        <?php if ($related): ?>
            <section class="ygv-landing-more">
                <div class="ygv-landing-more__in">
                    <h2 class="ygv-landing-more__title"><?php ygv_icon_e('bar-chart', 26); ?>Još anketa</h2>
                    <div class="ygv-landing-more__grid ygv-landing-more__grid--polls">
                        <?php
                        $card_template = get_stylesheet_directory() . '/inc/polls/templates/poll-card.php';
                        foreach ($related as $rid) {
                            $poll_id     = $rid; // promenljive koje poll-card.php očekuje
                            $img_url     = get_the_post_thumbnail_url($rid, 'medium_large');
                            $total_votes = (int) get_post_meta($rid, '_cs_poll_total_votes', true);
                            include $card_template;
                        }
                        ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    </main>
    <?php
endwhile;

get_footer();
