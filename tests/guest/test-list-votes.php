<?php
// Rang liste: gost na istoj mreži (isti IP) ne sme da obriše glasove prijavljenog korisnika.
// U kontejneru:
//   docker compose cp tests/guest/test-list-votes.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-list-votes.php
$GLOBALS['ygv_test_done'] = false;
register_shutdown_function(function () {
    if (empty($GLOBALS['ygv_test_done'])) {
        $e = error_get_last();
        echo "FAIL  test se nije završio: " . ($e['message'] ?? 'nepoznato') . "\n";
    }
});
define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
define('DOING_AJAX', true);
$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$fails = 0;
function t(string $name, $got, $want): void {
    global $fails;
    if ($got === $want) { echo "PASS  $name\n"; return; }
    $fails++;
    echo "FAIL  $name\n      got:  " . var_export($got, true) . "\n      want: " . var_export($want, true) . "\n";
}

// wp_send_json_* u AJAX-u zove wp_die → umesto izlaska bacamo izuzetak
class YgvTestDie extends Exception {}
add_filter('wp_die_ajax_handler', fn() => function () { throw new YgvTestDie(); });
function call_ajax(callable $fn, array $post): string {
    $_POST = $_REQUEST = $post;
    ob_start();
    try { $fn(); } catch (YgvTestDie $e) {}
    return (string) ob_get_clean();
}

global $wpdb;
$table = $wpdb->prefix . 'voting_list_votes';
$ip    = '203.0.113.9';

// Lista sa bar 2 stavke
$list = null; $items = [];
foreach (get_posts(['post_type' => 'voting_list', 'numberposts' => 30, 'post_status' => 'publish']) as $p) {
    $ids = get_post_meta($p->ID, '_voting_items', true);
    if (is_array($ids) && count($ids) >= 2) { $list = $p->ID; $items = array_values($ids); break; }
}
if (!$list) { echo "FAIL  treba lista sa 2 stavke\n"; $GLOBALS['ygv_test_done'] = true; exit(1); }
[$itemA, $itemB] = [(int) $items[0], (int) $items[1]];

$uid = wp_insert_user(['user_login' => 'lvtest_' . wp_generate_password(8, false), 'user_pass' => wp_generate_password(), 'user_email' => 'lvtest_' . wp_generate_password(8, false) . '@example.invalid']);
$cleanup = function () use ($table, $ip, $list, &$uid) {
    global $wpdb;
    static $done = false; if ($done) return; $done = true;
    $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE voting_list_id=%d AND ip_address=%s", $list, $ip));
    if ($uid) { wp_delete_user($uid); }
};
register_shutdown_function($cleanup);

try {
    // Prijavljen korisnik (isti IP) dao 7 poena stavci A
    $wpdb->insert($table, ['voting_list_id' => $list, 'voting_item_id' => $itemA, 'base_vote_value' => 7, 'vote_value' => 7,
        'expert_bonus' => 0, 'ip_address' => $ip, 'user_id' => $uid, 'created_at' => current_time('mysql', 1)]);
    $userRow = (int) $wpdb->insert_id;
    t('glas prijavljenog upisan', $userRow > 0, true);

    wp_set_current_user(0);

    // Gost sa istog IP-a daje 7 poena stavci B → briše SAMO svoje ranije sedmice, ne tuđe
    call_ajax('submit_vote', ['voting_list_id' => $list, 'voting_item_id' => $itemB, 'vote_value' => 7, 'nonce' => 'x']);
    $still = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE id=%d", $userRow));
    t('gost glasa: glas prijavljenog sa istog IP-a ostaje', $still, 1);

    // Gost "uklanja" glas sa stavke A → ne sme obrisati glas prijavljenog
    call_ajax('remove_vote', ['voting_list_id' => $list, 'voting_item_id' => $itemA, 'vote_value' => 7, 'nonce' => 'x']);
    $still = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE id=%d", $userRow));
    t('gost uklanja: glas prijavljenog sa istog IP-a ostaje', $still, 1);

    // Gost i dalje može da ukloni SVOJ glas (stavka B)
    call_ajax('remove_vote', ['voting_list_id' => $list, 'voting_item_id' => $itemB, 'vote_value' => 7, 'nonce' => 'x']);
    $guest = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE voting_list_id=%d AND voting_item_id=%d AND user_id IS NULL AND ip_address=%s", $list, $itemB, $ip));
    t('gost uklanja svoj glas', $guest, 0);
} finally {
    $cleanup();
}

$GLOBALS['ygv_test_done'] = true;
echo $fails === 0 ? "SVE PASS\n" : "PALO: $fails\n";
exit($fails === 0 ? 0 : 1);
