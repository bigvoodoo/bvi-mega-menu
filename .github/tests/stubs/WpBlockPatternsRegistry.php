<?php

/**
 * Minimal WP_Block_Patterns_Registry stub, backed by a fixture global.
 *
 * Stubbing the registry as a class — rather than letting the production code
 * read a test global — keeps BlockScanner free of any test-only branch.
 *
 * @since 5.0.0
 *
 * @package Bvi\Plugin\MegaMenu\Tests
 */

if (!class_exists('WP_Block_Patterns_Registry')) {
    /**
     * Test stub mirroring the pattern registry.
     *
     * @since 5.0.0
     */
    class WP_Block_Patterns_Registry
    {
        /**
         * Returns the shared instance.
         *
         * @since 5.0.0
         *
         * @return self
         */
        public static function get_instance(): self
        {
            return new self();
        }

        /**
         * Whether a pattern slug is registered.
         *
         * @since 5.0.0
         *
         * @param string $slug Pattern slug.
         * @return bool
         */
        public function is_registered($slug): bool
        {
            return isset($GLOBALS['bvi_test_patterns'][$slug]);
        }

        /**
         * Returns a registered pattern's definition.
         *
         * @since 5.0.0
         *
         * @param string $slug Pattern slug.
         * @return array
         */
        public function get_registered($slug): array
        {
            return ['content' => $GLOBALS['bvi_test_patterns'][$slug] ?? ''];
        }
    }
}
