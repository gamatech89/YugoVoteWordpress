<?php
/**
 * Theme Configuration Constants
 *
 * Defines global constants used throughout the theme, such as custom page slugs,
 * API keys (though be careful with version control for sensitive keys), or other settings.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// =========================================================================
// Custom Page Slugs for Authentication & Account Management
// =========================================================================
// Please ensure these slugs match the actual slugs of your WordPress pages.

define('CUSTOM_LOGIN_PAGE_SLUG', 'login');          
define('CUSTOM_REGISTER_PAGE_SLUG', 'registracija');  
define('CUSTOM_COMPLETE_PROFILE_PAGE_SLUG', 'kompletiranje-naloga'); 
define('CUSTOM_ACCOUNT_PAGE_SLUG', 'moj-nalog');             

?>