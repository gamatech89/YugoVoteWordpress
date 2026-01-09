<?php
/**
 * Tournament Manager
 * Handles bracket creation, round advancement, and tie-breaker logic
 */

if (!defined('ABSPATH')) exit;

class YUV_Tournament_Manager {

    /**
     * Create tournament bracket (supports 8 or 16 players)
     */
    public function create_bracket($tournament_id) {
        $contestants = get_post_meta($tournament_id, '_yuv_contestants', true);
        $start_date = get_post_meta($tournament_id, '_yuv_start_date', true);
        $tournament_size = (int) get_post_meta($tournament_id, '_yuv_tournament_size', true) ?: 16;
        $round_interval = (int) get_post_meta($tournament_id, '_yuv_round_interval', true) ?: 24;

        if (!is_array($contestants) || count($contestants) !== $tournament_size) {
            return ['success' => false, 'message' => "Potrebno je tačno {$tournament_size} takmičara"];
        }

        if (empty($start_date)) {
            return ['success' => false, 'message' => 'Datum početka nije postavljen'];
        }

        // Shuffle contestants for random bracket seeding
        shuffle($contestants);

        $tournament_title = get_the_title($tournament_id);
        $lists = ['of' => [], 'qf' => [], 'sf' => [], 'final' => []];

        // Calculate start timestamp
        $start_timestamp = strtotime($start_date);

        $round_offset = 0;

        // For 16-player tournaments, create 8 Octofinal matches (ALL at start)
        if ($tournament_size === 16) {
            $of_start = $start_timestamp;
            
            for ($i = 0; $i < 8; $i++) {
                $match_num = $i + 1;
                $contestant1 = $contestants[$i * 2];
                $contestant2 = $contestants[$i * 2 + 1];

                $list_id = $this->create_match(
                    "$tournament_title - Osmina finala $match_num",
                    [$contestant1, $contestant2],
                    $of_start,
                    $round_interval,
                    $tournament_id,
                    'of',
                    $match_num
                );

                $lists['of'][] = $list_id;
            }
            $round_offset = 1; // Next round after 1 interval
        }

        // Create 4 Quarterfinal matches (simultaneous)
        $qf_start = $start_timestamp + ($round_offset * $round_interval * 3600);
        
        for ($i = 0; $i < 4; $i++) {
            $match_num = $i + 1;

            // For 8-player: use contestants directly
            // For 16-player: leave empty (filled by OF winners)
            $qf_contestants = ($tournament_size === 8) ? [$contestants[$i * 2], $contestants[$i * 2 + 1]] : [];
            $qf_status = ($tournament_size === 8) ? null : 'future';

            $list_id = $this->create_match(
                "$tournament_title - Četvrtfinale $match_num",
                $qf_contestants,
                $qf_start,
                $round_interval,
                $tournament_id,
                'qf',
                $match_num,
                $qf_status
            );

            $lists['qf'][] = $list_id;
            
            // Link OF winners to QF (only for 16-player tournaments)
            if ($tournament_size === 16) {
                $of1_id = $lists['of'][$i * 2];
                $of2_id = $lists['of'][$i * 2 + 1];
                update_post_meta($of1_id, '_yuv_next_match', $list_id);
                update_post_meta($of2_id, '_yuv_next_match', $list_id);
            }
        }

        // Create 2 Semifinal matches (simultaneous)
        $round_offset++;
        $sf_start = $start_timestamp + ($round_offset * $round_interval * 3600);

        for ($i = 0; $i < 2; $i++) {
            $match_num = $i + 1;
            
            $list_id = $this->create_match(
                "$tournament_title - Polufinale $match_num",
                [],
                $sf_start,
                $round_interval,
                $tournament_id,
                'sf',
                $match_num,
                'future'
            );

            $lists['sf'][] = $list_id;

            // Link QF winners to SF
            $qf1_id = $lists['qf'][$i * 2];
            $qf2_id = $lists['qf'][$i * 2 + 1];
            update_post_meta($qf1_id, '_yuv_next_match', $list_id);
            update_post_meta($qf2_id, '_yuv_next_match', $list_id);
        }

        // Create Final match
        $round_offset++;
        $final_start = $start_timestamp + ($round_offset * $round_interval * 3600);

        $final_id = $this->create_match(
            "$tournament_title - FINALE",
            [],
            $final_start,
            $round_interval,
            $tournament_id,
            'final',
            1,
            'future'
        );

        $lists['final'][] = $final_id;

        // Link SF winners to Final
        update_post_meta($lists['sf'][0], '_yuv_next_match', $final_id);
        update_post_meta($lists['sf'][1], '_yuv_next_match', $final_id);

        return ['success' => true, 'lists' => $lists];
    }

    /**
     * Create single match (voting_list)
     */
    private function create_match($title, $contestants, $start_timestamp, $duration_hours, $tournament_id, $stage, $match_num, $status = 'publish') {
        global $wpdb;

        $post_id = wp_insert_post([
            'post_type' => 'voting_list',
            'post_title' => $title,
            'post_status' => $status,
            'post_date' => gmdate('Y-m-d H:i:s', $start_timestamp),
        ]);

        if (is_wp_error($post_id)) {
            return 0;
        }

        // Tournament meta
        update_post_meta($post_id, '_yuv_tournament_id', $tournament_id);
        update_post_meta($post_id, '_yuv_stage', $stage);
        update_post_meta($post_id, '_yuv_match_number', $match_num);
        update_post_meta($post_id, '_yuv_end_time', $start_timestamp + ($duration_hours * 3600));
        update_post_meta($post_id, '_yuv_match_completed', '0');
        update_post_meta($post_id, '_is_tournament_match', '1'); // Mark as tournament-only

        // Voting settings
        update_post_meta($post_id, '_voting_scale', 10);

        // Create voting items if contestants provided
        $item_ids = [];
        if (!empty($contestants)) {
            foreach ($contestants as $contestant) {
                // Create voting item post
                $item_id = wp_insert_post([
                    'post_type' => 'voting_items',
                    'post_title' => $contestant['name'],
                    'post_status' => 'publish',
                    'post_content' => $contestant['description'] ?? '', // Save description as content
                ]);

                if (!is_wp_error($item_id)) {
                    // Set featured image from attachment ID
                    if (!empty($contestant['image_id'])) {
                        set_post_thumbnail($item_id, $contestant['image_id']);
                    }
                    
                    // Also save custom image URL as meta (for backward compatibility)
                    if (!empty($contestant['image_url'])) {
                        update_post_meta($item_id, '_custom_image_url', $contestant['image_url']);
                    }

                    // Save short description meta
                    if (!empty($contestant['description'])) {
                        update_post_meta($item_id, '_short_description', $contestant['description']);
                    }

                    // Add to relation table
                    $wpdb->insert(
                        $wpdb->prefix . 'voting_list_item_relations',
                        [
                            'voting_list_id' => $post_id,
                            'voting_item_id' => $item_id,
                            'custom_image_url' => $contestant['image_url'] ?? null,
                        ]
                    );

                    $item_ids[] = $item_id;
                }
            }
        }

        update_post_meta($post_id, '_voting_items', $item_ids);

        return $post_id;
    }

    /**
     * Advance all tournaments (called by WP Cron)
     */
    public function advance_all_tournaments() {
        $tournaments = get_posts([
            'post_type' => 'yuv_tournament',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_yuv_bracket_created',
                    'value' => '1',
                ]
            ]
        ]);

        foreach ($tournaments as $tournament) {
            $this->advance_tournament($tournament->ID);
        }
    }

    /**
     * Advance single tournament
     */
    public function advance_tournament($tournament_id) {
        global $wpdb;
        
        $bracket_lists = get_post_meta($tournament_id, '_yuv_bracket_lists', true);
        if (empty($bracket_lists)) return;

        $current_time = current_time('timestamp');
        $advanced = [];

        // Check all matches in all stages
        foreach (['qf', 'sf', 'final'] as $stage) {
            if (empty($bracket_lists[$stage])) continue;

            foreach ($bracket_lists[$stage] as $list_id) {
                $completed = get_post_meta($list_id, '_yuv_match_completed', true);
                if ($completed) continue;

                $end_time = (int) get_post_meta($list_id, '_yuv_end_time', true);
                
                // Check if match time expired
                if ($current_time >= $end_time) {
                    $result = $this->complete_match($list_id, $tournament_id);
                    if ($result['success']) {
                        $advanced[] = $result;
                    }
                }
            }
        }

        // Log advancement
        if (!empty($advanced)) {
            update_post_meta($tournament_id, '_yuv_last_advancement', [
                'timestamp' => $current_time,
                'results' => $advanced,
            ]);
        }

        return $advanced;
    }

    /**
     * Complete match and advance winner
     */
    private function complete_match($list_id, $tournament_id) {
        global $wpdb;

        $votes_table = $wpdb->prefix . 'voting_list_votes';
        $items = get_post_meta($list_id, '_voting_items', true);

        if (empty($items) || count($items) !== 2) {
            return ['success' => false, 'message' => 'Invalid match items'];
        }

        // Get vote counts for both items
        $results = [];
        foreach ($items as $item_id) {
            $score = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(vote_value) FROM {$votes_table} WHERE voting_list_id = %d AND voting_item_id = %d",
                $list_id,
                $item_id
            ));
            
            $results[$item_id] = (int) $score;
        }

        // Determine winner
        arsort($results);
        $sorted_items = array_keys($results);
        $first_score = $results[$sorted_items[0]];
        $second_score = $results[$sorted_items[1]];

        $tie_breaker_used = false;
        $winner_id = $sorted_items[0];

        // TIE-BREAKER LOGIC
        if ($first_score === $second_score) {
            // Use RAND() for tie-breaker
            $winner_id = $sorted_items[rand(0, 1)];
            $tie_breaker_used = true;
        }

        // Mark match as completed
        update_post_meta($list_id, '_yuv_match_completed', '1');
        update_post_meta($list_id, '_yuv_winner_id', $winner_id);
        update_post_meta($list_id, '_yuv_tie_breaker_used', $tie_breaker_used);
        update_post_meta($list_id, '_yuv_final_scores', $results);

        // Get next match
        $next_match_id = get_post_meta($list_id, '_yuv_next_match', true);
        
        if (!$next_match_id) {
            // This is the final - store winner in tournament meta
            update_post_meta($tournament_id, '_yuv_winner_id', $winner_id);
            
            return [
                'success' => true,
                'list_id' => $list_id,
                'winner_id' => $winner_id,
                'winner_name' => get_the_title($winner_id),
                'tie_breaker' => $tie_breaker_used,
                'stage' => 'final',
                'message' => 'Tournament completed! Winner: ' . get_the_title($winner_id),
            ];
        }

        // Clone winner to next match
        $next_items = get_post_meta($next_match_id, '_voting_items', true) ?: [];
        
        // Create new voting item for next round (clone original)
        $winner_data = get_post($winner_id);
        $winner_image_id = get_post_thumbnail_id($winner_id);
        $winner_image_url = get_post_meta($winner_id, '_custom_image_url', true);
        $winner_description = get_post_meta($winner_id, '_short_description', true);

        $new_item_id = wp_insert_post([
            'post_type' => 'voting_items',
            'post_title' => $winner_data->post_title,
            'post_content' => $winner_data->post_content,
            'post_status' => 'publish',
        ]);

        if ($winner_image_id) {
            set_post_thumbnail($new_item_id, $winner_image_id);
        }
        
        if ($winner_image_url) {
            update_post_meta($new_item_id, '_custom_image_url', $winner_image_url);
        }
        
        if ($winner_description) {
            update_post_meta($new_item_id, '_short_description', $winner_description);
        }

        // Add to next match relation
        $wpdb->insert(
            $wpdb->prefix . 'voting_list_item_relations',
            [
                'voting_list_id' => $next_match_id,
                'voting_item_id' => $new_item_id,
                'custom_image_url' => $winner_image_url,
            ]
        );

        $next_items[] = $new_item_id;
        update_post_meta($next_match_id, '_voting_items', $next_items);

        // If next match now has 2 items, publish it
        if (count($next_items) === 2) {
            wp_update_post([
                'ID' => $next_match_id,
                'post_status' => 'publish',
            ]);
        }

        return [
            'success' => true,
            'list_id' => $list_id,
            'winner_id' => $winner_id,
            'winner_name' => get_the_title($winner_id),
            'tie_breaker' => $tie_breaker_used,
            'next_match_id' => $next_match_id,
            'stage' => get_post_meta($list_id, '_yuv_stage', true),
            'message' => get_the_title($winner_id) . ' advances to next round',
        ];
    }
}
