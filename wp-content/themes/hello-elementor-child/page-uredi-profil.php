<?php
/**
 * Template Name: Uredi Profil
 * Template Post Type: page
 *
 * Edit profile page for logged-in users.
 * Similar structure to complete-profile but for existing users.
 */

if (!defined('ABSPATH')) exit;

// Require login
if (!is_user_logged_in()) {
    wp_redirect(home_url('/prijava/'));
    exit;
}

$current_user_id = get_current_user_id();
$current_user = wp_get_current_user();

// Get existing user data
$user_gender = get_user_meta($current_user_id, '_user_gender', true);
$user_dob = get_user_meta($current_user_id, '_user_dob', true);
$user_dob_parts = $user_dob ? explode('-', $user_dob) : ['', '', ''];
$user_dob_year = $user_dob_parts[0] ?? '';
$user_dob_month = $user_dob_parts[1] ?? '';
$user_dob_day = $user_dob_parts[2] ?? '';
$user_country = get_user_meta($current_user_id, '_user_country', true);
$user_interests = get_user_meta($current_user_id, '_user_points_of_interest', true);
$user_referral = get_user_meta($current_user_id, '_user_referral_source', true);
$display_name = $current_user->display_name;

if (!is_array($user_interests)) {
    $user_interests = [];
}

// Countries list
$countries = [
    "Srbija" => "🇷🇸 Srbija",
    "Hrvatska" => "🇭🇷 Hrvatska",
    "Bosna i Hercegovina" => "🇧🇦 Bosna i Hercegovina",
    "Crna Gora" => "🇲🇪 Crna Gora",
    "Makedonija" => "🇲🇰 Severna Makedonija",
    "Slovenija" => "🇸🇮 Slovenija",
    "Other" => "🌍 Drugo"
];

// Get parent categories for interests
$parent_categories = get_terms([
    'taxonomy'   => 'voting_list_category',
    'parent'     => 0,
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC'
]);

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ygv_edit_profile_nonce'])) {
    if (wp_verify_nonce($_POST['ygv_edit_profile_nonce'], 'ygv_edit_profile')) {
        // Sanitize inputs
        $new_display_name = sanitize_text_field($_POST['display_name'] ?? '');
        $new_gender = sanitize_text_field($_POST['gender'] ?? '');
        $new_dob_day = sanitize_text_field($_POST['dob_day'] ?? '');
        $new_dob_month = sanitize_text_field($_POST['dob_month'] ?? '');
        $new_dob_year = sanitize_text_field($_POST['dob_year'] ?? '');
        $new_country = sanitize_text_field($_POST['country'] ?? '');
        $new_interests = isset($_POST['interests']) ? array_map('intval', $_POST['interests']) : [];
        
        // Update display name
        if ($new_display_name && $new_display_name !== $current_user->display_name) {
            wp_update_user(['ID' => $current_user_id, 'display_name' => $new_display_name]);
            $display_name = $new_display_name;
        }
        
        // Update gender
        update_user_meta($current_user_id, '_user_gender', $new_gender);
        $user_gender = $new_gender;
        
        // Update DOB
        if ($new_dob_year && $new_dob_month && $new_dob_day) {
            $new_dob = sprintf('%04d-%02d-%02d', $new_dob_year, $new_dob_month, $new_dob_day);
            update_user_meta($current_user_id, '_user_dob', $new_dob);
            $user_dob = $new_dob;
            $user_dob_year = $new_dob_year;
            $user_dob_month = $new_dob_month;
            $user_dob_day = $new_dob_day;
        }
        
        // Update country
        update_user_meta($current_user_id, '_user_country', $new_country);
        $user_country = $new_country;
        
        // Update interests
        update_user_meta($current_user_id, '_user_points_of_interest', $new_interests);
        $user_interests = $new_interests;
        
        $message = __('Profil je uspešno ažuriran!', 'hello-elementor-child');
        $message_type = 'success';
    } else {
        $message = __('Greška pri ažuriranju. Pokušajte ponovo.', 'hello-elementor-child');
        $message_type = 'error';
    }
}

wp_enqueue_style('ygv-templates', get_stylesheet_directory_uri() . '/css/templates.css', [], '1.0.0');
wp_enqueue_style('ygv-account', get_stylesheet_directory_uri() . '/css/account.css', [], '1.0.0');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; }
        .ygv-edit-profile-page { min-height: 100vh; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); }
        .ygv-edit-profile-container { max-width: 600px; margin: 0 auto; padding: 80px 20px 40px; }
        .ygv-edit-back-btn {
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
        .ygv-edit-back-btn:hover {
            background: var(--ygv-primary, #2d3a8c);
            color: white;
            border-color: var(--ygv-primary, #2d3a8c);
        }
        .ygv-edit-back-btn svg { width: 18px; height: 18px; }
        .ygv-edit-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .ygv-edit-card h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px;
            color: #1e293b;
        }
        .ygv-edit-card .subtitle {
            color: #64748b;
            margin: 0 0 24px;
        }
        .ygv-form-group { margin-bottom: 20px; }
        .ygv-form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .ygv-form-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        .ygv-form-input:focus {
            outline: none;
            border-color: var(--ygv-primary, #2d3a8c);
            box-shadow: 0 0 0 3px rgba(45, 58, 140, 0.1);
        }
        .ygv-form-select { background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23374151' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 14px center; appearance: none; padding-right: 36px; cursor: pointer; }
        .ygv-gender-options { display: flex; gap: 12px; }
        .ygv-gender-option {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ygv-gender-option:hover { border-color: #cbd5e1; }
        .ygv-gender-option.selected { border-color: var(--ygv-primary, #2d3a8c); background: rgba(45, 58, 140, 0.05); }
        .ygv-gender-option input { display: none; }
        .ygv-gender-option .emoji { font-size: 28px; margin-bottom: 6px; }
        .ygv-gender-option .label { font-size: 13px; font-weight: 500; color: #374151; }
        .ygv-dob-row { display: flex; gap: 10px; }
        .ygv-dob-row select { flex: 1; }
        .ygv-interests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
        }
        .ygv-interest-chip {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        .ygv-interest-chip:hover { border-color: #cbd5e1; }
        .ygv-interest-chip.selected { border-color: var(--ygv-primary, #2d3a8c); background: rgba(45, 58, 140, 0.05); }
        .ygv-interest-chip input { display: none; }
        .ygv-btn-save {
            width: 100%;
            padding: 14px 24px;
            background: var(--ygv-primary, #2d3a8c);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ygv-btn-save:hover { background: #1e2a6e; }
        .ygv-message {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .ygv-message--success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .ygv-message--error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        @media (max-width: 768px) {
            .ygv-edit-profile-container { padding: 70px 16px 30px; }
            .ygv-edit-card { padding: 24px 20px; }
            .ygv-edit-back-btn { top: 12px; left: 12px; padding: 8px 14px; font-size: 13px; }
            .ygv-gender-options { flex-wrap: wrap; }
            .ygv-gender-option { flex: 0 0 calc(33.33% - 8px); }
        }
    </style>
</head>
<body <?php body_class('ygv-edit-profile-page'); ?>>

<a href="<?php echo esc_url(home_url('/moj-nalog/')); ?>" class="ygv-edit-back-btn">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
    </svg>
    Moj Nalog
</a>

<div class="ygv-edit-profile-container">
    <div class="ygv-edit-card">
        <h1><?php echo esc_html__('Uredi Profil', 'hello-elementor-child'); ?></h1>
        <p class="subtitle"><?php echo esc_html__('Ažuriraj svoje podatke', 'hello-elementor-child'); ?></p>
        
        <?php if ($message): ?>
            <div class="ygv-message ygv-message--<?php echo esc_attr($message_type); ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('ygv_edit_profile', 'ygv_edit_profile_nonce'); ?>
            
            <!-- Display Name -->
            <div class="ygv-form-group">
                <label class="ygv-form-label"><?php echo esc_html__('Ime za prikaz', 'hello-elementor-child'); ?></label>
                <input type="text" name="display_name" class="ygv-form-input" value="<?php echo esc_attr($display_name); ?>">
            </div>
            
            <!-- Gender -->
            <div class="ygv-form-group">
                <label class="ygv-form-label"><?php echo esc_html__('Pol', 'hello-elementor-child'); ?></label>
                <div class="ygv-gender-options">
                    <label class="ygv-gender-option <?php echo $user_gender === 'male' ? 'selected' : ''; ?>">
                        <input type="radio" name="gender" value="male" <?php checked($user_gender, 'male'); ?>>
                        <span class="emoji">👨</span>
                        <span class="label"><?php echo esc_html__('Muški', 'hello-elementor-child'); ?></span>
                    </label>
                    <label class="ygv-gender-option <?php echo $user_gender === 'female' ? 'selected' : ''; ?>">
                        <input type="radio" name="gender" value="female" <?php checked($user_gender, 'female'); ?>>
                        <span class="emoji">👩</span>
                        <span class="label"><?php echo esc_html__('Ženski', 'hello-elementor-child'); ?></span>
                    </label>
                    <label class="ygv-gender-option <?php echo $user_gender === 'other' ? 'selected' : ''; ?>">
                        <input type="radio" name="gender" value="other" <?php checked($user_gender, 'other'); ?>>
                        <span class="emoji">😊</span>
                        <span class="label"><?php echo esc_html__('Drugo', 'hello-elementor-child'); ?></span>
                    </label>
                </div>
            </div>
            
            <!-- Date of Birth -->
            <div class="ygv-form-group">
                <label class="ygv-form-label"><?php echo esc_html__('Datum rođenja', 'hello-elementor-child'); ?></label>
                <div class="ygv-dob-row">
                    <select name="dob_day" class="ygv-form-input ygv-form-select">
                        <option value=""><?php echo esc_html__('Dan', 'hello-elementor-child'); ?></option>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?php echo $d; ?>" <?php selected($user_dob_day, str_pad($d, 2, '0', STR_PAD_LEFT)); ?>><?php echo str_pad($d, 2, '0', STR_PAD_LEFT); ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="dob_month" class="ygv-form-input ygv-form-select">
                        <option value=""><?php echo esc_html__('Mesec', 'hello-elementor-child'); ?></option>
                        <?php 
                        $months = ['Januar', 'Februar', 'Mart', 'April', 'Maj', 'Jun', 'Jul', 'Avgust', 'Septembar', 'Oktobar', 'Novembar', 'Decembar'];
                        foreach ($months as $i => $month): 
                            $m = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                        ?>
                            <option value="<?php echo $m; ?>" <?php selected($user_dob_month, $m); ?>><?php echo esc_html($month); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dob_year" class="ygv-form-input ygv-form-select">
                        <option value=""><?php echo esc_html__('Godina', 'hello-elementor-child'); ?></option>
                        <?php for ($y = date('Y') - 10; $y >= 1940; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php selected($user_dob_year, $y); ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <!-- Country -->
            <div class="ygv-form-group">
                <label class="ygv-form-label"><?php echo esc_html__('Država', 'hello-elementor-child'); ?></label>
                <select name="country" class="ygv-form-input ygv-form-select">
                    <option value=""><?php echo esc_html__('Izaberi državu', 'hello-elementor-child'); ?></option>
                    <?php foreach ($countries as $code => $label): ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($user_country, $code); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Interests -->
            <div class="ygv-form-group">
                <label class="ygv-form-label"><?php echo esc_html__('Interesovanja', 'hello-elementor-child'); ?></label>
                <div class="ygv-interests-grid">
                    <?php foreach ($parent_categories as $cat): 
                        $is_selected = in_array($cat->term_id, $user_interests);
                    ?>
                        <label class="ygv-interest-chip <?php echo $is_selected ? 'selected' : ''; ?>">
                            <input type="checkbox" name="interests[]" value="<?php echo esc_attr($cat->term_id); ?>" <?php checked($is_selected); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="ygv-btn-save">
                <?php echo esc_html__('Sačuvaj Promene', 'hello-elementor-child'); ?>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gender selection toggle
    document.querySelectorAll('.ygv-gender-option').forEach(function(option) {
        option.addEventListener('click', function() {
            document.querySelectorAll('.ygv-gender-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    
    // Interest chips toggle
    document.querySelectorAll('.ygv-interest-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            this.classList.toggle('selected');
        });
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
