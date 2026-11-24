<?php
// SEO etapa 2: putanje, JSON-LD, kartica kviza, stranice kviza i ankete. U kontejneru:
//   docker compose cp tests/seo/test-etapa2.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-etapa2.php
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
$made = ['posts' => [], 'terms' => []];
register_shutdown_function(function () use (&$made) {
    foreach ($made['posts'] as $id) { wp_delete_post($id, true); }
    foreach ($made['terms'] as [$id, $tx]) { wp_delete_term($id, $tx); }
});
function mk_post(string $type, string $title, array $extra = []): int {
    global $made, $wpdb;
    $id = wp_insert_post(array_merge(['post_type' => $type, 'post_status' => 'publish', 'post_title' => 'tmp'], $extra));
    // naslov upisujemo direktno, kao što ga WP čuva (sa entitetima), bez kses filtera
    $wpdb->update($wpdb->posts, ['post_title' => $title], ['ID' => $id]);
    clean_post_cache($id);
    $made['posts'][] = $id;
    return $id;
}
function mk_term(string $name, string $tx, int $parent = 0): int {
    global $made;
    $r = wp_insert_term($name, $tx, ['parent' => $parent, 'slug' => sanitize_title('ygvtest-' . $name . '-' . wp_rand())]);
    $made['terms'][] = [$r['term_id'], $tx];
    return (int) $r['term_id'];
}

wp_set_current_user(0);
$P  = mk_term('P', 'voting_list_category');
$C  = mk_term('C', 'voting_list_category', $P);
$L1 = mk_post('voting_list', 'L1');
wp_set_object_terms($L1, [$P, $C], 'voting_list_category');
$L2 = mk_post('voting_list', 'L2');
wp_set_object_terms($L2, [$P], 'voting_list_category');
$K  = mk_term('K', 'quiz_category');
$Q1 = mk_post('quiz', 'Kviz &quot;A&amp;B&quot; &lt;/script&gt;');
wp_set_object_terms($Q1, [$K], 'quiz_category');
$Q2 = mk_post('quiz', 'Q2');
$A  = mk_post('voting_poll', 'A');

// ---------- putanje ----------
$home = ['name' => 'Početna', 'url' => home_url('/')];
t('lista u podkategoriji', ygv_breadcrumb_trail_for(get_post($L1)), [$home, ['name' => 'P', 'url' => get_term_link($P)], ['name' => 'C', 'url' => get_term_link($C)], ['name' => 'L1', 'url' => get_permalink($L1)]]);
t('lista u nadkategoriji', array_column(ygv_breadcrumb_trail_for(get_post($L2)), 'name'), ['Početna', 'P', 'L2']);
t('podkategorija', array_column(ygv_breadcrumb_trail_for(get_term($C)), 'name'), ['Početna', 'P', 'C']);
t('nadkategorija', array_column(ygv_breadcrumb_trail_for(get_term($P)), 'name'), ['Početna', 'P']);
t('kviz', ygv_breadcrumb_trail_for(get_post($Q1)), [['name' => 'Kvizovi', 'url' => get_post_type_archive_link('quiz')], ['name' => 'K', 'url' => ygv_quiz_category_url(get_term($K))], ['name' => 'Kviz "A&B" </script>', 'url' => get_permalink($Q1)]]);
t('kviz bez kategorije', array_column(ygv_breadcrumb_trail_for(get_post($Q2)), 'name'), ['Kvizovi', 'Q2']);
t('kategorija kviza vodi na filtriranu arhivu', ygv_quiz_category_url(get_term($K)), add_query_arg('cat', get_term($K)->slug, get_post_type_archive_link('quiz')));
t('anketa', array_column(ygv_breadcrumb_trail_for(get_post($A)), 'url')[0], get_permalink(get_page_by_path('anketa')));
t('anketa: imena', array_column(ygv_breadcrumb_trail_for(get_post($A)), 'name'), ['Ankete', 'A']);
t('ostalo: bez putanje', ygv_breadcrumb_trail_for(get_post((int) get_option('page_on_front'))), []);

// ---------- JSON-LD ----------
$j = ygv_schema_json([ygv_schema_breadcrumb_node(ygv_breadcrumb_trail_for(get_post($Q1)))]);
$d = json_decode($j, true);
t('JSON bez </script>', stripos($j, '</script') === false, true);
t('JSON se dekodira na tačan naslov', $d['@graph'][0]['itemListElement'][2]['name'] ?? null, 'Kviz "A&B" </script>');
t('JSON: @context', $d['@context'] ?? null, 'https://schema.org');
t('JSON: BreadcrumbList', $d['@graph'][0]['@type'] ?? null, 'BreadcrumbList');
t('pozicije od 1', array_column($d['@graph'][0]['itemListElement'], 'position'), [1, 2, 3]);
t('ListItem tip', array_unique(array_column($d['@graph'][0]['itemListElement'], '@type')), ['ListItem']);

// ---------- kartica kviza (partial) ----------
ob_start(); ygv_render_quiz_card($Q1, 0); $h = ob_get_clean();
t('kartica: naslov je link ka kvizu', (bool) preg_match('#<h3 class="ygv-quiz-card__title"><a class="ygv-quiz-card__title-link" href="' . preg_quote(get_permalink($Q1), '#') . '">#', $h), true);
t('kartica: dugme otvara prozor', strpos($h, 'data-quiz-id="' . $Q1 . '"') !== false, true);
t('kartica: naslov escapovan jednom', strpos($h, 'Kviz &quot;A&amp;B&quot; &lt;/script&gt;') !== false, true);
t('boja težine: tesko', ygv_quiz_difficulty_color('Tesko'), '#ef4444');
t('boja težine: nepoznata', ygv_quiz_difficulty_color('xyz'), '#6366f1');
t('napredak: gost', ygv_quiz_progress(0, $Q1), ['best_percent' => 0, 'attempts' => 0]);

// ---------- stranica kviza ----------
// Iscrtava šablon kao da je otvorena stranica $id; upozorenja iz novih fajlova idu u $GLOBALS['ygv_warnings'].
function ygv_test_render_singular(int $id, string $tpl): string {
    global $wp_query, $wp_the_query, $post;
    $GLOBALS['ygv_warnings'] = [];
    $new_files = '#/(single-quiz|single-voting_poll|quiz-landing|poll-landing|quiz-card|seo-schema|seo-h1)\.php$#';
    set_error_handler(function ($no, $msg, $file, $line) use ($new_files) {
        if (preg_match($new_files, $file)) { $GLOBALS['ygv_warnings'][] = basename($file) . ":$line $msg"; }
        return true;
    }, E_WARNING | E_NOTICE | E_DEPRECATED | E_USER_WARNING | E_USER_NOTICE);
    $wp_query = $wp_the_query = new WP_Query(['p' => $id, 'post_type' => get_post_type($id)]);
    $post = get_post($id);
    setup_postdata($post);
    ob_start();
    include get_stylesheet_directory() . '/' . $tpl;
    $html = ob_get_clean();
    restore_error_handler();
    wp_reset_postdata();
    return $html;
}

t('cta gost zaključan', ygv_quiz_cta_state(0, false, 0), 'locked');
t('cta gost otvoren', ygv_quiz_cta_state(0, true, 0), 'start');
t('cta prijavljen odigrao', ygv_quiz_cta_state(5, false, 2), 'replay');
t('cta prijavljen nije igrao', ygv_quiz_cta_state(5, false, 0), 'start');
update_post_meta($Q1, '_quiz_description', 'Opis iz polja.');
t('opis iz _quiz_description', ygv_quiz_landing_data($Q1, 0)['description'], 'Opis iz polja.');
$wpdb->update($wpdb->posts, ['post_excerpt' => 'Izvod.'], ['ID' => $Q2]); clean_post_cache($Q2);
t('opis: izvod kad nema polja', ygv_quiz_landing_data($Q2, 0)['description'], 'Izvod.');
$wpdb->update($wpdb->posts, ['post_excerpt' => ''], ['ID' => $Q2]); clean_post_cache($Q2);
$d = ygv_quiz_landing_data($Q2, 0);
t('podrazumevano', [$d['questions'], $d['seconds'], $d['xp'], $d['difficulty'], $d['category'], $d['description']], [10, 10, 20, '', null, '']);
t('kviz sa kategorijom: termin', ygv_quiz_landing_data($Q1, 0)['category'] instanceof WP_Term, true);

// preporuke: Q3 je u K, Q4 i Q5 nisu; datumi u budućnosti da budu najnoviji na sajtu
$Q3 = mk_post('quiz', 'Q3'); wp_set_object_terms($Q3, [$K], 'quiz_category');
$Q4 = mk_post('quiz', 'Q4');
$Q5 = mk_post('quiz', 'Q5');
foreach ([$Q3 => '2030-01-04', $Q4 => '2030-01-02', $Q5 => '2030-01-03'] as $id => $date) {
    $wpdb->update($wpdb->posts, ['post_date' => "$date 10:00:00", 'post_date_gmt' => "$date 09:00:00"], ['ID' => $id]);
    clean_post_cache($id);
}
t('preporuke: prvo iz kategorije, pa dopuna, bez trenutnog i duplikata', ygv_quiz_related_ids($Q1), [$Q3, $Q5, $Q4]);
t('preporuke: granica', count(ygv_quiz_related_ids($Q1, 2)), 2);

$html = ygv_test_render_singular($Q2, 'single-quiz.php');
t('stranica bez slike/kategorije/težine: bez upozorenja', $GLOBALS['ygv_warnings'], []);
t('stranica: tačno jedan h1', substr_count($html, '<h1'), 1);
t('gost: nema "Igraj ponovo"', strpos($html, 'Igraj ponovo'), false);
t('gost: dugme za prozor', strpos($html, 'ygv-quiz-start-btn') !== false && strpos($html, 'data-quiz-id="' . $Q2 . '"') !== false, true);
preg_match('#<button type="button" class="ygv-landing-cta[^>]*>.*?</button>#s', $html, $btn);
t('zaključan za gosta: poruka za prijavu, bez napomene', [strpos($btn[0] ?? '', 'data-locked="true"') !== false, strpos($btn[0] ?? '', 'Prijavi se da igraš') !== false, strpos($html, 'Igraš odmah, bez registracije')], [true, true, false]);
t('još kvizova', strpos($html, 'Još kvizova') !== false && substr_count($html, 'class="ygv-quiz-card ') === 3, true);

update_post_meta($Q1, '_allow_guest_play', '1');
$html = ygv_test_render_singular($Q1, 'single-quiz.php');
t('otvoren za goste: napomena', strpos($html, 'Igraš odmah, bez registracije') !== false, true);
preg_match('#<button type="button" class="ygv-landing-cta[^>]*>.*?</button>#s', $html, $btn);
t('otvoren za goste: Započni kviz, bez zaključavanja', strpos($btn[0] ?? '', 'Započni kviz') !== false && strpos($btn[0] ?? '', 'data-locked') === false, true);
t('h1 escapovan jednom', strpos($html, '>Kviz &quot;A&amp;B&quot; &lt;/script&gt;</h1>') !== false, true);
t('opis na stranici', strpos($html, 'Opis iz polja.') !== false, true);
// get_header() učitava header.php samo jednom po procesu, pa JSON-LD za Q1 tražimo direktno (upit je i dalje Q1)
$ld = json_decode(ygv_schema_json(ygv_schema_graph()), true);
t('JSON-LD na stranici kviza', array_column($ld['@graph'][0]['itemListElement'] ?? [], 'name'), ['Kvizovi', 'K', 'Kviz "A&B" </script>']);
t('vidljiva putanja: kategorija vodi na filtriranu arhivu', strpos($html, 'href="' . esc_url(ygv_quiz_category_url(get_term($K))) . '"') !== false, true);

$GLOBALS['ygv_test_done'] = true;
echo $fails === 0 ? "SVE PASS\n" : "PALO: $fails\n";
exit($fails === 0 ? 0 : 1);
