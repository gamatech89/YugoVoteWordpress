<?php
/**
 * Theme functions and definitions.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
    exit();
}

define('HELLO_ELEMENTOR_CHILD_VERSION', '2.0.2');

// **** LOAD THEME CONFIGURATION CONSTANTS ****
if (file_exists(get_stylesheet_directory() . '/inc/config.php')) {
    require_once get_stylesheet_directory() . '/inc/config.php';
}

// PSR-4 autoloader for theme classes
$autoload = get_stylesheet_directory() . '/inc/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

require_once ABSPATH . 'wp-admin/includes/upgrade.php';


// Enqueue main styles
function hello_elementor_child_enqueue_scripts() {
    
    wp_enqueue_style(
        'category-colors',
        get_stylesheet_directory_uri() . '/css/category-colors.css',
        [],
        HELLO_ELEMENTOR_CHILD_VERSION
    );

    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        [],
        HELLO_ELEMENTOR_CHILD_VERSION
    );

	wp_enqueue_script(
        'hello-elementor-main-script',
        get_stylesheet_directory_uri() . '/js/app.js',
        ['jquery'],
        HELLO_ELEMENTOR_CHILD_VERSION,
        true
    );

}
add_action('wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts');

// Load Custom Features
require_once get_stylesheet_directory() . '/inc/init.php';

// ---- KONTAKT FORM AJAX HANDLER ----
function ygv_kontakt_submit_handler() {
    check_ajax_referer('ygv_kontakt_nonce', 'ygv_kontakt_nonce_field');

    $ime    = sanitize_text_field($_POST['ime'] ?? '');
    $email  = sanitize_email($_POST['email'] ?? '');
    $tema   = sanitize_text_field($_POST['tema'] ?? 'Opšto pitanje');
    $poruka = sanitize_textarea_field($_POST['poruka'] ?? '');

    if (!$ime || !is_email($email) || !$poruka) {
        wp_send_json_error(['message' => 'Nedostaju obavezna polja.']);
    }

    $subject = '[YugoVote Kontakt] ' . $tema . ' — ' . $ime;
    $body    = "Ime: {$ime}\nE-mail: {$email}\nTema: {$tema}\n\nPoruka:\n{$poruka}";
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $ime . ' <' . $email . '>',
    ];

    $sent = wp_mail('office@yugovote.com', $subject, $body, $headers);

    if ($sent) {
        wp_send_json_success(['message' => 'Poruka poslata.']);
    } else {
        wp_send_json_error(['message' => 'Greška pri slanju.']);
    }
}
add_action('wp_ajax_ygv_kontakt_submit',        'ygv_kontakt_submit_handler');
add_action('wp_ajax_nopriv_ygv_kontakt_submit', 'ygv_kontakt_submit_handler');

// ---- PAGE LOADER ----
// CSS + JS both inline in <head> at priority 1 — runs before any template or
// Elementor hook, so it works on every page including the homepage canvas template
// which does not call wp_body_open. The loader div is created via JS and appended
// to <html> (document.documentElement) since <body> doesn't exist yet at this point.
function ygv_page_loader_head() {
    ?>
    <style id="ygv-page-loader-style">
    #ygv-page-loader{position:fixed;inset:0;z-index:99999;background:#0d1117;display:flex;align-items:center;justify-content:center;transition:opacity .35s ease,visibility .35s ease;opacity:1;visibility:visible;pointer-events:all}
    #ygv-page-loader.ygv-loader--hidden{opacity:0;visibility:hidden;pointer-events:none}
    .ygv-loader__spinner{width:40px;height:40px;border:3px solid rgba(255,255,255,.12);border-top-color:var(--ygv-primary,#2D3A8C);border-radius:50%;animation:ygv-spin .7s linear infinite}
    @keyframes ygv-spin{to{transform:rotate(360deg)}}
    </style>
    <script>
    (function() {
        // Create and inject loader into <html> — works before <body> exists
        var loader = document.createElement('div');
        loader.id = 'ygv-page-loader';
        loader.setAttribute('aria-hidden', 'true');
        var spinner = document.createElement('div');
        spinner.className = 'ygv-loader__spinner';
        loader.appendChild(spinner);
        document.documentElement.appendChild(loader);

        function hideLoader() {
            loader.classList.add('ygv-loader--hidden');
        }

        if (document.readyState === 'complete') {
            hideLoader();
        } else {
            window.addEventListener('load', hideLoader);
            setTimeout(hideLoader, 3000);
        }

        document.addEventListener('click', function(e) {
            var link = e.target.closest('a');
            if (!link) return;
            var href = link.getAttribute('href');
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript') === 0) return;
            if (link.hostname && link.hostname !== window.location.hostname) return;
            if (link.target === '_blank') return;
            loader.classList.remove('ygv-loader--hidden');
        });

        window.addEventListener('pageshow', function(e) {
            if (e.persisted) hideLoader();
        });
    })();
    </script>
    <?php
}
add_action('wp_head', 'ygv_page_loader_head', 1);




