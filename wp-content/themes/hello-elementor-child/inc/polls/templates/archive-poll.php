<?php
if (!defined('ABSPATH')) exit;
// Promenljive: $query
?>
<div class="cs-poll-archive-grid">
    <?php while ($query->have_posts()) : $query->the_post(); 
        $poll_id = get_the_ID();
        $total_votes = (int) get_post_meta($poll_id, '_cs_poll_total_votes', true);
    ?>
        <div class="cs-archive-poll-card">
            <h4><?php the_title(); ?></h4>
            <div class="cs-archive-meta">
                <span><?php echo get_the_date('d.m.Y'); ?></span>
                <span class="cs-sep">•</span>
                <span>Ukupno glasova: <strong><?php echo $total_votes; ?></strong></span>
            </div>
            
            <div class="cs-poll-mini-render">
                 <?php echo do_shortcode('[voting_poll id="' . $poll_id . '"]'); ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php
if ($query->max_num_pages > 1) {
    echo '<div class="cs-pagination">' . paginate_links(['total' => $query->max_num_pages]) . '</div>';
}
?>