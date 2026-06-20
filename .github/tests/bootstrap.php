<?php

/**
 * PHPUnit bootstrap for plugin integrity tests.
 *
 * Loads the Composer autoloader so tests can resolve plugin classes
 * via the Bvi\Plugin\Sherloq\ namespace. WordPress is intentionally
 * not loaded — these tests verify static structural properties only.
 *
 * Lives under .github/tests/ so the testing scaffold is excluded
 * from the deployed plugin payload.
 *
 * @since 1.0.1
 *
 * @package Bvi\Plugin\Sherloq\Tests
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

if (!defined('BVI_PLUGIN_SHERLOQ_TESTS_DIR')) {
    define('BVI_PLUGIN_SHERLOQ_TESTS_DIR', __DIR__);
}

if (!defined('BVI_PLUGIN_SHERLOQ_ROOT_DIR')) {
    define('BVI_PLUGIN_SHERLOQ_ROOT_DIR', dirname(__DIR__, 2));
}

// minimal WP class stubs so plugin classes that extend WP core can be loaded for structural tests
if (!class_exists('WP_List_Table')) {
    class WP_List_Table
    {
        public function __construct($args = []) {}
    }
}
