<?php
/**
 * Template Name: Ankete Arhiva
 * Description: Stranica za prikaz arhive svih anketa
 */

if (!defined('ABSPATH'))
    exit;

get_header();
?>

<style>
    /* Inline critical styles to ensure they load */
    .ygv-hidden {
        display: none !important;
    }
</style>

<main class="ygv-page ygv-page--ankete">

    <!-- ========== HERO ========== -->
    <?php
    $total_polls = wp_count_posts('voting_poll')->publish;
    ?>
    <section class="ygv-page-hero ygv-page-hero--polls">
        <div class="ygv-page-hero__inner">
            <div class="ygv-page-hero__label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M9 11l3 3L22 4" />
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                </svg>
                Ankete
            </div>
            <h1 class="ygv-page-hero__title">Otvori dušu</h1>
            <p class="ygv-page-hero__desc">
                Ankete o temama koje su bitne. Glasaj, vidi rezultate i poredi se sa zajednicom.
            </p>
            <div class="ygv-page-hero__stats">
                <div class="ygv-page-hero__stat">
                    <span class="ygv-page-hero__stat-value"><?php echo intval($total_polls); ?></span>
                    <span class="ygv-page-hero__stat-label">Anketa</span>
                </div>
                <?php if (get_current_user_id()):
                    global $wpdb;
                    $voted = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(DISTINCT poll_id) FROM {$wpdb->prefix}cs_poll_votes WHERE user_id = %d",
                        get_current_user_id()
                    ));
                    ?>
                    <div class="ygv-page-hero__stat">
                        <span class="ygv-page-hero__stat-value"><?php echo intval($voted); ?></span>
                        <span class="ygv-page-hero__stat-label">Tvojih glasova</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="ygv-page-hero__wave">
            <svg viewBox="0 0 1440 48" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,48 C360,0 1080,0 1440,48 L1440,48 L0,48 Z" fill="#f8fafc" />
            </svg>
        </div>
    </section>

    <div class="ygv-container">

        <!-- Latest Poll (Anketa Dana) -->
        <?php
        $latest = new WP_Query([
            'post_type' => 'voting_poll',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if ($latest->have_posts()):
            $latest->the_post();
            $poll_id = get_the_ID();
            $title = get_the_title();
            $image_url = get_the_post_thumbnail_url($poll_id, 'large');
            $description = get_the_content();

            include get_stylesheet_directory() . '/inc/polls/templates/daily-poll.php';
            wp_reset_postdata();
        endif;
        ?>

        <!-- Archive Section -->
        <section class="ygv-section ygv-section--archive">
            <div class="ygv-poll-archive">
                <div class="ygv-poll-archive__header">
                    <h2 class="ygv-poll-archive__title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M9 11l3 3L22 4" />
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                        </svg>
                        Prethodne Ankete
                    </h2>
                    <p class="ygv-poll-archive__subtitle">Pogledajte kako su glasali ostali korisnici</p>
                </div>

                <?php
                $per_page      = 9;
                $archive_query = new WP_Query([
                    'post_type'      => 'voting_poll',
                    'post_status'    => 'publish',
                    'posts_per_page' => $per_page,
                    'offset'         => 1,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ]);
                $total_archive = max(0, $total_polls - 1);
                $max_pages     = (int) ceil($total_archive / $per_page);
                $card_template = get_stylesheet_directory() . '/inc/polls/templates/poll-card.php';
                ?>
                <div class="ygv-poll-archive__grid"
                    id="ygv-poll-grid"
                    data-page="1"
                    data-max-pages="<?php echo esc_attr($max_pages); ?>"
                    data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('cs_load_more_polls')); ?>">

                    <?php
                    if ($archive_query->have_posts()):
                        while ($archive_query->have_posts()):
                            $archive_query->the_post();
                            $poll_id     = get_the_ID();
                            $date        = get_the_date('d. M Y.');
                            $img_url     = get_the_post_thumbnail_url($poll_id, 'medium_large');
                            $total_votes = (int) get_post_meta($poll_id, '_cs_poll_total_votes', true);
                            include $card_template;
                        endwhile;
                    else: ?>
                        <p class="ygv-poll-archive__empty">Nema prethodnih anketa.</p>
                    <?php endif;
                    wp_reset_postdata(); ?>
                </div>

                <?php if ($max_pages > 1): ?>
                    <div class="ygv-poll-archive__sentinel" id="ygv-poll-sentinel">
                        <div class="ygv-poll-archive__spinner"></div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</main>

<?php get_footer(); ?>