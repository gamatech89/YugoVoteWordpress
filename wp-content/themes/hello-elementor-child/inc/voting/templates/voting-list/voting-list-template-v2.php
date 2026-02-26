<?php
/**
 * Voting List Template V2 - Multiple Layout Options
 * 
 * Layouts:
 * - grid: Card grid (2-3 columns, mobile-first vertical cards)
 * - compact: Compact list with always-visible voting
 * - classic: Improved horizontal cards (original style, enhanced)
 * 
 * @package HelloElementorChild
 */

if (!defined('ABSPATH'))
    exit;

// Get the voting list ID
$voting_list_id = get_query_var('voting_list_id');
if (!$voting_list_id) {
    echo "<p>No voting list ID found.</p>";
    return;
}

$list_post = get_post($voting_list_id);
if (!$list_post || $list_post->post_type !== 'voting_list') {
    echo "<p>Invalid voting list.</p>";
    return;
}

// Get layout preference (can be set via shortcode attr or list meta)
$layout_override = get_query_var('voting_layout_override');
$layout = $layout_override ?: get_post_meta($voting_list_id, '_voting_layout', true) ?: 'grid';
// Allow URL parameter override for testing: ?layout=compact
if (isset($_GET['layout']) && in_array($_GET['layout'], ['grid', 'compact', 'classic'])) {
    $layout = sanitize_text_field($_GET['layout']);
}

// Fetch Voting Scale (default 10)
$voting_scale = get_post_meta($voting_list_id, '_voting_scale', true) ?: 10;

// Get category info for theming
$list_categories = get_the_terms($voting_list_id, 'voting_list_category');
$category_color = '#2D3A8C'; // Default navy
$category_name = '';

if (!empty($list_categories) && !is_wp_error($list_categories)) {
    $cat = $list_categories[0];
    $category_name = $cat->name;

    // Get top-level parent for color
    while ($cat->parent != 0) {
        $parent = get_term($cat->parent, 'voting_list_category');
        if (!is_wp_error($parent) && $parent) {
            $cat = $parent;
        }
        else {
            break;
        }
    }
    $category_color = get_term_meta($cat->term_id, 'category_color', true) ?: '#2D3A8C';
}

// Fetch voting items
global $wpdb;
$table_name = $wpdb->prefix . "voting_list_item_relations";

$voting_items_ids = get_post_meta($voting_list_id, '_voting_items', true);
if (!is_array($voting_items_ids) || empty($voting_items_ids)) {
    echo "<p>No voting items found for this list.</p>";
    return;
}

// Query items
$query = new WP_Query([
    'post_type' => 'voting_items',
    'post_status' => 'publish',
    'post__in' => $voting_items_ids,
    'posts_per_page' => -1,
    'orderby' => 'post__in'
]);

if (!$query->have_posts()) {
    echo "<p>No voting items found.</p>";
    return;
}

// Prepare items data
$items = [];
while ($query->have_posts()) {
    $query->the_post();
    $item_id = get_the_ID();

    $default_short_desc = get_post_meta($item_id, '_short_description', true);
    $default_image = get_the_post_thumbnail_url($item_id, 'medium') ?: get_template_directory_uri() . '/images/default.jpg';

    // Check pivot table for overrides
    $pivot_data = $wpdb->get_row($wpdb->prepare(
        "SELECT short_description, custom_image_url, url FROM $table_name WHERE voting_list_id = %d AND voting_item_id = %d",
        $voting_list_id, $item_id
    ), ARRAY_A);

    $items[] = [
        'id' => $item_id,
        'title' => get_the_title(),
        'short_desc' => !empty($pivot_data['short_description']) ? $pivot_data['short_description'] : $default_short_desc,
        'image' => !empty($pivot_data['custom_image_url']) ? $pivot_data['custom_image_url'] : $default_image,
        'video_url' => !empty($pivot_data['url']) ? $pivot_data['url'] : get_post_meta($item_id, '_item_url', true),
        'permalink' => get_permalink($item_id),
        'ranking' => array_search($item_id, $voting_items_ids) + 1,
    ];
}
wp_reset_postdata();

?>

<style>
    :root {
        --ygv-list-color: <?php echo esc_attr($category_color);
        ?>;
    }
</style>

<?php
// Show layout switcher if enabled (for testing)
$show_switcher = get_query_var('show_layout_switcher', true);
if ($show_switcher): ?>
<!-- Layout Switcher (for testing - can be removed in production) -->
<div class="ygv-layout-switcher">
    <span>Prikaz:</span>
    <a href="?layout=grid" class="<?php echo $layout === 'grid' ? 'active' : ''; ?>">Mreža</a>
    <a href="?layout=compact" class="<?php echo $layout === 'compact' ? 'active' : ''; ?>">Kompaktno</a>
    <a href="?layout=classic" class="<?php echo $layout === 'classic' ? 'active' : ''; ?>">Klasično</a>
</div>
<?php
endif; ?>

<section class="ygv-voting-list ygv-voting-list--<?php echo esc_attr($layout); ?>"
    data-list-id="<?php echo esc_attr($voting_list_id); ?>" data-layout="<?php echo esc_attr($layout); ?>">

    <?php if ($layout === 'grid'): ?>
    <!-- ========================================
         LAYOUT A: GRID CARDS (Mobile-First)
         ======================================== -->
    <div class="ygv-vote-grid">
        <?php foreach ($items as $item):
        $is_top3 = $item['ranking'] <= 3;
        $rank_class = $is_top3 ? 'ygv-rank-' . $item['ranking'] : '';
?>
        <div class="ygv-vote-card <?php echo $rank_class; ?>" data-item-id="<?php echo esc_attr($item['id']); ?>">

            <div class="ygv-vote-card__media">
                <span class="ygv-vote-card__rank">
                    <?php echo $item['ranking']; ?>
                </span>
                <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>"
                    class="ygv-vote-card__img">
                <?php if ($item['video_url']): ?>
                <button class="ygv-vote-card__play" data-video-url="<?php echo esc_url($item['video_url']); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>
                <?php
        endif; ?>
            </div>

            <div class="ygv-vote-card__body">
                <h3 class="ygv-vote-card__title">
                    <a href="<?php echo esc_url($item['permalink']); ?>">
                        <?php echo esc_html($item['title']); ?>
                    </a>
                </h3>
                <?php if ($item['short_desc']): ?>
                <p class="ygv-vote-card__desc">
                    <?php echo esc_html(wp_trim_words($item['short_desc'], 12)); ?>
                </php
        endif; ?>
                <?php endif; ?>

                <div class="ygv-vote-card__score">
                    <span class="ygv-vote-card__points" data-item-id="<?php echo esc_attr($item['id']); ?>">0</span>
                    <span class="ygv-vote-card__label">poena</span>
                </div>

                <div class="ygv-vote-card__buttons">
                    <?php for ($i = 1; $i <= $voting_scale; $i++): ?>
                    <button class="ygv-vote-btn" data-value="<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </button>
                    <?php
        endfor; ?>
                </div>
            </div>
        </div>
        <?php
    endforeach; ?>
    </div>

    <?php
elseif ($layout === 'compact'): ?>
    <!-- ========================================
         LAYOUT B: COMPACT LIST (Always Visible Voting)
         ======================================== -->
    <div class="ygv-vote-compact">
        <?php foreach ($items as $item):
        $is_top3 = $item['ranking'] <= 3;
?>
        <div class="ygv-compact-row <?php echo $is_top3 ? 'ygv-compact-row--top' . $item['ranking'] : ''; ?>"
            data-item-id="<?php echo esc_attr($item['id']); ?>">

            <div class="ygv-compact-row__rank">
                <?php if ($item['ranking'] === 1): ?>
                <span class="ygv-medal ygv-medal--gold">🥇</span>
                <?php
        elseif ($item['ranking'] === 2): ?>
                <span class="ygv-medal ygv-medal--silver">🥈</span>
                <?php
        elseif ($item['ranking'] === 3): ?>
                <span class="ygv-medal ygv-medal--bronze">🥉</span>
                <?php
        else: ?>
                <span class="ygv-compact-row__num">
                    <?php echo $item['ranking']; ?>
                </span>
                <?php
        endif; ?>
            </div>

            <div class="ygv-compact-row__thumb">
                <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
            </div>

            <div class="ygv-compact-row__info">
                <h3 class="ygv-compact-row__title">
                    <a href="<?php echo esc_url($item['permalink']); ?>">
                        <?php echo esc_html($item['title']); ?>
                    </a>
                </h3>
                <?php if ($item['short_desc']): ?>
                <p class="ygv-compact-row__desc">
                    <?php echo esc_html(wp_trim_words($item['short_desc'], 15)); ?>
                </p>
                <?php
        endif; ?>
            </div>

            <div class="ygv-compact-row__score">
                <span class="ygv-compact-row__points" data-item-id="<?php echo esc_attr($item['id']); ?>">0</span>
                <span class="ygv-compact-row__label">poena</span>
            </div>

            <div class="ygv-compact-row__vote">
                <?php for ($i = 1; $i <= $voting_scale; $i++): ?>
                <button class="ygv-vote-btn ygv-vote-btn--compact" data-value="<?php echo $i; ?>">
                    <?php echo $i; ?>
                </button>
                <?php
        endfor; ?>
            </div>
        </div>
        <?php
    endforeach; ?>
    </div>

    <?php
else: ?>
    <!-- ========================================
         LAYOUT C: CLASSIC HORIZONTAL (Enhanced)
         ======================================== -->
    <div class="ygv-vote-classic">
        <?php foreach ($items as $item):
        $is_top3 = $item['ranking'] <= 3;
?>
        <div class="ygv-classic-card <?php echo $is_top3 ? 'ygv-classic-card--top' . $item['ranking'] : ''; ?>"
            data-item-id="<?php echo esc_attr($item['id']); ?>">

            <div class="ygv-classic-card__media">
                <span class="ygv-classic-card__rank">
                    <?php echo $item['ranking']; ?>
                </span>
                <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
                <?php if ($item['video_url']): ?>
                <button class="ygv-classic-card__play" data-video-url="<?php echo esc_url($item['video_url']); ?>">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>
                <?php
        endif; ?>
            </div>

            <div class="ygv-classic-card__content">
                <div class="ygv-classic-card__header">
                    <h2 class="ygv-classic-card__title">
                        <a href="<?php echo esc_url($item['permalink']); ?>">
                            <?php echo esc_html($item['title']); ?>
                        </a>
                    </h2>
                    <?php if ($item['short_desc']): ?>
                    <p class="ygv-classic-card__desc">
                        <?php echo wp_kses_post($item['short_desc']); ?>
                    </p>
                    <?php
        endif; ?>
                </div>

                <div class="ygv-classic-card__voting">
                    <div class="ygv-classic-card__score">
                        <span class="ygv-classic-card__points"
                            data-item-id="<?php echo esc_attr($item['id']); ?>">0</span>
                        <span class="ygv-classic-card__label">poena</span>
                    </div>

                    <div class="ygv-classic-card__buttons">
                        <?php for ($i = 1; $i <= $voting_scale; $i++): ?>
                        <button class="ygv-vote-btn ygv-vote-btn--classic" data-value="<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </button>
                        <?php
        endfor; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    endforeach; ?>
    </div>
    <?php
endif; ?>

</section>