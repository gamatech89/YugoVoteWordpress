<?php if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$user = wp_get_current_user();

// Get current user meta settings
$email_notifications = get_user_meta($user_id, 'ygv_email_notifications', true) ?: 'all';
$profile_visibility = get_user_meta($user_id, 'ygv_profile_visibility', true) ?: 'public';
$show_level_badge = get_user_meta($user_id, 'ygv_show_level_badge', true) ?: 'yes';

// Handle form submission
if (isset($_POST['ygv_save_settings']) && wp_verify_nonce($_POST['ygv_settings_nonce'], 'ygv_save_settings')) {
    $email_notifications = sanitize_key($_POST['email_notifications'] ?? 'all');
    $profile_visibility = sanitize_key($_POST['profile_visibility'] ?? 'public');
    $show_level_badge = sanitize_key($_POST['show_level_badge'] ?? 'yes');
    
    update_user_meta($user_id, 'ygv_email_notifications', $email_notifications);
    update_user_meta($user_id, 'ygv_profile_visibility', $profile_visibility);
    update_user_meta($user_id, 'ygv_show_level_badge', $show_level_badge);
    
    $settings_saved = true;
}
?>

<div class="ygv-settings">
    
    <?php if (!empty($settings_saved)): ?>
    <div class="ygv-success-banner">
        <span>✅</span>
        <strong><?php echo esc_html__('Podešavanja su sačuvana!', 'hello-elementor-child'); ?></strong>
    </div>
    <?php endif; ?>
    
    <form method="post" class="ygv-settings-form">
        <?php wp_nonce_field('ygv_save_settings', 'ygv_settings_nonce'); ?>
        
        <!-- Email Notifications -->
        <div class="ygv-card">
            <h3><?php echo esc_html__('E-mail Obaveštenja', 'hello-elementor-child'); ?></h3>
            <p class="ygv-card-subtitle"><?php echo esc_html__('Izaberi koja obaveštenja želiš da primaš', 'hello-elementor-child'); ?></p>
            
            <div class="ygv-settings-group">
                <label class="ygv-radio-item">
                    <input type="radio" name="email_notifications" value="all" <?php checked($email_notifications, 'all'); ?>>
                    <span class="ygv-radio-label">
                        <strong><?php echo esc_html__('Sva obaveštenja', 'hello-elementor-child'); ?></strong>
                        <span><?php echo esc_html__('Dostignuća, novi nivoi, aktivnost na listama, vesti', 'hello-elementor-child'); ?></span>
                    </span>
                </label>
                
                <label class="ygv-radio-item">
                    <input type="radio" name="email_notifications" value="important" <?php checked($email_notifications, 'important'); ?>>
                    <span class="ygv-radio-label">
                        <strong><?php echo esc_html__('Samo važna', 'hello-elementor-child'); ?></strong>
                        <span><?php echo esc_html__('Novi nivoi i dostignuća', 'hello-elementor-child'); ?></span>
                    </span>
                </label>
                
                <label class="ygv-radio-item">
                    <input type="radio" name="email_notifications" value="none" <?php checked($email_notifications, 'none'); ?>>
                    <span class="ygv-radio-label">
                        <strong><?php echo esc_html__('Bez obaveštenja', 'hello-elementor-child'); ?></strong>
                        <span><?php echo esc_html__('Ne šalji mi e-mail obaveštenja', 'hello-elementor-child'); ?></span>
                    </span>
                </label>
            </div>
        </div>
        
        <!-- Privacy Settings -->
        <div class="ygv-card">
            <h3><?php echo esc_html__('Privatnost', 'hello-elementor-child'); ?></h3>
            <p class="ygv-card-subtitle"><?php echo esc_html__('Kontroliši ko može da vidi tvoj profil', 'hello-elementor-child'); ?></p>
            
            <div class="ygv-settings-group">
                <label class="ygv-radio-item">
                    <input type="radio" name="profile_visibility" value="public" <?php checked($profile_visibility, 'public'); ?>>
                    <span class="ygv-radio-label">
                        <strong><?php echo esc_html__('Javni profil', 'hello-elementor-child'); ?></strong>
                        <span><?php echo esc_html__('Svi mogu videti tvoj nivo i aktivnost', 'hello-elementor-child'); ?></span>
                    </span>
                </label>
                
                <label class="ygv-radio-item">
                    <input type="radio" name="profile_visibility" value="private" <?php checked($profile_visibility, 'private'); ?>>
                    <span class="ygv-radio-label">
                        <strong><?php echo esc_html__('Privatni profil', 'hello-elementor-child'); ?></strong>
                        <span><?php echo esc_html__('Samo ti možeš videti detalje profila', 'hello-elementor-child'); ?></span>
                    </span>
                </label>
            </div>
            
            <div class="ygv-setting-item">
                <label class="ygv-checkbox-item">
                    <input type="checkbox" name="show_level_badge" value="yes" <?php checked($show_level_badge, 'yes'); ?>>
                    <span class="ygv-checkbox-label">
                        <strong><?php echo esc_html__('Prikaži bedž nivoa', 'hello-elementor-child'); ?></strong>
                        <span><?php echo esc_html__('Prikaži tvoj nivo pored imena na listama i komentarima', 'hello-elementor-child'); ?></span>
                    </span>
                </label>
            </div>
        </div>
        
        <!-- Account Info -->
        <div class="ygv-card">
            <h3><?php echo esc_html__('Informacije o Nalogu', 'hello-elementor-child'); ?></h3>
            
            <div class="ygv-info-list">
                <div class="ygv-info-row">
                    <span class="ygv-info-label"><?php echo esc_html__('Korisničko ime', 'hello-elementor-child'); ?></span>
                    <span class="ygv-info-value"><?php echo esc_html($user->user_login); ?></span>
                </div>
                <div class="ygv-info-row">
                    <span class="ygv-info-label"><?php echo esc_html__('E-mail', 'hello-elementor-child'); ?></span>
                    <span class="ygv-info-value"><?php echo esc_html($user->user_email); ?></span>
                </div>
                <div class="ygv-info-row">
                    <span class="ygv-info-label"><?php echo esc_html__('Član od', 'hello-elementor-child'); ?></span>
                    <span class="ygv-info-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?></span>
                </div>
            </div>
            
            <p class="ygv-muted" style="margin-top: 16px; font-size: 13px;">
                <?php printf(
                    esc_html__('Za promenu e-maila ili korisničkog imena, kontaktiraj nas na %s', 'hello-elementor-child'),
                    '<a href="mailto:support@yugovote.com">support@yugovote.com</a>'
                ); ?>
            </p>
        </div>
        
        <div class="ygv-form-actions">
            <button type="submit" name="ygv_save_settings" class="ygv-btn ygv-btn-primary">
                <?php echo esc_html__('Sačuvaj Podešavanja', 'hello-elementor-child'); ?>
            </button>
        </div>
    </form>
</div>
