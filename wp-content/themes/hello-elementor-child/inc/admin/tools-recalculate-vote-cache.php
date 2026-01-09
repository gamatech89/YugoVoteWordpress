<?php
// Admin tool to recalculate vote caches for all voting items

add_action('admin_menu', function () {
    add_submenu_page(
        'tools.php',
        'Recalculate Vote Cache',
        'Recalculate Vote Cache',
        'manage_options',
        'recalculate-vote-cache',
        'render_recalculate_vote_cache_page'
    );
});

function render_recalculate_vote_cache_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['recalculate_votes'])) {
        $items = get_posts([
            'post_type' => 'voting_items',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);

        foreach ($items as $item_id) {
            if (function_exists('update_vote_score_cache')) {
                update_vote_score_cache($item_id);
            }
        }

        echo '<div class="notice notice-success"><p>✅ All vote caches have been recalculated.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Recalculate Vote Caches</h1>
        <p>This will go through all <strong>voting items</strong> and update their score and vote count cache.</p>
        <form method="post">
            <?php submit_button('Run Recalculation', 'primary', 'recalculate_votes'); ?>
        </form>
    </div>
    <?php
}
