<?php
/**
 * Custom AJAX Login Handler for Elementor Login Form
 */

if (!defined('ABSPATH')) exit;

/**
 * AJAX Login Handler
 */
add_action('wp_ajax_nopriv_yugo_ajax_login', 'yugo_ajax_login_handler');
add_action('wp_ajax_yugo_ajax_login', 'yugo_ajax_login_handler');

function yugo_ajax_login_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'yugo_login_nonce')) {
        wp_send_json_error(['message' => 'Sigurnosna provera nije uspela. Osvežite stranicu i pokušajte ponovo.']);
        exit;
    }

    // Support both standard WP field names (log/pwd) and our custom names (username/password)
    $username = '';
    if (!empty($_POST['username'])) {
        $username = sanitize_user($_POST['username']);
    } elseif (!empty($_POST['log'])) {
        $username = sanitize_user($_POST['log']);
    }
    
    $password = '';
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
    } elseif (!empty($_POST['pwd'])) {
        $password = $_POST['pwd'];
    }
    
    $remember = false;
    if (isset($_POST['remember'])) {
        $remember = $_POST['remember'] === 'true' || $_POST['remember'] === 'forever' || $_POST['remember'] === '1';
    } elseif (isset($_POST['rememberme'])) {
        $remember = $_POST['rememberme'] === 'forever' || $_POST['rememberme'] === '1' || !empty($_POST['rememberme']);
    }

    if (empty($username) || empty($password)) {
        wp_send_json_error(['message' => 'Molimo unesite korisničko ime i lozinku.']);
        exit;
    }

    $creds = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    ];

    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
        $error_code = $user->get_error_code();
        
        switch ($error_code) {
            case 'invalid_username':
            case 'invalid_email':
                $message = 'Nevažeće korisničko ime ili email adresa.';
                break;
            case 'incorrect_password':
                $message = 'Lozinka koju ste uneli nije tačna.';
                break;
            case 'empty_username':
                $message = 'Polje za korisničko ime je prazno.';
                break;
            case 'empty_password':
                $message = 'Polje za lozinku je prazno.';
                break;
            default:
                $message = 'Prijava neuspešna. Molimo proverite Vaše podatke.';
        }
        
        wp_send_json_error(['message' => $message, 'code' => $error_code]);
        exit;
    }

    // Login successful — determine redirect based on role
    // Admin check FIRST (the form has a hidden redirect_to field hardcoded to /moj-nalog/)
    if (user_can($user, 'manage_options')) {
        // Administrators always go to wp-admin
        $redirect_url = admin_url();
    } elseif (isset($_POST['redirect_to']) && !empty($_POST['redirect_to'])) {
        $redirect_url = esc_url_raw($_POST['redirect_to']);
    } else {
        // Regular users go to account page
        $account_slug = defined('CUSTOM_ACCOUNT_PAGE_SLUG') ? CUSTOM_ACCOUNT_PAGE_SLUG : 'moj-nalog';
        $redirect_url = home_url('/' . $account_slug . '/');
    }

    wp_send_json_success([
        'message' => 'Uspešna prijava! Preusmeravanje...',
        'redirect' => $redirect_url,
    ]);
    exit;
}

/**
 * Enqueue login scripts
 */
add_action('wp_enqueue_scripts', 'yugo_enqueue_login_scripts');

function yugo_enqueue_login_scripts() {
    $login_slug = defined('CUSTOM_LOGIN_PAGE_SLUG') ? CUSTOM_LOGIN_PAGE_SLUG : 'prijava';
    
    // Only on login page
    if (!is_page($login_slug)) {
        return;
    }

    wp_enqueue_script(
        'yugo-login',
        get_stylesheet_directory_uri() . '/js/login.js',
        ['jquery'],
        '1.0.3',
        true
    );

    $account_slug = defined('CUSTOM_ACCOUNT_PAGE_SLUG') ? CUSTOM_ACCOUNT_PAGE_SLUG : 'moj-nalog';

    wp_localize_script('yugo-login', 'yugoLogin', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('yugo_login_nonce'),
        'redirect' => home_url('/' . $account_slug . '/'),
    ]);
}
