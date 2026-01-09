<?php
/**
 * Global Helpers Initializer
 *
 * Loads all global helper functions used across modules.
 * These are utility functions not tied to a specific module.
 *
 * @package YugoVote
 */

if (!defined('ABSPATH')) {
    exit();
}

$base = __DIR__ . '/';

// Icons helper
if (file_exists($base . 'icons.php')) {
    require_once $base . 'icons.php';
}

// Category color generator
if (file_exists($base . 'category-color-generator.php')) {
    require_once $base . 'category-color-generator.php';
}

// Utility functions
if (file_exists($base . 'utilities.php')) {
    require_once $base . 'utilities.php';
}
