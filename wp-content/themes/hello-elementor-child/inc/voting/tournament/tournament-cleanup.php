<?php
/**
 * Tournament Cleanup Utilities
 * Functions for cleaning up orphaned tournament matches
 */

if (!defined('ABSPATH')) exit;

/**
 * Find and delete all orphaned tournament matches
 * (matches whose parent tournament no longer exists)
 * 
 * @return array Results with counts of found and deleted matches
 */
function yuv_cleanup_orphaned_tournament_matches() {
    global $wpdb;
    
    $results = [
        'found' => 0,
        'deleted' => 0,
        'errors' => []
    ];
    
    // Find all voting_list posts marked as tournament matches
    $tournament_matches = get_posts([
        'post_type' => 'voting_list',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_is_tournament_match',
                'value' => '1',
                'compare' => '='
            ]
        ],
        'fields' => 'ids'
    ]);
    
    if (empty($tournament_matches)) {
        return $results;
    }
    
    // Check each match to see if its tournament still exists
    foreach ($tournament_matches as $match_id) {
        $tournament_id = get_post_meta($match_id, '_yuv_tournament_id', true);
        
        if (!$tournament_id) {
            // Match has no tournament ID - orphaned
            $results['found']++;
            
            if (wp_delete_post($match_id, true)) {
                $results['deleted']++;
            } else {
                $results['errors'][] = "Failed to delete match #{$match_id}";
            }
            continue;
        }
        
        // Check if tournament exists
        $tournament_status = get_post_status($tournament_id);
        
        if (!$tournament_status || $tournament_status === false) {
            // Tournament doesn't exist - orphaned match
            $results['found']++;
            
            if (wp_delete_post($match_id, true)) {
                $results['deleted']++;
            } else {
                $results['errors'][] = "Failed to delete match #{$match_id} (tournament #{$tournament_id})";
            }
        }
    }
    
    return $results;
}

/**
 * Admin page for cleanup utility
 */
function yuv_register_cleanup_admin_page() {
    add_submenu_page(
        'edit.php?post_type=yuv_tournament',
        'Cleanup Orphaned Matches',
        'Cleanup',
        'manage_options',
        'yuv-tournament-cleanup',
        'yuv_render_cleanup_admin_page'
    );
}
add_action('admin_menu', 'yuv_register_cleanup_admin_page');

/**
 * Render cleanup admin page
 */
function yuv_render_cleanup_admin_page() {
    // Handle cleanup request
    if (isset($_POST['yuv_cleanup_orphans']) && check_admin_referer('yuv_cleanup_orphans')) {
        $results = yuv_cleanup_orphaned_tournament_matches();
        
        echo '<div class="notice notice-success"><p>';
        echo sprintf(
            'Cleanup complete! Found %d orphaned matches, deleted %d successfully.',
            $results['found'],
            $results['deleted']
        );
        echo '</p></div>';
        
        if (!empty($results['errors'])) {
            echo '<div class="notice notice-error"><p>';
            echo 'Errors:<br>' . implode('<br>', $results['errors']);
            echo '</p></div>';
        }
    }
    
    // Count current orphaned matches
    global $wpdb;
    $all_matches = get_posts([
        'post_type' => 'voting_list',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_is_tournament_match',
                'value' => '1',
                'compare' => '='
            ]
        ],
        'fields' => 'ids'
    ]);
    
    $orphaned_count = 0;
    $orphaned_list = [];
    
    foreach ($all_matches as $match_id) {
        $tournament_id = get_post_meta($match_id, '_yuv_tournament_id', true);
        
        if (!$tournament_id || !get_post_status($tournament_id)) {
            $orphaned_count++;
            $match_title = get_the_title($match_id);
            $orphaned_list[] = sprintf(
                '#%d: %s (Tournament ID: %s)',
                $match_id,
                $match_title ?: '(no title)',
                $tournament_id ?: 'missing'
            );
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Tournament Match Cleanup</h1>
        
        <div class="card">
            <h2>Orphaned Matches</h2>
            <p>
                Orphaned matches are tournament voting_list posts whose parent tournament has been deleted.
                These matches can't be accessed normally but may still appear in queries.
            </p>
            
            <?php if ($orphaned_count > 0): ?>
                <p><strong>Found <?php echo $orphaned_count; ?> orphaned match(es):</strong></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <?php foreach ($orphaned_list as $item): ?>
                        <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <form method="post">
                    <?php wp_nonce_field('yuv_cleanup_orphans'); ?>
                    <button 
                        type="submit" 
                        name="yuv_cleanup_orphans" 
                        class="button button-primary"
                        onclick="return confirm('This will permanently delete <?php echo $orphaned_count; ?> orphaned match(es). Continue?');"
                    >
                        Delete Orphaned Matches
                    </button>
                </form>
            <?php else: ?>
                <p><strong>✓ No orphaned matches found.</strong> All tournament matches have valid parent tournaments.</p>
            <?php endif; ?>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>How it Works</h2>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>When you delete a tournament, all its matches are automatically deleted (as of this update)</li>
                <li>This cleanup tool finds matches from tournaments deleted before automatic cleanup was implemented</li>
                <li>Orphaned matches are identified by checking if their <code>_yuv_tournament_id</code> references a non-existent post</li>
                <li>Deletion is permanent (bypasses trash)</li>
            </ul>
        </div>
    </div>
    <?php
}
