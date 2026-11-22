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
 * Gost: kolačić postavlja i server (u AJAX odgovoru) — Safari briše kolačiće postavljene iz JS-a
 * posle 7 dana, a onda bi se izgubila veza sa ranijim glasovima. Vraća oznaku gosta ('' za prijavljene).
 */
function yuv_showdown_ensure_guest_cookie(): string
{
    if (is_user_logged_in()) {
        return '';
    }
    $token = yuv_showdown_guest_token();
    if ($token === '') {
        $token = bin2hex(random_bytes(16));
        $_COOKIE[YUV_SHOWDOWN_GUEST_COOKIE] = $token;
    }
    if (!headers_sent()) {
        setcookie(YUV_SHOWDOWN_GUEST_COOKIE, $token, [
            'expires'  => time() + YEAR_IN_SECONDS,
            'path'     => '/',
            'secure'   => is_ssl(),
            'httponly' => false, // JS ga čita da ne bi pravio novu oznaku
            'samesite' => 'Lax',
        ]);
    }
    return $token;
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
