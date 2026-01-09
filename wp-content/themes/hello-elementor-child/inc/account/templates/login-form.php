<?php
/**
 * YugoVote Login Form Template
 * Location: inc/account/templates/login-form.php
 *
 * Renders the custom login form used by the [yugo_login_form] shortcode.
 * Data is passed in via $errors (array), $success_message (string), $attempted_username (string).
 *
 * Requirements:
 * - Used in combination with inc/account/account-shortcodes.php and yugo_account_render_template().
 * - Slugs and hooks defined in inc/account/auth-hooks.php.
 *
 * Usage:
 * - Add [yugo_login_form] shortcode to any page, post, or Elementor shortcode widget.
 *
 * @package Yugovote_Theme
 */

if (is_user_logged_in()) {
    // Redirect logged-in users to account page.
    $redirect_url = home_url('/' . CUSTOM_ACCOUNT_PAGE_SLUG . '/');
    wp_redirect($redirect_url);
    exit;
}
?>
<main id="site-content" role="main" class="cs-custom-auth-page cs-login-page">
    <div class="cs-auth-container">
        <div class="cs-auth-form-wrapper">

            <h1 class="cs-auth-title">
                <?php esc_html_e('Prijavite se na Vaš Nalog', 'your-text-domain'); ?>
            </h1>

            <?php
            // Display any error messages (login failures, etc.)
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    echo '<p class="cs-auth-message cs-auth-error">' . esc_html($error) . '</p>';
                }
            }

            // Display any success messages (logout, password reset, etc.)
            if (!empty($success_message)) {
                echo '<p class="cs-auth-message cs-auth-success">' . esc_html($success_message) . '</p>';
            }
            ?>

            <?php
            // Standard WordPress login form with custom labels and redirect
            $login_form_args = array(
                'echo'           => true,
                'redirect'       => home_url('/' . CUSTOM_ACCOUNT_PAGE_SLUG . '/'), // Redirect after login
                'form_id'        => 'cs-login-form',
                'label_username' => esc_html__('Korisničko ime ili Email Adresa', 'your-text-domain'),
                'label_password' => esc_html__('Lozinka', 'your-text-domain'),
                'label_remember' => esc_html__('Zapamti me', 'your-text-domain'),
                'label_log_in'   => esc_html__('Prijavi se', 'your-text-domain'),
                'id_username'    => 'user_login',
                'id_password'    => 'user_pass',
                'id_remember'    => 'rememberme',
                'id_submit'      => 'wp-submit-login',
                'remember'       => true,
                'value_username' => $attempted_username,
                'value_remember' => false, 
            );
            wp_login_form($login_form_args);
            ?>

            <p class="cs-auth-links">
                <!-- Link to registration page -->
                <a href="<?php echo esc_url(home_url('/' . CUSTOM_REGISTER_PAGE_SLUG . '/')); ?>">
                    <?php esc_html_e('Napravite nalog', 'your-text-domain'); ?>
                </a>
                <br>
                <?php 
                // Link to lost password page (redirects back here after reset)
                $lost_password_url = wp_lostpassword_url(home_url('/' . CUSTOM_LOGIN_PAGE_SLUG . '/')); 
                ?>
                <a href="<?php echo esc_url($lost_password_url); ?>">
                    <?php esc_html_e('Zaboravili ste lozinku?', 'your-text-domain'); ?>
                </a>
            </p>
            
            <div class="cs-social-login-section">
                <?php // Placeholder for Social Login Buttons ?>
            </div>

        </div>
    </div>
</main>
