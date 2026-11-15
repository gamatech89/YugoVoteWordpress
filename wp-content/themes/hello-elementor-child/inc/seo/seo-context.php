<?php
/**
 * SEO — kontekst trenutne stranice: naslov, opis, URL, slika, tip, noindex.
 * Ručno polje (_ygv_seo_title / _ygv_seo_desc) ima prednost nad automatskim.
 */

if (!defined('ABSPATH')) {
    exit;
}

const YGV_SEO_SUFFIX = ' | YugoVote';

/* -------------------------------------------------------------------------
 * Podaci za liste
 * ---------------------------------------------------------------------- */

/**
 * Imena stavki liste, poređana po poenima (SUM(vote_value)); bez glasova → redosled iz _voting_items.
 * Keš 12h (transient), briše se na save_post_voting_list.
 */
function ygv_seo_list_top_names(int $list_id, int $n = 3): array {
    $key    = 'ygv_seo_top_' . $list_id;
    $cached = get_transient($key);
    if (is_array($cached)) {
        return array_slice($cached, 0, $n);
    }

    $ids = get_post_meta($list_id, '_voting_items', true);
    $ids = is_array($ids) ? array_values(array_unique(array_filter(array_map('intval', $ids)))) : [];

    $names = [];
    if ($ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'voting_list_votes';
        $in    = implode(',', $ids); // samo int-ovi
        $rows  = $wpdb->get_col($wpdb->prepare(
            "SELECT voting_item_id FROM {$table}
             WHERE voting_list_id = %d AND voting_item_id IN ({$in})
             GROUP BY voting_item_id ORDER BY SUM(vote_value) DESC LIMIT 8",
            $list_id
        ));
        $ordered = array_map('intval', (array) $rows);
        foreach ($ids as $id) {
            if (count($ordered) >= 8) {
                break;
            }
            if (!in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }
        foreach ($ordered as $id) {
            if (get_post_status($id) !== 'publish') {
                continue;
            }
            $name = ygv_seo_clean(get_the_title($id));
            // Različite stavke ponekad imaju isto ime — ne ponavljamo ga u opisu.
            if ($name !== '' && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }
    }

    set_transient($key, $names, 12 * HOUR_IN_SECONDS);
    return array_slice($names, 0, $n);
}

add_action('save_post_voting_list', function ($post_id) {
    delete_transient('ygv_seo_top_' . (int) $post_id);
});

/* -------------------------------------------------------------------------
 * Automatski naslovi i opisi
 * ---------------------------------------------------------------------- */

function ygv_seo_auto_title_post(WP_Post $p): string {
    $title = ygv_seo_clean(get_the_title($p));
    switch ($p->post_type) {
        case 'quiz':
            return stripos($title, 'kviz') === false ? 'Kviz: ' . $title : $title;
        case 'voting_poll':
            return 'Anketa: ' . $title;
        default:
            return $title;
    }
}

function ygv_seo_auto_desc_post(WP_Post $p): string {
    $content = ygv_seo_clean((string) $p->post_content);
    $title   = ygv_seo_clean(get_the_title($p));

    switch ($p->post_type) {
        case 'voting_list':
            return ygv_seo_list_desc($content, ygv_seo_list_top_names($p->ID, 3));

        case 'quiz':
            $desc = ygv_seo_clean((string) get_post_meta($p->ID, '_quiz_description', true));
            if ($desc !== '') {
                return ygv_seo_truncate($desc);
            }
            $n = (int) get_post_meta($p->ID, '_num_questions', true);
            return ygv_seo_truncate('Kviz: ' . $title . '.' . ($n > 0 ? " {$n} pitanja." : '') . ' Proveri koliko znaš.');

        case 'voting_poll':
            $answers = get_post_meta($p->ID, '_cs_poll_answers', true);
            $texts   = [];
            if (is_array($answers)) {
                foreach (array_slice($answers, 0, 3) as $a) {
                    $texts[] = ygv_seo_clean(is_array($a) ? (string) ($a['text'] ?? '') : (string) $a);
                }
            }
            $q    = $content !== '' ? $content : $title;
            $opts = ygv_seo_join_names($texts);
            return ygv_seo_truncate($q . ($opts !== '' ? ' ' . $opts . ' ili nešto treće? Glasaj u anketi.' : ' Glasaj u anketi.'));

        case 'yuv_showdown':
            $items = get_post_meta($p->ID, '_yuv_showdown_items', true);
            $names = [];
            if (is_array($items)) {
                foreach (array_slice($items, 0, 4) as $it) {
                    $names[] = ygv_seo_clean(is_array($it) ? (string) ($it['name'] ?? '') : '');
                }
            }
            $who = ygv_seo_join_names($names);
            return ygv_seo_truncate($title . ': showdown dvoboj.' . ($who !== '' ? ' ' . $who . '.' : '') . ' Izaberi ko pobeđuje.');

        default:
            if ($content !== '') {
                return ygv_seo_truncate($content);
            }
            return ygv_seo_static_copy()['home']['desc'];
    }
}

function ygv_seo_auto_title_term(WP_Term $t): string {
    $name = ygv_seo_clean($t->name);
    switch ($t->taxonomy) {
        case 'voting_list_category':
            return $name . ': rang liste';
        case 'quiz_category':
            return 'Kvizovi: ' . $name;
        default:
            return $name;
    }
}

function ygv_seo_auto_desc_term(WP_Term $t): string {
    $desc = ygv_seo_clean((string) $t->description);
    if ($desc !== '') {
        return ygv_seo_truncate($desc);
    }
    return ygv_seo_truncate(ygv_seo_clean($t->name) . ': liste i kvizovi na YugoVote. Glasaj i reci svoje.');
}

/* -------------------------------------------------------------------------
 * Slike
 * ---------------------------------------------------------------------- */

function ygv_seo_default_image(): array {
    return [
        'url'    => get_stylesheet_directory_uri() . '/assets/images/og-default.jpg',
        'width'  => 1200,
        'height' => 630,
    ];
}

function ygv_seo_attachment_image(int $attachment_id): ?array {
    if ($attachment_id <= 0) {
        return null;
    }
    $src = wp_get_attachment_image_src($attachment_id, 'large');
    if (!$src || empty($src[0])) {
        return null;
    }
    return ['url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2]];
}

/* -------------------------------------------------------------------------
 * Ručna polja
 * ---------------------------------------------------------------------- */

function ygv_seo_manual(string $kind, int $id, string $field): string {
    $key = '_ygv_seo_' . $field;
    $val = $kind === 'term' ? get_term_meta($id, $key, true) : get_post_meta($id, $key, true);
    return is_string($val) ? trim($val) : '';
}

/* -------------------------------------------------------------------------
 * Kontekst
 * ---------------------------------------------------------------------- */

/**
 * @return array{title:string,desc:string,url:string,type:string,image:array,noindex:bool}
 */
function ygv_seo_context(): array {
    static $ctx = null;
    if ($ctx !== null) {
        return $ctx;
    }

    $copy  = ygv_seo_static_copy();
    $title = '';
    $desc  = '';
    $url   = '';
    $type  = 'website';
    $image = null;

    if (is_front_page()) {
        $front = (int) get_option('page_on_front');
        $title = ($front ? ygv_seo_manual('post', $front, 'title') : '') ?: $copy['home']['title'];
        $desc  = ($front ? ygv_seo_manual('post', $front, 'desc') : '') ?: $copy['home']['desc'];
        $url   = home_url('/');
    } elseif (is_singular()) {
        $p = get_queried_object();
        if ($p instanceof WP_Post) {
            $title = ygv_seo_manual('post', $p->ID, 'title') ?: ygv_seo_auto_title_post($p);
            $desc  = ygv_seo_manual('post', $p->ID, 'desc') ?: ygv_seo_auto_desc_post($p);
            $url   = get_permalink($p);
            $type  = 'article';
            $image = ygv_seo_attachment_image((int) get_post_thumbnail_id($p));
        }
    } elseif (is_tax() || is_category() || is_tag()) {
        $t = get_queried_object();
        if ($t instanceof WP_Term) {
            $title = ygv_seo_manual('term', $t->term_id, 'title') ?: ygv_seo_auto_title_term($t);
            $desc  = ygv_seo_manual('term', $t->term_id, 'desc') ?: ygv_seo_auto_desc_term($t);
            $link  = get_term_link($t);
            $url   = is_wp_error($link) ? '' : $link;
            $image = ygv_seo_attachment_image((int) get_term_meta($t->term_id, 'category_featured_image', true));
        }
    } elseif (is_post_type_archive()) {
        $pt = get_query_var('post_type');
        $pt = is_array($pt) ? reset($pt) : (string) $pt;
        $k  = 'archive_' . $pt;
        $obj   = get_post_type_object($pt);
        $title = $copy[$k]['title'] ?? ($obj ? $obj->labels->name : '');
        $desc  = $copy[$k]['desc'] ?? $copy['home']['desc'];
        $link  = get_post_type_archive_link($pt);
        $url   = $link ?: '';
    } elseif (is_search()) {
        $title = 'Pretraga: ' . ygv_seo_clean(get_search_query(false));
        $desc  = $copy['home']['desc'];
    } elseif (is_404()) {
        $title = 'Stranica nije pronađena';
        $desc  = $copy['home']['desc'];
    } elseif (is_author()) {
        $u     = get_queried_object();
        $title = $u instanceof WP_User ? ygv_seo_clean($u->display_name) : '';
        $desc  = $copy['home']['desc'];
    }

    $title = trim($title);
    $ctx = [
        'title'   => $title === '' ? 'YugoVote' : $title . YGV_SEO_SUFFIX,
        'desc'    => $desc !== '' ? ygv_seo_truncate($desc) : $copy['home']['desc'],
        'url'     => $url,
        'type'    => $type,
        'image'   => $image ?: ygv_seo_default_image(),
        'noindex' => ygv_seo_is_noindex_request(),
    ];
    return $ctx;
}
