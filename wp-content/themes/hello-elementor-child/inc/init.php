<?php
/**
 * Initialize custom functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit();
}

// --- LOADING QUIZZES FEATURE ---
require_once get_stylesheet_directory() . '/inc/quizzes/quizzes-init.php'; 

// --- LOADING VOTING FEATURE ---
require_once get_stylesheet_directory() . '/inc/voting/voting-init.php'; 

// --- LOADING ADMIN FUNCTIONALITY ---
require_once get_stylesheet_directory() . '/inc/admin/admin-init.php';

// --- LOADING ACCOUNT FEATURE ---
require_once get_stylesheet_directory() . '/inc/account/account-init.php';

// --- LOADING POLLS FEATURE ---
require_once get_stylesheet_directory() . '/inc/polls/polls-init.php';

// --- LOADING DATABASE MIGRATIONS ---
require_once get_stylesheet_directory() . '/inc/migrations/migrations-init.php';

// --- LOADING GLOBAL HELPERS ---
require_once get_stylesheet_directory() . '/inc/helpers/helpers-init.php';







