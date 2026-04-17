<?php

namespace Bvi\Plugin\MegaMenu\Service\Shortcode;

use Bvi\Plugin\MegaMenu\Blocks\RelatedLinks;

/**
 * [related_links] shortcode for classic theme support.
 *
 * Also used by the Related Links block for building related link output via
 * the static {@see self::get_custom_related_links()} helper.
 *
 * The classic shortcode delegates rendering to the `bvi/related-links` block
 * so both classic and block themes share a single code path.
 *
 * @package bvi-mega-menu
 */
class RelatedLinksShortcode
{
    public function __construct()
    {
        add_shortcode('related_links', [$this, 'render']);
    }

    /**
     * Render the related links shortcode.
     *
     * Accepts the same options as the block: `menu` (classic slug or
     * `wp_navigation:{id}`). Leaving `menu` empty falls through to the
     * block's auto-detect → default-settings resolution chain.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function render($atts = []): string
    {
        $atts = (array) $atts;

        $args = shortcode_atts(
            [
                'menu' => '',
            ],
            $atts,
            'related_links',
        );

        $block = new RelatedLinks();

        return $block->render(
            [
                'menuSlug' => (string) $args['menu'],
            ],
            '',
        );
    }

    /**
     * Get custom related links for a specific post.
     *
     * Used by the Related Links block to check for per-page overrides.
     *
     * @param int $post_id The post ID.
     * @return array Array of custom link objects, or empty array.
     */
    public static function get_custom_related_links(int $post_id): array
    {
        $custom_links = get_post_meta($post_id, '_bvi_related_links', true);

        if (empty($custom_links) || !is_array($custom_links)) {
            return [];
        }

        $items = [];
        foreach ($custom_links as $index => $link) {
            if (empty($link['url']) && empty($link['title'])) {
                continue;
            }

            $item = (object) [
                'ID' => $post_id . '-' . $index,
                'post_id' => 0,
                'post_title' => $link['title'] ?? '',
                'parent_id' => 0,
                'position' => $index,
                'url' => $link['url'] ?? '',
                'classes' => ['menu-item-custom'],
                'type' => 'custom',
                'object' => 'custom',
                'target' => $link['target'] ?? '',
                'attr_title' => '',
                'xfn' => '',
            ];

            $items[] = $item;
        }

        return $items;
    }
}
