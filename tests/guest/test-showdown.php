<?php
// Dvoboji: gost se prepoznaje po kolačiću (ne po IP-u), glasovi gosta prelaze na nalog posle prijave,
// server ne crta lične rezultate u keširani HTML, javni nonce važi za goste.
// U kontejneru:
//   docker compose cp tests/guest/test-showdown.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-showdown.php
$GLOBALS['ygv_test_done'] = false;
register_shutdown_function(function () {
    if (empty($GLOBALS['ygv_test_done'])) {
        $e = error_get_last();
        echo "FAIL  test se nije završio: " . ($e['message'] ?? 'nepoznato') . "\n";
    }
});
define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$fails = 0;
function t(string $name, $got, $want): void {
    global $fails;
    if ($got === $want) { echo "PASS  $name\n"; return; }
    $fails++;
    echo "FAIL  $name\n      got:  " . var_export($got, true) . "\n      want: " . var_export($want, true) . "\n";
}

global $wpdb;
$table = $wpdb->prefix . 'yuv_showdown_sessions';
$m     = new YUV_Showdown_Manager();

// Dva dvoboja sa bar 2 učesnika
$sds = [];
foreach (get_posts(['post_type' => 'yuv_showdown', 'numberposts' => 20, 'post_status' => 'publish']) as $p) {
    if (count($m->get_items($p->ID)) >= 2) { $sds[] = $p; }
    if (count($sds) === 2) { break; }
}
if (count($sds) < 2) { echo "FAIL  trebaju 2 dvoboja u lokalnoj bazi\n"; $GLOBALS['ygv_test_done'] = true; exit(1); }
[$sdA, $sdB] = $sds;
$names = fn($sd) => array_column($m->get_items($sd->ID), 'name');
$resA  = $names($sdA);
$resB  = $names($sdB);

$tok1 = bin2hex(random_bytes(16));
$tok2 = bin2hex(random_bytes(16));
$uid  = 0;

// Čišćenje i kad test pukne (fatal preskače finally).
$cleanup = function () use (&$uid, $table, $tok1, $tok2) {
    global $wpdb;
    static $done = false;
    if ($done) { return; }
    $done = true;
    $wpdb->suppress_errors(true);
    $wpdb->query("DELETE FROM $table WHERE ip_address = '203.0.113.7'");
    $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE guest_token IN (%s,%s)", $tok1, $tok2));
    if ($uid) {
        $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE user_id=%d", $uid));
        wp_delete_user($uid);
    }
};
register_shutdown_function($cleanup);

try {
    // --- šema
    $cols = $wpdb->get_col("SHOW COLUMNS FROM $table");
    t('tabela ima kolonu guest_token', in_array('guest_token', $cols, true), true);

    // --- gost po kolačiću, ne po IP-u
    $r = $m->save_session($sdA->ID, 0, '203.0.113.7', $resA, end($resA), $tok1);
    t('gost (token1) čuva glas', $r['success'] ?? null, true);
    t('isti token = već glasao', $m->has_user_played($sdA->ID, 0, '203.0.113.7', $tok1), true);
    t('isti IP, drugi token = nije glasao (porodica/mobilna mreža)', $m->has_user_played($sdA->ID, 0, '203.0.113.7', $tok2), false);
    $r = $m->save_session($sdA->ID, 0, '203.0.113.7', $resA, end($resA), $tok1);
    t('isti token ne može dva puta', $r['success'] ?? null, false);
    t('sesija gosta po tokenu', is_array($m->get_user_session($sdA->ID, 0, '', $tok1)), true);

    // --- prijava: glas gosta prelazi na nalog
    $uid = wp_insert_user(['user_login' => 'sdtest_' . $tok2, 'user_pass' => wp_generate_password(), 'user_email' => $tok2 . '@example.invalid']);
    t('test korisnik napravljen', is_int($uid) && $uid > 0, true);

    // nalog je već glasao u dvoboju B kao prijavljen; gost (token1) glasao u B pre toga
    $m->save_session($sdB->ID, 0, '203.0.113.7', $resB, end($resB), $tok1);
    $m->save_session($sdB->ID, $uid, '203.0.113.7', $resB, $resB[0], '');

    $_COOKIE['yuv_sd_guest'] = $tok1;
    do_action('set_logged_in_cookie', 'x', time() + 3600, time() + 3600, $uid, 'logged_in', 'tok');
    unset($_COOKIE['yuv_sd_guest']);

    t('posle prijave: nalog vidi svoj glas iz dvoboja A', $m->has_user_played($sdA->ID, $uid), true);
    $guestB = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE showdown_id=%d AND guest_token=%s AND user_id IS NULL", $sdB->ID, $tok1));
    t('dvoboj B: nalog je već glasao → glas gosta ostaje anoniman (ne briše se)', $guestB, 1);
    $userB = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE showdown_id=%d AND user_id=%d", $sdB->ID, $uid));
    t('dvoboj B: nalog i dalje ima tačno jedan glas', $userB, 1);

    // --- loš token se ignoriše
    t('neispravan token se odbacuje', yuv_showdown_guest_token("abc'; DROP"), '');
    t('ispravan token prolazi', yuv_showdown_guest_token($tok2), $tok2);

    // --- shortcode ne crta lične rezultate (keš bi ih podelio svim gostima)
    $_COOKIE['yuv_sd_guest'] = $tok1;
    $html = do_shortcode('[yuv_showdown id="' . $sdA->ID . '"]');
    unset($_COOKIE['yuv_sd_guest']);
    t('shortcode: bez vidljivih rezultata u HTML-u', strpos($html, 'sd-results-screen--visible') === false, true);
    t('shortcode: bez "Pobede:" u HTML-u', strpos($html, 'Pobede:') === false, true);

    // --- javni nonce: gost prolazi i sa zastarelim, prijavljen ne
    wp_set_current_user(0);
    t('gost: zastareli nonce prolazi', ygv_public_nonce_ok('yuv_showdown_nonce', '0000000000'), true);
    wp_set_current_user($uid);
    t('prijavljen: zastareli nonce ne prolazi', ygv_public_nonce_ok('yuv_showdown_nonce', '0000000000'), false);
    t('prijavljen: važeći nonce prolazi', ygv_public_nonce_ok('yuv_showdown_nonce', wp_create_nonce('yuv_showdown_nonce')), true);
    wp_set_current_user(0);
} finally {
    $cleanup();
}

$GLOBALS['ygv_test_done'] = true;
echo $fails === 0 ? "SVE PASS\n" : "PALO: $fails\n";
exit($fails === 0 ? 0 : 1);
