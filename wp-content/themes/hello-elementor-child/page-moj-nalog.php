<?php
/**
 * Template Name: Moj Nalog - Pure PHP
 * Template Post Type: page
 *
 * Pure PHP account page template - no Elementor dependency
 * Renders the [yugo_account] shortcode with custom wrapper
 * 
 * @package HelloElementorChild
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Redirect non-logged-in users to login page
if (!is_user_logged_in()) {
    wp_redirect(home_url('/prijava/?redirect_to=' . urlencode(home_url('/moj-nalog/'))));
    exit;
}

$user = wp_get_current_user();
$avatar_url = get_avatar_url($user->ID, ['size' => 120]);
$display_name = $user->display_name ?: $user->user_login;

// Get user stats
global $wpdb;
$overall_table = $wpdb->prefix . 'ygv_user_overall_progress';
$overall = $wpdb->get_row($wpdb->prepare(
    "SELECT overall_xp, overall_level FROM {$overall_table} WHERE user_id = %d",
    $user->ID
), ARRAY_A) ?: ['overall_xp' => 0, 'overall_level' => 1];

// Get level title
$level_config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : null;
$title = 'Rookie';
if ($level_config) {
    foreach ($level_config['tiers'] as $tier) {
        if ($overall['overall_level'] >= $tier['min_level'] && $overall['overall_level'] <= $tier['max_level']) {
            $title = $tier['title'];
            break;
        }
    }
}

// Enqueue styles
wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], '1.0.0');

get_header();
?>

<div class="ygv-page ygv-account-page">
    
    <!-- Account Header -->
    <section class="ygv-account-header">
        <div class="ygv-container">
            <div class="ygv-account-header__inner">
                <div class="ygv-account-header__avatar">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
                    <span class="ygv-account-header__level"><?php echo esc_html($overall['overall_level']); ?></span>
                </div>
                <div class="ygv-account-header__info">
                    <h1 class="ygv-account-header__name"><?php echo esc_html($display_name); ?></h1>
                    <p class="ygv-account-header__title"><?php echo esc_html($title); ?></p>
                </div>
                <div class="ygv-account-header__actions">
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="ygv-btn ygv-btn--outline">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Odjavi se
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Account Content -->
    <section class="ygv-account-content">
        <div class="ygv-container">
            <?php echo do_shortcode('[yugo_account]'); ?>
        </div>
    </section>
    
</div>

<style>
/* Account Page Styles */
.ygv-account-page {
    background: var(--ygv-bg);
    min-height: 100vh;
}

.ygv-account-header {
    background: linear-gradient(135deg, var(--ygv-primary) 0%, #1e2a5e 100%);
    padding: 40px 20px;
    color: white;
}

.ygv-account-header__inner {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

.ygv-account-header__avatar {
    position: relative;
    width: 80px;
    height: 80px;
    flex-shrink: 0;
}

.ygv-account-header__avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    object-fit: cover;
}

.ygv-account-header__level {
    position: absolute;
    bottom: -4px;
    right: -4px;
    width: 32px;
    height: 32px;
    background: var(--ygv-gold);
    color: #1a1a1a;
    font-size: 14px;
    font-weight: 800;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid var(--ygv-primary);
}

.ygv-account-header__info {
    flex: 1;
    min-width: 200px;
}

.ygv-account-header__name {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 4px;
}

.ygv-account-header__title {
    font-size: 14px;
    opacity: 0.8;
    margin: 0;
}

.ygv-account-header__actions {
    display: flex;
    gap: 12px;
}

.ygv-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.ygv-btn--outline {
    background: transparent;
    border: 2px solid rgba(255,255,255,0.4);
    color: white;
}

.ygv-btn--outline:hover {
    background: rgba(255,255,255,0.1);
    border-color: white;
}

.ygv-account-content {
    padding: 32px 20px 60px;
}

/* Override existing account styles to fit new design */
.ygv-account-content .cs-acc-nav {
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .ygv-account-header__inner {
        flex-direction: column;
        text-align: center;
    }
    
    .ygv-account-header__actions {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php get_footer(); ?>
