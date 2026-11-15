<?php
// Test import skripte. U kontejneru:
//   docker compose cp scripts/seo/import-meta.php wordpress:/tmp/ && docker compose cp tests/seo/test-import.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-import.php
$GLOBALS['ygv_test_done'] = false;
register_shutdown_function(function () {
    if (empty($GLOBALS['ygv_test_done'])) {
        $e = error_get_last();
        echo "FAIL  test se nije završio: " . ($e['message'] ?? 'nepoznato') . "\n";
    }
});
define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
require '/var/www/html/wp-load.php';
define('YGV_SEO_IMPORT_NO_RUN', true);
require '/tmp/import-meta.php';

$fails = 0;
function t(string $name, $got, $want): void {
    global $fails;
    if ($got === $want) { echo "PASS  $name\n"; return; }
    $fails++;
    echo "FAIL  $name\n      got:  " . var_export($got, true) . "\n      want: " . var_export($want, true) . "\n";
}

$list = get_posts(['post_type' => 'voting_list', 'name' => 'najbolji-crnogorski-fudbaleri', 'numberposts' => 1])[0];
$quiz = get_posts(['post_type' => 'quiz', 'numberposts' => 1])[0];
$term = get_terms(['taxonomy' => 'voting_list_category', 'hide_empty' => false, 'number' => 1])[0];
$clean = function () use ($list, $quiz, $term) {
    foreach (['title', 'desc', 'noindex'] as $f) {
        delete_post_meta($list->ID, '_ygv_seo_' . $f);
        delete_post_meta($quiz->ID, '_ygv_seo_' . $f);
        delete_term_meta($term->term_id, '_ygv_seo_' . $f);
    }
};
$clean();

$entries = [
    ['id' => $list->ID, 'kind' => 'post', 'type' => 'voting_list', 'title' => 'Lista naslov', 'desc' => 'Lista opis'],
    ['id' => $quiz->ID, 'kind' => 'post', 'type' => 'quiz', 'title' => 'Kviz naslov', 'desc' => 'Kviz opis', 'noindex' => true],
    ['id' => $term->term_id, 'kind' => 'term', 'type' => 'voting_list_category', 'title' => 'Term naslov', 'desc' => 'Term opis'],
    ['id' => 99999999, 'kind' => 'post', 'type' => 'voting_list', 'title' => 'X', 'desc' => 'Y'],
    ['id' => $list->ID, 'kind' => 'post', 'type' => 'quiz', 'title' => 'X', 'desc' => 'Y'],
];

// dry-run
$r = ygv_seo_import($entries, true);
t('dry: written', $r['written'], 7);   // 3x title + 3x desc + 1x noindex
t('dry: missing', $r['missing'], 1);
t('dry: type_mismatch', $r['type_mismatch'], 1);
t('dry: ništa nije upisano', metadata_exists('post', $list->ID, '_ygv_seo_title'), false);

// pravi upis
$r = ygv_seo_import($entries, false);
t('real: written', $r['written'], 7);
t('real: naslov liste', get_post_meta($list->ID, '_ygv_seo_title', true), 'Lista naslov');
t('real: noindex kviza', get_post_meta($quiz->ID, '_ygv_seo_noindex', true), '1');
t('real: term opis', get_term_meta($term->term_id, '_ygv_seo_desc', true), 'Term opis');
t('real: lista nije noindex', metadata_exists('post', $list->ID, '_ygv_seo_noindex'), false);

// idempotentno
$r = ygv_seo_import($entries, false);
t('ponovo: written 0', $r['written'], 0);
t('ponovo: kept 7', $r['kept'], 7);

// ručna izmena urednika se ne prepisuje
update_post_meta($list->ID, '_ygv_seo_desc', 'Urednik je ovo napisao');
$entries[0]['desc'] = 'Novi opis iz fajla';
$r = ygv_seo_import($entries, false);
t('ručno: ostaje', get_post_meta($list->ID, '_ygv_seo_desc', true), 'Urednik je ovo napisao');
t('ručno: written 0', $r['written'], 0);

// prazan title/desc u unosu se preskače
$clean();
$r = ygv_seo_import([['id' => $list->ID, 'kind' => 'post', 'type' => 'voting_list', 'title' => '', 'desc' => '  ']], false);
t('prazno: ništa', $r['written'], 0);
t('prazno: nema meta', metadata_exists('post', $list->ID, '_ygv_seo_title'), false);

$clean();
$GLOBALS['ygv_test_done'] = true;
echo $fails ? "\n$fails FAIL\n" : "\nSVE PASS\n";
exit($fails ? 1 : 0);
