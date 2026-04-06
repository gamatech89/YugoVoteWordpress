<?php
/**
 * Showdown Manager
 * Core logic for showdown items, sessions, and leaderboards
 */

if (!defined('ABSPATH'))
    exit;

class YUV_Showdown_Manager
{

    /**
     * Get the currently active showdown (latest published)
     * Only one can be active at a time
     */
    public function get_active_showdown()
    {
        $showdowns = get_posts([
            'post_type' => 'yuv_showdown',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_yuv_showdown_status',
                    'value' => 'active',
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        return !empty($showdowns) ? $showdowns[0] : null;
    }

    /**
     * Get the latest completed showdown
     */
    public function get_latest_completed()
    {
        $showdowns = get_posts([
            'post_type' => 'yuv_showdown',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_yuv_showdown_status',
                    'value' => 'completed',
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        return !empty($showdowns) ? $showdowns[0] : null;
    }

    /**
     * Check if user has already played a showdown
     */
    public function has_user_played($showdown_id, $user_id = 0, $ip_address = '')
    {
        global $wpdb;
        $table = $wpdb->prefix . 'yuv_showdown_sessions';

        if ($user_id > 0) {
            return (bool)$wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE showdown_id = %d AND user_id = %d LIMIT 1",
                $showdown_id,
                $user_id
            ));
        }

        return (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE showdown_id = %d AND user_id IS NULL AND ip_address = %s LIMIT 1",
            $showdown_id,
            $ip_address
        ));
    }

    /**
     * Get all items for a showdown
     */
    public function get_items($showdown_id)
    {
        $items = get_post_meta($showdown_id, '_yuv_showdown_items', true);
        if (!is_array($items) || empty($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (empty($item['name']))
                continue;

        $image_url = '';
            // Always prefer image_id for proper size — stored image_url may be a small thumbnail crop
            if (!empty($item['image_id'])) {
                $image_url = wp_get_attachment_image_url($item['image_id'], 'full');
            }
            if (!$image_url) {
                $image_url = $item['image_url'] ?? '';
            }

            $result[] = [
                'id' => sanitize_title($item['name']) . '-' . count($result),
                'name' => $item['name'],
                'description' => $item['description'] ?? '',
                'image' => $image_url,
                'image_id' => $item['image_id'] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Save a completed session
     * 
     * @param int    $showdown_id
     * @param int    $user_id     (0 for guests)
     * @param string $ip_address
     * @param array  $results     Array of item names in elimination order (first eliminated = index 0)
     * @param string $winner_name Name of the winning item
     * @return array
     */
    public function save_session($showdown_id, $user_id, $ip_address, $results, $winner_name)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'yuv_showdown_sessions';

        // Prevent duplicate sessions
        if ($this->has_user_played($showdown_id, $user_id, $ip_address)) {
            return ['success' => false, 'message' => 'Već ste odigrali ovaj Showdown'];
        }

        // Find winner item index from items meta
        $items = $this->get_items($showdown_id);
        $winner_item_id = 0;
        foreach ($items as $idx => $item) {
            if ($item['name'] === $winner_name) {
                $winner_item_id = $idx + 1; // 1-indexed
                break;
            }
        }

        $inserted = $wpdb->insert($table, [
            'showdown_id' => $showdown_id,
            'user_id' => $user_id > 0 ? $user_id : null,
            'ip_address' => $ip_address,
            'results_json' => wp_json_encode($results),
            'winner_item_id' => $winner_item_id,
            'created_at' => current_time('mysql'),
        ], ['%d', '%d', '%s', '%s', '%d', '%s']);

        if ($inserted === false) {
            return ['success' => false, 'message' => 'Greška pri čuvanju rezultata'];
        }

        return ['success' => true, 'session_id' => $wpdb->insert_id];
    }

    /**
     * Get leaderboard for a showdown
     * Ranks items by how many times they won (were the last one standing)
     * Also counts how many rounds each item survived on average
     */
    public function get_leaderboard($showdown_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'yuv_showdown_sessions';

        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT results_json FROM $table WHERE showdown_id = %d",
            $showdown_id
        ));

        if (empty($sessions)) {
            return [];
        }

        $items = $this->get_items($showdown_id);
        $item_names = array_column($items, 'name');

        // Initialize scores
        $scores = [];
        foreach ($item_names as $name) {
            $scores[$name] = [
                'name' => $name,
                'wins' => 0,
                'total_rank' => 0,
                'sessions' => 0,
            ];
        }

        // Process all sessions
        foreach ($sessions as $session) {
            $results = json_decode($session->results_json, true);
            if (!is_array($results))
                continue;

            $total_items = count($results);

            // results array: index 0 = first eliminated (worst), last index = winner (best)
            foreach ($results as $position => $item_name) {
                if (!isset($scores[$item_name]))
                    continue;

                // Rank: last item = rank 1 (winner), first item = rank N (worst)
                $rank = $total_items - $position;
                $scores[$item_name]['total_rank'] += $rank;
                $scores[$item_name]['sessions']++;

                // If this is the last item (winner)
                if ($position === $total_items - 1) {
                    $scores[$item_name]['wins']++;
                }
            }
        }

        // Calculate average rank and sort
        foreach ($scores as &$score) {
            $score['avg_rank'] = $score['sessions'] > 0
                ? round($score['total_rank'] / $score['sessions'], 2)
                : 999;
        }

        // Sort by: wins DESC, then avg_rank ASC (lower = better)
        usort($scores, function ($a, $b) {
            if ($b['wins'] !== $a['wins']) {
                return $b['wins'] - $a['wins'];
            }
            return $a['avg_rank'] <=> $b['avg_rank'];
        });

        // Add item data (image, description)
        $items_by_name = [];
        foreach ($items as $item) {
            $items_by_name[$item['name']] = $item;
        }

        foreach ($scores as &$score) {
            $item = $items_by_name[$score['name']] ?? null;
            if ($item) {
                $score['image'] = $item['image'];
                $score['description'] = $item['description'];
            }
        }

        return $scores;
    }

    /**
     * Get archive of completed showdowns
     */
    public function get_archive($count = 20)
    {
        $showdowns = get_posts([
            'post_type' => 'yuv_showdown',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'meta_query' => [
                [
                    'key' => '_yuv_showdown_status',
                    'value' => 'completed',
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $archive = [];
        foreach ($showdowns as $showdown) {
            $leaderboard = $this->get_leaderboard($showdown->ID);
            $top3 = array_slice($leaderboard, 0, 3);

            $total_sessions = $this->get_session_count($showdown->ID);

            $archive[] = [
                'id' => $showdown->ID,
                'title' => $showdown->post_title,
                'description' => $showdown->post_content,
                'thumbnail' => get_the_post_thumbnail_url($showdown->ID, 'medium'),
                'date' => get_the_date('d.m.Y', $showdown->ID),
                'top3' => $top3,
                'total_sessions' => $total_sessions,
            ];
        }

        return $archive;
    }

    /**
     * Get total session count for a showdown
     */
    public function get_session_count($showdown_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'yuv_showdown_sessions';

        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE showdown_id = %d",
            $showdown_id
        ));
    }

    /**
     * Get user's previous session results for a showdown
     */
    public function get_user_session($showdown_id, $user_id = 0, $ip_address = '')
    {
        global $wpdb;
        $table = $wpdb->prefix . 'yuv_showdown_sessions';

        if ($user_id > 0) {
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE showdown_id = %d AND user_id = %d LIMIT 1",
                $showdown_id,
                $user_id
            ));
        }
        else {
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE showdown_id = %d AND user_id IS NULL AND ip_address = %s LIMIT 1",
                $showdown_id,
                $ip_address
            ));
        }

        if (!$row)
            return null;

        return [
            'session_id' => $row->id,
            'results' => json_decode($row->results_json, true),
            'winner_item' => $row->winner_item_id,
            'created_at' => $row->created_at,
        ];
    }
}