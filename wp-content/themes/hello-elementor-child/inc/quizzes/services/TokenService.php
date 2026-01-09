<?php
namespace HelloElementorChild\Quizzes\Services;

if (!defined('ABSPATH')) {
    exit();
}

class TokenService {
    protected string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'ygv_user_tokens';
    }

    public function current_wallet(int $user_id): array {
        $w = $this->ensure_wallet($user_id);

        $dyn_max = (int) apply_filters('ygv_tokens_user_max', $w['max_tokens'], $user_id);
        if ($dyn_max !== (int) $w['max_tokens']) {
            $w['max_tokens'] = $dyn_max;
            $this->put_wallet($w);
        }
        return $w;
    }

    public function ensure_wallet(int $user_id): array {
        $w = $this->get_wallet($user_id);
        if ($w) {
            if (empty($w['refill_anchor'])) {
                $w['refill_anchor'] = time();
                $this->put_wallet($w);
            }
            return $w;
        }

        $max   = (int) apply_filters('ygv_tokens_default_max', 48);
        $start = (int) apply_filters('ygv_tokens_default_start', $max);
        $rate  = (int) apply_filters('ygv_tokens_default_regen_rate', 2);
        $intv  = (int) apply_filters('ygv_tokens_default_regen_interval_minutes', 60);

        $now = time();
        $this->upsert_wallet([
            'user_id' => $user_id,
            'tokens'  => $start,
            'max_tokens' => $max,
            'regen_rate' => $rate,
            'regen_interval_minutes' => $intv,
            'refill_anchor' => $now,
        ]);

        return $this->get_wallet($user_id);
    }

    public function get_wallet(int $user_id): array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE user_id = %d", $user_id),
            ARRAY_A
        );
        return $row ?: [];
    }

    public function put_wallet(array $w): bool {
        global $wpdb;

        $uid = isset($w['user_id']) ? (int)$w['user_id'] : 0;
        if ($uid <= 0) return false;

        $data = [
            'tokens'                 => isset($w['tokens']) ? (int)$w['tokens'] : 0,
            'max_tokens'             => isset($w['max_tokens']) ? (int)$w['max_tokens'] : 48,
            'regen_rate'             => isset($w['regen_rate']) ? (int)$w['regen_rate'] : 2,
            'regen_interval_minutes' => isset($w['regen_interval_minutes']) ? (int)$w['regen_interval_minutes'] : 60,
            'refill_anchor'          => isset($w['refill_anchor']) ? (int)$w['refill_anchor'] : time(),
        ];

        $exists = $this->get_wallet($uid);

        if ($exists) {
            $ok = $wpdb->update(
                $this->table,
                $data,
                ['user_id' => $uid],
                ['%d','%d','%d','%d','%d'],
                ['%d']
            );
            return ($ok !== false);
        }

        $data['user_id'] = $uid;
        $ok = $wpdb->insert(
            $this->table,
            $data,
            ['%d','%d','%d','%d','%d','%d']
        );
        return (bool) $ok;
    }

    protected function upsert_wallet(array $w): void {
        global $wpdb;
        $ins = $wpdb->insert(
            $this->table,
            [
                'user_id' => (int)$w['user_id'],
                'tokens' => (int)$w['tokens'],
                'max_tokens' => (int)$w['max_tokens'],
                'regen_rate' => (int)$w['regen_rate'],
                'regen_interval_minutes' => (int)$w['regen_interval_minutes'],
                'refill_anchor' => (int)$w['refill_anchor'],
            ],
            ['%d','%d','%d','%d','%d','%d']
        );
        if (!$ins) {
            $this->put_wallet($w);
        }
    }

    public function lazy_refill(int $user_id): array {
        $w = $this->current_wallet($user_id);

        $interval = max(60, (int)$w['regen_interval_minutes'] * 60);
        $rate     = max(1,  (int)$w['regen_rate']);
        $max      = (int)$w['max_tokens'];

        $now    = time();
        $anchor = (int)$w['refill_anchor'];
        if ($anchor <= 0) { $anchor = $now; $w['refill_anchor'] = $anchor; }

        $ticks = intdiv($now - $anchor, $interval);
        if ($ticks > 0) {
            $w['refill_anchor'] = $anchor + $ticks * $interval;

            if ((int)$w['tokens'] < $max) {
                $add = min($ticks * $rate, $max - (int)$w['tokens']);
                if ($add > 0) {
                    $w['tokens'] = (int)$w['tokens'] + $add;
                }
            }
            $this->put_wallet($w);
        }
        return $w;
    }

    public function spend_tokens_atomic(int $user_id, int $amount): bool {
        global $wpdb;
        $amount = max(1, (int)$amount);

        $this->lazy_refill($user_id);

        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table}
             SET tokens = tokens - %d
             WHERE user_id = %d AND tokens >= %d",
            $amount, $user_id, $amount
        ));
        return ($updated > 0);
    }

    public function seconds_to_next_refill(int $user_id): int {
        $w = $this->current_wallet($user_id);
        $interval = max(60, (int)$w['regen_interval_minutes'] * 60);
        $now = time();
        $anchor = (int)$w['refill_anchor'];
        if ($anchor <= 0) return $interval;

        $elapsed = ($now - $anchor) % $interval;
        $remain  = $interval - $elapsed;
        return $remain > 0 ? $remain : $interval;
    }
}
