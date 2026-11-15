<?php
// Unit testovi za SEO tekst helpere. Pokretanje: php tests/seo/run-tests.php
define('ABSPATH', '/');
require __DIR__ . '/../../wp-content/themes/hello-elementor-child/inc/seo/seo-text.php';

$fails = 0;
function t(string $name, $got, $want): void {
    global $fails;
    if ($got === $want) { echo "PASS  $name\n"; return; }
    $fails++;
    echo "FAIL  $name\n      got:  " . var_export($got, true) . "\n      want: " . var_export($want, true) . "\n";
}

// clean
t('clean: entiteti, tagovi, shortcode, komentar',
  ygv_seo_clean('Rock &#8211; <b>pop</b> [gallery id="1"] <!-- x -->  ok'), 'Rock – pop ok');
t('clean: navodnici i nbsp', ygv_seo_clean("&#8220;Genije&#8221;&nbsp;i&amp;ja"), '“Genije” i&ja');

// truncate
$r = ygv_seo_truncate(str_repeat('ab ', 100), 155);
t('truncate: <=155', mb_strlen($r) <= 155, true);
t('truncate: završava sa …', mb_substr($r, -1), '…');
t('truncate: ne seče reč', (bool) preg_match('/ab…$/u', $r), true);
$r = ygv_seo_truncate(str_repeat('ž', 200), 155);
t('truncate: multibyte dužina tačno 155', mb_strlen($r), 155);
t('truncate: validan UTF-8', mb_check_encoding($r, 'UTF-8'), true);
t('truncate: multibyte završava sa …', mb_substr($r, -1), '…');
t('truncate: kratak tekst netaknut', ygv_seo_truncate('Kratko.', 155), 'Kratko.');
$r = ygv_seo_truncate('Prva rečenica ide ovde, a onda dolazi zarez, i još mnogo reči koje ne staju', 45);
t('truncate: bez visećeg zareza pre …', (bool) preg_match('/[,;:–-]…$/u', $r), false);

// join_names
t('join: tri imena', ygv_seo_join_names(['Savićević', 'Mijatović', 'Vučinić']), 'Savićević, Mijatović i Vučinić');
t('join: dva imena', ygv_seo_join_names(['A', 'B']), 'A i B');
t('join: prazni se izbacuju', ygv_seo_join_names(['A', '', '  ']), 'A');
t('join: prazno', ygv_seo_join_names([]), '');

// list_desc
t('list_desc: sve prazno', ygv_seo_list_desc('', [], 155), '');
t('list_desc: bez uvoda', ygv_seo_list_desc('', ['A', 'B']), 'Rang lista: A i B. Glasaj!');
t('list_desc: bez imena = skraćen uvod', ygv_seo_list_desc('Kratak uvod liste.', []), 'Kratak uvod liste.');
$r = ygv_seo_list_desc(str_repeat('reč ', 80), ['Savićević', 'Mijatović', 'Vučinić']);
t('list_desc: <=155', mb_strlen($r) <= 155, true);
t('list_desc: završava imenima', (bool) preg_match('/Savićević, Mijatović i Vučinić\. Glasaj!$/u', $r), true);
t('list_desc: bez " ." i ",."', (bool) preg_match('/ \.|,\./u', $r), false);
$r = ygv_seo_list_desc('Neki crnogorski fudbaleri kao da nisu iz Crne Gore. Osvajali su sve.', ['Savićević', 'Mijatović']);
t('list_desc: kratak uvod ceo + imena', $r, 'Neki crnogorski fudbaleri kao da nisu iz Crne Gore. Osvajali su sve. Savićević i Mijatović. Glasaj!');


// sentence (završna interpunkcija za automatske opise)
t('sentence: dodaje tačku', ygv_seo_sentence('Euroliga poredak'), 'Euroliga poredak.');
t('sentence: skida dvotačku', ygv_seo_sentence('Najbolji fudbaleri su:'), 'Najbolji fudbaleri su.');
t('sentence: skida zarez i crtu', ygv_seo_sentence('Ovo je to, –'), 'Ovo je to.');
t('sentence: ostavlja upitnik', ygv_seo_sentence('Čiji si ti?'), 'Čiji si ti?');
t('sentence: ostavlja …', ygv_seo_sentence('I tako dalje…'), 'I tako dalje…');
t('sentence: prazno', ygv_seo_sentence('  '), '');
t('list_desc: uvod sa dvotačkom bez ":."', ygv_seo_list_desc('Najbolji fudbaleri su:', ['A', 'B']), 'Najbolji fudbaleri su. A i B. Glasaj!');

echo $fails ? "\n$fails FAIL\n" : "\nSVE PASS\n";
exit($fails ? 1 : 0);
