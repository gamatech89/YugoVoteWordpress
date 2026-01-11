<?php
if (!defined('ABSPATH')) exit;

// Page slugs (define only once)
defined('CUSTOM_LOGIN_PAGE_SLUG')      || define('CUSTOM_LOGIN_PAGE_SLUG', 'prijava');
defined('CUSTOM_REGISTER_PAGE_SLUG')   || define('CUSTOM_REGISTER_PAGE_SLUG', 'registracija');
defined('CUSTOM_COMPLETE_PROFILE_PAGE_SLUG') || define('CUSTOM_COMPLETE_PROFILE_PAGE_SLUG', 'kompletiranje-naloga');
defined('CUSTOM_ACCOUNT_PAGE_SLUG')    || define('CUSTOM_ACCOUNT_PAGE_SLUG', 'moj-nalog');

/** Redirect auth errors back to custom login (only for non-AJAX requests) */
add_filter('authenticate', function($user, $username, $password){
    if (!is_wp_error($user)) return $user;
    
    // Don't redirect for AJAX requests - let the AJAX handler return JSON
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return $user;
    }
    
    // Also check for wp_doing_ajax() and common AJAX indicators
    if (wp_doing_ajax()) {
        return $user;
    }
    
    // Check if this is an AJAX request by headers
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return $user;
    }

    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $login_url = home_url('/'.CUSTOM_LOGIN_PAGE_SLUG.'/');

    if ($ref && strpos($ref, $login_url) !== false) {
        $codes = implode(',', $user->get_error_codes());
        $to = add_query_arg(['login_error'=>$codes,'username'=>urlencode($username)], $login_url);
        wp_safe_redirect($to);
        exit;
    }
    return $user;
}, 99, 3);

/** Override wp_login_url to our login page */
add_filter('login_url', function($login_url, $redirect, $force_reauth){
    $url = home_url('/'.CUSTOM_LOGIN_PAGE_SLUG.'/');
    if ($redirect)    $url = add_query_arg('redirect_to', urlencode($redirect), $url);
    if ($force_reauth) $url = add_query_arg('reauth', '1', $url);
    return $url;
}, 10, 3);

/** Override lost password URL to custom page */
add_filter('lostpassword_url', function($lostpassword_url, $redirect){
    $url = home_url('/zaboravljena-lozinka/');
    if ($redirect) {
        $url = add_query_arg('redirect_to', urlencode($redirect), $url);
    }
    return $url;
}, 10, 2);

/** Redirect wp-login.php?action=lostpassword to custom page */
add_action('login_form_lostpassword', function(){
    wp_redirect(home_url('/zaboravljena-lozinka/'));
    exit;
});

/** After logout send to custom login with flag */
add_filter('logout_url', function($logout_url, $redirect){
    $dest = home_url('/'.CUSTOM_LOGIN_PAGE_SLUG.'/?loggedout=true');
    return add_query_arg('redirect_to', urlencode($dest), $logout_url);
}, 10, 2);

/** Handle custom registration form */
add_action('init', function(){
    if (!isset($_POST['cs_custom_register_form'], $_POST['custom_register_nonce'])) return;
    if (!wp_verify_nonce($_POST['custom_register_nonce'], 'custom_register_action')) {
        wp_safe_redirect(add_query_arg('registration_error','nonce_failure', home_url('/'.CUSTOM_REGISTER_PAGE_SLUG.'/'))); exit;
    }
    $username = isset($_POST['user_login']) ? sanitize_user(trim($_POST['user_login'])) : '';
    $email    = isset($_POST['user_email']) ? sanitize_email(trim($_POST['user_email'])) : '';
    $password = isset($_POST['user_pass']) ? (string) $_POST['user_pass'] : '';
    $confirm  = isset($_POST['user_pass_confirm']) ? (string) $_POST['user_pass_confirm'] : '';

    $errors = new WP_Error();
    if ($username === '') $errors->add('empty_username', __('Greška: Polje za korisničko ime je prazno.', 'hello-elementor-child'));
    if (username_exists($username)) $errors->add('username_exists', __('Greška: Korisničko ime je zauzeto.', 'hello-elementor-child'));
    if (!is_email($email)) $errors->add('invalid_email', __('Greška: Nevažeća email adresa.', 'hello-elementor-child'));
    if (email_exists($email)) $errors->add('email_exists', __('Greška: Email je već registrovan.', 'hello-elementor-child'));
    if ($password === '') $errors->add('empty_password', __('Greška: Polje za lozinku je prazno.', 'hello-elementor-child'));
    if ($password !== $confirm) $errors->add('password_mismatch', __('Greška: Lozinke se ne poklapaju.', 'hello-elementor-child'));

    if ($errors->has_errors()) {
        set_transient('registration_errors_'.md5($_SERVER['REMOTE_ADDR']), $errors->get_error_messages(), MINUTE_IN_SECONDS*5);
        $url = add_query_arg([
            'registration_attempt'=>'failed',
            'user_login'=>urlencode($username),
            'user_email'=>urlencode($email)
        ], home_url('/'.CUSTOM_REGISTER_PAGE_SLUG.'/'));
        wp_safe_redirect($url); exit;
    }

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        set_transient('registration_errors_'.md5($_SERVER['REMOTE_ADDR']), $user_id->get_error_messages(), MINUTE_IN_SECONDS*5);
        wp_safe_redirect(add_query_arg('registration_attempt','failed_creation', home_url('/'.CUSTOM_REGISTER_PAGE_SLUG.'/'))); exit;
    }

    wp_set_current_user($user_id, $username);
    wp_set_auth_cookie($user_id, true);

    wp_safe_redirect(home_url('/'.CUSTOM_COMPLETE_PROFILE_PAGE_SLUG.'/?new_registration=true')); exit;
});

/** Handle complete-profile form - Multi-step */
add_action('init', function(){
    // Handle Step 1 (Basic Info)
    if (isset($_POST['profile_step']) && $_POST['profile_step'] === '1' && isset($_POST['profile_step1_nonce'])) {
        if (!is_user_logged_in()) { wp_safe_redirect(home_url('/'.CUSTOM_LOGIN_PAGE_SLUG.'/')); exit; }
        $user_id = get_current_user_id();
        
        if (!wp_verify_nonce($_POST['profile_step1_nonce'], 'custom_complete_profile_step1')) {
            wp_safe_redirect(add_query_arg('profile_error','nonce_failure', home_url('/'.CUSTOM_COMPLETE_PROFILE_PAGE_SLUG.'/'))); exit;
        }

        $gender = isset($_POST['user_gender']) ? sanitize_text_field($_POST['user_gender']) : '';
        $d = (int)($_POST['user_dob_day'] ?? 0);
        $m = (int)($_POST['user_dob_month'] ?? 0);
        $y = (int)($_POST['user_dob_year'] ?? 0);
        $country = isset($_POST['user_country']) ? sanitize_text_field($_POST['user_country']) : '';
        $referral = isset($_POST['user_referral']) ? sanitize_text_field($_POST['user_referral']) : '';

        // Save step 1 data
        $gender ? update_user_meta($user_id, '_user_gender', $gender) : null;
        if ($d && $m && $y) {
            update_user_meta($user_id, '_user_dob_day', $d);
            update_user_meta($user_id, '_user_dob_month', $m);
            update_user_meta($user_id, '_user_dob_year', $y);
            update_user_meta($user_id, '_user_dob', sprintf('%04d-%02d-%02d',$y,$m,$d));
        }
        $country ? update_user_meta($user_id, '_user_country', $country) : null;
        $referral ? update_user_meta($user_id, '_user_referral_source', $referral) : null;

        // Redirect to step 2
        wp_safe_redirect(add_query_arg('step', '2', home_url('/'.CUSTOM_COMPLETE_PROFILE_PAGE_SLUG.'/'))); exit;
    }
    
    // Handle Step 2 (Interests) - Final submission
    if (!isset($_POST['cs_custom_complete_profile_form'], $_POST['profile_step2_nonce'])) return;
    if (!is_user_logged_in()) { wp_safe_redirect(home_url('/'.CUSTOM_LOGIN_PAGE_SLUG.'/')); exit; }
    $user_id = get_current_user_id();
    if (!wp_verify_nonce($_POST['profile_step2_nonce'], 'custom_complete_profile_step2')) {
        wp_safe_redirect(add_query_arg(['profile_error'=>'nonce_failure', 'step'=>'2'], home_url('/'.CUSTOM_COMPLETE_PROFILE_PAGE_SLUG.'/'))); exit;
    }

    $poi = isset($_POST['points_of_interest']) && is_array($_POST['points_of_interest']) ? array_map('intval', $_POST['points_of_interest']) : [];

    $errors = new WP_Error();
    if ($poi) {
        foreach ($poi as $term_id) {
            if (!term_exists((int)$term_id, 'voting_list_category')) { $errors->add('interest_invalid', __('Greška: Izabrana je nevažeća interesna kategorija.', 'hello-elementor-child')); break; }
        }
    }

    if ($errors->has_errors()) {
        set_transient('complete_profile_errors_'.md5($_SERVER['REMOTE_ADDR'].$user_id), $errors->get_error_messages(), MINUTE_IN_SECONDS*5);
        wp_safe_redirect(add_query_arg(['profile_update'=>'failed', 'step'=>'2'], home_url('/'.CUSTOM_COMPLETE_PROFILE_PAGE_SLUG.'/'))); exit;
    }

    $poi ? update_user_meta($user_id, '_user_points_of_interest', $poi) : delete_user_meta($user_id, '_user_points_of_interest');

    wp_safe_redirect(add_query_arg('profile_completed','true', home_url('/'.CUSTOM_ACCOUNT_PAGE_SLUG.'/'))); exit;
});
