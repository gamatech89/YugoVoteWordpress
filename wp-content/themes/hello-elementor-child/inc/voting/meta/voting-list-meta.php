<?php

function add_voting_list_metabox() {
    add_meta_box(
        'voting_list_metabox',
        'Voting List Settings',
        'voting_list_metabox_callback',
        'voting_list',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_voting_list_metabox');

function voting_list_metabox_callback($post) {
    $selected_items = get_post_meta($post->ID, '_voting_items', true);
    $selected_items = is_array($selected_items) ? $selected_items : [];
    $voting_scale = get_post_meta($post->ID, '_voting_scale', true) ?: 10; // Default 10

    // VIP fields
    $is_vip_list = get_post_meta($post->ID, '_is_vip_list', true);
    $vip_person_id = get_post_meta($post->ID, '_vip_person_id', true);
    $vip_persons = get_posts([
        'post_type'      => 'vip_person',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    // Get VIP ranks from pivot table
    $vip_ranks = [];
    if ($is_vip_list && !empty($selected_items)) {
        global $wpdb;
        $pivot_table = $wpdb->prefix . 'voting_list_item_relations';
        $rank_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT voting_item_id, vip_rank FROM {$pivot_table} WHERE voting_list_id = %d AND vip_rank IS NOT NULL",
            $post->ID
        ), ARRAY_A);
        foreach ($rank_rows as $row) {
            $vip_ranks[intval($row['voting_item_id'])] = intval($row['vip_rank']);
        }
    }

    // Fetch Voting Item Categories for filtering
    $categories = get_terms([
        'taxonomy'   => 'voting_item_category',
        'hide_empty' => false
    ]);

    // Fetch all voting items initially
    $items = get_posts([
        'post_type'      => 'voting_items',
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ]);
    
    $voting_list_id = isset($_GET['post']) ? intval($_GET['post']) : 0;

    wp_nonce_field('save_voting_list_meta', 'voting_list_meta_nonce');
    
    $featured = get_post_meta($post->ID, '_is_featured', true);
    ?>
    


    <p>
        <label>
            <input type="checkbox" name="voting_list_is_featured" value="1" <?php checked($featured, '1'); ?> />
            <strong>Mark this list as Featured</strong>
        </label>
    </p>

    <!-- VIP List Section -->
    <div style="background: #fff8e1; border: 1px solid #ffe082; border-radius: 8px; padding: 16px; margin: 16px 0;">
        <h3 style="margin-top: 0;">⭐ VIP List Settings</h3>
        <p>
            <label>
                <input type="checkbox" name="voting_list_is_vip" id="voting_list_is_vip" value="1" <?php checked($is_vip_list, '1'); ?> />
                <strong>This is a VIP List</strong>
            </label>
            <br><small>Enable to assign a VIP person and their rankings to this list.</small>
        </p>

        <div id="vip-list-fields" style="<?php echo $is_vip_list ? '' : 'display:none;'; ?>">
            <p>
                <label for="vip_person_id"><strong>VIP Person:</strong></label>
                <select name="vip_person_id" id="vip_person_id" class="widefat">
                    <option value="">-- Select VIP Person --</option>
                    <?php foreach ($vip_persons as $vp): ?>
                        <option value="<?php echo esc_attr($vp->ID); ?>" <?php selected($vip_person_id, $vp->ID); ?>>
                            <?php echo esc_html($vp->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($vip_persons)): ?>
                    <small>No VIP Persons found. <a href="<?php echo admin_url('post-new.php?post_type=vip_person'); ?>">Create one</a></small>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <script>
    jQuery(function($) {
        $('#voting_list_is_vip').on('change', function() {
            $('#vip-list-fields').toggle(this.checked);
        });
    });
    </script>


    <h3>Voting Items Selection</h3>

    <p>
        <label for="voting_scale"><strong>Number of Voting Choices:</strong></label>
        <input type="number" name="voting_scale" id="voting_scale" class="widefat" value="<?php echo esc_attr($voting_scale); ?>" min="1" max="10">
        <small>Define how many voting choices will be available (e.g., 1-5, 1-10).</small>
    </p>
    

    <p>
        <label for="voting_item_category">Filter by Category:</label>
        <select id="voting_item_category" class="widefat">
            <option value="">-- All Categories --</option>
            <?php foreach ($categories as $category) { ?>
                <option value="<?php echo esc_attr($category->term_id); ?>">
                    <?php echo esc_html($category->name); ?>
                </option>
            <?php } ?>
        </select>
    </p>
    <button type="button" id="filter_voting_items" class="button">Filter Items</button>
    <br><br>

    <p>
        <label for="voting_item_select">Select Voting Items:</label>
        <select id="voting_item_select" class="widefat search-select">
            <option value="">-- Select Voting Item --</option>
            <?php
            foreach ($items as $item) {
                $selected = in_array($item->ID, $selected_items) ? 'selected' : '';
                echo '<option value="' . esc_attr($item->ID) . '" ' . $selected . '>' . esc_html($item->post_title) . '</option>';
            }
            ?>
        </select>
    </p>
    <button type="button" id="add_voting_item" class="button">Add Item</button>

    <h4>Selected Voting Items</h4>
    <table id="voting_items_table" class="widefat">
        <thead>
            <tr>
                <th>Item</th>
                <th class="vip-rank-col" style="width:90px;<?php echo $is_vip_list ? '' : 'display:none;'; ?>">VIP Rank</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($selected_items as $item_id) {
                $item = get_post($item_id);
                if ($item) {
                    $rank_val = isset($vip_ranks[$item_id]) ? $vip_ranks[$item_id] : '';
                    echo '<tr data-id="' . esc_attr($item_id) . '">';
                    echo '<td>' . esc_html($item->post_title) . '</td>';
                    echo '<td class="vip-rank-col" style="' . ($is_vip_list ? '' : 'display:none;') . '">';
                    echo '<input type="number" name="vip_ranks[' . esc_attr($item_id) . ']" value="' . esc_attr($rank_val) . '" min="1" max="100" style="width:60px;" />';
                    echo '</td>';
                    echo '<td><button type="button" class="remove-voting-item button">Remove</button><button type="button" class="edit-voting-item button">Edit</button></td>';
                    echo '</tr>';
                }
            } ?>
        </tbody>
    </table>

    <script>
    jQuery(function($) {
        $('#voting_list_is_vip').on('change', function() {
            $('.vip-rank-col').toggle(this.checked);
        });
    });
    </script>

    <input type="hidden" id="voting_items" name="voting_items" value="<?php echo esc_attr(json_encode($selected_items)); ?>">
    <input type="hidden" id="current_voting_list_id" value="<?php echo esc_attr($voting_list_id); ?>">
    <?php
}

function save_voting_list_data($post_id) {
    if (!isset($_POST['voting_list_meta_nonce']) || !wp_verify_nonce($_POST['voting_list_meta_nonce'], 'save_voting_list_meta')) {
        return;
    }

    if (array_key_exists('voting_items', $_POST)) {
        $selected_items = json_decode(stripslashes($_POST['voting_items']), true);
        update_post_meta($post_id, '_voting_items', $selected_items);

        // Auto-sync: ensure every item has a pivot table row
        if (!empty($selected_items) && is_array($selected_items)) {
            yuv_sync_pivot_rows($post_id, $selected_items);
        }
    }

    if (isset($_POST['voting_scale'])) {
        update_post_meta($post_id, '_voting_scale', intval($_POST['voting_scale']));
    }
    if (isset($_POST['voting_list_is_featured'])) {
        update_post_meta($post_id, '_is_featured', '1');
    } else {
        delete_post_meta($post_id, '_is_featured');
    }

    // VIP List fields
    if (isset($_POST['voting_list_is_vip'])) {
        update_post_meta($post_id, '_is_vip_list', '1');
    } else {
        delete_post_meta($post_id, '_is_vip_list');
    }

    if (isset($_POST['vip_person_id'])) {
        $vip_pid = intval($_POST['vip_person_id']);
        if ($vip_pid > 0) {
            update_post_meta($post_id, '_vip_person_id', $vip_pid);
        } else {
            delete_post_meta($post_id, '_vip_person_id');
        }
    }

    // Save VIP ranks to pivot table
    if (!empty($_POST['vip_ranks']) && is_array($_POST['vip_ranks'])) {
        global $wpdb;
        $pivot = $wpdb->prefix . 'voting_list_item_relations';
        foreach ($_POST['vip_ranks'] as $item_id => $rank) {
            $item_id = intval($item_id);
            $rank = $rank !== '' ? intval($rank) : null;
            $wpdb->update(
                $pivot,
                ['vip_rank' => $rank, 'updated_at' => current_time('mysql', 1)],
                ['voting_list_id' => $post_id, 'voting_item_id' => $item_id]
            );
        }
    }
}
add_action('save_post', 'save_voting_list_data');

/**
 * Ensure every item in a voting list has a corresponding row in the pivot table.
 * Only inserts missing rows — never overwrites existing custom data.
 * Also copies the item's WP featured image URL into custom_image_url if available.
 *
 * @param int   $list_id  The voting list post ID.
 * @param array $item_ids Array of voting item post IDs.
 */
function yuv_sync_pivot_rows($list_id, $item_ids) {
    global $wpdb;
    $table = $wpdb->prefix . 'voting_list_item_relations';

    // Get existing pivot item IDs for this list in one query
    $existing = $wpdb->get_col($wpdb->prepare(
        "SELECT voting_item_id FROM $table WHERE voting_list_id = %d",
        $list_id
    ));
    $existing = array_map('intval', $existing);

    // Find items that need pivot rows
    $missing = array_diff(array_map('intval', $item_ids), $existing);

    if (empty($missing)) {
        return;
    }

    // Insert missing rows with the item's featured image as custom_image_url
    foreach ($missing as $item_id) {
        $image_url = get_the_post_thumbnail_url($item_id, 'medium');

        $wpdb->insert($table, [
            'voting_list_id'  => $list_id,
            'voting_item_id'  => $item_id,
            'custom_image_url' => $image_url ?: null,
            'created_at'      => current_time('mysql', 1),
            'updated_at'      => current_time('mysql', 1),
        ]);
    }
}
