<?php
// Integracioni testovi konteksta/sitemap-a (ispravke posle recenzije). U kontejneru:
//   docker compose cp tests/seo/test-context.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-context.php
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
function fake_post(array $f): WP_Post {
    return new WP_Post((object) array_merge(['ID' => 0, 'post_title' => '', 'post_content' => '', 'post_type' => 'post', 'post_password' => '', 'post_status' => 'publish', 'filter' => 'raw'], $f));
}

// --- #3 interpunkcija u automatskim opisima
$d = ygv_seo_auto_desc_post(fake_post(['post_type' => 'quiz', 'post_title' => 'Čiji si ti?']));
t('kviz: bez "?."', strpos($d, '?.') === false, true);
$d = ygv_seo_auto_desc_post(fake_post(['post_type' => 'quiz', 'post_title' => 'Kviz o pivu']));
t('kviz: bez "Kviz: Kviz"', stripos($d, 'Kviz: Kviz') === false, true);

$poll = get_posts(['post_type' => 'voting_poll', 'numberposts' => 1, 'meta_key' => '_cs_poll_answers']);
$pid  = $poll ? $poll[0]->ID : 0;
$d = ygv_seo_auto_desc_post(fake_post(['ID' => $pid, 'post_type' => 'voting_poll', 'post_title' => 'X', 'post_content' => 'Euroliga poredak']));
t('anketa: sadržaj se završava tačkom pre odgovora', strpos($d, 'Euroliga poredak. ') === 0, true);
$d = ygv_seo_auto_desc_post(fake_post(['post_type' => 'voting_poll', 'post_title' => 'Kuda na more', 'post_content' => '']));
t('anketa: prazan sadržaj → naslov sa tačkom', strpos($d, 'Kuda na more. ') === 0, true);

$term = new WP_Term((object) ['term_id' => 0, 'name' => 'Pevači', 'taxonomy' => 'voting_item_category', 'description' => '']);
t('kategorija stavki: ne pominje kvizove', stripos(ygv_seo_auto_desc_term($term), 'kviz') === false, true);

// --- #6 lozinka
$d = ygv_seo_auto_desc_post(fake_post(['post_type' => 'page', 'post_title' => 'Zaključano', 'post_content' => 'Tajni sadržaj stranice', 'post_password' => 'x']));
t('lozinka: sadržaj ne curi', strpos($d, 'Tajni') === false, true);

// --- #1 sitemap bez meta_query, sa post__not_in
$lists = get_posts(['post_type' => 'voting_list', 'numberposts' => 2, 'orderby' => 'ID', 'order' => 'ASC']);
[$a, $b] = [$lists[0]->ID, $lists[1]->ID];
update_post_meta($a, '_ygv_seo_noindex', '1');
update_post_meta($b, '_is_tournament_match', '1');
$args = apply_filters('wp_sitemaps_posts_query_args', ['post_type' => 'voting_list'], 'voting_list');
t('sitemap: nema meta_query', empty($args['meta_query']), true);
t('sitemap: noindex lista isključena', in_array($a, $args['post__not_in'] ?? [], true), true);
t('sitemap: turnir lista isključena', in_array($b, $args['post__not_in'] ?? [], true), true);
$args = apply_filters('wp_sitemaps_posts_query_args', ['post_type' => 'voting_items'], 'voting_items');
t('sitemap stavke: nema meta_query', empty($args['meta_query']), true);
$prijava = get_page_by_path('prijava');
$args = apply_filters('wp_sitemaps_posts_query_args', ['post_type' => 'page'], 'page');
t('sitemap stranice: prijava isključena', in_array($prijava->ID, $args['post__not_in'] ?? [], true), true);
delete_post_meta($a, '_ygv_seo_noindex');
delete_post_meta($b, '_is_tournament_match');

$terms = get_terms(['taxonomy' => 'voting_list_category', 'hide_empty' => false, 'number' => 1]);
$tid = $terms[0]->term_id;
update_term_meta($tid, '_ygv_seo_noindex', '1');
$targs = apply_filters('wp_sitemaps_taxonomies_query_args', ['taxonomy' => 'voting_list_category'], 'voting_list_category');
t('sitemap termini: nema meta_query', empty($targs['meta_query']), true);
t('sitemap termini: noindex term isključen', in_array($tid, $targs['exclude'] ?? [], true), true);
delete_term_meta($tid, '_ygv_seo_noindex');

$GLOBALS['ygv_test_done'] = true;
echo $fails ? "\n$fails FAIL\n" : "\nSVE PASS\n";
exit($fails ? 1 : 0);
