<?php
/**
 * SEO — H1 na sirovim Hello arhivama (/liste/, /vip-osobe/, kategorije kvizova i stavki).
 *
 * Hello podešavanje „sakrij naslov“ gasi naslove na celom sajtu (filter hello_elementor_page_title).
 * Ovde ga vraćamo samo za arhive koje nemaju sopstveni šablon, bez prefiksa „Kategorija:“ i bez
 * engleskih naziva tipova („Voting Lists“) — za njih koristimo naslove iz etape 1.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Koja Hello arhiva je trenutni zahtev: 'voting_list' | 'vip_person' | 'quiz_category' | 'voting_item_category' | ''.
 */
function ygv_seo_h1_archive_context(): string {
    if (is_post_type_archive('voting_list')) {
        return 'voting_list';
    }
    if (is_post_type_archive('vip_person')) {
        return 'vip_person';
    }
    if (is_tax('quiz_category')) {
        return 'quiz_category';
    }
    if (is_tax('voting_item_category')) {
        return 'voting_item_category';
    }
    return '';
}

add_filter('hello_elementor_page_title', function ($show) {
    return ygv_seo_h1_archive_context() !== '' ? true : $show;
}, 20);

add_filter('get_the_archive_title_prefix', function ($prefix) {
    return ygv_seo_h1_archive_context() !== '' ? '' : $prefix;
});

add_filter('get_the_archive_title', function ($title) {
    $ctx = ygv_seo_h1_archive_context();
    if ($ctx === 'voting_list' || $ctx === 'vip_person') {
        $copy = ygv_seo_static_copy();
        return esc_html($copy['archive_' . $ctx]['title'] ?? $title);
    }
    return $title;
});
