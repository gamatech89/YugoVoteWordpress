<?php
/**
 * Template Name: Ankete Arhiva
 * Description: Stranica za prikaz arhive svih anketa
 */

if (!defined('ABSPATH')) exit;

get_header();
?>

<style>
/* Inline critical styles to ensure they load */
.ygv-hidden { display: none !important; }
</style>

<main class="ygv-page ygv-page--ankete">
    <div class="ygv-container">
        
        <!-- Latest Poll (Anketa Dana) -->
        <?php 
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
            
            include get_stylesheet_directory() . '/inc/polls/templates/daily-poll.php';
            wp_reset_postdata();
        endif;
        ?>
        
        <!-- Archive Section -->
        <section class="ygv-section ygv-section--archive">
            <div class="ygv-poll-archive">
                <div class="ygv-poll-archive__header">
                    <h2 class="ygv-poll-archive__title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        Prethodne Ankete
                    </h2>
                    <p class="ygv-poll-archive__subtitle">Pogledajte kako su glasali ostali korisnici</p>
                </div>
                
                <div class="ygv-poll-archive__grid">
                    <?php
                    $paged = get_query_var('paged') ? get_query_var('paged') : 1;
                    $archive_query = new WP_Query([
                        'post_type'      => 'voting_poll',
                        'post_status'    => 'publish',
                        'posts_per_page' => 9,
                        'paged'          => $paged,
                        'offset'         => 1, // Skip the latest (shown above)
                    ]);

                    if ($archive_query->have_posts()) :
                        while ($archive_query->have_posts()) : $archive_query->the_post();
                            $poll_id     = get_the_ID();
                            $date        = get_the_date('d. M Y.');
                            $img_url     = get_the_post_thumbnail_url($poll_id, 'medium_large');
                            $total_votes = (int) get_post_meta($poll_id, '_cs_poll_total_votes', true);
                    ?>
                        <article class="ygv-poll-card">
                            <div class="ygv-poll-card__image">
                                <?php if ($img_url) : ?>
                                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php the_title_attribute(); ?>">
                                <?php else : ?>
                                    <div class="ygv-poll-card__image-placeholder">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M9 11l3 3L22 4"/>
                                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="ygv-poll-card__body">
                                <div class="ygv-poll-card__meta">
                                    <time class="ygv-poll-card__date">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <?php echo esc_html($date); ?>
                                    </time>
                                    <?php if ($total_votes > 0) : ?>
                                        <span class="ygv-poll-card__votes">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                <circle cx="9" cy="7" r="4"/>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                            </svg>
                                            <?php echo number_format_i18n($total_votes); ?> glasova
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="ygv-poll-card__title"><?php the_title(); ?></h3>
                                <div class="ygv-poll-card__content">
                                    <?php echo do_shortcode('[voting_poll id="' . $poll_id . '"]'); ?>
                                </div>
                            </div>
                        </article>
                    <?php
                        endwhile;
                    else :
                    ?>
                        <p class="ygv-poll-archive__empty">Nema prethodnih anketa.</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($archive_query->max_num_pages > 1) : ?>
                    <nav class="ygv-poll-archive__pagination">
                        <?php 
                        echo paginate_links([
                            'total' => $archive_query->max_num_pages,
                            'current' => $paged,
                            'prev_text' => '← Prethodna',
                            'next_text' => 'Sledeća →',
                        ]); 
                        ?>
                    </nav>
                <?php endif; ?>
                
                <?php wp_reset_postdata(); ?>
            </div>
        </section>
        
    </div>
</main>

<?php get_footer(); ?>
