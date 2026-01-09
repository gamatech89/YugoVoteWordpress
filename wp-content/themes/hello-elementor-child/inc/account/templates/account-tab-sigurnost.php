<?php if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$user = wp_get_current_user();

// Password change handling
$password_error = '';
$password_success = false;

if (isset($_POST['ygv_change_password']) && wp_verify_nonce($_POST['ygv_security_nonce'], 'ygv_change_password')) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = __('Sva polja su obavezna.', 'hello-elementor-child');
    } elseif (!wp_check_password($current_password, $user->user_pass, $user_id)) {
        $password_error = __('Trenutna lozinka nije tačna.', 'hello-elementor-child');
    } elseif ($new_password !== $confirm_password) {
        $password_error = __('Nove lozinke se ne poklapaju.', 'hello-elementor-child');
    } elseif (strlen($new_password) < 8) {
        $password_error = __('Nova lozinka mora imati najmanje 8 karaktera.', 'hello-elementor-child');
    } else {
        wp_set_password($new_password, $user_id);
        // Re-login user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        $password_success = true;
    }
}

// Get last login info
$last_login = get_user_meta($user_id, 'ygv_last_login', true);
$login_count = (int) get_user_meta($user_id, 'ygv_login_count', true);
?>

<div class="ygv-security">
    
    <!-- Password Change -->
    <div class="ygv-card">
        <h3><?php echo esc_html__('Promena Lozinke', 'hello-elementor-child'); ?></h3>
        <p class="ygv-card-subtitle"><?php echo esc_html__('Redovno menjaj lozinku radi sigurnosti naloga', 'hello-elementor-child'); ?></p>
        
        <?php if ($password_success): ?>
        <div class="ygv-success-banner">
            <span>✅</span>
            <strong><?php echo esc_html__('Lozinka je uspešno promenjena!', 'hello-elementor-child'); ?></strong>
        </div>
        <?php endif; ?>
        
        <?php if ($password_error): ?>
        <div class="ygv-error-banner">
            <span>⚠️</span>
            <strong><?php echo esc_html($password_error); ?></strong>
        </div>
        <?php endif; ?>
        
        <form method="post" class="ygv-password-form">
            <?php wp_nonce_field('ygv_change_password', 'ygv_security_nonce'); ?>
            
            <div class="ygv-form-group">
                <label for="current_password"><?php echo esc_html__('Trenutna lozinka', 'hello-elementor-child'); ?></label>
                <input type="password" name="current_password" id="current_password" class="ygv-input" required>
            </div>
            
            <div class="ygv-form-group">
                <label for="new_password"><?php echo esc_html__('Nova lozinka', 'hello-elementor-child'); ?></label>
                <input type="password" name="new_password" id="new_password" class="ygv-input" minlength="8" required>
                <span class="ygv-form-hint"><?php echo esc_html__('Minimum 8 karaktera', 'hello-elementor-child'); ?></span>
            </div>
            
            <div class="ygv-form-group">
                <label for="confirm_password"><?php echo esc_html__('Potvrdi novu lozinku', 'hello-elementor-child'); ?></label>
                <input type="password" name="confirm_password" id="confirm_password" class="ygv-input" required>
            </div>
            
            <button type="submit" name="ygv_change_password" class="ygv-btn ygv-btn-primary">
                <?php echo esc_html__('Promeni Lozinku', 'hello-elementor-child'); ?>
            </button>
        </form>
    </div>
    
    <!-- Security Options -->
    <div class="ygv-card">
        <h3><?php echo esc_html__('Dodatne Opcije Sigurnosti', 'hello-elementor-child'); ?></h3>
        
        <div class="ygv-security-options">
            <div class="ygv-security-option">
                <div class="ygv-option-info">
                    <span class="ygv-option-icon">📱</span>
                    <div>
                        <strong><?php echo esc_html__('Dvofaktorska autentifikacija (2FA)', 'hello-elementor-child'); ?></strong>
                        <p><?php echo esc_html__('Dodaj dodatni nivo sigurnosti za tvoj nalog', 'hello-elementor-child'); ?></p>
                    </div>
                </div>
                <span class="ygv-coming-soon-badge"><?php echo esc_html__('Uskoro', 'hello-elementor-child'); ?></span>
            </div>
            
            <div class="ygv-security-option">
                <div class="ygv-option-info">
                    <span class="ygv-option-icon">🔌</span>
                    <div>
                        <strong><?php echo esc_html__('Aktivne sesije', 'hello-elementor-child'); ?></strong>
                        <p><?php echo esc_html__('Pregledaj i odjavi se sa drugih uređaja', 'hello-elementor-child'); ?></p>
                    </div>
                </div>
                <span class="ygv-coming-soon-badge"><?php echo esc_html__('Uskoro', 'hello-elementor-child'); ?></span>
            </div>
            
            <div class="ygv-security-option">
                <div class="ygv-option-info">
                    <span class="ygv-option-icon">📋</span>
                    <div>
                        <strong><?php echo esc_html__('Istorija aktivnosti', 'hello-elementor-child'); ?></strong>
                        <p><?php echo esc_html__('Pregledaj sve prijave i aktivnosti na nalogu', 'hello-elementor-child'); ?></p>
                    </div>
                </div>
                <span class="ygv-coming-soon-badge"><?php echo esc_html__('Uskoro', 'hello-elementor-child'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Danger Zone -->
    <div class="ygv-card ygv-danger-zone">
        <h3><?php echo esc_html__('Opasna Zona', 'hello-elementor-child'); ?></h3>
        <p class="ygv-card-subtitle"><?php echo esc_html__('Ove akcije su nepovratne', 'hello-elementor-child'); ?></p>
        
        <div class="ygv-danger-actions">
            <div class="ygv-danger-item">
                <div class="ygv-danger-info">
                    <strong><?php echo esc_html__('Deaktiviraj nalog', 'hello-elementor-child'); ?></strong>
                    <p><?php echo esc_html__('Privremeno deaktiviraj svoj nalog. Možeš ga ponovo aktivirati prijavom.', 'hello-elementor-child'); ?></p>
                </div>
                <button type="button" class="ygv-btn ygv-btn-outline-danger" disabled>
                    <?php echo esc_html__('Deaktiviraj', 'hello-elementor-child'); ?>
                </button>
            </div>
            
            <div class="ygv-danger-item">
                <div class="ygv-danger-info">
                    <strong><?php echo esc_html__('Obriši nalog', 'hello-elementor-child'); ?></strong>
                    <p><?php echo esc_html__('Trajno obriši nalog i sve podatke. Ova akcija se ne može poništiti.', 'hello-elementor-child'); ?></p>
                </div>
                <button type="button" class="ygv-btn ygv-btn-danger" disabled>
                    <?php echo esc_html__('Obriši Nalog', 'hello-elementor-child'); ?>
                </button>
            </div>
        </div>
        
        <p class="ygv-muted" style="margin-top: 16px; font-size: 12px;">
            <?php echo esc_html__('Za brisanje naloga, kontaktiraj podršku na support@yugovote.com', 'hello-elementor-child'); ?>
        </p>
    </div>
</div>
