<?php
if (!defined('ABSPATH')) exit;

/**
 * [yugo_login_form]
 * Renders the login form using /inc/account/templates/login-form.php
 */
if (!function_exists('yugo_login_form_shortcode')) {
    function yugo_login_form_shortcode($atts = []) {
        $data = [
            'errors'            => [],
            'success_message'   => '',
            'attempted_username'=> isset($_GET['username']) ? sanitize_user(wp_unslash($_GET['username'])) : '',
        ];

        if (isset($_GET['login_error'])) {
            $codes = explode(',', sanitize_text_field(wp_unslash($_GET['login_error'])));
            if (in_array('incorrect_password', $codes, true)) {
                $data['errors'][] = esc_html__('Greška: Lozinka koju ste uneli nije tačna.', 'hello-elementor-child');
            } elseif (in_array('invalid_username', $codes, true)) {
                $data['errors'][] = esc_html__('Greška: Nevažeće korisničko ime.', 'hello-elementor-child');
            } elseif (in_array('invalid_email', $codes, true)) {
                $data['errors'][] = esc_html__('Greška: Nevažeća email adresa.', 'hello-elementor-child');
            } elseif (in_array('empty_username', $codes, true)) {
                $data['errors'][] = esc_html__('Greška: Polje za korisničko ime je prazno.', 'hello-elementor-child');
            } elseif (in_array('empty_password', $codes, true)) {
                $data['errors'][] = esc_html__('Greška: Polje za lozinku je prazno.', 'hello-elementor-child');
            } else {
                $data['errors'][] = esc_html__('Prijava neuspešna. Molimo proverite Vaše podatke i pokušajte ponovo.', 'hello-elementor-child');
            }
        }

        if (isset($_GET['loggedout']) && $_GET['loggedout'] === 'true') {
            $data['success_message'] = esc_html__('Uspešno ste se odjavili.', 'hello-elementor-child');
        }
        if (isset($_GET['registration_disabled'])) {
            $data['errors'][] = esc_html__('Registracija korisnika trenutno nije dozvoljena.', 'hello-elementor-child');
        }
        if (isset($_GET['checkemail'])) {
            $v = $_GET['checkemail'];
            if ($v === 'confirm')   $data['success_message'] = esc_html__('Proverite Vaš email za link za potvrdu.', 'hello-elementor-child');
            if ($v === 'newpass')   $data['success_message'] = esc_html__('Proverite Vaš email za novu lozinku.', 'hello-elementor-child');
            if ($v === 'registered')$data['success_message'] = esc_html__('Registracija uspešna. Molimo proverite Vaš email.', 'hello-elementor-child');
        }

        if (empty($data['attempted_username']) && !empty($_POST['log'])) {
            $data['attempted_username'] = esc_attr(wp_unslash($_POST['log']));
        }

        ob_start();
        yugo_account_render_template('login-form', $data);
        return ob_get_clean();
    }
    add_shortcode('yugo_login_form', 'yugo_login_form_shortcode');
}
