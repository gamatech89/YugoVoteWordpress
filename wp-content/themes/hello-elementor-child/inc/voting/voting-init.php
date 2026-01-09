<?php
/**
 * Voting Feature Initializer
 *
 * Loads all necessary files for the voting functionality.
 *
 * @package HelloElementorChild
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit();
}

// Define the base path for the voting includes for cleaner require_once statements
$voting_inc_path = get_stylesheet_directory() . '/inc/voting/';

// Load Custom Post Types
require_once $voting_inc_path . 'cpts/cpt-user-level.php';
require_once $voting_inc_path . 'cpts/cpt-voting-list.php';
require_once $voting_inc_path . 'cpts/cpt-voting-list-items.php';

// Load Taxonomies
require_once $voting_inc_path . 'taxonomies/taxonomy-voting-list-category.php';
require_once $voting_inc_path . 'taxonomies/taxonomy-voting-item-category.php';

// Load Meta Boxes
require_once $voting_inc_path . 'meta/user-level-meta.php';
require_once $voting_inc_path . 'meta/voting-list-meta.php';
require_once $voting_inc_path . 'meta/voting-list-items-meta.php';
require_once $voting_inc_path . 'meta/voting-list-taxonomy-meta.php';

// Load Shortcodes
require_once $voting_inc_path . 'voting-shortcodes.php';

// Load Hooks
require_once $voting_inc_path . 'voting-hooks.php';

// Load API Endpoints
require_once $voting_inc_path . 'api/voting-endpoints.php';

// Load Helper Functions & Scripts specific to Voting
// (Your main init.php listed db-schema.php and voting-scripts.php here.
// If you have a general voting helpers.php, include it too.)
if (file_exists($voting_inc_path . 'helpers.php')) { // Optional general helpers file
    require_once $voting_inc_path . 'helpers.php'; 
}

if (file_exists($voting_inc_path . 'voting-scripts.php')) { // For enqueuing voting-specific scripts/styles
    require_once $voting_inc_path . 'voting-scripts.php';
}



?>