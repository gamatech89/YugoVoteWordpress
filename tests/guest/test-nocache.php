<?php
// Stranice sa formama (prijava, registracija…) ne smeju u javni LiteSpeed keš: nonce u formi bi zastareo posle 24h.
// U kontejneru:
//   docker compose cp tests/guest/test-nocache.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-nocache.php
$GLOBALS['ygv_test_done'] = false;
register_shutdown_function(function () {
    if (empty($GLOBALS['ygv_test_done'])) {
        $e = error_get_last();
        echo "FAIL  test se nije završio: " . ($e['message'] ?? 'nepoznato') . "\n";
    }
});
define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
require '/var/www/html/wp-load.php';

$fails = 0;
function t(string $name, $got, $want): void {
    global $fails;
    if ($got === $want) { echo "PASS  $name\n"; return; }
    $fails++;
    echo "FAIL  $name\n      got:  " . var_export($got, true) . "\n      want: " . var_export($want, true) . "\n";
}

$nocache = 0;
add_action('litespeed_control_set_nocache', function () use (&$nocache) { $nocache++; });

function ygv_test_query_page(string $slug): bool {
    $page = get_page_by_path($slug);
    if (!$page) { return false; }
    $GLOBALS['wp_query']     = new WP_Query(['page_id' => $page->ID]);
    $GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
    return true;
}

t('funkcija postoji', function_exists('ygv_nocache_form_pages'), true);

foreach (['prijava', 'registracija', 'zaboravljena-lozinka', 'kompletiranje-naloga'] as $slug) {
    if (!ygv_test_query_page($slug)) { echo "SKIP  $slug (nema stranice lokalno)\n"; continue; }
    $nocache = 0;
    if (function_exists('ygv_nocache_form_pages')) { ygv_nocache_form_pages(); }
    t("$slug: bez javnog keša", $nocache, 1);
}

// Obična stranica ostaje u kešu
if (ygv_test_query_page('o-nama')) {
    $nocache = 0;
    if (function_exists('ygv_nocache_form_pages')) { ygv_nocache_form_pages(); }
    t('o-nama: keš ostaje', $nocache, 0);
}

$GLOBALS['ygv_test_done'] = true;
echo $fails === 0 ? "SVE PASS\n" : "PALO: $fails\n";
exit($fails === 0 ? 0 : 1);
