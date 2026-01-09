<?php
/**
 * Video Popup Integration
 *
 * This code block defines a function to include the HTML markup for a global video popup
 * into the site's footer. It ensures the popup is available on front-end pages
 * where video playback might be initiated.
 *
 * @package HelloElementorChild
 */


if (!function_exists('cs_add_video_popup_to_footer')) {
    /**
     * Includes the video popup HTML template in the website's footer.
     *
     * This function checks if the current view is the WordPress admin area
     * and exits if so, ensuring the popup is only added to front-end pages.
     * It then locates and includes the popup's template file.
     */
    function cs_add_video_popup_to_footer() {
        if (is_admin()) {
            return;
        }

  
        $popup_template_path = get_stylesheet_directory() . '/inc/voting/templates/voting-list/video-popup-template.php'; 

        // Check if the template file exists before trying to include it.
        if (file_exists($popup_template_path)) {
            include $popup_template_path; // Include the popup HTML.
        } else {
            // Optional: Log an error if the template file is missing, helpful for debugging.
   
        }
    }
   
    add_action('wp_footer', 'cs_add_video_popup_to_footer');
}

if (!function_exists('cs_add_search_popup_to_footer')) {
    function cs_add_search_popup_to_footer() {
        if (is_admin()) return;

        $template_path = get_stylesheet_directory() . '/inc/voting/templates/global/search-popup.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        }
    }
    add_action('wp_footer', 'cs_add_search_popup_to_footer');
}

/**
 * Redirect tournament matches if accessed directly
 * Tournament matches should only be viewed through the duel arena shortcode
 */
if (!function_exists('yuv_redirect_tournament_matches')) {
    function yuv_redirect_tournament_matches() {
        if (is_singular('voting_list')) {
            global $post;
            $is_tournament = get_post_meta($post->ID, '_is_tournament_match', true);
            
            if ($is_tournament == '1') {
                // Redirect to home page or tournament archive
                wp_redirect(home_url('/'));
                exit;
            }
        }
    }
    add_action('template_redirect', 'yuv_redirect_tournament_matches');
}

/**
 * Exclude tournament matches from main voting_list queries
 */
if (!function_exists('yuv_exclude_tournament_from_queries')) {
    function yuv_exclude_tournament_from_queries($query) {
        // Only on frontend, main query, for voting_list post type
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') === 'voting_list') {
            $meta_query = $query->get('meta_query') ?: [];
            
            $meta_query[] = [
                'key' => '_is_tournament_match',
                'compare' => 'NOT EXISTS',
            ];
            
            $query->set('meta_query', $meta_query);
        }
    }
    add_action('pre_get_posts', 'yuv_exclude_tournament_from_queries');
}

?>