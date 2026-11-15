<?php
/**
 * SEO — ručno pisani tekstovi za početnu i arhive (nisu postovi, pa nemaju SEO kutiju).
 * Naslovi su bez sufiksa " | YugoVote" (dodaje ga ygv_seo_context()).
 */

if (!defined('ABSPATH')) {
    exit;
}

function ygv_seo_static_copy(): array {
    return [
        'home' => [
            'title' => 'Rang liste, kvizovi i ankete sa ex-YU prostora',
            'desc'  => 'Ko je najbolji fudbaler, pevač, film ili glumac sa ovih prostora? Glasaj na stotinama rang lista, igraj kvizove i reci svoje u anketama.',
        ],
        'archive_voting_list' => [
            'title' => 'Sve rang liste za glasanje',
            'desc'  => 'Preko 600 rang lista: fudbaleri, pevači, filmovi, serije i albumi sa ex-YU prostora. Izaberi listu, glasaj i promeni redosled.',
        ],
        'archive_quiz' => [
            'title' => 'Kvizovi o muzici, filmu, sportu i istoriji',
            'desc'  => 'Više od 150 kvizova o domaćoj muzici, filmu, sportu i istoriji. Od lakih do ekspertskih: proveri koliko znaš i popni se na tabelu.',
        ],
        'archive_yuv_showdown' => [
            'title' => 'Showdown: dvoboji favorita',
            'desc'  => 'Dva favorita, jedan glas. Showdown dvoboji pevača, fudbalera i legendi sa ex-YU prostora: izaberi ko pobeđuje i vidi kako glasaju drugi.',
        ],
        'archive_vip_person' => [
            'title' => 'VIP liste poznatih ličnosti',
            'desc'  => 'Rang liste koje su složile poznate ličnosti. Pogledaj kako su poređali svoje favorite, pa glasaj i pokaži da li se slažeš sa njima.',
        ],
    ];
}
