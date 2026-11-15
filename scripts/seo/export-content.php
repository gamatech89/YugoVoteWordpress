<?php
/**
 * Izvoz sadržaja za pisanje SEO naslova/opisa (samo čitanje).
 *
 * Produkcija:  ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp eval-file -' < scripts/seo/export-content.php > export.json
 * Lokalno:     docker compose cp scripts/seo/export-content.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/export-content.php > export.json
 */

if (!defined('ABSPATH')) {
    require getenv('WP_LOAD') ?: '/var/www/html/wp-load.php';
}

function ygv_x_clean($s): string {
    $s = preg_replace('/<!--.*?-->|\[[^\]]*\]/su', ' ', (string) $s);
    $s = html_entity_decode(wp_strip_all_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', str_replace("\xC2\xA0", ' ', $s)));
}

function ygv_x_cut(string $s, int $max = 600): string {
    return mb_strlen($s) > $max ? mb_substr($s, 0, $max) . '…' : $s;
}

function ygv_x_terms(int $id, string $tax): array {
    $t = get_the_terms($id, $tax);
    return is_array($t) ? array_values(array_map(fn($x) => ygv_x_clean($x->name), $t)) : [];
}

function ygv_x_top(int $list_id, int $n = 8): array {
    if (function_exists('ygv_seo_list_top_names')) {
        delete_transient('ygv_seo_top_' . $list_id);
        return ygv_seo_list_top_names($list_id, $n);
    }
    return [];
}

$out = [];
$public_pages = ['kontakt', 'o-nama', 'anketa', 'uslovi-koriscenja', 'politika-privatnosti', 'podrska'];

foreach (['voting_list', 'quiz', 'voting_poll', 'yuv_showdown', 'vip_person', 'page'] as $pt) {
    $q = [
        'post_type'      => $pt,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ];
    if ($pt === 'page') {
        $q['post_name__in'] = $public_pages;
    }
    foreach (get_posts($q) as $p) {
        if ($pt === 'voting_list' && get_post_meta($p->ID, '_is_tournament_match', true) === '1') {
            continue;
        }
        $row = [
            'id'    => $p->ID,
            'kind'  => 'post',
            'type'  => $pt,
            'title' => ygv_x_clean(get_the_title($p)),
            'url'   => wp_make_link_relative(get_permalink($p)),
            'intro' => ygv_x_cut(ygv_x_clean($p->post_content)),
        ];
        switch ($pt) {
            case 'voting_list':
                $items             = get_post_meta($p->ID, '_voting_items', true);
                $row['categories'] = ygv_x_terms($p->ID, 'voting_list_category');
                $row['count']      = is_array($items) ? count($items) : 0;
                $row['top']        = ygv_x_top($p->ID, 8);
                $row['vip']        = get_post_meta($p->ID, '_is_vip_list', true) === '1';
                break;
            case 'quiz':
                $row['intro']         = ygv_x_cut(ygv_x_clean(get_post_meta($p->ID, '_quiz_description', true)));
                $row['num_questions'] = (int) get_post_meta($p->ID, '_num_questions', true);
                $lvl                  = (int) get_post_meta($p->ID, '_quiz_difficulty', true);
                $row['difficulty']    = $lvl ? ygv_x_clean(get_the_title($lvl)) : '';
                $row['categories']    = ygv_x_terms($p->ID, 'quiz_category');
                break;
            case 'voting_poll':
                $answers        = get_post_meta($p->ID, '_cs_poll_answers', true);
                $row['answers'] = is_array($answers)
                    ? array_values(array_map(fn($a) => ygv_x_clean(is_array($a) ? ($a['text'] ?? '') : $a), $answers))
                    : [];
                break;
            case 'yuv_showdown':
                $items      = get_post_meta($p->ID, '_yuv_showdown_items', true);
                $row['top'] = is_array($items)
                    ? array_values(array_filter(array_map(fn($i) => ygv_x_clean(is_array($i) ? ($i['name'] ?? '') : ''), $items)))
                    : [];
                break;
            case 'page':
                $row['intro'] = ygv_x_cut($row['intro'], 400);
                break;
        }
        $out[] = $row;
    }
}

foreach (['voting_list_category', 'quiz_category'] as $tax) {
    foreach (get_terms(['taxonomy' => $tax, 'hide_empty' => false]) as $t) {
        $parent = $t->parent ? get_term($t->parent, $tax) : null;
        $link   = get_term_link($t);
        $out[]  = [
            'id'     => $t->term_id,
            'kind'   => 'term',
            'type'   => $tax,
            'title'  => ygv_x_clean($t->name),
            'url'    => is_wp_error($link) ? '' : wp_make_link_relative($link),
            'intro'  => ygv_x_cut(ygv_x_clean($t->description)),
            'parent' => $parent && !is_wp_error($parent) ? ygv_x_clean($parent->name) : '',
            'count'  => (int) $t->count,
        ];
    }
}

echo wp_json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
