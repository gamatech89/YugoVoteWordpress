<?php
/**
 * SEO — ispis u <head>: <title>, lang, meta description, canonical (arhive), Open Graph, Twitter.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Parent tema (Hello Elementor) ispisuje svoj description iz excerpt-a — gasimo ga.
add_filter('hello_elementor_description_meta_tag', '__return_false');

add_filter('pre_get_document_title', function ($title) {
    if (is_admin() || is_feed()) {
        return $title;
    }
    // pre_get_document_title zaobilazi WP-ovo escapovanje, pa ga radimo sami.
    return esc_html(ygv_seo_context()['title']);
}, 20);

add_filter('document_title_separator', function () {
    return '|';
});

add_filter('language_attributes', function ($output) {
    if (is_admin()) {
        return $output;
    }
    if (preg_match('/\blang="[^"]*"/', $output)) {
        return preg_replace('/\blang="[^"]*"/', 'lang="sr-Latn"', $output);
    }
    return trim($output . ' lang="sr-Latn"');
});

/**
 * Canonical za taksonomije i arhive (core ga daje samo za singular).
 */
function ygv_seo_archive_canonical(): string {
    if (!(is_tax() || is_category() || is_tag() || is_post_type_archive())) {
        return '';
    }
    return ygv_seo_context()['url'];
}

add_action('wp_head', function () {
    if (is_admin() || is_feed()) {
        return;
    }
    $c   = ygv_seo_context();
    $img = $c['image'];
    $out = [];

    $out[] = '<meta name="description" content="' . esc_attr($c['desc']) . '" />';

    $canonical = ygv_seo_archive_canonical();
    if ($canonical !== '') {
        $out[] = '<link rel="canonical" href="' . esc_url($canonical) . '" />';
    }

    $og = [
        'og:locale'      => 'sr_RS',
        'og:site_name'   => 'YugoVote',
        'og:type'        => $c['type'],
        'og:title'       => $c['title'],
        'og:description' => $c['desc'],
        'og:url'         => $c['url'] !== '' ? $c['url'] : home_url(add_query_arg([])),
    ];
    foreach ($og as $prop => $val) {
        $esc   = in_array($prop, ['og:url'], true) ? esc_url($val) : esc_attr($val);
        $out[] = '<meta property="' . $prop . '" content="' . $esc . '" />';
    }
    $out[] = '<meta property="og:image" content="' . esc_url($img['url']) . '" />';
    if (!empty($img['width']) && !empty($img['height'])) {
        $out[] = '<meta property="og:image:width" content="' . (int) $img['width'] . '" />';
        $out[] = '<meta property="og:image:height" content="' . (int) $img['height'] . '" />';
    }

    $out[] = '<meta name="twitter:card" content="summary_large_image" />';
    $out[] = '<meta name="twitter:title" content="' . esc_attr($c['title']) . '" />';
    $out[] = '<meta name="twitter:description" content="' . esc_attr($c['desc']) . '" />';
    $out[] = '<meta name="twitter:image" content="' . esc_url($img['url']) . '" />';

    echo "\n<!-- YugoVote SEO -->\n" . implode("\n", $out) . "\n<!-- /YugoVote SEO -->\n";
}, 1);
