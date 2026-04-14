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
            <h1 class="ygv-auth-branding__title">Yu Go Vote</h1>
            <p class="ygv-auth-branding__tagline">Pridruži se zajednici glasača!</p>
            <div class="ygv-auth-branding__features">
                <div class="ygv-auth-feature">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    <span>Kreiraj i deli svoje liste</span>
                </div>
                <div class="ygv-auth-feature">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <span>Osvoji značke i dostignuća</span>
                </div>
                <div class="ygv-auth-feature">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <span>Zarađuj YugoCoins za nagrade</span>
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
                    <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/mascot.svg'); ?>" alt="Yu Go Vote" width="60">
                    <span>Yu Go Vote</span>
                </div>
                
                <h2 class="ygv-auth-form-title">Kreiraj nalog</h2>
                <p class="ygv-auth-form-subtitle">Registruj se i počni da glasaš za omiljene liste</p>

                <?php
                // Display registration errors stored in a transient
                $transient_key = 'registration_errors_' . md5($_SERVER['REMOTE_ADDR']);
                $registration_errors = get_transient($transient_key);

                if ($registration_errors && is_array($registration_errors)) {
                    echo '<div class="ygv-auth-alert ygv-auth-alert--error"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><ul>';
                    foreach ($registration_errors as $error) {
                        echo '<li>' . esc_html($error) . '</li>';
                    }
                    echo '</ul></div>';
                    delete_transient($transient_key);
                }

                if (isset($_GET['registration_attempt']) && $_GET['registration_attempt'] === 'failed_creation') {
                    echo '<div class="ygv-auth-alert ygv-auth-alert--error"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' . esc_html__('Greška pri kreiranju naloga. Pokušajte ponovo.', 'your-text-domain') . '</div>';
                }

                $repopulate_username = isset($_GET['user_login']) ? sanitize_user(wp_unslash($_GET['user_login'])) : '';
                $repopulate_email = isset($_GET['user_email']) ? sanitize_email(wp_unslash($_GET['user_email'])) : '';
                ?>
                
                <form id="ygv-register-form" class="ygv-auth-form" action="<?php echo esc_url(get_permalink()); ?>" method="post">
                    <div class="ygv-auth-field">
                        <label for="user_login_reg">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Korisničko ime
                        </label>
                        <input type="text" name="user_login" id="user_login_reg" value="<?php echo esc_attr($repopulate_username); ?>" placeholder="Izaberite korisničko ime" required>
                    </div>
                    
                    <div class="ygv-auth-field">
                        <label for="user_email_reg">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            Email adresa
                        </label>
                        <input type="email" name="user_email" id="user_email_reg" value="<?php echo esc_attr($repopulate_email); ?>" placeholder="vasa@email.com" required>
                    </div>
                    
                    <div class="ygv-auth-field">
                        <label for="user_pass_reg">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Lozinka
                        </label>
                        <div class="ygv-auth-password-wrapper">
                            <input type="password" name="user_pass" id="user_pass_reg" placeholder="Minimum 8 karaktera" autocomplete="new-password" required>
                            <button type="button" class="ygv-auth-toggle-password" aria-label="Prikaži lozinku">
                                <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <div class="ygv-auth-password-strength" id="password-strength"></div>
                    </div>
                    
                    <div class="ygv-auth-field">
                        <label for="user_pass_confirm_reg">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            Potvrdi lozinku
                        </label>
                        <input type="password" name="user_pass_confirm" id="user_pass_confirm_reg" placeholder="Ponovite lozinku" autocomplete="new-password" required>
                    </div>
                    
                    <div class="ygv-auth-terms">
                        <label class="ygv-auth-checkbox">
                            <input type="checkbox" name="terms_accept" required>
                            <span class="checkmark"></span>
                            Slažem se sa <a href="/uslovi-koriscenja/" target="_blank">uslovima korišćenja</a>
                        </label>
                    </div>
                    
                    <?php wp_nonce_field('custom_register_action', 'custom_register_nonce'); ?>
                    <input type="hidden" name="cs_custom_register_form" value="1">
                    
                    <button type="submit" class="ygv-auth-submit">
                        <span>Registruj se</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </form>
                
                <div class="ygv-auth-divider">
                    <span>ili</span>
                </div>
                
                <div class="ygv-auth-social">
                    <button type="button" class="ygv-auth-social-btn ygv-auth-social-btn--google" disabled>
                        <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Google (uskoro)
                    </button>
                </div>
                
                <div class="ygv-auth-footer">
                    <p>Već imate nalog? <a href="<?php echo esc_url(home_url('/' . CUSTOM_LOGIN_PAGE_SLUG . '/')); ?>">Prijavite se</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    document.querySelectorAll('.ygv-auth-toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
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
    });
    
    // Password strength indicator
    const passInput = document.getElementById('user_pass_reg');
    const strengthDiv = document.getElementById('password-strength');
    if (passInput && strengthDiv) {
        passInput.addEventListener('input', function() {
            const val = this.value;
            let strength = 0;
            let text = '';
            let color = '';
            
            if (val.length >= 8) strength++;
            if (val.match(/[a-z]/) && val.match(/[A-Z]/)) strength++;
            if (val.match(/[0-9]/)) strength++;
            if (val.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (val.length === 0) {
                strengthDiv.innerHTML = '';
            } else if (strength <= 1) {
                text = 'Slaba'; color = '#ef4444';
            } else if (strength === 2) {
                text = 'Srednja'; color = '#f59e0b';
            } else if (strength === 3) {
                text = 'Dobra'; color = '#22c55e';
            } else {
                text = 'Odlična'; color = '#10b981';
            }
            
            if (val.length > 0) {
                strengthDiv.innerHTML = '<div class="strength-bar" style="width: ' + (strength * 25) + '%; background: ' + color + '"></div><span style="color: ' + color + '">' + text + '</span>';
            }
        });
    }
});
</script>

</div><!-- .ygv-auth-fullpage -->
<?php wp_footer(); ?>
</body>
</html>