<?php
/**
 * Template Name: Complete Profile Page
 * Template Post Type: page
 *
 * This template displays the form for users to add additional profile details
 * like gender, date of birth, country, and points of interest, typically after 
 * initial registration or if these details are missing.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This page is only for logged-in users.
if (!is_user_logged_in()) {
    // Redirect to login page if not logged in. Ensure CUSTOM_LOGIN_PAGE_SLUG is defined.
    $login_page_url = defined('CUSTOM_LOGIN_PAGE_SLUG') ? home_url('/' . CUSTOM_LOGIN_PAGE_SLUG . '/') : wp_login_url(get_permalink());
    wp_redirect($login_page_url);
    exit;
}

$current_user_id = get_current_user_id();

// Optional: Check if profile is already "complete" based on your criteria.
// If so, you might redirect to the main account page.
// Example (define your own criteria for 'cs_is_user_profile_truly_complete'):
// if (function_exists('cs_is_user_profile_truly_complete') && cs_is_user_profile_truly_complete($current_user_id)) {
//    if (defined('CUSTOM_ACCOUNT_PAGE_SLUG')) {
//        wp_redirect(home_url('/' . CUSTOM_ACCOUNT_PAGE_SLUG . '/'));
//        exit;
//    }
// }

// Fetch parent categories for "Points of Interest"
$parent_voting_list_categories = get_terms([
    'taxonomy'   => 'voting_list_category', // Your taxonomy for list categories
    'parent'     => 0,                      // Only top-level categories
    'hide_empty' => false,                  // Show all, even if no lists are in them yet
    'orderby'    => 'name',
    'order'      => 'ASC'
]);

// --- Populate with existing user meta for pre-filling the form ---
// Check if coming from a redirect with old input (not implemented in handler yet, but good for future)
$user_gender        = isset($_GET['gender']) ? sanitize_text_field(wp_unslash($_GET['gender'])) : get_user_meta($current_user_id, '_user_gender', true);
$user_dob_day       = isset($_GET['dob_day']) ? intval($_GET['dob_day']) : get_user_meta($current_user_id, '_user_dob_day', true);
$user_dob_month     = isset($_GET['dob_month']) ? intval($_GET['dob_month']) : get_user_meta($current_user_id, '_user_dob_month', true);
$user_dob_year      = isset($_GET['dob_year']) ? intval($_GET['dob_year']) : get_user_meta($current_user_id, '_user_dob_year', true);
$user_country       = isset($_GET['country']) ? sanitize_text_field(wp_unslash($_GET['country'])) : get_user_meta($current_user_id, '_user_country', true);
$user_interests     = get_user_meta($current_user_id, '_user_points_of_interest', true); // This will be an array

if (!is_array($user_interests)) {
    $user_interests = []; // Ensure it's an array for in_array() checks
}

// Example country list - replace with a comprehensive list or a helper function
$countries = [
    "Srbija" => "Srbija",
    "Hrvatska" => "Hrvatska",
    "Bosna i Hercegovina" => "Bosna i Hercegovina",
    "Crna Gora" => "Crna Gora",
    "Makedonija" => "Makedonija", // Severna Makedonija might be more current
    "Slovenija" => "Slovenija",
    "Other" => "Drugo (Other)" // Example
];
// Consider fetching a standard country list for better UX and data consistency.

get_header(); 
?>

<main id="site-content" role="main" class="cs-custom-auth-page cs-complete-profile-page">
    <div class="cs-auth-container">
        <div class="cs-auth-form-wrapper">
            <h1 class="cs-auth-title"><?php esc_html_e('Kompletirajte Vaš Nalog', 'your-text-domain'); // Complete Your Account ?></h1>
            
            <?php if (isset($_GET['new_registration']) && $_GET['new_registration'] === 'true') : ?>
                <p class="cs-auth-message cs-auth-info"><?php esc_html_e('Hvala na registraciji! Molimo Vas unesite još nekoliko detalja.', 'your-text-domain'); // Thank you for registering! Please provide a few more details. ?></p>
            <?php else: ?>
                <p class="cs-auth-message cs-auth-info"><?php esc_html_e('Molimo Vas unesite dodatne informacije o Vašem nalogu.', 'your-text-domain'); // Please provide additional information for your account. ?></p>
            <?php endif; ?>


            <?php
            // Display form submission errors stored in a transient
            $transient_key = 'complete_profile_errors_' . md5($_SERVER['REMOTE_ADDR'] . $current_user_id);
            $profile_errors = get_transient($transient_key);

            if ($profile_errors && is_array($profile_errors)) {
                echo '<div class="cs-auth-message cs-auth-error"><ul>';
                foreach ($profile_errors as $error_message) {
                    echo '<li>' . esc_html($error_message) . '</li>';
                }
                echo '</ul></div>';
                delete_transient($transient_key); 
            } elseif (isset($_GET['profile_update']) && $_GET['profile_update'] === 'failed' && empty($profile_errors)) {
                // Generic message if specific errors aren't set in transient but redirect indicates failure
                 echo '<p class="cs-auth-message cs-auth-error">' . esc_html__('Došlo je do greške prilikom čuvanja podataka. Molimo pokušajte ponovo.', 'your-text-domain') . '</p>';
            }
            ?>

            <form id="cs-complete-profile-form" class="cs-custom-form" action="<?php echo esc_url(get_permalink()); // Submit to the current page ?>" method="post">
                
                <fieldset class="cs-form-fieldset">
                    <legend><?php esc_html_e('Osnovne Informacije', 'your-text-domain'); // Basic Information ?></legend>
                    
                    <p class="form-row form-row-gender">
                        <label><?php esc_html_e('Pol', 'your-text-domain'); // Gender ?></label>
                        <span class="cs-radio-group">
                            <label for="gender_male"><input type="radio" name="user_gender" id="gender_male" value="male" <?php checked($user_gender, 'male'); ?>> <?php esc_html_e('Muški', 'your-text-domain'); // Male ?></label>
                            <label for="gender_female"><input type="radio" name="user_gender" id="gender_female" value="female" <?php checked($user_gender, 'female'); ?>> <?php esc_html_e('Ženski', 'your-text-domain'); // Female ?></label>
                            <label for="gender_other"><input type="radio" name="user_gender" id="gender_other" value="other" <?php checked($user_gender, 'other'); ?>> <?php esc_html_e('Drugo', 'your-text-domain'); // Other ?></label>
                            <label for="gender_prefer_not_to_say"><input type="radio" name="user_gender" id="gender_prefer_not_to_say" value="prefer_not_to_say" <?php checked($user_gender, 'prefer_not_to_say', true); // Default to this if nothing else is checked ?>> <?php esc_html_e('Ne želim da navedem', 'your-text-domain'); // Prefer not to say ?></label>
                        </span>
                    </p>

                    <p class="form-row form-row-dob">
                        <label><?php esc_html_e('Datum Rođenja', 'your-text-domain'); // Date of Birth ?></label>
                        <span class="cs-dob-group">
                            <select name="user_dob_day" id="user_dob_day" aria-label="<?php esc_attr_e('Day of Birth', 'your-text-domain'); ?>">
                                <option value=""><?php esc_html_e('Dan', 'your-text-domain'); // Day ?></option>
                                <?php for ($i = 1; $i <= 31; $i++) : ?>
                                    <option value="<?php echo $i; ?>" <?php selected($user_dob_day, $i); ?>><?php printf('%02d', $i); ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="user_dob_month" id="user_dob_month" aria-label="<?php esc_attr_e('Month of Birth', 'your-text-domain'); ?>">
                                <option value=""><?php esc_html_e('Mesec', 'your-text-domain'); // Month ?></option>
                                <?php for ($i = 1; $i <= 12; $i++) : ?>
                                    <option value="<?php echo $i; ?>" <?php selected($user_dob_month, $i); ?>><?php echo esc_html(DateTime::createFromFormat('!m', $i)->format('F')); // Full month name, localized by WP date settings if possible ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="user_dob_year" id="user_dob_year" aria-label="<?php esc_attr_e('Year of Birth', 'your-text-domain'); ?>">
                                <option value=""><?php esc_html_e('Godina', 'your-text-domain'); // Year ?></option>
                                <?php $current_year = date('Y'); for ($i = $current_year; $i >= $current_year - 100; $i--) : // Approx 100 years range ?>
                                    <option value="<?php echo $i; ?>" <?php selected($user_dob_year, $i); ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </span>
                    </p>

                    <p class="form-row form-row-country">
                        <label for="user_country"><?php esc_html_e('Država', 'your-text-domain'); // Country ?></label>
                        <select name="user_country" id="user_country" class="widefat">
                            <option value=""><?php esc_html_e('-- Izaberite državu --', 'your-text-domain'); // -- Select Country -- ?></option>
                            <?php foreach ($countries as $country_code => $country_name) : // Using key as value, name as label from example array ?>
                                <option value="<?php echo esc_attr($country_code); ?>" <?php selected($user_country, $country_code); ?>><?php echo esc_html($country_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                         <small class="description"><?php esc_html_e('Molimo Vas izaberite Vašu državu prebivališta.', 'your-text-domain'); ?></small>
                    </p>
                </fieldset>

                <?php if (!empty($parent_voting_list_categories) && !is_wp_error($parent_voting_list_categories)) : ?>
                <fieldset class="cs-form-fieldset">
                    <legend><?php esc_html_e('Interesovanja', 'your-text-domain'); // Points of Interest ?></legend>
                    <p><?php esc_html_e('Izaberite kategorije lista koje Vas najviše interesuju:', 'your-text-domain'); // Select list categories that interest you most: ?></p>
                    <div class="cs-checkbox-group cs-points-of-interest">
                        <?php foreach ($parent_voting_list_categories as $category) : ?>
                            <label for="interest_<?php echo esc_attr($category->term_id); ?>" class="cs-checkbox-label">
                                <input type="checkbox" 
                                       name="points_of_interest[]" 
                                       id="interest_<?php echo esc_attr($category->term_id); ?>" 
                                       value="<?php echo esc_attr($category->term_id); ?>"
                                       <?php checked(in_array((string)$category->term_id, array_map('strval', $user_interests), true)); ?>>
                                <?php echo esc_html($category->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <?php endif; ?>
                
                <p class="form-submit">
                    <?php wp_nonce_field('custom_complete_profile_action', 'custom_complete_profile_nonce'); ?>
                    <input type="hidden" name="cs_custom_complete_profile_form" value="1" />
                    <input type="submit" name="wp-submit-complete-profile" id="wp-submit-complete-profile" class="button button-primary widefat" value="<?php esc_attr_e('Sačuvaj podatke i završi', 'your-text-domain'); // Save data and finish ?>" />
                </p>
                
            </form>

            <p class="cs-auth-links cs-auth-skip-link">
                <?php // Ensure CUSTOM_ACCOUNT_PAGE_SLUG is defined ?>
                <a href="<?php echo esc_url(home_url('/' . (defined('CUSTOM_ACCOUNT_PAGE_SLUG') ? CUSTOM_ACCOUNT_PAGE_SLUG : 'moj-nalog') . '/')); ?>"><?php esc_html_e('Preskoči za sada / Idi na Moj Nalog', 'your-text-domain'); // Skip for now / Go to My Account ?></a>
            </p>

        </div>
    </div>
</main>

<?php
get_footer(); 
?>