<?php
/**
 * Showdown — prepoznavanje gosta po kolačiću i prebacivanje glasova na nalog posle prijave.
 *
 * Kolačić `yuv_sd_guest` (32 hex znaka) postavlja js/showdown.js pre prvog AJAX poziva.
 */

if (!defined('ABSPATH'))
    exit;

define('YUV_SHOWDOWN_GUEST_COOKIE', 'yuv_sd_guest');

/**
 * Oznaka gosta: iz argumenta ili kolačića; vraća '' ako nije ispravna.
 */
function yuv_showdown_guest_token(?string $raw = null): string
{
    if ($raw === null) {
        $raw = isset($_COOKIE[YUV_SHOWDOWN_GUEST_COOKIE]) ? (string) wp_unslash($_COOKIE[YUV_SHOWDOWN_GUEST_COOKIE]) : '';
    }
    return preg_match('/^[a-f0-9]{32}$/', $raw) ? $raw : '';
}

/**
 * Pri svakoj prijavi (i formom i AJAX-om i posle registracije — sve ide kroz wp_set_auth_cookie)
 * glasovi gosta iz ovog pregledača prelaze na nalog.
 */
add_action('set_logged_in_cookie', function ($logged_in_cookie, $expire, $expiration, $user_id) {
    $token = yuv_showdown_guest_token();
    if ($token === '' || (int) $user_id <= 0) {
        return;
    }
    (new YUV_Showdown_Manager())->claim_guest_sessions((int) $user_id, $token);
}, 10, 4);
