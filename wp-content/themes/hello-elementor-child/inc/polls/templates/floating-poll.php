<?php
/**
 * Floating Poll Button Template
 * Renders a fixed button on all pages that opens a slide-out panel with the latest poll
 */
if (!defined('ABSPATH')) exit;

// Get the latest poll
$latest_poll = new WP_Query([
    'post_type'      => 'voting_poll',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

if (!$latest_poll->have_posts()) return;

$latest_poll->the_post();
$poll_id = get_the_ID();
$poll_title = get_the_title();
wp_reset_postdata();

// Get the ankete archive page URL
$archive_page = get_pages(['meta_key' => '_wp_page_template', 'meta_value' => 'page-ankete.php']);
$archive_url = !empty($archive_page) ? get_permalink($archive_page[0]->ID) : home_url('/ankete/');
?>

<!-- Floating Poll Button -->
<button class="ygv-fab-poll" id="ygv-fab-poll" aria-label="Anketa Dana" title="Anketa Dana">
    <svg class="ygv-fab-poll__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 11l3 3L22 4"/>
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
    </svg>
    <span class="ygv-fab-poll__pulse"></span>
</button>

<!-- Poll Slide-out Panel -->
<div class="ygv-poll-panel" id="ygv-poll-panel">
    <div class="ygv-poll-panel__overlay" id="ygv-poll-panel-overlay"></div>
    <div class="ygv-poll-panel__content">
        <div class="ygv-poll-panel__header">
            <div class="ygv-poll-panel__badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                </svg>
                Anketa Dana
            </div>
            <button class="ygv-poll-panel__close" id="ygv-poll-panel-close" aria-label="Zatvori">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        
        <div class="ygv-poll-panel__body">
            <?php echo do_shortcode('[voting_poll id="' . $poll_id . '"]'); ?>
        </div>
        
        <div class="ygv-poll-panel__footer">
            <a href="<?php echo esc_url($archive_url); ?>" class="ygv-poll-panel__archive-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                Pogledaj sve ankete
            </a>
        </div>
    </div>
</div>

<script>
(function() {
    const fab = document.getElementById('ygv-fab-poll');
    const panel = document.getElementById('ygv-poll-panel');
    const overlay = document.getElementById('ygv-poll-panel-overlay');
    const closeBtn = document.getElementById('ygv-poll-panel-close');
    
    if (!fab || !panel) return;
    
    function openPanel() {
        panel.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
    
    function closePanel() {
        panel.classList.remove('is-open');
        document.body.style.overflow = '';
    }
    
    fab.addEventListener('click', openPanel);
    overlay.addEventListener('click', closePanel);
    closeBtn.addEventListener('click', closePanel);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && panel.classList.contains('is-open')) {
            closePanel();
        }
    });
})();
</script>
