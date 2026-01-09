<?php
/**
 * [yugo_login_form] Shortcode
 *
 * Registers and renders the custom login form using the /inc/account/templates/login-form.php template.
 * Handles error and success messages, pre-fills attempted username, and prepares all data for the template.
 * 
 * Usage:
 *   - Add [yugo_login_form] to any WordPress page, post, or Elementor Shortcode widget.
 *   - Automatically handles login errors, logout messages, and various states based on query parameters.
 *
 * @return string Rendered login form HTML.
 */
if (!function_exists('yugo_login_form_shortcode')) {
    function yugo_login_form_shortcode($atts = []) {
        // Prepare data for template
        $data = [];

        // Error and message handling logic (from your template)
        $data['errors'] = [];
        $data['success_message'] = '';
        $data['attempted_username'] = isset($_GET['username']) ? sanitize_user(wp_unslash($_GET['username'])) : '';

        if (isset($_GET['login_error'])) {
            $error_codes_str = sanitize_text_field(wp_unslash($_GET['login_error']));
            $error_codes_arr = explode(',', $error_codes_str);
            $display_error_message = '';

            if (in_array('incorrect_password', $error_codes_arr)) {
                $display_error_message = esc_html__('Greška: Lozinka koju ste uneli nije tačna.', 'your-text-domain');
            } elseif (in_array('invalid_username', $error_codes_arr)) {
                $display_error_message = esc_html__('Greška: Nevažeće korisničko ime.', 'your-text-domain');
            } elseif (in_array('invalid_email', $error_codes_arr)) {
                $display_error_message = esc_html__('Greška: Nevažeća email adresa.', 'your-text-domain');
            } elseif (in_array('empty_username', $error_codes_arr)) {
                $display_error_message = esc_html__('Greška: Polje za korisničko ime je prazno.', 'your-text-domain');
            } elseif (in_array('empty_password', $error_codes_arr)) {
                $display_error_message = esc_html__('Greška: Polje za lozinku je prazno.', 'your-text-domain');
            } else {
                $display_error_message = esc_html__('Prijava neuspešna. Molimo proverite Vaše podatke i pokušajte ponovo.', 'your-text-domain');
            }
            $data['errors'][] = $display_error_message;
        }

        if (isset($_GET['loggedout']) && $_GET['loggedout'] === 'true') {
            $data['success_message'] = esc_html__('Uspešno ste se odjavili.', 'your-text-domain');
        }
        if (isset($_GET['registration_disabled'])) {
            $data['errors'][] = esc_html__('Registracija korisnika trenutno nije dozvoljena.', 'your-text-domain');
        }
        if (isset($_GET['checkemail'])) {
            if ($_GET['checkemail'] === 'confirm') {
                $data['success_message'] = esc_html__('Proverite Vaš email za link za potvrdu.', 'your-text-domain');
            } elseif ($_GET['checkemail'] === 'newpass') {
                $data['success_message'] = esc_html__('Proverite Vaš email za novu lozinku.', 'your-text-domain');
            } elseif ($_GET['checkemail'] === 'registered') {
                $data['success_message'] = esc_html__('Registracija uspešna. Molimo proverite Vaš email.', 'your-text-domain');
            }
        }

        // Pass attempted username to template if any
        if (empty($data['attempted_username']) && !empty($_POST['log'])) { 
            $data['attempted_username'] = esc_attr(wp_unslash($_POST['log']));
        }

        ob_start();
        yugo_account_render_template('login-form', $data);
        return ob_get_clean();
    }
    add_shortcode('yugo_login_form', 'yugo_login_form_shortcode');
}

