<?php
/**
 * Simple PSR-4 autoloader for the child theme.
 */

if (!defined('ABSPATH')) {
    exit();
}

spl_autoload_register(function ($class) {
    $prefix   = 'HelloElementorChild\\';
    $base_dir = get_stylesheet_directory() . '/inc/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
