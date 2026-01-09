<?php
if (!defined('ABSPATH')) exit;

/**
 * [yugo_category_levels]
 * Shows the logged-in user's level per quiz category.
 */
function yugo_category_levels_sc($atts=[]) {
    if (!is_user_logged_in()) {
        return '<div class="ygv-card">'.esc_html__('Morate biti prijavljeni.', 'hello-elementor-child').'</div>';
    }
    $u = get_current_user_id();
    $rows = function_exists('ygv_progress') ? ygv_progress()->get_category_levels($u) : [];

    if (!$rows) {
        return '<div class="ygv-card">'.esc_html__('Još uvek nemate XP po kategorijama.', 'hello-elementor-child').'</div>';
    }

    // Fetch term names
    $out = '<div class="cs-levels-grid">';
    foreach ($rows as $r) {
        $term = get_term((int)$r['category_term_id'], 'quiz_category');
        if (is_wp_error($term) || !$term) continue;

        $xp   = (int)$r['xp'];
        $lev  = (int)$r['level'];
        $thr  = ygv_progress()->get_thresholds('category');
        $next = null;
        foreach ($thr as $L=>$need) { if ($L>$lev) { $next=$need; break; } }
        $to_next = ($next !== null) ? max(0, $next - $xp) : null;

        $pct = 0;
        if ($next !== null) {
            $curr_floor = $thr[$lev] ?? 0;
            $span = max(1, $next - $curr_floor);
            $pct = (int) round(100 * ($xp - $curr_floor) / $span);
        }

        $out .= '<div class="cs-level-card">
            <div class="cs-level-title">'.esc_html($term->name).'</div>
            <div class="cs-level-line"><span style="width:'.$pct.'%;"></span></div>
            <div class="cs-level-meta">Lvl '.$lev.' · XP '.$xp. ($to_next!==null ? ' · još '.$to_next.' do sledećeg' : ' · max').'</div>
        </div>';
    }
    $out .= '</div>';

 

    return $out;
}
add_shortcode('yugo_category_levels', 'yugo_category_levels_sc');
