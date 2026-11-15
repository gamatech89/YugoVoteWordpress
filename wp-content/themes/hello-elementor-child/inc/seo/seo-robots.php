<?php
/**
 * SEO — noindex pravila i čišćenje core sitemap-a.
 * Stranice ostaju dostupne (nema 404/410); samo se sklanjaju iz Google-a.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ygv_seo_noindex_post_types(): array {
    return ['question', 'quiz_levels', 'post'];
}

function ygv_seo_noindex_taxonomies(): array {
    return ['question_category', 'category'];
}

function ygv_seo_noindex_page_slugs(): array {
    return [
        'test', 'test-vip', 'pocetna-old', 'moj-nalog', 'uredi-profil', 'prijava',
        'registracija', 'zaboravljena-lozinka', 'kompletiranje-naloga',
    ];
}

function ygv_seo_is_noindex_post(WP_Post $p): bool {
    if (in_array($p->post_type, ygv_seo_noindex_post_types(), true)) {
        return true;
    }
    if (get_post_meta($p->ID, '_ygv_seo_noindex', true) === '1') {
        return true;
    }
    if ($p->post_type === 'page' && in_array($p->post_name, ygv_seo_noindex_page_slugs(), true)) {
        return true;
    }
    if ($p->post_type === 'voting_list' && get_post_meta($p->ID, '_is_tournament_match', true) === '1') {
        return true;
    }
    return false;
}

function ygv_seo_is_noindex_term(WP_Term $t): bool {
    if (in_array($t->taxonomy, ygv_seo_noindex_taxonomies(), true)) {
        return true;
    }
    return get_term_meta($t->term_id, '_ygv_seo_noindex', true) === '1';
}

/**
 * Da li je trenutni zahtev noindex.
 */
function ygv_seo_is_noindex_request(): bool {
    if (is_search() || is_404() || is_author() || is_date()) {
        return true;
    }
    if (is_front_page()) {
        return false;
    }
    if (is_home()) {
        return true; // blog indeks — sajt nema blog, tu su samo test postovi
    }
    if (is_singular()) {
        $p = get_queried_object();
        return $p instanceof WP_Post && ygv_seo_is_noindex_post($p);
    }
    if (is_tax() || is_category() || is_tag()) {
        $t = get_queried_object();
        return $t instanceof WP_Term && ygv_seo_is_noindex_term($t);
    }
    if (is_post_type_archive()) {
        $pt = get_query_var('post_type');
        $pt = is_array($pt) ? reset($pt) : (string) $pt;
        return in_array($pt, ygv_seo_noindex_post_types(), true);
    }
    return false;
}

add_filter('wp_robots', function (array $robots) {
    if (is_admin()) {
        return $robots;
    }
    if (ygv_seo_context()['noindex']) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
        unset($robots['max-image-preview']);
    }
    return $robots;
}, 20);

/* -------------------------------------------------------------------------
 * Sitemap
 * ---------------------------------------------------------------------- */

add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
    return $name === 'users' ? false : $provider;
}, 10, 2);

add_filter('wp_sitemaps_post_types', function (array $post_types) {
    foreach (ygv_seo_noindex_post_types() as $pt) {
        unset($post_types[$pt]);
    }
    return $post_types;
});

add_filter('wp_sitemaps_taxonomies', function (array $taxonomies) {
    foreach (ygv_seo_noindex_taxonomies() as $tx) {
        unset($taxonomies[$tx]);
    }
    return $taxonomies;
});

/**
 * ID-jevi postova koji ne idu u sitemap: ručno sakriveni i turnirske liste.
 * Jedan upit po indeksu meta_key, umesto meta_query JOIN-ova na velikim tipovima. Memo po zahtevu.
 */
function ygv_seo_sitemap_excluded_post_ids(): array {
    static $ids = null;
    if ($ids === null) {
        global $wpdb;
        $ids = array_map('intval', $wpdb->get_col(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
             WHERE meta_key IN ('_ygv_seo_noindex', '_is_tournament_match') AND meta_value = '1'"
        ));
    }
    return $ids;
}

function ygv_seo_sitemap_excluded_term_ids(): array {
    static $ids = null;
    if ($ids === null) {
        global $wpdb;
        $ids = array_map('intval', $wpdb->get_col(
            "SELECT DISTINCT term_id FROM {$wpdb->termmeta} WHERE meta_key = '_ygv_seo_noindex' AND meta_value = '1'"
        ));
    }
    return $ids;
}

add_filter('wp_sitemaps_posts_query_args', function (array $args, string $post_type) {
    $exclude = ygv_seo_sitemap_excluded_post_ids();

    if ($post_type === 'page') {
        $exclude = array_merge($exclude, get_posts([
            'post_type'      => 'page',
            'post_name__in'  => ygv_seo_noindex_page_slugs(),
            'post_status'    => 'any',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ]));
    }
    if ($exclude) {
        $args['post__not_in'] = array_values(array_unique(array_merge($args['post__not_in'] ?? [], $exclude)));
    }
    return $args;
}, 10, 2);

add_filter('wp_sitemaps_taxonomies_query_args', function (array $args) {
    $exclude = ygv_seo_sitemap_excluded_term_ids();
    if ($exclude) {
        $current         = isset($args['exclude']) ? wp_parse_id_list($args['exclude']) : [];
        $args['exclude'] = array_values(array_unique(array_merge($current, $exclude)));
    }
    return $args;
});
