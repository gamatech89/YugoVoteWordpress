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

// Enqueue unified styles
wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], '1.0.0');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; }
        .ygv-auth-fullpage { min-height: 100vh; display: flex; flex-direction: column; }
        .ygv-auth-back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255,255,255,0.95);
            border: 1px solid var(--ygv-border, #e2e8f0);
            border-radius: 10px;
            color: var(--ygv-text, #1e293b);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .ygv-auth-back-btn:hover {
            background: var(--ygv-primary, #2d3a8c);
            color: white;
            border-color: var(--ygv-primary, #2d3a8c);
        }
        .ygv-auth-back-btn svg { width: 18px; height: 18px; }
        @media (max-width: 768px) {
            .ygv-auth-back-btn { top: 12px; left: 12px; padding: 8px 14px; font-size: 13px; }
        }
    </style>
</head>
<body <?php body_class('ygv-auth-fullpage-body'); ?>>

<a href="<?php echo esc_url(home_url('/')); ?>" class="ygv-auth-back-btn">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
    </svg>
    Nazad
</a>

<div class="ygv-auth-fullpage">
<div class="ygv-auth-page-wrapper">
    <!-- Left Side - Branding -->
    <div class="ygv-auth-branding">
        <div class="ygv-auth-branding__content">
            <div class="ygv-auth-branding__logo">
                <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/mascot.svg'); ?>" alt="YugoVote Mascot" class="ygv-auth-mascot">
            </div>
            <h1 class="ygv-auth-branding__title">YugoVote</h1>
            <p class="ygv-auth-branding__tagline">Glasaj za najbolje liste iz Jugoslavije!</p>
            <div class="ygv-auth-branding__features">
                <div class="ygv-auth-feature">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <span>Sakupljaj XP i napreduj kroz nivoe</span>
                </div>
                <div class="ygv-auth-feature">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <span>Osvoji nagrade za redovno glasanje</span>
                </div>
                <div class="ygv-auth-feature">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Pridruži se zajednici glasača</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Side - Form -->
    <div class="ygv-auth-form-side">
        <div class="ygv-auth-form-container">
            <div class="ygv-auth-form-card">
                <!-- Mobile Logo -->
                <div class="ygv-auth-mobile-logo">
                    <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/mascot.svg'); ?>" alt="YugoVote" width="60">
                    <span>YugoVote</span>
                </div>
                
                <h2 class="ygv-auth-form-title">Dobrodošli nazad!</h2>
                <p class="ygv-auth-form-subtitle">Prijavite se na svoj nalog da nastavite sa glasanjem</p>

                <?php
                // Display login errors passed via query string
                if (isset($_GET['login_error'])) {
                    $error_codes_str = sanitize_text_field(wp_unslash($_GET['login_error']));
                    $error_codes_arr = explode(',', $error_codes_str);
                    $display_error_message = '';

                    if (in_array('incorrect_password', $error_codes_arr)) {
                        $display_error_message = esc_html__('Lozinka koju ste uneli nije tačna.', 'your-text-domain');
                    } elseif (in_array('invalid_username', $error_codes_arr)) {
                        $display_error_message = esc_html__('Nevažeće korisničko ime.', 'your-text-domain');
                    } elseif (in_array('invalid_email', $error_codes_arr)) {
                        $display_error_message = esc_html__('Nevažeća email adresa.', 'your-text-domain');
                    } elseif (in_array('empty_username', $error_codes_arr)) {
                        $display_error_message = esc_html__('Unesite korisničko ime.', 'your-text-domain');
                    } elseif (in_array('empty_password', $error_codes_arr)) {
                        $display_error_message = esc_html__('Unesite lozinku.', 'your-text-domain');
                    } else {
                        $display_error_message = esc_html__('Prijava neuspešna. Proverite podatke.', 'your-text-domain');
                    }
                    echo '<div class="ygv-auth-alert ygv-auth-alert--error"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' . $display_error_message . '</div>';
                }

                if (isset($_GET['loggedout']) && $_GET['loggedout'] === 'true') {
                    echo '<div class="ygv-auth-alert ygv-auth-alert--success"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' . esc_html__('Uspešno ste se odjavili.', 'your-text-domain') . '</div>';
                }
                if (isset($_GET['checkemail'])) {
                    if ($_GET['checkemail'] === 'newpass') {
                        echo '<div class="ygv-auth-alert ygv-auth-alert--success"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' . esc_html__('Proverite email za novu lozinku.', 'your-text-domain') . '</div>';
                    }
                }
                ?>

                <?php
                $attempted_username = isset($_GET['username']) ? sanitize_user(wp_unslash($_GET['username'])) : '';
                ?>
                
                <form id="ygv-login-form" class="ygv-auth-form" method="post" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>">
                    <input type="hidden" name="yugo_login_nonce" value="<?php echo wp_create_nonce('yugo_login_nonce'); ?>">
                    <div class="ygv-auth-field">
                        <label for="user_login">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Email ili korisničko ime
                        </label>
                        <input type="text" name="log" id="user_login" value="<?php echo esc_attr($attempted_username); ?>" placeholder="Unesite email ili korisničko ime" required>
                    </div>
                    
                    <div class="ygv-auth-field">
                        <label for="user_pass">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Lozinka
                        </label>
                        <div class="ygv-auth-password-wrapper">
                            <input type="password" name="pwd" id="user_pass" placeholder="Unesite lozinku" required>
                            <button type="button" class="ygv-auth-toggle-password" aria-label="Prikaži lozinku">
                                <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="ygv-auth-options">
                        <label class="ygv-auth-remember">
                            <input type="checkbox" name="rememberme" value="forever">
                            <span class="checkmark"></span>
                            Zapamti me
                        </label>
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="ygv-auth-forgot">Zaboravljena lozinka?</a>
                    </div>
                    
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/' . CUSTOM_ACCOUNT_PAGE_SLUG . '/')); ?>">
                    
                    <button type="submit" class="ygv-auth-submit">
                        <span>Prijavi se</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </form>
                
                <div class="ygv-auth-divider">
                    <span>ili</span>
                </div>
                
                <div class="ygv-auth-social">
                    <!-- Placeholder for social login -->
                    <button type="button" class="ygv-auth-social-btn ygv-auth-social-btn--google" disabled>
                        <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Google (uskoro)
                    </button>
                </div>
                
                <div class="ygv-auth-footer">
                    <p>Nemate nalog? <a href="<?php echo esc_url(home_url('/' . CUSTOM_REGISTER_PAGE_SLUG . '/')); ?>">Registrujte se</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.ygv-auth-toggle-password');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const eyeOpen = this.querySelector('.eye-open');
            const eyeClosed = this.querySelector('.eye-closed');
            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
            } else {
                input.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
            }
        });
    }
});
</script>

</div><!-- .ygv-auth-fullpage -->
<?php wp_footer(); ?>
</body>
</html>