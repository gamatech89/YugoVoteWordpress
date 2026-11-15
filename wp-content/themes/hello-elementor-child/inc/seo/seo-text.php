<?php
/**
 * SEO — čisti tekst helperi (bez WordPress zavisnosti, testirani u tests/seo/run-tests.php).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * HTML/shortcode/entiteti → čist tekst u jednom redu.
 */
function ygv_seo_clean(string $html): string {
    $s = preg_replace('/<!--.*?-->/su', ' ', $html);
    $s = preg_replace('/<(script|style)\b.*?<\/\1>/isu', ' ', $s);
    $s = preg_replace('/\[[^\]]*\]/u', ' ', $s);
    $s = preg_replace('/<[^>]*>/u', ' ', $s);
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = str_replace("\xC2\xA0", ' ', $s); // nbsp
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

/**
 * Skraćuje na $max znakova (multibyte), na granici reči, sa "…" na kraju.
 */
function ygv_seo_truncate(string $text, int $max = 155): string {
    $text = trim($text);
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max - 1);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space >= (int) floor($max * 0.6)) {
        $cut = mb_substr($cut, 0, $space);
    }
    $cut = preg_replace('/[\s,;:–\-]+$/u', '', $cut);
    return $cut . '…';
}

/**
 * Zatvara rečenicu: skida viseću interpunkciju (, ; : –) i dodaje tačku ako nema . ! ? …
 */
function ygv_seo_sentence(string $text): string {
    $text = preg_replace('/[\s,;:–\-]+$/u', '', trim($text));
    if ($text === '') {
        return '';
    }
    return preg_match('/[.!?…]$/u', $text) ? $text : $text . '.';
}

/**
 * ['A','B','C'] → "A, B i C".
 */
function ygv_seo_join_names(array $names): string {
    $names = array_values(array_filter(array_map(
        static fn($n) => trim((string) $n),
        $names
    ), static fn($n) => $n !== ''));

    $count = count($names);
    if ($count === 0) {
        return '';
    }
    if ($count === 1) {
        return $names[0];
    }
    $last = array_pop($names);
    return implode(', ', $names) . ' i ' . $last;
}

/**
 * Opis liste: uvod (skraćen da sve stane) + imena + poziv na glasanje.
 */
function ygv_seo_list_desc(string $intro, array $names, int $max = 155): string {
    $intro = trim($intro);

    // Ako imena ne staju ni sa minimalnim uvodom, izbacuj ih od kraja.
    while ($names) {
        $joined = ygv_seo_join_names($names);
        if ($joined === '') {
            $names = [];
            break;
        }
        $suffix = $joined . '. Glasaj!';

        if ($intro === '') {
            $out = 'Rang lista: ' . $suffix;
            if (mb_strlen($out) <= $max) {
                return $out;
            }
        } else {
            $out = ygv_seo_sentence($intro) . ' ' . $suffix;
            if (mb_strlen($out) <= $max) {
                return $out;
            }
            $room = $max - mb_strlen($suffix) - 1;
            if ($room >= 40) {
                return ygv_seo_truncate($intro, $room) . ' ' . $suffix;
            }
        }
        array_pop($names);
    }

    return $intro === '' ? '' : ygv_seo_truncate($intro, $max);
}
