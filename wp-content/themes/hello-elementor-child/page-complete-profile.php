<?php
/**
 * Template Name: Complete Profile Page
 * Template Post Type: page
 *
 * Multi-step profile completion wizard:
 * Step 1: Basic Info (Gender, DOB, Country)
 * Step 2: Interests (Categories selection)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This page is only for logged-in users.
if (!is_user_logged_in()) {
    $login_page_url = defined('CUSTOM_LOGIN_PAGE_SLUG') ? home_url('/' . CUSTOM_LOGIN_PAGE_SLUG . '/') : wp_login_url(get_permalink());
    wp_redirect($login_page_url);
    exit;
}

$current_user_id = get_current_user_id();
$current_user = wp_get_current_user();

// Fetch parent categories for "Points of Interest"
$parent_voting_list_categories = get_terms([
    'taxonomy'   => 'voting_list_category',
    'parent'     => 0,
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC'
]);

// Populate with existing user meta
$user_gender = get_user_meta($current_user_id, '_user_gender', true);
$user_dob_day = get_user_meta($current_user_id, '_user_dob_day', true);
$user_dob_month = get_user_meta($current_user_id, '_user_dob_month', true);
$user_dob_year = get_user_meta($current_user_id, '_user_dob_year', true);
$user_country = get_user_meta($current_user_id, '_user_country', true);
$user_interests = get_user_meta($current_user_id, '_user_points_of_interest', true);
$user_referral = get_user_meta($current_user_id, '_user_referral_source', true);

if (!is_array($user_interests)) {
    $user_interests = [];
}

$countries = [
    "Srbija" => "🇷🇸 Srbija",
    "Hrvatska" => "🇭🇷 Hrvatska",
    "Bosna i Hercegovina" => "🇧🇦 Bosna i Hercegovina",
    "Crna Gora" => "🇲🇪 Crna Gora",
    "Makedonija" => "🇲🇰 Severna Makedonija",
    "Slovenija" => "🇸🇮 Slovenija",
    "Other" => "🌍 Drugo"
];

// Determine current step
$current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$current_step = max(1, min(2, $current_step)); // Clamp between 1 and 2

wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], '1.0.0');

get_header(); 
?>

<div class="ygv-auth-page-wrapper ygv-complete-profile-wrapper">
    <!-- Left Side - Branding -->
    <div class="ygv-auth-branding ygv-auth-branding--welcome">
        <div class="ygv-auth-branding__content">
            <div class="ygv-auth-branding__logo">
                <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/mascot.svg'); ?>" alt="YugoVote Mascot" class="ygv-auth-mascot ygv-auth-mascot--celebrate">
            </div>
            <h1 class="ygv-auth-branding__title">Dobrodošli, <?php echo esc_html($current_user->display_name ?: $current_user->user_login); ?>!</h1>
            <p class="ygv-auth-branding__tagline">Još samo par koraka do punog iskustva</p>
            
            <div class="ygv-auth-progress-steps">
                <div class="ygv-progress-step completed">
                    <div class="step-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span>Registracija</span>
                </div>
                <div class="ygv-progress-step <?php echo $current_step === 1 ? 'active' : ($current_step > 1 ? 'completed' : ''); ?>">
                    <div class="step-icon">
                        <?php if ($current_step > 1): ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php else: ?>
                            2
                        <?php endif; ?>
                    </div>
                    <span>Osnovni podaci</span>
                </div>
                <div class="ygv-progress-step <?php echo $current_step === 2 ? 'active' : ''; ?>">
                    <div class="step-icon">3</div>
                    <span>Interesovanja</span>
                </div>
                <div class="ygv-progress-step">
                    <div class="step-icon">4</div>
                    <span>Glasanje!</span>
                </div>
            </div>
            
            <div class="ygv-auth-branding__features">
                <div class="ygv-auth-feature">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>Vaši podaci su sigurni</span>
                </div>
                <div class="ygv-auth-feature">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>Možete preskočiti za sada</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Side - Form -->
    <div class="ygv-auth-form-side">
        <div class="ygv-auth-form-container">
            <div class="ygv-auth-form-card ygv-complete-profile-card">
                <!-- Mobile Logo -->
                <div class="ygv-auth-mobile-logo">
                    <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/mascot.svg'); ?>" alt="YugoVote" width="60">
                    <span>YugoVote</span>
                </div>
                
                <!-- Step Indicator (Mobile) -->
                <div class="ygv-step-indicator">
                    <span class="ygv-step-indicator__current">Korak <?php echo $current_step; ?></span>
                    <span class="ygv-step-indicator__total">od 2</span>
                </div>
                
                <?php if ($current_step === 1) : ?>
                    <!-- STEP 1: Basic Info -->
                    <h2 class="ygv-auth-form-title">Osnovni podaci</h2>
                    <p class="ygv-auth-form-subtitle">Recite nam malo o sebi</p>
                    
                    <?php if (isset($_GET['new_registration']) && $_GET['new_registration'] === 'true') : ?>
                        <div class="ygv-auth-alert ygv-auth-alert--success">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            Registracija uspešna! Dopunite profil za bolje iskustvo.
                        </div>
                    <?php endif; ?>

                    <?php
                    $transient_key = 'complete_profile_errors_' . md5($_SERVER['REMOTE_ADDR'] . $current_user_id);
                    $profile_errors = get_transient($transient_key);

                    if ($profile_errors && is_array($profile_errors)) {
                        echo '<div class="ygv-auth-alert ygv-auth-alert--error"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><ul>';
                        foreach ($profile_errors as $error_message) {
                            echo '<li>' . esc_html($error_message) . '</li>';
                        }
                        echo '</ul></div>';
                        delete_transient($transient_key);
                    }
                    ?>

                    <form id="ygv-complete-profile-form" class="ygv-auth-form ygv-complete-profile-form" action="<?php echo esc_url(add_query_arg('step', '2', get_permalink())); ?>" method="post">
                        
                        <!-- Gender Selection -->
                        <div class="ygv-auth-field">
                            <label>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                                Pol
                            </label>
                            <div class="ygv-gender-select">
                                <label class="ygv-gender-option <?php echo $user_gender === 'male' ? 'selected' : ''; ?>">
                                    <input type="radio" name="user_gender" value="male" <?php checked($user_gender, 'male'); ?>>
                                    <span class="gender-icon">👨</span>
                                    <span>Muški</span>
                                </label>
                                <label class="ygv-gender-option <?php echo $user_gender === 'female' ? 'selected' : ''; ?>">
                                    <input type="radio" name="user_gender" value="female" <?php checked($user_gender, 'female'); ?>>
                                    <span class="gender-icon">👩</span>
                                    <span>Ženski</span>
                                </label>
                                <label class="ygv-gender-option <?php echo $user_gender === 'other' ? 'selected' : ''; ?>">
                                    <input type="radio" name="user_gender" value="other" <?php checked($user_gender, 'other'); ?>>
                                    <span class="gender-icon">🧑</span>
                                    <span>Drugo</span>
                                </label>
                            </div>
                        </div>

                        <!-- Date of Birth -->
                        <div class="ygv-auth-field">
                            <label>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                Datum rođenja
                            </label>
                            <div class="ygv-dob-select">
                                <select name="user_dob_day" id="user_dob_day">
                                    <option value="">Dan</option>
                                    <?php for ($i = 1; $i <= 31; $i++) : ?>
                                        <option value="<?php echo $i; ?>" <?php selected($user_dob_day, $i); ?>><?php printf('%02d', $i); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select name="user_dob_month" id="user_dob_month">
                                    <option value="">Mesec</option>
                                    <?php 
                                    $months = ['Januar', 'Februar', 'Mart', 'April', 'Maj', 'Jun', 'Jul', 'Avgust', 'Septembar', 'Oktobar', 'Novembar', 'Decembar'];
                                    for ($i = 1; $i <= 12; $i++) : ?>
                                        <option value="<?php echo $i; ?>" <?php selected($user_dob_month, $i); ?>><?php echo $months[$i-1]; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select name="user_dob_year" id="user_dob_year">
                                    <option value="">Godina</option>
                                    <?php $current_year = date('Y'); for ($i = $current_year - 13; $i >= $current_year - 100; $i--) : ?>
                                        <option value="<?php echo $i; ?>" <?php selected($user_dob_year, $i); ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Country -->
                        <div class="ygv-auth-field">
                            <label for="user_country">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                Država
                            </label>
                            <select name="user_country" id="user_country">
                                <option value="">Izaberite državu</option>
                                <?php foreach ($countries as $code => $name) : ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($user_country, $code); ?>><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- How did you hear about us -->
                        <div class="ygv-auth-field">
                            <label for="user_referral">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                Kako ste čuli za nas? <span class="optional">(opciono)</span>
                            </label>
                            <select name="user_referral" id="user_referral">
                                <option value="">Izaberite opciju</option>
                                <option value="instagram" <?php selected($user_referral, 'instagram'); ?>>📱 Instagram</option>
                                <option value="tiktok" <?php selected($user_referral, 'tiktok'); ?>>🎵 TikTok</option>
                                <option value="facebook" <?php selected($user_referral, 'facebook'); ?>>📘 Facebook</option>
                                <option value="youtube" <?php selected($user_referral, 'youtube'); ?>>▶️ YouTube</option>
                                <option value="friend" <?php selected($user_referral, 'friend'); ?>>👥 Od prijatelja</option>
                                <option value="google" <?php selected($user_referral, 'google'); ?>>🔍 Google pretraga</option>
                                <option value="other" <?php selected($user_referral, 'other'); ?>>🌐 Drugo</option>
                            </select>
                        </div>
                        
                        <?php wp_nonce_field('custom_complete_profile_step1', 'profile_step1_nonce'); ?>
                        <input type="hidden" name="profile_step" value="1">
                        
                        <div class="ygv-complete-profile-actions">
                            <button type="submit" class="ygv-btn-action">
                                <span>Nastavi</span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </button>
                            
                            <a href="<?php echo esc_url(home_url('/' . (defined('CUSTOM_ACCOUNT_PAGE_SLUG') ? CUSTOM_ACCOUNT_PAGE_SLUG : 'moj-nalog') . '/')); ?>" class="ygv-btn-skip">
                                Preskoči za sada
                            </a>
                        </div>
                    </form>
                    
                <?php else : ?>
                    <!-- STEP 2: Interests -->
                    <h2 class="ygv-auth-form-title">Vaša interesovanja</h2>
                    <p class="ygv-auth-form-subtitle">Izaberite kategorije koje vas zanimaju za personalizovane preporuke</p>

                    <form id="ygv-complete-profile-form-step2" class="ygv-auth-form ygv-complete-profile-form" action="<?php echo esc_url(get_permalink()); ?>" method="post">
                        
                        <?php if (!empty($parent_voting_list_categories) && !is_wp_error($parent_voting_list_categories)) : ?>
                        <div class="ygv-auth-field ygv-interests-field">
                            <p class="ygv-auth-field-hint" style="margin-top: 0;">Kliknite na kategorije koje vas interesuju - možete izabrati više</p>
                            <div class="ygv-interests-grid ygv-interests-grid--large">
                                <?php foreach ($parent_voting_list_categories as $category) : 
                                    $is_checked = in_array((string)$category->term_id, array_map('strval', $user_interests), true);
                                    // Get category icon or use default
                                    $cat_icon = '';
                                    switch(strtolower($category->slug)) {
                                        case 'biznis': $cat_icon = '💼'; break;
                                        case 'culture-club': $cat_icon = '🎭'; break;
                                        case 'film-i-tv': $cat_icon = '🎬'; break;
                                        case 'muzika': $cat_icon = '🎵'; break;
                                        case 'sport': $cat_icon = '⚽'; break;
                                        case 'trendy-lifestyle': $cat_icon = '✨'; break;
                                        default: $cat_icon = '📌';
                                    }
                                ?>
                                    <label class="ygv-interest-chip ygv-interest-chip--large <?php echo $is_checked ? 'selected' : ''; ?>">
                                        <input type="checkbox" name="points_of_interest[]" value="<?php echo esc_attr($category->term_id); ?>" <?php checked($is_checked); ?>>
                                        <span class="chip-icon"><?php echo $cat_icon; ?></span>
                                        <span class="chip-label"><?php echo esc_html($category->name); ?></span>
                                        <span class="chip-check">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php wp_nonce_field('custom_complete_profile_step2', 'profile_step2_nonce'); ?>
                        <input type="hidden" name="profile_step" value="2">
                        <input type="hidden" name="cs_custom_complete_profile_form" value="1">
                        
                        <div class="ygv-complete-profile-actions">
                            <button type="submit" class="ygv-btn-action">
                                <span>Završi i kreni</span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </button>
                            
                            <div class="ygv-step-nav">
                                <a href="<?php echo esc_url(add_query_arg('step', '1', get_permalink())); ?>" class="ygv-btn-back">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                                    Nazad
                                </a>
                                <a href="<?php echo esc_url(home_url('/' . (defined('CUSTOM_ACCOUNT_PAGE_SLUG') ? CUSTOM_ACCOUNT_PAGE_SLUG : 'moj-nalog') . '/')); ?>" class="ygv-btn-skip">
                                    Preskoči
                                </a>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gender selection
    document.querySelectorAll('.ygv-gender-option input').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.ygv-gender-option').forEach(function(opt) {
                opt.classList.remove('selected');
            });
            if (this.checked) {
                this.closest('.ygv-gender-option').classList.add('selected');
            }
        });
    });
    
    // Interest chips selection
    document.querySelectorAll('.ygv-interest-chip input').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                this.closest('.ygv-interest-chip').classList.add('selected');
            } else {
                this.closest('.ygv-interest-chip').classList.remove('selected');
            }
        });
    });
});
</script>

<?php get_footer(); ?>