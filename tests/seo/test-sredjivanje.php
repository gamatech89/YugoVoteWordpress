<?php
// Sređivanje posle etape 2: sitnice iz recenzije + izvori šuma u debug.log-u. U kontejneru:
//   docker compose cp tests/seo/test-sredjivanje.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-sredjivanje.php
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

// ---------- privremeni podaci (brišu se na kraju, i posle fatalne greške) ----------
remove_action('created_voting_list_category', 'regenerate_category_color_css');
remove_action('edited_voting_list_category', 'regenerate_category_color_css');
$made = ['posts' => [], 'pivot' => [], 'meta' => []];
register_shutdown_function(function () use (&$made) {
    global $wpdb;
    foreach ($made['pivot'] as $id) { $wpdb->delete($wpdb->prefix . 'voting_list_item_relations', ['id' => $id]); }
    foreach ($made['meta'] as [$uid, $key, $old]) {
        $old === '' ? delete_user_meta($uid, $key) : update_user_meta($uid, $key, $old);
    }
    foreach ($made['posts'] as $id) { wp_delete_post($id, true); }
});
function mk_post(string $type, string $title, array $extra = []): int {
    global $made, $wpdb;
    $id = wp_insert_post(array_merge(['post_type' => $type, 'post_status' => 'publish', 'post_title' => 'tmp'], $extra));
    $wpdb->update($wpdb->posts, ['post_title' => $title], ['ID' => $id]);
    clean_post_cache($id);
    $made['posts'][] = $id;
    return $id;
}
function mk_image(): int {
    global $made;
    $id = wp_insert_attachment(['post_mime_type' => 'image/jpeg', 'post_title' => 'ygvtest', 'post_status' => 'inherit'], '2030/01/ygvtest.jpg');
    update_post_meta($id, '_wp_attached_file', '2030/01/ygvtest.jpg');
    wp_update_attachment_metadata($id, ['width' => 1200, 'height' => 800, 'file' => '2030/01/ygvtest.jpg', 'sizes' => []]);
    $made['posts'][] = $id;
    return $id;
}
function render_singular(int $id, string $tpl): string {
    global $wp_query, $wp_the_query, $post;
    $wp_query = $wp_the_query = new WP_Query(['p' => $id, 'post_type' => get_post_type($id)]);
    $post = get_post($id);
    setup_postdata($post);
    ob_start();
    include get_stylesheet_directory() . '/' . $tpl;
    $html = ob_get_clean();
    wp_reset_postdata();
    return $html;
}
function landing_img(string $html): string {
    return preg_match('#class="ygv-landing-card__img">\s*(<img\b[^>]*>)#', $html, $m) ? $m[1] : '';
}

wp_set_current_user(0);
// get_header() se učitava samo jednom (require_once), pa ga ovde potrošimo da ne meša prvi render
ob_start(); get_header(); ob_end_clean();

// ---------- težina kviza: jedno mesto ----------
$lvl = mk_post('quiz_levels', 'Srednje', ['post_name' => 'srednje']);
$Q1  = mk_post('quiz', 'Dva  razmaka');
update_post_meta($Q1, '_quiz_difficulty', $lvl);
$Q2  = mk_post('quiz', 'Bez težine');
t('ygv_quiz_difficulty: naziv i slug', function_exists('ygv_quiz_difficulty') ? ygv_quiz_difficulty($Q1) : null, ['name' => 'Srednje', 'slug' => 'srednje']);
t('ygv_quiz_difficulty: bez nivoa', function_exists('ygv_quiz_difficulty') ? ygv_quiz_difficulty($Q2) : null, ['name' => '', 'slug' => '']);
$d = ygv_quiz_landing_data($Q1, 0);
t('podaci stranice: bez neiskorišćenog title', array_key_exists('title', $d), false);
t('podaci stranice: težina i boja', [$d['difficulty'], $d['difficulty_color']], ['Srednje', '#f59e0b']);

// ---------- stranica kviza: H1 i slika ----------
set_post_thumbnail($Q1, mk_image());
$html = render_singular($Q1, 'single-quiz.php');
t('kviz: H1 bez duplog razmaka', preg_match('#<h1 class="ygv-landing-card__title">(.*?)</h1>#s', $html, $m) ? $m[1] : null, 'Dva razmaka');
$img = landing_img($html);
t('kviz: slika postoji', $img !== '', true);
t('kviz: slika nije lazy', strpos($img, 'loading="lazy"'), false);

// ---------- stranica ankete: slika i „Još anketa“ ----------
$A1 = mk_post('voting_poll', 'Anketa  A1');
$A2 = mk_post('voting_poll', 'Anketa A2');
foreach ([$A1, $A2] as $a) { update_post_meta($a, '_cs_poll_answers', [['text' => 'Da', 'votes' => 0], ['text' => 'Ne', 'votes' => 0]]); }
set_post_thumbnail($A1, mk_image());
$html = render_singular($A1, 'single-voting_poll.php');
t('anketa: H1 bez duplog razmaka', preg_match('#<h1 class="ygv-landing-card__title">(.*?)</h1>#s', $html, $m) ? $m[1] : null, 'Anketa A1');
t('ygv_render_poll_card postoji', function_exists('ygv_render_poll_card'), true);
if (function_exists('ygv_render_poll_card')) {
    $poll_id = 12345; // spoljna promenljiva ne sme da se promeni
    ob_start(); ygv_render_poll_card($A2); $card = ob_get_clean();
    t('kartica ankete: forma te ankete', (bool) preg_match('#data-poll-id="' . $A2 . '"#', $card), true);
    t('kartica ankete: ne dira spoljni $poll_id', $poll_id, 12345);
}
t('anketa: šablon ne prepisuje $poll_id u petlji', strpos(file_get_contents(get_stylesheet_directory() . '/single-voting_poll.php'), '$poll_id     = $rid'), false);

// ---------- debug.log: bez poruka po zahtevu ----------
$noisy = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(get_stylesheet_directory() . '/inc', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getExtension() === 'php' && strpos(file_get_contents($f->getPathname()), "error_log('YGV: registering") !== false) {
        $noisy[] = basename($f->getPathname());
    }
}
t('nema „YGV: registering“ u logu', $noisy, []);

// ProgressService: tabele se proveravaju jednom (verzija u opciji), ne na svakom zahtevu
$n = 0;
add_filter('dbdelta_queries', function ($q) use (&$n) { $n++; return $q; });
$cls = 'HelloElementorChild\\Quizzes\\Services\\ProgressService';
$ver_const = $cls . '::DB_VERSION';
update_option('ygv_progress_db_version', 'stara-verzija'); // ne delete_option: lokalni objektni keš ume da vrati obrisanu opciju
new $cls();
t('progress: stara verzija → dbDelta za 3 tabele', $n, 3);
t('progress: verzija upisana', defined($ver_const) && get_option('ygv_progress_db_version') === constant($ver_const), true);
$n = 0;
new $cls();
t('progress: posle toga bez dbDelta', $n, 0);

// Pretraga: bez upita ka tabelama koje ne postoje; stavka se nalazi preko pivot tabele
$bad = [];
add_filter('query', function ($q) use (&$bad) {
    global $wpdb;
    if (preg_match('#\b' . $wpdb->prefix . '(voting_items|voting_list_items)\b#', $q)) { $bad[] = $q; }
    return $q;
});
$L = mk_post('voting_list', 'Ygvtestlista');
$I = mk_post('voting_items', 'Ygvtestpivotstavka');
$wpdb->insert($wpdb->prefix . 'voting_list_item_relations', ['voting_list_id' => $L, 'voting_item_id' => $I]);
$made['pivot'][] = (int) $wpdb->insert_id;
add_filter('wp_doing_ajax', '__return_true');
add_filter('wp_die_ajax_handler', function () { return function () { throw new RuntimeException('ygv-die'); }; });
function run_search(string $s): array {
    $_POST = ['search' => $s];
    ob_start();
    try { ygv_live_search_handler(); } catch (RuntimeException $e) {}
    $out = json_decode((string) ob_get_clean(), true);
    return $out['data'] ?? [];
}
$res = run_search('Ygvtestlista');
t('pretraga liste: pronađena', in_array($L, array_column($res, 'id'), true), true);
$res = run_search('Ygvtestpivotstavka');
$hit = array_values(array_filter($res, function ($r) use ($L) { return $r['id'] === $L; }));
t('pretraga stavke: lista iz pivot tabele', $hit ? ($hit[0]['match_info'] ?? '') : null, 'Sadrži: Ygvtestpivotstavka');
t('pretraga: bez upita ka nepostojećim tabelama', $bad, []);

// /anketa/: „Tvojih glasova“ iz user meta (_cs_voted_polls), ne iz nepostojeće tabele
$admin = (int) (get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID'])[0] ?? 0);
$made['meta'][] = [$admin, '_cs_voted_polls', get_user_meta($admin, '_cs_voted_polls', true)];
update_user_meta($admin, '_cs_voted_polls', [$A1, $A2, $A2]);
wp_set_current_user($admin);
$page = get_page_by_path('anketa');
$wp_query = $wp_the_query = new WP_Query(['page_id' => $page ? $page->ID : 0]);
$errs = [];
add_filter('query', function ($q) use (&$errs) { if (strpos($q, 'cs_poll_votes') !== false) { $errs[] = $q; } return $q; });
ob_start(); include get_stylesheet_directory() . '/page-ankete.php'; $html = ob_get_clean();
t('/anketa/: tvojih glasova = 2', preg_match('#stat-value">(\d+)</span>\s*<span class="ygv-page-hero__stat-label">Tvojih glasova#', $html, $m) ? $m[1] : null, '2');
t('/anketa/: bez upita ka cs_poll_votes', $errs, []);
wp_set_current_user(0);

$GLOBALS['ygv_test_done'] = true;
echo $fails === 0 ? "SVE PASS\n" : "PALO: $fails\n";
exit($fails === 0 ? 0 : 1);
