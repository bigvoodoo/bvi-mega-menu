<?php

/**
 * Minimal WordPress function stubs so pure plugin logic can be exercised
 * without loading WordPress.
 *
 * Kept apart from the class stubs because WPCS forbids a single file from
 * declaring both functions and OO structures.
 *
 * @since 5.0.0
 *
 * @package Bvi\Plugin\MegaMenu\Tests
 */

// fixtures the block-API stubs read from, populated per test
$GLOBALS['bvi_test_blocks'] = [];
$GLOBALS['bvi_test_patterns'] = [];
$GLOBALS['bvi_test_filters'] = [];

if (!function_exists('wp_parse_url')) {
    /**
     * Test stub mirroring wp_parse_url() for the default component.
     *
     * @since 5.0.0
     *
     * @param string $url       URL to parse.
     * @param int    $component Component to retrieve, or -1 for all.
     * @return mixed
     */
    function wp_parse_url($url, $component = -1)
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- this stub is wp_parse_url()
        return parse_url($url, $component);
    }
}

if (!function_exists('parse_blocks')) {
    /**
     * Test stub returning a pre-parsed tree keyed by a marker string.
     *
     * @since 5.0.0
     *
     * @param string $content Marker naming a fixture in $GLOBALS['bvi_test_blocks'].
     * @return array
     */
    function parse_blocks($content)
    {
        return $GLOBALS['bvi_test_blocks'][$content] ?? [];
    }
}

if (!function_exists('get_post')) {
    /**
     * Test stub returning an object whose post_content is the requested marker.
     *
     * @since 5.0.0
     *
     * @param int $post_id Post ID.
     * @return object|null
     */
    function get_post($post_id = 0)
    {
        $key = 'wp_block:' . (int) $post_id;

        if (!isset($GLOBALS['bvi_test_blocks'][$key])) {
            return null;
        }

        return (object) ['ID' => (int) $post_id, 'post_content' => $key];
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Test stub running any callbacks a test registered in $GLOBALS['bvi_test_filters'].
     *
     * @since 5.0.0
     *
     * @param string $hook_name Filter name.
     * @param mixed  $value     Value to filter.
     * @return mixed
     */
    function apply_filters($hook_name, $value)
    {
        foreach ($GLOBALS['bvi_test_filters'][$hook_name] ?? [] as $callback) {
            $value = $callback($value);
        }

        return $value;
    }
}

if (!function_exists('get_permalink')) {
    /**
     * Test stub returning an empty permalink so URL matching falls back to ->url.
     *
     * @since 5.0.0
     *
     * @param int $post_id Post ID.
     * @return string
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- stub mirrors core signature
    function get_permalink($post_id = 0)
    {
        return '';
    }
}
