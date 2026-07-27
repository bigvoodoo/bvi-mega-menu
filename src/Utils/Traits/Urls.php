<?php

namespace Bvi\Plugin\MegaMenu\Utils\Traits;

/**
 * Shared URL comparison helpers for current-item detection.
 *
 * @package bvi-mega-menu
 */
trait Urls
{
    /**
     * Strip scheme/host/trailing slashes so host-relative and absolute URLs compare equal.
     *
     * @since 5.0.0
     *
     * @param string $url URL to normalize.
     * @return string
     */
    public static function normalize_url(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $parsed = wp_parse_url($url);
        if (!is_array($parsed)) {
            return rtrim($url, '/');
        }

        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        return rtrim($path, '/') . $query;
    }

    /**
     * Whether two URLs address the same resource once normalized.
     *
     * @since 5.0.0
     *
     * @param string $url_a First URL.
     * @param string $url_b Second URL.
     * @return bool
     */
    public static function matches(string $url_a, string $url_b): bool
    {
        $normalized_a = self::normalize_url($url_a);
        $normalized_b = self::normalize_url($url_b);

        if ($normalized_a === '' || $normalized_b === '') {
            return false;
        }

        return $normalized_a === $normalized_b;
    }

    /**
     * Best-effort current URL for comparison.
     *
     * @since 5.0.0
     *
     * @return string
     */
    public static function current_url(): string
    {
        $queried = get_queried_object();
        if ($queried instanceof \WP_Post) {
            return (string) get_permalink($queried);
        }
        if ($queried instanceof \WP_Term) {
            $link = get_term_link($queried);
            return is_wp_error($link) ? '' : (string) $link;
        }

        if (function_exists('is_front_page') && is_front_page()) {
            return (string) home_url('/');
        }

        global $post;
        if (!empty($post)) {
            return (string) get_permalink($post);
        }

        return '';
    }

    /**
     * Whether the given URL addresses the current request.
     *
     * @since 5.0.0
     *
     * @param string $url URL to test.
     * @return bool
     */
    public static function is_current(string $url): bool
    {
        return self::matches($url, self::current_url());
    }
}
