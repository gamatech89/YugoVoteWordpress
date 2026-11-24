<?php
/**
 * SEO — strukturirani podaci (JSON-LD) i putanje (breadcrumb).
 *
 * Početna: WebSite + Organization. Lista, kategorija lista, kviz i anketa: BreadcrumbList,
 * tačno ona putanja koja se vidi na stranici (za liste i kategorije je to logika iz
 * voting-list-fullpage.php i taxonomy-voting_list_category-fullpage.php; check_head.py to proverava).
 * Spec: docs/superpowers/specs/2026-11-24-yugovote-seo-etapa2-design.md
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kategorija kviza u putanji vodi na lepu, filtriranu arhivu (/kvizovi/?cat=slug).
 */
function ygv_quiz_category_url(WP_Term $t): string {
    return add_query_arg('cat', $t->slug, get_post_type_archive_link('quiz'));
}

/**
 * Putanja za post ili termin.
 *
 * @return list<array{name:string,url:string}> Prazno za tipove bez putanje.
 */
function ygv_breadcrumb_trail_for(object $obj): array {
    $item = static function (string $name, $url): array {
        return ['name' => ygv_seo_clean($name), 'url' => is_string($url) ? $url : ''];
    };
    $home = $item('Početna', home_url('/'));

    if ($obj instanceof WP_Term) {
        if ($obj->taxonomy !== 'voting_list_category') {
            return [];
        }
        $trail = [$home];
        if ((int) $obj->parent !== 0) {
            $parent = get_term((int) $obj->parent, 'voting_list_category');
            if ($parent instanceof WP_Term) {
                $trail[] = $item($parent->name, get_term_link($parent));
            }
        }
        $trail[] = $item($obj->name, get_term_link($obj));
        return $trail;
    }

    if (!$obj instanceof WP_Post) {
        return [];
    }

    switch ($obj->post_type) {
        case 'voting_list':
            // Ista logika kao vidljiva putanja (voting-list-fullpage.php): prva podkategorija, inače prva.
            $trail = [$home];
            $terms = get_the_terms($obj->ID, 'voting_list_category');
            if (!empty($terms) && !is_wp_error($terms)) {
                $primary = $terms[0];
                foreach ($terms as $term) {
                    if ($term->parent !== 0) {
                        $primary = $term;
                        break;
                    }
                }
                if ($primary->parent !== 0) {
                    $parent = get_term($primary->parent, 'voting_list_category');
                    if ($parent instanceof WP_Term) {
                        $trail[] = $item($parent->name, get_term_link($parent));
                    }
                }
                $trail[] = $item($primary->name, get_term_link($primary));
            }
            $trail[] = $item(get_the_title($obj), get_permalink($obj));
            return $trail;

        case 'quiz':
            $trail = [$item('Kvizovi', get_post_type_archive_link('quiz'))];
            $terms = get_the_terms($obj->ID, 'quiz_category');
            if (!empty($terms) && !is_wp_error($terms)) {
                $trail[] = $item($terms[0]->name, ygv_quiz_category_url($terms[0]));
            }
            $trail[] = $item(get_the_title($obj), get_permalink($obj));
            return $trail;

        case 'voting_poll':
            $page = get_page_by_path('anketa');
            $url  = $page ? get_permalink($page) : home_url('/anketa/');
            return [$item('Ankete', $url), $item(get_the_title($obj), get_permalink($obj))];
    }
    return [];
}

/**
 * Putanja za trenutni zahtev (samo pojedinačne stranice i taksonomije).
 */
function ygv_breadcrumb_trail(): array {
    if (!(is_singular() || is_tax())) {
        return [];
    }
    $obj = get_queried_object();
    return is_object($obj) ? ygv_breadcrumb_trail_for($obj) : [];
}

function ygv_schema_breadcrumb_node(array $trail): array {
    $items = [];
    foreach (array_values($trail) as $i => $crumb) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $crumb['name'],
            'item'     => $crumb['url'],
        ];
    }
    return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

/**
 * Čvorovi grafa za trenutni zahtev.
 */
function ygv_schema_graph(): array {
    if (is_front_page()) {
        $home = home_url('/');
        return [
            [
                '@type'      => 'WebSite',
                '@id'        => $home . '#website',
                'url'        => $home,
                'name'       => 'YugoVote',
                'inLanguage' => 'sr-Latn',
                'publisher'  => ['@id' => $home . '#organization'],
            ],
            [
                '@type'  => 'Organization',
                '@id'    => $home . '#organization',
                'name'   => 'YugoVote',
                'url'    => $home,
                'logo'   => [
                    '@type'  => 'ImageObject',
                    'url'    => get_stylesheet_directory_uri() . '/assets/images/logo-schema.png',
                    'width'  => 512,
                    'height' => 512,
                ],
                'sameAs' => [
                    'https://www.facebook.com/profile.php?id=61591842951679',
                    'https://www.instagram.com/yugo_vote/',
                ],
            ],
        ];
    }
    $trail = ygv_breadcrumb_trail();
    return $trail ? [ygv_schema_breadcrumb_node($trail)] : [];
}

/**
 * JSON za <script type="application/ld+json">; JSON_HEX_TAG sprečava da "</script>" iz naslova zatvori tag.
 */
function ygv_schema_json(array $graph): string {
    return (string) wp_json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
    );
}

add_action('wp_head', function () {
    if (is_admin() || is_feed()) {
        return;
    }
    $graph = ygv_schema_graph();
    if (!$graph) {
        return;
    }
    echo '<script type="application/ld+json">' . ygv_schema_json($graph) . "</script>\n";
}, 2);
