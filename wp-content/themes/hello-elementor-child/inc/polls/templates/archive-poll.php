<?php
if (!defined('ABSPATH')) exit;
// Promenljive: $query
?>
<div class="ygv-poll-archive">
    <div class="ygv-poll-archive__header">
        <h1 class="ygv-poll-archive__title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            Arhiva Anketa
        </h1>
        <p class="ygv-poll-archive__subtitle">Pogledajte prethodne ankete i kako su glasali ostali korisnici</p>
    </div>
    
    <div class="ygv-poll-archive__grid">
        <?php while ($query->have_posts()) : $query->the_post(); 
            $poll_id = get_the_ID();
            $total_votes = (int) get_post_meta($poll_id, '_cs_poll_total_votes', true);
            $date = get_the_date('d. M Y.');
        ?>
            <article class="ygv-poll-card">
                <div class="ygv-poll-card__header">
                    <time class="ygv-poll-card__date">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <?php echo esc_html($date); ?>
                    </time>
                </div>
                
                <h2 class="ygv-poll-card__title"><?php the_title(); ?></h2>
                
                <div class="ygv-poll-card__content">
                    <?php echo do_shortcode('[voting_poll id="' . $poll_id . '"]'); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>

    <?php if ($query->max_num_pages > 1) : ?>
        <nav class="ygv-poll-archive__pagination">
            <?php 
            echo paginate_links([
                'total' => $query->max_num_pages,
                'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Prethodna',
                'next_text' => 'Sledeća <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
            ]); 
            ?>
        </nav>
    <?php endif; ?>
</div>