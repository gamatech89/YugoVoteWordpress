<?php
/**
 * VIP Person Meta Box
 * 
 * Adds subtitle and social links fields to the VIP Person CPT.
 *
 * @package HelloElementorChild
 */

function add_vip_person_metabox() {
    add_meta_box(
        'vip_person_metabox',
        'VIP Person Details',
        'vip_person_metabox_callback',
        'vip_person',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_vip_person_metabox');

function vip_person_metabox_callback($post) {
    $subtitle    = get_post_meta($post->ID, '_vip_subtitle', true);
    $instagram   = get_post_meta($post->ID, '_vip_instagram', true);
    $twitter     = get_post_meta($post->ID, '_vip_twitter', true);
    $website     = get_post_meta($post->ID, '_vip_website', true);

    wp_nonce_field('save_vip_person_meta', 'vip_person_meta_nonce');
    ?>
    <p>
        <label for="vip_subtitle"><strong>Subtitle / Title:</strong></label>
        <input type="text" name="vip_subtitle" id="vip_subtitle" class="widefat" 
               value="<?php echo esc_attr($subtitle); ?>" 
               placeholder="e.g. Tennis Legend, Actor, Musician">
        <span class="description">Short title or description shown below the name.</span>
    </p>

    <hr>
    <h3>Social Links (Optional)</h3>

    <p>
        <label for="vip_instagram"><strong>Instagram URL:</strong></label>
        <input type="url" name="vip_instagram" id="vip_instagram" class="widefat" 
               value="<?php echo esc_url($instagram); ?>" 
               placeholder="https://instagram.com/...">
    </p>

    <p>
        <label for="vip_twitter"><strong>Twitter / X URL:</strong></label>
        <input type="url" name="vip_twitter" id="vip_twitter" class="widefat" 
               value="<?php echo esc_url($twitter); ?>" 
               placeholder="https://x.com/...">
    </p>

    <p>
        <label for="vip_website"><strong>Website URL:</strong></label>
        <input type="url" name="vip_website" id="vip_website" class="widefat" 
               value="<?php echo esc_url($website); ?>" 
               placeholder="https://...">
    </p>

    <hr>
    <?php
    // Show lists curated by this VIP person
    $vip_lists = get_posts([
        'post_type'      => 'voting_list',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft', 'pending'],
        'meta_query'     => [
            [
                'key'   => '_vip_person_id',
                'value' => $post->ID,
            ]
        ]
    ]);

    echo '<h3>Curated Lists (' . count($vip_lists) . ')</h3>';

    if (empty($vip_lists)) {
        echo '<p><em>No voting lists are assigned to this VIP person yet. Assign them from the Voting List editor.</em></p>';
    } else {
        echo '<table class="widefat fixed striped"><thead><tr>';
        echo '<th>List Name</th><th style="width:100px;">Status</th><th style="width:160px;">Actions</th>';
        echo '</tr></thead><tbody>';

        foreach ($vip_lists as $list) {
            $status = get_post_status($list);
            $edit_link = get_edit_post_link($list->ID);
            echo '<tr>';
            echo '<td>' . esc_html($list->post_title ?: ('#' . $list->ID)) . '</td>';
            echo '<td><code>' . esc_html($status) . '</code></td>';
            echo '<td>';
            if ($edit_link) {
                echo '<a class="button button-small" target="_blank" href="' . esc_url($edit_link) . '">Edit</a> ';
            }
            if ($status === 'publish') {
                echo '<a class="button button-small" target="_blank" href="' . esc_url(get_permalink($list->ID)) . '">View</a>';
            }
            echo '</td></tr>';
        }

        echo '</tbody></table>';
    }
}

function save_vip_person_data($post_id) {
    if (!isset($_POST['vip_person_meta_nonce']) || !wp_verify_nonce($_POST['vip_person_meta_nonce'], 'save_vip_person_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_type']) && 'vip_person' !== $_POST['post_type']) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Subtitle
    if (isset($_POST['vip_subtitle'])) {
        update_post_meta($post_id, '_vip_subtitle', sanitize_text_field($_POST['vip_subtitle']));
    }

    // Social links
    $url_fields = ['vip_instagram', 'vip_twitter', 'vip_website'];
    foreach ($url_fields as $field) {
        if (isset($_POST[$field])) {
            $val = esc_url_raw(trim($_POST[$field]));
            if (!empty($val)) {
                update_post_meta($post_id, '_' . $field, $val);
            } else {
                delete_post_meta($post_id, '_' . $field);
            }
        }
    }
}
add_action('save_post', 'save_vip_person_data');
