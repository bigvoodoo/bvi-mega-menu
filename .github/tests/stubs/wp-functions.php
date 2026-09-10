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
$GLOBALS['bvi_test_nav_menus'] = [];
$GLOBALS['bvi_test_nav_menu_items'] = [];

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

if (!function_exists('wp_strip_all_tags')) {
    /**
     * Test stub mirroring wp_strip_all_tags().
     *
     * @since 5.0.0
     *
     * @param string $text          Text to strip.
     * @param bool   $remove_breaks Whether to collapse line breaks and tabs to a single space.
     * @return string
     */
    function wp_strip_all_tags($text, $remove_breaks = false)
    {
        $text = (string) preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $text);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- this stub is wp_strip_all_tags()
        $text = strip_tags($text);

        if ($remove_breaks) {
            $text = (string) preg_replace('/[\r\n\t ]+/', ' ', $text);
        }

        return trim($text);
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

if (!function_exists('wp_get_nav_menu_object')) {
    /**
     * Test stub returning a fixture menu object keyed by slug or numeric id.
     *
     * @since 5.0.0
     *
     * @param int|string $menu Menu slug, id, or term.
     * @return object|false
     */
    function wp_get_nav_menu_object($menu)
    {
        return $GLOBALS['bvi_test_nav_menus'][(string) $menu] ?? false;
    }
}

if (!function_exists('wp_get_nav_menu_items')) {
    /**
     * Test stub returning fixture menu items keyed by term id.
     *
     * @since 5.0.0
     *
     * @param int   $menu_id Menu term id.
     * @param array $args    Unused.
     * @return array
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- stub mirrors core signature
    function wp_get_nav_menu_items($menu_id, $args = [])
    {
        return $GLOBALS['bvi_test_nav_menu_items'][(int) $menu_id] ?? [];
    }
}

if (!function_exists('is_wp_error')) {
    /**
     * Test stub; no test fixture ever produces a WP_Error.
     *
     * @since 5.0.0
     *
     * @param mixed $thing Value to check.
     * @return bool
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- stub mirrors core signature
    function is_wp_error($thing)
    {
        return false;
    }
}

if (!function_exists('get_queried_object')) {
    /**
     * Test stub; no test fixture sets a queried object.
     *
     * @since 5.0.0
     *
     * @return null
     */
    function get_queried_object()
    {
        return null;
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
