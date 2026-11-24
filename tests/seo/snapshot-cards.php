<?php
// Snimak HTML-a kartica kviza (AJAX filter arhive + slajder), pre i posle prelaska na zajednički partial.
//   docker compose cp tests/seo/snapshot-cards.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/snapshot-cards.php save
//   ... php /tmp/snapshot-cards.php compare
// Poređenje ignoriše link u naslovu (nov je), razmake, entitete i nasumični id slajdera.
$GLOBALS['ygv_test_done'] = false;
register_shutdown_function(function () {
    if (empty($GLOBALS['ygv_test_done'])) {
        $e = error_get_last();
        echo "FAIL  test se nije završio: " . ($e['message'] ?? 'nepoznato') . "\n";
    }
});
define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
define('DOING_AJAX', true);
require '/var/www/html/wp-load.php';

$mode = $argv[1] ?? 'compare';
$file = '/tmp/ygv-cards.json';

class YgvSnapDie extends Exception {}
add_filter('wp_die_ajax_handler', fn() => function () { throw new YgvSnapDie(); });

function snap_ajax(int $paged): string {
    $_POST = $_REQUEST = ['action' => 'ygv_archive_filter', 'nonce' => wp_create_nonce('ygv_archive_filter'), 'cat' => '', 'diff' => '0', 'paged' => (string) $paged];
    ob_start();
    try { ygv_archive_filter_ajax(); } catch (YgvSnapDie $e) {}
    $r = json_decode((string) ob_get_clean(), true);
    return (string) ($r['data']['html'] ?? 'NEMA HTML');
}
function snap_all(): array {
    global $wpdb;
    $out = [];
    $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
    // Privremeni rezultati administratora za dva najnovija kviza: "odličan" (90%) i "odigrano" (50%).
    $table = $wpdb->prefix . 'ygv_user_quiz_progress';
    $latest = get_posts(['post_type' => 'quiz', 'numberposts' => 2, 'fields' => 'ids']);
    $aid = (int) ($admins[0] ?? 0);
    $had = $wpdb->get_col($wpdb->prepare("SELECT quiz_id FROM $table WHERE user_id = %d", $aid));
    foreach (array_combine($latest, [90, 50]) as $qid => $pct) {
        if (!in_array((string) $qid, $had, true)) {
            $wpdb->insert($table, ['user_id' => $aid, 'quiz_id' => $qid, 'best_percent' => $pct, 'attempts' => 2, 'awarded_xp' => 0, 'last_attempt_at' => '2026-11-24 00:00:00']);
        }
    }
    register_shutdown_function(function () use ($wpdb, $table, $aid, $latest, $had) {
        foreach ($latest as $qid) {
            if (!in_array((string) $qid, $had, true)) {
                $wpdb->delete($table, ['user_id' => $aid, 'quiz_id' => $qid]);
            }
        }
    });
    foreach (['guest' => 0, 'admin' => (int) ($admins[0] ?? 0)] as $who => $uid) {
        wp_set_current_user($uid);
        $out["$who-ajax1"]  = snap_ajax(1);
        $out["$who-ajax2"]  = snap_ajax(2);
        $out["$who-slider"] = do_shortcode('[quiz_slider]');
    }
    return $out;
}
function norm(string $h): string {
    $h = preg_replace('#(<h3 class="ygv-quiz-card__title">)\s*<a class="ygv-quiz-card__title-link" href="[^"]*">(.*?)</a>\s*(</h3>)#s', '$1$2$3', $h);
    $h = preg_replace('/ygv-quiz-slider-[0-9a-f]+/', 'ygv-quiz-slider-X', $h);
    $h = html_entity_decode($h, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return preg_replace('/\s+/u', '', $h);
}

$now = snap_all();
if ($mode === 'save') {
    file_put_contents($file, wp_json_encode($now));
    echo 'sačuvano: ' . count($now) . " snimaka\n";
    $GLOBALS['ygv_test_done'] = true;
    exit(0);
}

$old = json_decode((string) @file_get_contents($file), true) ?: [];
$fails = 0;
foreach ($now as $k => $html) {
    $a = norm($old[$k] ?? '');
    $b = norm($html);
    if ($a !== '' && $a === $b) { echo "PASS  $k\n"; continue; }
    $fails++;
    $i = 0; $n = min(strlen($a), strlen($b));
    while ($i < $n && $a[$i] === $b[$i]) { $i++; }
    echo "FAIL  $k (razlika od znaka $i)\n      staro: " . substr($a, max(0, $i - 80), 300) . "\n      novo:  " . substr($b, max(0, $i - 80), 300) . "\n";
}
$GLOBALS['ygv_test_done'] = true;
echo $fails === 0 ? "SVE PASS\n" : "PALO: $fails\n";
exit($fails === 0 ? 0 : 1);
