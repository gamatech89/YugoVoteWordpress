<?php

function add_voting_item_metabox() {
    add_meta_box(
        'voting_item_metabox',
        'Voting Item Details',
        'voting_item_metabox_callback',
        'voting_items',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_voting_item_metabox');

/**
 * Callback function to render the fields in the Voting Item meta box.
 *
 * @param WP_Post $post The current post object.
 */
function voting_item_metabox_callback( $post ) {
    // --- Your existing fields ---
    $short_description = get_post_meta( $post->ID, '_short_description', true );
    $item_url          = get_post_meta( $post->ID, '_item_url', true );
    $image_source_text = get_post_meta( $post->ID, '_image_source_text', true );

    wp_nonce_field( 'save_voting_item_meta_action', 'voting_item_meta_nonce' );
    ?>
    <p>
        <label for="short_description"><strong><?php esc_html_e( 'Short Description:', 'your-text-domain' ); ?></strong></label>
        <textarea name="short_description" id="short_description" class="widefat" required><?php echo esc_textarea( $short_description ); ?></textarea>
        <span class="cs-admin-description"><?php esc_html_e( 'Short description for voting card', 'your-text-domain' ); ?></span>
    </p>

    <hr>

    <p>
        <label for="item_url"><strong><?php esc_html_e( 'Item URL:', 'your-text-domain' ); ?></strong></label>
        <input type="url" name="item_url" id="item_url" value="<?php echo esc_url( $item_url ); ?>" class="widefat">
        <span class="cs-admin-description"><?php esc_html_e( 'Video URL from YouTube to showcase something important related to this item.', 'your-text-domain' ); ?></span>
    </p>

    <hr>

    <p>
        <label for="image_source_text"><strong><?php esc_html_e( 'Image Source / Credit:', 'your-text-domain' ); ?></strong></label>
        <input type="text" name="image_source_text" id="image_source_text" value="<?php echo esc_attr( $image_source_text ); ?>" class="widefat">
        <span class="cs-admin-description"><?php esc_html_e( 'Enter the source or credit for the main image of this item.', 'your-text-domain' ); ?></span>
    </p>

    <hr>
    <?php

    // --- Appears In Lists (combines DB relations + postmeta fallback) ---
    global $wpdb;

    $item_id         = (int) $post->ID;
    $relations_table = $wpdb->prefix . 'voting_list_item_relations';

    echo '<h3>' . esc_html__( 'Appears In Lists:', 'your-text-domain' ) . '</h3>';

    $list_ids = [];

    // A) From normalized relations table (if it exists)
    $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $relations_table ) );
    if ( $table_exists ) {
        $ids_rel = (array) $wpdb->get_col(
            $wpdb->prepare(
                "SELECT voting_list_id FROM {$relations_table} WHERE voting_item_id = %d",
                $item_id
            )
        );
        $list_ids = array_merge( $list_ids, array_map( 'intval', $ids_rel ) );
    }

    // B) From postmeta `_voting_items` (serialized array) – matches "123" (string) and i:123; (int)
    // Use two LIKE clauses to be safe across how the array was stored.
    $meta_ids = get_posts( [
        'post_type'      => 'voting_list',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'suppress_filters' => true,
        'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future', 'trash' ],
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => '_voting_items',
                'value'   => '"' . $item_id . '"', // stored as strings
                'compare' => 'LIKE',
            ],
            [
                'key'     => '_voting_items',
                'value'   => 'i:' . $item_id . ';', // stored as integers
                'compare' => 'LIKE',
            ],
        ],
    ] );

    if ( ! empty( $meta_ids ) ) {
        $list_ids = array_merge( $list_ids, array_map( 'intval', $meta_ids ) );
    }

    // Normalize & dedupe
    $list_ids = array_values( array_unique( array_filter( $list_ids ) ) );

    if ( empty( $list_ids ) ) {
        if ( ! $table_exists ) {
            echo '<p><em>' . esc_html__( 'No lists found. Note: relations table not found, and no lists have this item in their _voting_items meta.', 'your-text-domain' ) . '</em></p>';
        } else {
            echo '<p><em>' . esc_html__( 'This item is not assigned to any list (neither in relations table nor in _voting_items meta).', 'your-text-domain' ) . '</em></p>';
        }
        return;
    }

    // Fetch the posts in one go to render titles/links
    $voting_lists = get_posts( [
        'post_type'      => 'voting_list',
        'post__in'       => $list_ids,
        'posts_per_page' => -1,
        'orderby'        => 'post__in',
        'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future', 'trash' ],
        'no_found_rows'  => true,
    ] );

    if ( empty( $voting_lists ) ) {
        echo '<p><em>' . esc_html__( 'List IDs were found, but posts could not be loaded (maybe deleted permanently).', 'your-text-domain' ) . '</em></p>';
        return;
    }

    echo '<table class="widefat fixed striped"><thead><tr>';
    echo '<th>' . esc_html__( 'List Name', 'your-text-domain' ) . '</th>';
    echo '<th style="width:120px;">' . esc_html__( 'Status', 'your-text-domain' ) . '</th>';
    echo '<th style="width:240px;">' . esc_html__( 'Actions', 'your-text-domain' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $voting_lists as $list ) {
        $status    = get_post_status( $list );
        $edit_link = get_edit_post_link( $list->ID );
        $view_link = ( $status === 'publish' ) ? get_permalink( $list->ID ) : '';

        echo '<tr>';
        echo '<td>' . esc_html( $list->post_title ?: ( '#' . $list->ID ) ) . '</td>';
        echo '<td><code>' . esc_html( $status ) . '</code></td>';
        echo '<td>';
        if ( $edit_link ) {
            echo '<a class="button button-small" target="_blank" href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit', 'your-text-domain' ) . '</a> ';
        }
        if ( $view_link ) {
            echo '<a class="button button-small" target="_blank" href="' . esc_url( $view_link ) . '">' . esc_html__( 'View', 'your-text-domain' ) . '</a> ';
        }
        echo '<a class="button button-small" href="' . esc_url( admin_url('edit.php?post_type=voting_list&cs_lookup_id=' . $list->ID) ) . '">Find in Admin</a>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<style>#voting_item_metabox .widefat{max-height:340px;overflow:auto}</style>';
}

/**
 * Saves the meta data for the Voting Item.
 *
 * @param int $post_id The ID of the post being saved.
 */
function save_voting_item_data($post_id) {

    if (!isset($_POST['voting_item_meta_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['voting_item_meta_nonce'], 'save_voting_item_meta_action')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_type']) && 'voting_items' == $_POST['post_type']) { 
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    } else {
        // Not our CPT, so bail. This check might be redundant if the save_post action is specific.
        return;
    }

    // --- Save existing fields ---
    if (array_key_exists('short_description', $_POST)) {
        update_post_meta($post_id, '_short_description', sanitize_textarea_field($_POST['short_description']));
    }

    if (array_key_exists('item_url', $_POST)) {
   
         $item_url_value = esc_url_raw(trim($_POST['item_url']));
        if (!empty($item_url_value)) {
            update_post_meta($post_id, '_item_url', $item_url_value);
        } else {
            delete_post_meta($post_id, '_item_url'); 
        }
    }

    // --- Save the NEW Image Source field ---
    if (array_key_exists('image_source_text', $_POST)) {
        $source_text = sanitize_text_field(trim($_POST['image_source_text'])); 
        if (!empty($source_text)) {
            update_post_meta($post_id, '_image_source_text', $source_text);
        } else {
            delete_post_meta($post_id, '_image_source_text'); // Delete if submitted empty
        }
    }
}
add_action('save_post', 'save_voting_item_data');

