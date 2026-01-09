<?php
/**
 * Template Name: Custom Registration Page
 * Template Post Type: page
 *
 * This template displays the custom user registration form.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


// If user is already logged in, redirect them away.
if (is_user_logged_in()) {
    // Use the constant for the account page slug
    wp_redirect(home_url('/' . CUSTOM_ACCOUNT_PAGE_SLUG . '/')); 
    exit;
}

get_header(); 
?>

<main id="site-content" role="main" class="cs-custom-auth-page cs-register-page">
    <div class="cs-auth-container">
        <div class="cs-auth-form-wrapper">
            <h1 class="cs-auth-title"><?php esc_html_e('Napravi svoj nalog', 'your-text-domain'); // Create Your Profile ?></h1>

            <?php
            // Display registration errors stored in a transient
            $transient_key = 'registration_errors_' . md5($_SERVER['REMOTE_ADDR']);
            $registration_errors = get_transient($transient_key);

            if ($registration_errors && is_array($registration_errors)) {
                echo '<div class="cs-auth-message cs-auth-error">';
                echo '<ul>';
                foreach ($registration_errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
                delete_transient($transient_key); // Clear the errors after displaying them
            }

            // Check for specific success/failure query args (can be refined)
            if (isset($_GET['registration_attempt']) && $_GET['registration_attempt'] === 'failed_creation') {
                 echo '<p class="cs-auth-message cs-auth-error">' . esc_html__('An error occurred during user creation. Please try again.', 'your-text-domain') . '</p>';
            }
            if (isset($_GET['registration_error']) && $_GET['registration_error'] === 'nonce_failure') {
                 echo '<p class="cs-auth-message cs-auth-error">' . esc_html__('Security check failed. Please try again.', 'your-text-domain') . '</p>';
            }

            $repopulate_username = isset($_GET['user_login']) ? sanitize_user(wp_unslash($_GET['user_login'])) : '';
            $repopulate_email    = isset($_GET['user_email']) ? sanitize_email(wp_unslash($_GET['user_email'])) : '';
            ?>

            <form id="cs-register-form" class="cs-custom-form" action="<?php echo esc_url(get_permalink()); ?>" method="post">
                <p class="register-username">
                    <label for="user_login_reg"><?php esc_html_e('Korisničko ime', 'your-text-domain'); // Username ?></label>
                    <input type="text" name="user_login" id="user_login_reg" class="input" value="<?php echo esc_attr($repopulate_username); ?>" size="20" required />
                </p>
                <p class="register-email">
                    <label for="user_email_reg"><?php esc_html_e('Email adresa', 'your-text-domain'); // Email ?></label>
                    <input type="email" name="user_email" id="user_email_reg" class="input" value="<?php echo esc_attr($repopulate_email); ?>" size="25" required />
                </p>
                <p class="register-password">
                    <label for="user_pass_reg"><?php esc_html_e('Lozinka', 'your-text-domain'); // Password ?></label>
                    <input type="password" name="user_pass" id="user_pass_reg" class="input" value="" size="20" autocomplete="new-password" required />
                </p>
                <p class="register-password-confirm">
                    <label for="user_pass_confirm_reg"><?php esc_html_e('Potvrdite lozinku', 'your-text-domain'); // Confirm Password ?></label>
                    <input type="password" name="user_pass_confirm" id="user_pass_confirm_reg" class="input" value="" size="20" autocomplete="new-password" required />
                </p>
                <p class="register-submit">
                    <?php wp_nonce_field('custom_register_action', 'custom_register_nonce'); ?>
                    <input type="hidden" name="cs_custom_register_form" value="1" />
                    <input type="submit" name="wp-submit-register" id="wp-submit-register" class="button button-primary" value="<?php esc_attr_e('Registrujte se', 'your-text-domain'); // Register ?>" />
                </p>
            </form>

            <p class="cs-auth-links">
                <?php // Use the constant for the login page slug ?>
                <a href="<?php echo esc_url(home_url('/' . CUSTOM_LOGIN_PAGE_SLUG . '/')); ?>"><?php esc_html_e('Već imate nalog? Prijavite se', 'your-text-domain'); ?></a>
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