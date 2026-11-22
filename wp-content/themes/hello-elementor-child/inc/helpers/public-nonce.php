<?php
/**
 * Nonce za javne (gost) akcije + stranice sa formama van javnog keša.
 *
 * Zašto: LiteSpeed kešira stranice za goste do 7 dana, a WordPress nonce važi najviše 24h.
 * Gost iz keša šalje zastareli nonce i server ga odbija (403) — glasovi u dvobojima/anketama/listama
 * se ne upisuju, kviz ne može da se igra. Gostu nonce ionako ne pruža zaštitu (svako ga dobije
 * otvaranjem stranice), pa ga proveravamo samo za prijavljene, čije stranice nisu u javnom kešu.
 *
 * @package YugoVote
 */

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Da li je nonce u redu za javnu akciju: gost uvek prolazi, prijavljen mora imati važeći nonce.
 *
 * @param string      $action Naziv nonce akcije.
 * @param string|null $nonce  Vrednost; null = čita se iz $_REQUEST[$field].
 * @param string      $field  Polje zahteva kad je $nonce null.
 */
function ygv_public_nonce_ok(string $action, ?string $nonce = null, string $field = 'nonce'): bool {
    if (!is_user_logged_in()) {
        return true;
    }
    if ($nonce === null) {
        $nonce = isset($_REQUEST[$field]) ? sanitize_text_field(wp_unslash($_REQUEST[$field])) : '';
    }
    return (bool) wp_verify_nonce($nonce, $action);
}

/**
 * Stranice sa formama koje nose nonce (prijava, registracija, nalog) ne idu u javni keš.
 */
function ygv_nocache_form_pages(): void {
    $slugs = ['prijava', 'registracija', 'zaboravljena-lozinka', 'kompletiranje-naloga', 'moj-nalog', 'uredi-profil', 'kontakt'];
    if (is_page($slugs)) {
        do_action('litespeed_control_set_nocache', 'YugoVote: forma sa nonce-om');
    }
}
add_action('template_redirect', 'ygv_nocache_form_pages', 1);
