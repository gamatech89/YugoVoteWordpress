<?php
/**
 * Import ručno pisanih SEO naslova/opisa u post/term meta.
 *
 * - Upisuje SAMO prazna polja; ono što je urednik već uneo ostaje ("kept").
 * - "dry-run" ne upisuje ništa, samo izveštava.
 *
 * Lokalno:
 *   docker compose cp scripts/seo/import-meta.php wordpress:/tmp/ && docker compose cp scripts/seo/meta-etapa1.json wordpress:/tmp/
 *   docker compose exec -T wordpress php /tmp/import-meta.php /tmp/meta-etapa1.json dry-run
 *
 * Produkcija (bez kopiranja fajlova na server — JSON se ugradi u skriptu):
 *   python3 scripts/seo/bundle-import.py scripts/seo/meta-etapa1.json > /tmp/seo-import-bundle.php
 *   ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp eval-file - dry-run' < /tmp/seo-import-bundle.php
 *   ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp eval-file -' < /tmp/seo-import-bundle.php
 */

if (!defined('ABSPATH')) {
    require getenv('WP_LOAD') ?: '/var/www/html/wp-load.php';
}

/**
 * @param array $entries [{id, kind: post|term, type, title, desc, noindex?}]
 * @return array{written:int,kept:int,missing:int,type_mismatch:int,examples:array}
 */
function ygv_seo_import(array $entries, bool $dry): array {
    $r = ['written' => 0, 'kept' => 0, 'missing' => 0, 'type_mismatch' => 0, 'examples' => []];
    $ex = function (string $bucket, string $line) use (&$r) {
        if (count($r['examples'][$bucket] ?? []) < 10) {
            $r['examples'][$bucket][] = $line;
        }
    };

    foreach ($entries as $e) {
        $id   = (int) ($e['id'] ?? 0);
        $kind = ($e['kind'] ?? 'post') === 'term' ? 'term' : 'post';
        $type = (string) ($e['type'] ?? '');

        if ($kind === 'post') {
            $obj    = $id ? get_post($id) : null;
            $actual = $obj ? $obj->post_type : null;
        } else {
            $obj    = $id ? get_term($id) : null;
            $obj    = ($obj && !is_wp_error($obj)) ? $obj : null;
            $actual = $obj ? $obj->taxonomy : null;
        }
        if (!$obj) {
            $r['missing']++;
            $ex('missing', "{$kind} {$id} ({$type})");
            continue;
        }
        if ($actual !== $type) {
            $r['type_mismatch']++;
            $ex('type_mismatch', "{$kind} {$id}: očekivan {$type}, a jeste {$actual}");
            continue;
        }

        $get = fn($key) => $kind === 'term' ? get_term_meta($id, $key, true) : get_post_meta($id, $key, true);
        $set = fn($key, $val) => $kind === 'term' ? update_term_meta($id, $key, $val) : update_post_meta($id, $key, $val);

        foreach (['title', 'desc'] as $field) {
            $val = trim((string) ($e[$field] ?? ''));
            if ($val === '') {
                continue;
            }
            $key = '_ygv_seo_' . $field;
            $cur = trim((string) $get($key));
            if ($cur !== '') {
                $r['kept']++;
                if ($cur !== $val) {
                    $ex('kept_different', "{$kind} {$id} {$field}: ostaje \"{$cur}\"");
                }
                continue;
            }
            if (!$dry) {
                $set($key, sanitize_text_field($val));
            }
            $r['written']++;
            $ex('written', "{$kind} {$id} {$field}: {$val}");
        }

        if (!empty($e['noindex'])) {
            if ($get('_ygv_seo_noindex') === '1') {
                $r['kept']++;
            } else {
                if (!$dry) {
                    $set('_ygv_seo_noindex', '1');
                }
                $r['written']++;
                $ex('written', "{$kind} {$id} noindex");
            }
        }
    }
    return $r;
}

function ygv_seo_import_report(array $r, bool $dry): string {
    $out = ($dry ? "DRY-RUN (ništa nije upisano)\n" : "UPISANO\n")
        . "written={$r['written']} kept={$r['kept']} missing={$r['missing']} type_mismatch={$r['type_mismatch']}\n";
    foreach ($r['examples'] as $bucket => $lines) {
        $out .= "\n[{$bucket}]\n  " . implode("\n  ", $lines) . "\n";
    }
    return $out;
}

if (!defined('YGV_SEO_IMPORT_NO_RUN')) {
    $ygv_args = isset($args) && is_array($args) ? $args : array_slice($argv ?? [], 1);
    $ygv_dry  = in_array('dry-run', $ygv_args, true) || getenv('YGV_SEO_DRY') === '1';

    if (isset($GLOBALS['YGV_SEO_DATA']) && is_array($GLOBALS['YGV_SEO_DATA'])) {
        $ygv_entries = $GLOBALS['YGV_SEO_DATA'];
    } else {
        $ygv_files   = array_values(array_filter($ygv_args, fn($a) => $a !== 'dry-run'));
        $ygv_path    = $ygv_files[0] ?? '';
        $ygv_entries = $ygv_path && is_readable($ygv_path) ? json_decode((string) file_get_contents($ygv_path), true) : null;
    }
    if (!is_array($ygv_entries)) {
        fwrite(STDERR, "Nema podataka: prosledi putanju do JSON fajla ili koristi bundle-import.py.\n");
        exit(1);
    }
    echo ygv_seo_import_report(ygv_seo_import($ygv_entries, $ygv_dry), $ygv_dry);
}
