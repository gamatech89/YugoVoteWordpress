<?php
if (!defined('ABSPATH')) exit;

// 1. Dodaj nove kolone u zaglavlje tabele
function cs_poll_custom_columns($columns) {
    // Pravimo novi niz da bismo kontrolisali redosled
    $new_columns = [];
    $new_columns['cb'] = $columns['cb']; // Checkbox
    $new_columns['title'] = $columns['title']; // Naslov
    $new_columns['poll_votes'] = 'Ukupno Glasova'; // NOVA KOLONA
    $new_columns['poll_shortcode'] = 'Shortcode'; // NOVA KOLONA (korisno za copy-paste)
    $new_columns['date'] = $columns['date']; // Datum

    return $new_columns;
}
add_filter('manage_voting_poll_posts_columns', 'cs_poll_custom_columns');

// 2. Popuni kolone podacima
function cs_poll_custom_columns_data($column, $post_id) {
    switch ($column) {
        case 'poll_votes':
            $votes = get_post_meta($post_id, '_cs_poll_total_votes', true);
            echo '<strong>' . ($votes ? intval($votes) : 0) . '</strong>';
            break;

        case 'poll_shortcode':
            echo '<code style="user-select:all;">[voting_poll id="' . $post_id . '"]</code>';
            break;
    }
}
add_action('manage_voting_poll_posts_custom_column', 'cs_poll_custom_columns_data', 10, 2);

// 3. Opciono: Omogući sortiranje po glasovima
function cs_poll_sortable_columns($columns) {
    $columns['poll_votes'] = 'poll_votes';
    return $columns;
}
add_filter('manage_edit-voting_poll_sortable_columns', 'cs_poll_sortable_columns');