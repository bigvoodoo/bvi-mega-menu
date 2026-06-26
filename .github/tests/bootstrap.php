<?php

/**
 * PHPUnit bootstrap for plugin integrity tests.
 *
 * Loads the Composer autoloader so tests can resolve plugin classes
 * via the Bvi\Plugin\MegaMenu\ namespace. WordPress is intentionally
 * not loaded — these tests verify static structural properties only.
 *
 * Lives under .github/tests/ so the testing scaffold is excluded
 * from the deployed plugin payload.
 *
 * @since 5.0.0
 *
 * @package Bvi\Plugin\MegaMenu\Tests
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

if (!defined('BVI_PLUGIN_MEGAMENU_TESTS_DIR')) {
    define('BVI_PLUGIN_MEGAMENU_TESTS_DIR', __DIR__);
}

if (!defined('BVI_PLUGIN_MEGAMENU_ROOT_DIR')) {
    define('BVI_PLUGIN_MEGAMENU_ROOT_DIR', dirname(__DIR__, 2));
}

// minimal WP class stubs so plugin classes that extend WP core can be loaded for structural tests
if (!class_exists('Walker_Nav_Menu')) {
    class Walker_Nav_Menu
    {
    }
}
