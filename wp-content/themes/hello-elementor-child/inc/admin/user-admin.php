<?php
// inc/quizzes/admin/user-admin.php
if (!defined('ABSPATH')) exit;

/**
 * Wallet/Levels panel on the user profile (only for admins).
 */
function ygv_user_wallet_box($user) {
    if (!current_user_can('manage_options')) return;

    $uid = (int) $user->ID;
    $w = function_exists('ygv_tokens')   ? ygv_tokens()->lazy_refill($uid) : [];
    $next = function_exists('ygv_tokens')? ygv_tokens()->seconds_to_next_refill($uid) : 0;
    $overall = 0;
    if (function_exists('ygv_progress') && method_exists(ygv_progress(), 'get_overall_progress')) {
            $overall = ygv_progress()->get_overall_progress($uid);
        } else {
            global $wpdb;
            $row = $wpdb->get_row(
                $wpdb->prepare("SELECT overall_level, overall_xp FROM {$wpdb->prefix}ygv_user_overall_progress WHERE user_id=%d", $uid),
                ARRAY_A
            );
            if ($row) {
                $overall = [
                    'overall_level' => (int)$row['overall_level'],
                    'overall_xp'    => (int)$row['overall_xp'],
                ];
            }
        }

    wp_nonce_field('ygv_user_wallet_save','ygv_user_wallet_nonce');
    ?>
    <h2>YugoVote — Wallet & Levels</h2>
    <table class="form-table" role="presentation">
      <tr>
        <th><label>Tokens</label></th>
        <td>
          <strong><?php echo isset($w['tokens']) ? (int)$w['tokens'] : 0; ?></strong>
          / <?php echo isset($w['max_tokens']) ? (int)$w['max_tokens'] : 0; ?>
          <br><small>Sledeće punjenje za: <?php echo gmdate('H:i:s', max(0,(int)$next)); ?></small>
        </td>
      </tr>
      <tr>
        <th><label>Ukupan nivo</label></th>
        <td>
          <?php
          $lvl = is_array($overall) ? (int)($overall['overall_level'] ?? 0) : (int)$overall;
          $xp  = is_array($overall) ? (int)($overall['overall_xp'] ?? 0)  : 0;
          echo 'Level ' . $lvl . ' &middot; XP: ' . $xp;
          ?>
        </td>
      </tr>
      <tr>
        <th><label for="ygv_grant_tokens">Dodeli tokene</label></th>
        <td>
          <input type="number" name="ygv_grant_tokens" id="ygv_grant_tokens"
                 min="0" step="1" value="0" class="regular-text">
          <p class="description">Dodaj ovoliko tokena (ne menja maksimum niti tajmer).</p>
        </td>
      </tr>
      <tr>
        <th><label for="ygv_set_tokens">Postavi tokene</label></th>
        <td>
          <input type="number" name="ygv_set_tokens" id="ygv_set_tokens"
                 min="0" step="1" class="regular-text" placeholder="ostavi prazno da ne menjaš">
          <p class="description">Direktno postavi trenutno stanje (0…max).</p>
        </td>
      </tr>
      <tr>
        <th><label for="ygv_set_max">Postavi maksimum</label></th>
        <td>
          <input type="number" name="ygv_set_max" id="ygv_set_max"
                 min="1" step="1" class="regular-text" placeholder="ostavi prazno da ne menjaš">
          <p class="description">Ručno promeni maksimum. (Dinamični filter ga kasnije može ažurirati.)</p>
        </td>
      </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'ygv_user_wallet_box');
add_action('edit_user_profile',  'ygv_user_wallet_box');

/** Save handler */
function ygv_user_wallet_save($user_id) {
    if (!current_user_can('manage_options')) return;
    if (empty($_POST['ygv_user_wallet_nonce']) ||
        !wp_verify_nonce($_POST['ygv_user_wallet_nonce'],'ygv_user_wallet_save')) {
        return;
    }
    if (!function_exists('ygv_tokens')) return;

    $w = ygv_tokens()->lazy_refill((int)$user_id);

    if (isset($_POST['ygv_grant_tokens'])) {
        $add = max(0, (int)$_POST['ygv_grant_tokens']);
        if ($add > 0) $w['tokens'] = min((int)$w['max_tokens'], (int)$w['tokens'] + $add);
    }
    if (isset($_POST['ygv_set_tokens']) && $_POST['ygv_set_tokens'] !== '') {
        $set = max(0, (int)$_POST['ygv_set_tokens']);
        $w['tokens'] = min((int)$w['max_tokens'], $set);
    }
    if (isset($_POST['ygv_set_max']) && $_POST['ygv_set_max'] !== '') {
        $w['max_tokens'] = max(1, (int)$_POST['ygv_set_max']);
    }
    ygv_tokens()->put_wallet($w);
}
add_action('personal_options_update','ygv_user_wallet_save');
add_action('edit_user_profile_update','ygv_user_wallet_save');

/**
 * Users table columns: Level + Tokens
 */
add_filter('manage_users_columns', function ($cols) {
    $cols['ygv_level']  = __('YV nivo', 'hello-elementor-child');
    $cols['ygv_tokens'] = __('Tokeni', 'hello-elementor-child');
    return $cols;
});

add_action('manage_users_custom_column', function ($val, $col, $user_id) {
    if ($col === 'ygv_level') {
        $overall = 0;
        if (function_exists('ygv_progress') && method_exists(ygv_progress(), 'get_overall_progress')) {
            $overall = ygv_progress()->get_overall_progress((int)$user_id);
        } else {
            global $wpdb;
            $row = $wpdb->get_row(
                $wpdb->prepare("SELECT overall_level, overall_xp FROM {$wpdb->prefix}ygv_user_overall_progress WHERE user_id=%d", $user_id),
                ARRAY_A
            );
            if ($row) {
                $overall = [
                    'overall_level' => (int)$row['overall_level'],
                    'overall_xp'    => (int)$row['overall_xp'],
                ];
            }
        }
        $lvl = is_array($overall) ? (int)($overall['overall_level'] ?? 0) : (int)$overall;
        $xp  = is_array($overall) ? (int)($overall['overall_xp'] ?? 0)  : 0;
        return 'Lv ' . $lvl . '<br><small>XP ' . $xp . '</small>';
    }

    if ($col === 'ygv_tokens') {
        $w = function_exists('ygv_tokens')
            ? ygv_tokens()->current_wallet((int)$user_id)
            : ['tokens'=>0,'max_tokens'=>0];
        return (int)$w['tokens'] . ' / ' . (int)$w['max_tokens'];
    }

    return $val;
}, 10, 3);

