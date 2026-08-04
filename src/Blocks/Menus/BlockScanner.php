<?php

namespace Bvi\Plugin\MegaMenu\Blocks\Menus;

/**
 * Collects and resolves the block trees relevant to the current page.
 *
 * Pattern-family blocks carry no inner blocks in parsed post content — their
 * markup lives in a wp_block post, the pattern registry, or a template part.
 * This class expands them in place so consumers can walk a single flat tree.
 *
 * @package bvi-mega-menu
 */
class BlockScanner
{
    /** @var int Fallback maximum expansion depth. */
    private const DEFAULT_MAX_DEPTH = 10;

    /**
     * Gather the parsed block trees for the current page, patterns already expanded.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public static function collect_page_blocks(): array
    {
        $trees = [];

        global $post;
        if (!empty($post) && !empty($post->post_content)) {
            $trees[] = parse_blocks($post->post_content);
        }

        if (!empty($GLOBALS['_wp_current_template_content'])) {
            $trees[] = parse_blocks($GLOBALS['_wp_current_template_content']);
        }

        $merged = [];
        foreach ($trees as $tree) {
            foreach ((array) $tree as $block) {
                $merged[] = $block;
            }
        }

        /**
         * Filters the parsed block trees searched for a mega menu on this request.
         *
         * Only the current post's content and the active template are collected by
         * default. A theme that renders a template part outside the template — so no
         * core/template-part block on the page points at it — can append that part's
         * parsed blocks here. Patterns inside anything added are expanded as usual.
         *
         * @since 5.0.0
         *
         * @param array $blocks Flat list of parsed blocks, before pattern expansion.
         */
        $merged = apply_filters('bvi_mega_menu_page_blocks', $merged);

        return self::resolve((array) $merged);
    }

    /**
     * Expand pattern-family blocks in place, recursing into what is expanded.
     *
     * @since 5.0.0
     *
     * @param array $blocks  Parsed block tree.
     * @param int   $depth   Current expansion depth.
     * @param array $visited Map of already-expanded source keys.
     * @return array
     */
    public static function resolve(array $blocks, int $depth = 0, array $visited = []): array
    {
        if ($depth >= self::get_max_depth()) {
            return $blocks;
        }

        $resolved = [];

        foreach ($blocks as $block) {
            $key = self::source_key($block);

            if ($key !== '' && !isset($visited[$key])) {
                $visited[$key] = true;

                $expanded = self::expand($block, $key);
                if (!empty($expanded)) {
                    $block['innerBlocks'] = self::resolve($expanded, $depth + 1, $visited);
                    $resolved[] = $block;
                    continue;
                }
            }

            if (!empty($block['innerBlocks'])) {
                $block['innerBlocks'] = self::resolve($block['innerBlocks'], $depth + 1, $visited);
            }

            $resolved[] = $block;
        }

        return $resolved;
    }

    /**
     * Build the cycle-guard key identifying a pattern block's content source.
     *
     * @since 5.0.0
     *
     * @param array $block Parsed block.
     * @return string Empty string when the block is not a pattern-family block.
     */
    private static function source_key(array $block): string
    {
        $name = $block['blockName'] ?? '';
        $attrs = $block['attrs'] ?? [];

        if ($name === 'core/block' && !empty($attrs['ref'])) {
            return 'block:' . (int) $attrs['ref'];
        }

        if ($name === 'core/pattern' && !empty($attrs['slug'])) {
            return 'pattern:' . (string) $attrs['slug'];
        }

        if ($name === 'core/template-part' && !empty($attrs['slug'])) {
            $theme = (string) ($attrs['theme'] ?? '');
            return 'part:' . $theme . '//' . (string) $attrs['slug'];
        }

        return '';
    }

    /**
     * Parse the content behind a pattern-family block.
     *
     * @since 5.0.0
     *
     * @param array  $block Parsed block.
     * @param string $key   Source key from source_key().
     * @return array
     */
    private static function expand(array $block, string $key): array
    {
        $attrs = $block['attrs'] ?? [];

        if (strpos($key, 'block:') === 0) {
            $ref_post = get_post((int) $attrs['ref']);
            if (empty($ref_post) || empty($ref_post->post_content)) {
                return [];
            }

            return parse_blocks($ref_post->post_content);
        }

        if (strpos($key, 'pattern:') === 0) {
            $content = self::pattern_content((string) $attrs['slug']);

            return $content === '' ? [] : parse_blocks($content);
        }

        $content = self::template_part_content((string) $attrs['slug'], (string) ($attrs['theme'] ?? ''));

        return $content === '' ? [] : parse_blocks($content);
    }

    /**
     * Look up a registered pattern's markup.
     *
     * @since 5.0.0
     *
     * @param string $slug Pattern slug.
     * @return string
     */
    private static function pattern_content(string $slug): string
    {
        if (!class_exists('\WP_Block_Patterns_Registry')) {
            return '';
        }

        $registry = \WP_Block_Patterns_Registry::get_instance();
        if (!$registry->is_registered($slug)) {
            return '';
        }

        $pattern = $registry->get_registered($slug);

        return (string) ($pattern['content'] ?? '');
    }

    /**
     * Look up a template part's markup.
     *
     * @since 5.0.0
     *
     * @param string $slug  Part slug.
     * @param string $theme Theme stylesheet, when the block names one.
     * @return string
     */
    private static function template_part_content(string $slug, string $theme): string
    {
        if (!function_exists('get_block_template')) {
            return '';
        }

        $stylesheet = $theme !== '' ? $theme : (string) get_stylesheet();
        $template = get_block_template($stylesheet . '//' . $slug, 'wp_template_part');

        return empty($template) ? '' : (string) ($template->content ?? '');
    }

    /**
     * Maximum pattern expansion depth.
     *
     * @since 5.0.0
     *
     * @return int
     */
    private static function get_max_depth(): int
    {
        /**
         * Filters how deep pattern expansion recurses before stopping.
         *
         * @since 5.0.0
         *
         * @param int $max_depth Maximum depth.
         */
        return (int) apply_filters('bvi_mega_menu_pattern_max_depth', self::DEFAULT_MAX_DEPTH);
    }
}
