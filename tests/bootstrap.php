<?php
/**
 * PHPUnit bootstrap file.
 */

// Require composer autoloader.
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define WordPress constants.
// Normally these would be defined by WordPress itself.
// We define minimal constants needed for the tests.
if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', dirname(__DIR__) . '/tests/mock/wp-content');
}

/**
 * Setup WordPress functions stubs.
 */
if (!function_exists('trailingslashit')) {
    function trailingslashit($string) {
        return rtrim($string, '/') . '/';
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) {
        global $wp_filter;
        if (isset($wp_filter[$tag])) {
            foreach ($wp_filter[$tag] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $value = call_user_func_array($callback['function'], array_merge([$value], $args));
                }
            }
        }
        return $value;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        global $wp_filter;
        $wp_filter[$tag][$priority][] = [
            'function' => $function_to_add,
            'accepted_args' => $accepted_args
        ];
    }
}

if (!function_exists('do_action')) {
    function do_action($tag, ...$args) {
        global $wp_filter;
        if (isset($wp_filter[$tag])) {
            foreach ($wp_filter[$tag] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    call_user_func_array($callback['function'], $args);
                }
            }
        }
    }
}

// Global variable to control is_child_theme behavior in tests
$GLOBALS['mock_is_child_theme'] = true; // Set to true for our tests

if (!function_exists('is_child_theme')) {
    function is_child_theme() {
        global $mock_is_child_theme;
        return $mock_is_child_theme;
    }
}

if (!function_exists('get_template_directory')) {
    function get_template_directory() {
        return dirname(__DIR__) . '/tests/mock/wp-content/themes/parent-theme';
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory() {
        return dirname(__DIR__) . '/tests/mock/wp-content/themes/child-theme';
    }
}

if (!function_exists('_doing_it_wrong')) {
    function _doing_it_wrong($function, $message, $version) {
        trigger_error(sprintf('%1$s was called incorrectly. %2$s since version %3$s', $function, $message, $version));
    }
}

// Create test directories
$dirs = [
    dirname(__DIR__) . '/tests/mock/wp-content',
    dirname(__DIR__) . '/tests/mock/wp-content/themes',
    dirname(__DIR__) . '/tests/mock/wp-content/themes/parent-theme',
    dirname(__DIR__) . '/tests/mock/wp-content/themes/parent-theme/test-plugin',
    dirname(__DIR__) . '/tests/mock/wp-content/themes/child-theme', 
    dirname(__DIR__) . '/tests/mock/wp-content/themes/child-theme/test-plugin',
    dirname(__DIR__) . '/tests/mock/plugin',
    dirname(__DIR__) . '/tests/mock/plugin/templates',
    dirname(__DIR__) . '/tests/mock/wp-content/test-plugin'
];

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Create mock global variable for filters
global $wp_filter;
$wp_filter = [];