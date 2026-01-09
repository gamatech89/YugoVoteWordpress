<?php

if (!defined('ABSPATH')) exit;

/**
 * Helper: Render Account Template Partial
 *
 * Loads and displays a PHP template file from /inc/account/templates/, passing data as local variables.
 * Allows modular separation of form markup (login, register, complete-profile, etc.) from business logic.
 *
 * @param string $template_name  Name of the template file (without .php extension).
 * @param array  $data           (Optional) Variables to extract and make available in the template.
 *
 * Usage example:
 *     yugo_account_render_template('login-form', ['errors' => $errors]);
 */

if (!function_exists('yugo_account_render_template')) {
    function yugo_account_render_template(string $slug, array $data = []) {
        $file = get_stylesheet_directory() . '/inc/account/templates/' . $slug . '.php';
        if (!file_exists($file)) {
            echo '<!-- Template not found: ' . esc_html($slug) . ' -->';
            return;
        }
        extract($data, EXTR_SKIP);
        include $file;
    }
}
