<?php

/**
 * PHPUnit bootstrap for plugin integrity tests.
 *
 * Loads the Composer autoloader so tests can resolve plugin classes
 * via the Bvi\Plugin\MegaMenu\ namespace. WordPress is intentionally
 * not loaded — the stubs under stubs/ supply only what the plugin's
 * pure logic touches.
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

require_once __DIR__ . '/stubs/wp-functions.php';
require_once __DIR__ . '/stubs/WalkerNavMenu.php';
require_once __DIR__ . '/stubs/WpBlockPatternsRegistry.php';
require_once __DIR__ . '/stubs/UrlsConsumer.php';
