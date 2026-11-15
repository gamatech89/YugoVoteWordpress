<?php
// Integracioni test SEO kutije. Pokreće se u WP kontejneru:
//   docker compose cp tests/seo/test-admin-save.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-admin-save.php
$GLOBALS['ygv_test_done'] = false;
register_shutdown_function(function () {
    if (empty($GLOBALS['ygv_test_done'])) {
        $e = error_get_last();
        echo "FAIL  test se nije završio: " . ($e['message'] ?? 'nepoznato') . "\n";
    }
});
define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/template.php';

$fails = 0;
function t(string $name, $got, $want): void {
    global $fails;
    if ($got === $want) { echo "PASS  $name\n"; return; }
    $fails++;
    echo "FAIL  $name\n      got:  " . var_export($got, true) . "\n      want: " . var_export($want, true) . "\n";
}

$admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
$admin = (int) ($admin[0] ?? 0);
$list  = get_posts(['post_type' => 'voting_list', 'name' => 'najbolji-crnogorski-fudbaleri', 'numberposts' => 1]);
$pid   = $list ? $list[0]->ID : 0;
$terms = get_terms(['taxonomy' => 'voting_list_category', 'hide_empty' => false, 'number' => 1]);
$tid   = $terms ? $terms[0]->term_id : 0;
t('setup: admin, lista i term postoje', $admin > 0 && $pid > 0 && $tid > 0, true);

$clean = function () use ($pid, $tid) {
    foreach (['title', 'desc', 'noindex'] as $f) {
        delete_post_meta($pid, '_ygv_seo_' . $f);
        delete_term_meta($tid, '_ygv_seo_' . $f);
    }
};
$clean();

// --- postovi
wp_set_current_user($admin);
$_POST = ['ygv_seo_nonce' => wp_create_nonce('ygv_seo_save'), 'ygv_seo_title' => '  Test naslov <b>x</b> ', 'ygv_seo_desc' => "Test opis\nu dva reda", 'ygv_seo_noindex' => '1'];
ygv_seo_save_post($pid);
t('post: naslov sačuvan i očišćen', get_post_meta($pid, '_ygv_seo_title', true), 'Test naslov x');
t('post: opis sačuvan', get_post_meta($pid, '_ygv_seo_desc', true), 'Test opis u dva reda');
t('post: noindex sačuvan', get_post_meta($pid, '_ygv_seo_noindex', true), '1');

$_POST = ['ygv_seo_nonce' => 'los-nonce', 'ygv_seo_title' => 'Hakovano', 'ygv_seo_desc' => '', ];
ygv_seo_save_post($pid);
t('post: loš nonce ne menja ništa', get_post_meta($pid, '_ygv_seo_title', true), 'Test naslov x');

wp_set_current_user(0);
$_POST = ['ygv_seo_nonce' => wp_create_nonce('ygv_seo_save'), 'ygv_seo_title' => 'Gost', 'ygv_seo_desc' => ''];
ygv_seo_save_post($pid);
wp_set_current_user($admin);
t('post: bez prava ne menja ništa', get_post_meta($pid, '_ygv_seo_title', true), 'Test naslov x');

$_POST = ['ygv_seo_nonce' => wp_create_nonce('ygv_seo_save'), 'ygv_seo_title' => '', 'ygv_seo_desc' => '   '];
ygv_seo_save_post($pid);
t('post: prazan naslov briše meta', metadata_exists('post', $pid, '_ygv_seo_title'), false);
t('post: prazan opis briše meta', metadata_exists('post', $pid, '_ygv_seo_desc'), false);
t('post: neštiklirano briše noindex', metadata_exists('post', $pid, '_ygv_seo_noindex'), false);

// --- termini
$_POST = ['ygv_seo_nonce' => wp_create_nonce('ygv_seo_save'), 'ygv_seo_title' => 'Term naslov', 'ygv_seo_desc' => 'Term opis', 'ygv_seo_noindex' => '1'];
ygv_seo_save_term($tid);
t('term: naslov', get_term_meta($tid, '_ygv_seo_title', true), 'Term naslov');
t('term: noindex', get_term_meta($tid, '_ygv_seo_noindex', true), '1');
$_POST = ['ygv_seo_nonce' => 'los', 'ygv_seo_title' => 'X'];
ygv_seo_save_term($tid);
t('term: loš nonce ne menja', get_term_meta($tid, '_ygv_seo_title', true), 'Term naslov');
$_POST = ['ygv_seo_nonce' => wp_create_nonce('ygv_seo_save'), 'ygv_seo_title' => '', 'ygv_seo_desc' => ''];
ygv_seo_save_term($tid);
t('term: prazno briše', metadata_exists('term', $tid, '_ygv_seo_title'), false);

// --- render
ob_start();
ygv_seo_render_post_box(get_post($pid));
$html = ob_get_clean();
t('render: polje naslova', strpos($html, 'name="ygv_seo_title"') !== false, true);
t('render: polje opisa', strpos($html, 'name="ygv_seo_desc"') !== false, true);
t('render: noindex checkbox', strpos($html, 'name="ygv_seo_noindex"') !== false, true);
t('render: nonce', strpos($html, 'name="ygv_seo_nonce"') !== false, true);
t('render: pregled automatskog opisa', strpos($html, esc_html(ygv_seo_auto_desc_post(get_post($pid)))) !== false, true);

ob_start();
ygv_seo_render_term_fields(get_term($tid));
$html = ob_get_clean();
t('render term: polja', strpos($html, 'name="ygv_seo_title"') !== false && strpos($html, 'name="ygv_seo_nonce"') !== false, true);

$clean();
$_POST = [];
$GLOBALS['ygv_test_done'] = true;
echo $fails ? "\n$fails FAIL\n" : "\nSVE PASS\n";
exit($fails ? 1 : 0);
