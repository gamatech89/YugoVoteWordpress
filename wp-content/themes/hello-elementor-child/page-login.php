<?php
/**
 * Template Name: Custom Login Page
 * Template Post Type: page
 *
 * This template displays the custom login page.
 * It redirects logged-in users to the 'Moj Nalog' page.
 * The login form submits to the standard wp-login.php, with error handling
 * to redirect back to this page.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// If user is already logged in, redirect them away from the login page.
if (is_user_logged_in()) {
    wp_redirect(home_url('/' . CUSTOM_ACCOUNT_PAGE_SLUG . '/')); 
    exit;
}

get_header(); 
?>

<main id="site-content" role="main" class="cs-custom-auth-page cs-login-page">
    <div class="cs-auth-container">
        <div class="cs-auth-form-wrapper">

            <h1 class="cs-auth-title"><?php esc_html_e('Prijavite se na Vaš Nalog', 'your-text-domain'); // Login to Your Account ?></h1>

            <?php
            // Display login errors passed via query string
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
                echo '<p class="cs-auth-message cs-auth-error">' . $display_error_message . '</p>';
            }

            // Display other messages (loggedout, etc.)
            if (isset($_GET['loggedout']) && $_GET['loggedout'] === 'true') {
                echo '<p class="cs-auth-message cs-auth-success">' . esc_html__('Uspešno ste se odjavili.', 'your-text-domain') . '</p>';
            }
            if (isset($_GET['registration_disabled'])) { // Renamed from registration=disabled for clarity
                echo '<p class="cs-auth-message cs-auth-error">' . esc_html__('Registracija korisnika trenutno nije dozvoljena.', 'your-text-domain') . '</p>';
            }
            if (isset($_GET['checkemail'])) {
                if ($_GET['checkemail'] === 'confirm') {
                    echo '<p class="cs-auth-message cs-auth-success">' . esc_html__('Proverite Vaš email za link za potvrdu.', 'your-text-domain') . '</p>';
                } elseif ($_GET['checkemail'] === 'newpass') {
                    echo '<p class="cs-auth-message cs-auth-success">' . esc_html__('Proverite Vaš email za novu lozinku.', 'your-text-domain') . '</p>';
                } elseif ($_GET['checkemail'] === 'registered') {
                    echo '<p class="cs-auth-message cs-auth-success">' . esc_html__('Registracija uspešna. Molimo proverite Vaš email.', 'your-text-domain') . '</p>';
                }
            }
            ?>

            <?php
            // Get attempted username to pre-fill, if available from redirect
            $attempted_username = isset($_GET['username']) ? sanitize_user(wp_unslash($_GET['username'])) : '';
            if (empty($attempted_username) && !empty($_POST['log'])) { 
                $attempted_username = esc_attr(wp_unslash($_POST['log']));
            }

            $login_form_args = array(
                'echo'           => true,
                'redirect'       => home_url('/' . CUSTOM_ACCOUNT_PAGE_SLUG . '/'), // Redirect on successful login
                'form_id'        => 'cs-login-form',
                'label_username' => esc_html__('Korisničko ime ili Email Adresa', 'your-text-domain'),
                'label_password' => esc_html__('Lozinka', 'your-text-domain'),
                'label_remember' => esc_html__('Zapamti me', 'your-text-domain'),
                'label_log_in'   => esc_html__('Prijavi se', 'your-text-domain'), // "Log In" or "Uloguj se"
                'id_username'    => 'user_login',
                'id_password'    => 'user_pass',
                'id_remember'    => 'rememberme',
                'id_submit'      => 'wp-submit-login',
                'remember'       => true,
                'value_username' => $attempted_username, // Pre-fill username
                'value_remember' => false, 
            );
            wp_login_form($login_form_args);
            ?>

            <p class="cs-auth-links">
                <?php // Link to your custom registration page using the constant ?>
                <a href="<?php echo esc_url(home_url('/' . CUSTOM_REGISTER_PAGE_SLUG . '/')); ?>"><?php esc_html_e('Napravite nalog', 'your-text-domain'); // Create an Account ?></a>
                <br>
                <?php // Lost password link - wp_lostpassword_url() will use your filtered login URL
                $lost_password_url = wp_lostpassword_url(home_url('/' . CUSTOM_LOGIN_PAGE_SLUG . '/')); 
                ?>
                <a href="<?php echo esc_url($lost_password_url); ?>"><?php esc_html_e('Zaboravili ste lozinku?', 'your-text-domain'); // Lost your password? ?></a>
            </p>
            
            <div class="cs-social-login-section">
                <?php // Placeholder for Social Login Buttons ?>
            </div>

        </div>
    </div>
</main>

<?php
get_footer(); 
?>