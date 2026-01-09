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
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($selected_items as $item_id) {
                $item = get_post($item_id);
                if ($item) {
                    echo '<tr data-id="' . esc_attr($item_id) . '">';
                    echo '<td>' . esc_html($item->post_title) . '</td>';
                    echo '<td><button type="button" class="remove-voting-item button">Remove</button><button type="button" class="edit-voting-item button">Edit</button></td>';
                    echo '</tr>';
                }
            } ?>
        </tbody>
    </table>

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
    }

    if (isset($_POST['voting_scale'])) {
        update_post_meta($post_id, '_voting_scale', intval($_POST['voting_scale']));
    }
    if (isset($_POST['voting_list_is_featured'])) {
    update_post_meta($post_id, '_is_featured', '1');
    } else {
        delete_post_meta($post_id, '_is_featured');
    }
}
add_action('save_post', 'save_voting_list_data');
