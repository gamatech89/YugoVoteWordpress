<?php
/**
 * Retrieve the content of an SVG icon file and optionally add a CSS class to it.
 *
 * This function looks for an SVG file in the theme's '/assets/icons/' directory
 * based on the provided name. If a class is specified, it attempts to inject
 * that class into the main <svg> tag of the icon.
 *
 * @param string $name  The filename of the SVG icon (without the .svg extension).
 * For example, 'play' would look for 'play.svg'.
 * @param string $class Optional. A CSS class (or classes) to add to the <svg> element.
 * Default is empty.
 * @return string       The SVG content as a string if the file is found, 
 * otherwise an empty string.
 */
function cs_get_svg_icon($name, $class = '') {
    $path = get_stylesheet_directory() . '/assets/icons/' . sanitize_file_name($name) . '.svg'; 

    if (file_exists($path)) {
        $svg = file_get_contents($path);
        if ($class) {
            $escaped_class = esc_attr($class);
            $svg = preg_replace('/<svg([^>]*?)>/', '<svg$1 class="' . $escaped_class . '">', $svg, 1);
        }
        return $svg;
    }
    return ''; 
}