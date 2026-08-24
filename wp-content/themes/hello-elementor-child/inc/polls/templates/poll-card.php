<?php
if (!defined('ABSPATH')) exit;
// Expected vars: $poll_id (int), $img_url (string|false), $total_votes (int)
?>
<article class="ygv-poll-card">
    <div class="ygv-poll-card__image">
        <?php if ($img_url): ?>
            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(get_the_title($poll_id)); ?>">
        <?php else: ?>
            <div class="ygv-poll-card__image-placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 11l3 3L22 4" />
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                </svg>
            </div>
        <?php endif; ?>
    </div>
    <div class="ygv-poll-card__body">
        <?php if ($total_votes > 0): ?>
            <div class="ygv-poll-card__meta">
                <span class="ygv-poll-card__votes">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <?php echo number_format_i18n($total_votes); ?> glasova
                </span>
            </div>
        <?php endif; ?>
        <h3 class="ygv-poll-card__title"><?php echo esc_html(get_the_title($poll_id)); ?></h3>
        <?php
        $poll_desc = wp_strip_all_tags(get_post_field('post_content', $poll_id));
        if ($poll_desc): ?>
            <p class="ygv-poll-card__desc"><?php echo esc_html(wp_trim_words($poll_desc, 28, '…')); ?></p>
        <?php endif; ?>
        <div class="ygv-poll-card__content">
            <?php echo do_shortcode('[voting_poll id="' . $poll_id . '"]'); ?>
        </div>
    </div>
</article>
