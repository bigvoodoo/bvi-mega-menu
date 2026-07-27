<?php

namespace Bvi\Plugin\MegaMenu\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the Urls trait's pure comparison logic.
 *
 * @package Bvi\Plugin\MegaMenu\Tests
 */
class UrlsTest extends TestCase
{
    /**
     * An empty URL normalizes to an empty string.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_normalize_url_returns_empty_for_empty_input(): void
    {
        $this->assertSame('', UrlsConsumer::normalize_url(''));
    }

    /**
     * Scheme and host are stripped so relative and absolute URLs compare equal.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_normalize_url_strips_scheme_and_host(): void
    {
        $this->assertSame('/about/team', UrlsConsumer::normalize_url('https://example.com/about/team/'));
        $this->assertSame('/about/team', UrlsConsumer::normalize_url('/about/team/'));
    }

    /**
     * Query strings survive normalization so they still differentiate URLs.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_normalize_url_keeps_query_string(): void
    {
        $this->assertSame('/search?q=widgets', UrlsConsumer::normalize_url('https://example.com/search/?q=widgets'));
    }

    /**
     * A host-relative and an absolute URL for the same page match.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_matches_is_true_across_absolute_and_relative_forms(): void
    {
        $this->assertTrue(UrlsConsumer::matches('https://example.com/about/', '/about'));
    }

    /**
     * Different paths do not match.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_matches_is_false_for_different_paths(): void
    {
        $this->assertFalse(UrlsConsumer::matches('/about', '/about/team'));
    }

    /**
     * An empty URL never matches, so unresolvable items are not flagged current.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_matches_is_false_when_either_url_is_empty(): void
    {
        $this->assertFalse(UrlsConsumer::matches('', '/about'));
        $this->assertFalse(UrlsConsumer::matches('/about', ''));
    }
}
