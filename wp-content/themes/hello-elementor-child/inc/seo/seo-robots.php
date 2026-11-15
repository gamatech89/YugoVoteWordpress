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
    if (is_search() || is_404() || is_author()) {
        return true;
    }
    if (is_front_page()) {
        return false;
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

add_filter('wp_sitemaps_posts_query_args', function (array $args, string $post_type) {
    $meta_query = isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : [];
    $meta_query[] = [
        'relation' => 'OR',
        ['key' => '_ygv_seo_noindex', 'compare' => 'NOT EXISTS'],
        ['key' => '_ygv_seo_noindex', 'value' => '1', 'compare' => '!='],
    ];
    if ($post_type === 'voting_list') {
        $meta_query[] = [
            'relation' => 'OR',
            ['key' => '_is_tournament_match', 'compare' => 'NOT EXISTS'],
            ['key' => '_is_tournament_match', 'value' => '1', 'compare' => '!='],
        ];
    }
    $args['meta_query'] = $meta_query;

    if ($post_type === 'page') {
        $exclude = get_posts([
            'post_type'      => 'page',
            'post_name__in'  => ygv_seo_noindex_page_slugs(),
            'post_status'    => 'any',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ]);
        if ($exclude) {
            $args['post__not_in'] = array_merge($args['post__not_in'] ?? [], $exclude);
        }
    }
    return $args;
}, 10, 2);

add_filter('wp_sitemaps_taxonomies_query_args', function (array $args) {
    $meta_query = isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : [];
    $meta_query[] = [
        'relation' => 'OR',
        ['key' => '_ygv_seo_noindex', 'compare' => 'NOT EXISTS'],
        ['key' => '_ygv_seo_noindex', 'value' => '1', 'compare' => '!='],
    ];
    $args['meta_query'] = $meta_query;
    return $args;
});
