<?php
/**
 * Template Name: Zaboravljena Lozinka
 * Template Post Type: page
 *
 * Custom Lost Password Page Template - matches login page layout
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/' . (defined('CUSTOM_ACCOUNT_PAGE_SLUG') ? CUSTOM_ACCOUNT_PAGE_SLUG : 'moj-nalog') . '/'));
    exit;
}

// Enqueue unified styles
wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], '1.0.0');

$login_slug = defined('CUSTOM_LOGIN_PAGE_SLUG') ? CUSTOM_LOGIN_PAGE_SLUG : 'prijava';

// Handle messages
$message = '';
$message_type = '';

if (isset($_GET['checkemail']) && $_GET['checkemail'] === 'confirm') {
    $message = 'Proverite Vašu email adresu za link za resetovanje lozinke.';
    $message_type = 'success';
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalidcombo':
            $message = 'Nema registrovanog korisnika sa tom email adresom ili korisničkim imenom.';
            break;
        case 'empty':
            $message = 'Molimo unesite korisničko ime ili email adresu.';
            break;
        default:
            $message = 'Došlo je do greške. Pokušajte ponovo.';
    }
    $message_type = 'error';
}
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
        .ygv-auth-icon-circle {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--ygv-primary, #2d3a8c), var(--ygv-primary-light, #4f5db3));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: white;
        }
        @media (max-width: 768px) {
            .ygv-auth-back-btn { top: 12px; left: 12px; padding: 8px 14px; font-size: 13px; }
        }
    </style>
</head>
<body <?php body_class('ygv-auth-fullpage-body'); ?>>

<a href="<?php echo esc_url(home_url('/' . $login_slug . '/')); ?>" class="ygv-auth-back-btn">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
    </svg>
    Nazad na prijavu
</a>

<div class="ygv-auth-fullpage">
<div class="ygv-auth-page-wrapper">
    <!-- Left Side - Branding (same as login page) -->
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
                
                <!-- Lock Icon -->
                <div class="ygv-auth-icon-circle">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                
                <h2 class="ygv-auth-form-title">Zaboravljena lozinka?</h2>
                <p class="ygv-auth-form-subtitle">Unesite Vašu email adresu ili korisničko ime i poslaćemo Vam link za resetovanje lozinke.</p>

                <?php if ($message): ?>
                    <div class="ygv-auth-alert ygv-auth-alert--<?php echo esc_attr($message_type); ?>">
                        <?php if ($message_type === 'success'): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <?php else: ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <?php endif; ?>
                        <?php echo esc_html($message); ?>
                    </div>
                <?php endif; ?>
                
                <form id="ygv-lostpassword-form" class="ygv-auth-form" method="post" action="<?php echo esc_url(network_site_url('wp-login.php?action=lostpassword', 'login_post')); ?>">
                    <div class="ygv-auth-field">
                        <label for="user_login">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Email ili korisničko ime
                        </label>
                        <input type="text" name="user_login" id="user_login" placeholder="Unesite email ili korisničko ime" required autocomplete="username">
                    </div>
                    
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/zaboravljena-lozinka/?checkemail=confirm')); ?>">
                    
                    <button type="submit" class="ygv-auth-submit">
                        <span>Pošalji link za reset</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </button>
                </form>
                
                <div class="ygv-auth-footer">
                    <p>Setili ste se lozinke? <a href="<?php echo esc_url(home_url('/' . $login_slug . '/')); ?>">Prijavite se</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

</div><!-- .ygv-auth-fullpage -->
<?php wp_footer(); ?>
</body>
</html>
