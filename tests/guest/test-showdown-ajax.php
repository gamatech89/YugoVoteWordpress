<?php
// Dvoboj: čuvanje kroz pravi AJAX handler, sa imenima koja imaju apostrof/ampersand
// (WordPress dodaje kose crte u $_POST).
// U kontejneru:
//   docker compose cp tests/guest/test-showdown-ajax.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-showdown-ajax.php
$GLOBALS['ygv_test_done'] = false;
register_shutdown_function(function () {
    if (empty($GLOBALS['ygv_test_done'])) {
        $e = error_get_last();
        echo "FAIL  test se nije završio: " . ($e['message'] ?? 'nepoznato') . "\n";
    }
});
define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
define('DOING_AJAX', true);
$_SERVER['REMOTE_ADDR'] = '203.0.113.11';
require '/var/www/html/wp-load.php';

$fails = 0;
function t(string $name, $got, $want): void {
    global $fails;
    if ($got === $want) { echo "PASS  $name\n"; return; }
    $fails++;
    echo "FAIL  $name\n      got:  " . var_export($got, true) . "\n      want: " . var_export($want, true) . "\n";
}
class YgvTestDie extends Exception {}
add_filter('wp_die_ajax_handler', fn() => function () { throw new YgvTestDie(); });
function call_ajax(callable $fn, array $post): array {
    $_POST = $_REQUEST = wp_slash($post); // kao wp_magic_quotes()
    ob_start();
    try { $fn(); } catch (YgvTestDie $e) {}
    return json_decode((string) ob_get_clean(), true) ?: [];
}

global $wpdb;
$table = $wpdb->prefix . 'yuv_showdown_sessions';
$names = ["Rock'n'roll", 'Ana & Mia', 'Obično "ime"'];
$sid = wp_insert_post(['post_type' => 'yuv_showdown', 'post_status' => 'publish', 'post_title' => 'Test dvoboj znakovi']);
update_post_meta($sid, '_yuv_showdown_items', array_map(fn($n) => ['name' => $n, 'description' => '', 'image_url' => '', 'image_id' => 0], $names));

$cleanup = function () use ($sid, $table) {
    global $wpdb;
    static $done = false; if ($done) return; $done = true;
    $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE showdown_id=%d", $sid));
    wp_delete_post($sid, true);
};
register_shutdown_function($cleanup);

try {
    wp_set_current_user(0);
    $_COOKIE['yuv_sd_guest'] = bin2hex(random_bytes(16));
    $order = ['Ana & Mia', 'Obično "ime"', "Rock'n'roll"]; // poslednji = pobednik
    $r = call_ajax('yuv_showdown_save_session_ajax', [
        'nonce' => '0000000000', 'showdown_id' => $sid,
        'results' => wp_json_encode($order), 'winner' => "Rock'n'roll",
    ]);
    t('gost: glas sa apostrofom u imenu je sačuvan', $r['success'] ?? null, true);
    $board = $r['data']['leaderboard'] ?? [];
    t('poredak: pobednik ima 1 pobedu', isset($board[0]) ? [$board[0]['name'], $board[0]['wins']] : null, ["Rock'n'roll", 1]);
} finally {
    $cleanup();
}

$GLOBALS['ygv_test_done'] = true;
echo $fails === 0 ? "SVE PASS\n" : "PALO: $fails\n";
exit($fails === 0 ? 0 : 1);
