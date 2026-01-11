<?php
/**
 * Template Name: Uredi Profil
 * Template Post Type: page
 *
 * Edit profile page for logged-in users.
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
$display_name = $current_user->display_name;
$first_name = $current_user->first_name;
$last_name = $current_user->last_name;

if (!is_array($user_interests)) {
    $user_interests = [];
}

// Countries list with flags
$countries = [
    "Srbija" => ["flag" => "🇷🇸", "name" => "Srbija"],
    "Hrvatska" => ["flag" => "🇭🇷", "name" => "Hrvatska"],
    "Bosna i Hercegovina" => ["flag" => "🇧🇦", "name" => "Bosna i Hercegovina"],
    "Crna Gora" => ["flag" => "🇲🇪", "name" => "Crna Gora"],
    "Makedonija" => ["flag" => "🇲🇰", "name" => "Severna Makedonija"],
    "Slovenija" => ["flag" => "🇸🇮", "name" => "Slovenija"],
    "Other" => ["flag" => "🌍", "name" => "Drugo"]
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
        $new_display_name = sanitize_text_field($_POST['display_name'] ?? '');
        $new_first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $new_last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $new_gender = sanitize_text_field($_POST['gender'] ?? '');
        $new_dob_day = sanitize_text_field($_POST['dob_day'] ?? '');
        $new_dob_month = sanitize_text_field($_POST['dob_month'] ?? '');
        $new_dob_year = sanitize_text_field($_POST['dob_year'] ?? '');
        $new_country = sanitize_text_field($_POST['country'] ?? '');
        $new_interests = isset($_POST['interests']) ? array_map('intval', $_POST['interests']) : [];
        
        // Update user data
        $update_data = ['ID' => $current_user_id];
        if ($new_display_name) $update_data['display_name'] = $new_display_name;
        if ($new_first_name !== $first_name) $update_data['first_name'] = $new_first_name;
        if ($new_last_name !== $last_name) $update_data['last_name'] = $new_last_name;
        
        if (count($update_data) > 1) {
            wp_update_user($update_data);
            $display_name = $new_display_name ?: $display_name;
            $first_name = $new_first_name;
            $last_name = $new_last_name;
        }
        
        update_user_meta($current_user_id, '_user_gender', $new_gender);
        $user_gender = $new_gender;
        
        if ($new_dob_year && $new_dob_month && $new_dob_day) {
            $new_dob = sprintf('%04d-%02d-%02d', $new_dob_year, $new_dob_month, $new_dob_day);
            update_user_meta($current_user_id, '_user_dob', $new_dob);
            $user_dob_year = $new_dob_year;
            $user_dob_month = $new_dob_month;
            $user_dob_day = $new_dob_day;
        }
        
        update_user_meta($current_user_id, '_user_country', $new_country);
        $user_country = $new_country;
        
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
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        :root {
            --ygv-primary: #2d3a8c;
            --ygv-primary-light: rgba(45, 58, 140, 0.08);
            --ygv-border: #e2e8f0;
            --ygv-text: #1e293b;
            --ygv-text-muted: #64748b;
        }
        html, body { margin: 0; padding: 0; height: 100%; }
        .ygv-edit-profile-page { min-height: 100vh; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); }
        .ygv-edit-profile-container { max-width: 560px; margin: 0 auto; padding: 80px 20px 40px; }
        
        .ygv-edit-back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255,255,255,0.95);
            border: 1px solid var(--ygv-border);
            border-radius: 10px;
            color: var(--ygv-text);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .ygv-edit-back-btn:hover { background: var(--ygv-primary); color: white; border-color: var(--ygv-primary); }
        .ygv-edit-back-btn svg { width: 18px; height: 18px; }
        
        .ygv-edit-card {
            background: white;
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .ygv-edit-card h1 { font-size: 26px; font-weight: 700; margin: 0 0 6px; color: var(--ygv-text); }
        .ygv-edit-card .subtitle { color: var(--ygv-text-muted); margin: 0 0 28px; font-size: 15px; }
        
        .ygv-form-group { margin-bottom: 24px; }
        .ygv-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .ygv-form-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .ygv-form-label .icon { color: var(--ygv-primary); opacity: 0.7; }
        .ygv-form-hint { font-size: 12px; color: var(--ygv-text-muted); margin-top: 4px; }
        
        .ygv-form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--ygv-border);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.2s;
            box-sizing: border-box;
            background: #fafbfc;
        }
        .ygv-form-input:focus { outline: none; border-color: var(--ygv-primary); background: white; box-shadow: 0 0 0 4px var(--ygv-primary-light); }
        .ygv-form-select { 
            background: #fafbfc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23374151' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 16px center; 
            appearance: none; 
            padding-right: 40px; 
            cursor: pointer; 
        }
        
        /* Gender Cards */
        .ygv-gender-options { display: flex; gap: 12px; }
        .ygv-gender-option {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 14px;
            border: 2px solid var(--ygv-border);
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafbfc;
        }
        .ygv-gender-option:hover { border-color: #cbd5e1; background: white; }
        .ygv-gender-option.selected { border-color: var(--ygv-primary); background: var(--ygv-primary-light); }
        .ygv-gender-option input { display: none; }
        .ygv-gender-option .emoji { font-size: 32px; margin-bottom: 8px; }
        .ygv-gender-option .label { font-size: 13px; font-weight: 600; color: #374151; }
        .ygv-gender-option.selected .label { color: var(--ygv-primary); }
        
        /* DOB Row */
        .ygv-dob-row { display: grid; grid-template-columns: 1fr 1.5fr 1fr; gap: 10px; }
        
        /* Country Select with Flag */
        .ygv-country-select { position: relative; }
        .ygv-country-select .flag-preview {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            pointer-events: none;
        }
        .ygv-country-select select { padding-left: 48px; }
        
        /* Interest Chips - matching kompletiranje-naloga style */
        .ygv-interests-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .ygv-interest-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border: 2px solid var(--ygv-border);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            background: #fafbfc;
            user-select: none;
        }
        .ygv-interest-chip:hover { border-color: #cbd5e1; background: white; }
        .ygv-interest-chip.selected { 
            border-color: var(--ygv-primary); 
            background: var(--ygv-primary); 
            color: white; 
        }
        .ygv-interest-chip input { display: none; }
        
        /* Save Button */
        .ygv-btn-save {
            width: 100%;
            padding: 16px 24px;
            background: var(--ygv-primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }
        .ygv-btn-save:hover { background: #1e2a6e; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(45, 58, 140, 0.3); }
        
        /* Messages */
        .ygv-message { padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 500; }
        .ygv-message--success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .ygv-message--error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        
        @media (max-width: 640px) {
            .ygv-edit-profile-container { padding: 70px 16px 30px; }
            .ygv-edit-card { padding: 28px 20px; border-radius: 16px; }
            .ygv-edit-back-btn { top: 12px; left: 12px; padding: 8px 14px; font-size: 13px; }
            .ygv-form-row { grid-template-columns: 1fr; }
            .ygv-gender-options { gap: 8px; }
            .ygv-gender-option { padding: 14px 10px; }
            .ygv-gender-option .emoji { font-size: 26px; }
            .ygv-dob-row { grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        }
    </style>
</head>
<body <?php body_class('ygv-edit-profile-page'); ?>>

<a href="<?php echo esc_url(home_url('/moj-nalog/')); ?>" class="ygv-edit-back-btn">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Moj Nalog
</a>

<div class="ygv-edit-profile-container">
    <div class="ygv-edit-card">
        <h1><?php echo esc_html__('Uredi Profil', 'hello-elementor-child'); ?></h1>
        <p class="subtitle"><?php echo esc_html__('Ažuriraj svoje podatke', 'hello-elementor-child'); ?></p>
        
        <?php if ($message): ?>
            <div class="ygv-message ygv-message--<?php echo esc_attr($message_type); ?>"><?php echo esc_html($message); ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('ygv_edit_profile', 'ygv_edit_profile_nonce'); ?>
            
            <!-- Display Name -->
            <div class="ygv-form-group">
                <label class="ygv-form-label">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?php echo esc_html__('Ime za prikaz', 'hello-elementor-child'); ?>
                </label>
                <input type="text" name="display_name" class="ygv-form-input" value="<?php echo esc_attr($display_name); ?>" placeholder="Kako želiš da te zovemo">
            </div>
            
            <!-- First & Last Name (Optional) -->
            <div class="ygv-form-group">
                <div class="ygv-form-row">
                    <div>
                        <label class="ygv-form-label"><?php echo esc_html__('Ime', 'hello-elementor-child'); ?> <span style="color: #94a3b8; font-weight: 400;">(opciono)</span></label>
                        <input type="text" name="first_name" class="ygv-form-input" value="<?php echo esc_attr($first_name); ?>" placeholder="Tvoje ime">
                    </div>
                    <div>
                        <label class="ygv-form-label"><?php echo esc_html__('Prezime', 'hello-elementor-child'); ?> <span style="color: #94a3b8; font-weight: 400;">(opciono)</span></label>
                        <input type="text" name="last_name" class="ygv-form-input" value="<?php echo esc_attr($last_name); ?>" placeholder="Tvoje prezime">
                    </div>
                </div>
            </div>
            
            <!-- Gender -->
            <div class="ygv-form-group">
                <label class="ygv-form-label">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                    <?php echo esc_html__('Pol', 'hello-elementor-child'); ?>
                </label>
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
                <label class="ygv-form-label">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php echo esc_html__('Datum rođenja', 'hello-elementor-child'); ?>
                </label>
                <div class="ygv-dob-row">
                    <select name="dob_day" class="ygv-form-input ygv-form-select">
                        <option value=""><?php echo esc_html__('Dan', 'hello-elementor-child'); ?></option>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?php echo str_pad($d, 2, '0', STR_PAD_LEFT); ?>" <?php selected($user_dob_day, str_pad($d, 2, '0', STR_PAD_LEFT)); ?>><?php echo str_pad($d, 2, '0', STR_PAD_LEFT); ?></option>
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
            
            <!-- Country with Flag -->
            <div class="ygv-form-group">
                <label class="ygv-form-label">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?php echo esc_html__('Država', 'hello-elementor-child'); ?>
                </label>
                <div class="ygv-country-select">
                    <?php 
                    $current_flag = $user_country && isset($countries[$user_country]) ? $countries[$user_country]['flag'] : '🌍';
                    ?>
                    <span class="flag-preview" id="country-flag"><?php echo $current_flag; ?></span>
                    <select name="country" class="ygv-form-input ygv-form-select" id="country-select">
                        <option value=""><?php echo esc_html__('Izaberi državu', 'hello-elementor-child'); ?></option>
                        <?php foreach ($countries as $code => $data): ?>
                            <option value="<?php echo esc_attr($code); ?>" data-flag="<?php echo esc_attr($data['flag']); ?>" <?php selected($user_country, $code); ?>>
                                <?php echo esc_html($data['flag'] . ' ' . $data['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Interests with pill-style chips -->
            <div class="ygv-form-group">
                <label class="ygv-form-label">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <?php echo esc_html__('Interesovanja', 'hello-elementor-child'); ?>
                </label>
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
            
            <button type="submit" class="ygv-btn-save"><?php echo esc_html__('Sačuvaj Promene', 'hello-elementor-child'); ?></button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gender selection
    document.querySelectorAll('.ygv-gender-option').forEach(function(opt) {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.ygv-gender-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    
    // Interest chips toggle
    document.querySelectorAll('.ygv-interest-chip').forEach(function(chip) {
        chip.addEventListener('click', function(e) {
            e.preventDefault();
            const checkbox = this.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            this.classList.toggle('selected', checkbox.checked);
        });
    });
    
    // Country flag update
    const countrySelect = document.getElementById('country-select');
    const flagPreview = document.getElementById('country-flag');
    if (countrySelect && flagPreview) {
        countrySelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            flagPreview.textContent = selected.dataset.flag || '🌍';
        });
    }
});
</script>

<?php wp_footer(); ?>
</body>
</html>
