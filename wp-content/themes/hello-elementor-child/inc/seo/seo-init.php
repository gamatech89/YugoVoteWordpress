<?php
/**
 * SEO modul — loader.
 *
 * Naslovi, meta opisi, Open Graph / Twitter kartice, lang, noindex, sitemap i SEO kutija u adminu.
 * Spec: docs/superpowers/specs/2026-11-15-yugovote-seo-etapa1-design.md
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ako je aktivan pravi SEO plugin, ne radimo ništa — izbegavamo duple tagove.
if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION')) {
    return;
}

define('YGV_SEO_DIR', get_stylesheet_directory() . '/inc/seo');

require_once YGV_SEO_DIR . '/seo-text.php';
require_once YGV_SEO_DIR . '/seo-static.php';
require_once YGV_SEO_DIR . '/seo-context.php';
require_once YGV_SEO_DIR . '/seo-head.php';
