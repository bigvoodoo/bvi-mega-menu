<?php

namespace Bvi\Plugin\MegaMenu\Blocks;

use Bvi\Plugin\MegaMenu\Blocks\Menus\BlockScanner;
use Bvi\Plugin\MegaMenu\Utils\Traits\Urls;

/**
 * Class MenuItem
 *
 * @package bvi-mega-menu
 */
class MenuItem extends AbstractBlock
{
    use Urls;

    /**
     * Constructor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('bvi/menu-item');
    }

    /**
     * Register the block type.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register(): void
    {
        if (file_exists($this->get_path() . '/block.json')) {
            register_block_type($this->get_path(), [
                'render_callback' => [$this, 'render'],
            ]);
        }
    }

    /**
     * Server-side render callback.
     *
     * @since 5.0.0
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Rendered InnerBlocks content.
     * @param object $block      Block instance, when supplied by the render callback.
     * @return string Rendered HTML.
     */
    public function render(array $attributes, string $content, $block = null): string
    {
        $label = (string) ($attributes['label'] ?? '');
        $url = (string) ($attributes['url'] ?? '');
        $target = !empty($attributes['openInNewTab']) ? '_blank' : '';
        $rel = $target === '_blank' ? 'noopener noreferrer' : '';
        $label_color = (string) ($attributes['labelColor'] ?? '');
        $panel_width = (string) ($attributes['panelWidth'] ?? '');

        $children = self::render_children($content, $block);
        $has_panel = $this->content_has_panel($children);

        $classes = ['bvi-menu-item'];
        if ($has_panel) {
            $classes[] = 'has-panel';
        }

        $current_url = self::current_url();
        $is_current = $current_url !== '' && self::matches($url, $current_url);

        if ($is_current) {
            $classes[] = 'current-menu-item';
            $classes[] = 'is-current';
        } elseif ($current_url !== '' && !empty($block->parsed_block['innerBlocks'])) {
            // expand patterns first so links inside a synced panel count the same as inline ones
            $descendant_depth = self::find_current_descendant_depth(
                BlockScanner::resolve($block->parsed_block['innerBlocks']),
                $current_url,
            );

            if ($descendant_depth === 1) {
                $classes[] = 'current-menu-parent';
                $classes[] = 'current-menu-ancestor';
            } elseif ($descendant_depth !== null) {
                $classes[] = 'current-menu-ancestor';
            }
        }

        $styles = [];
        if ($label_color !== '') {
            $styles[] = '--bvi-mm-item-color: ' . $label_color;
        }
        if ($panel_width !== '') {
            $styles[] = '--bvi-mm-panel-width: ' . $panel_width;
        }

        $wrapper_attrs = get_block_wrapper_attributes([
            'class' => implode(' ', $classes),
            'style' => implode(';', $styles),
        ]);

        if ($label === '') {
            return '';
        }

        $link_attrs = '';
        if ($url !== '') {
            $link_attrs .= ' href="' . esc_url($url) . '"';
        }
        if ($target !== '') {
            $link_attrs .= ' target="' . esc_attr($target) . '"';
        }
        if ($rel !== '') {
            $link_attrs .= ' rel="' . esc_attr($rel) . '"';
        }
        if ($is_current) {
            $link_attrs .= ' aria-current="page"';
        }
        if ($has_panel) {
            $link_attrs .= ' aria-haspopup="true" aria-expanded="false"';
        }

        $label_html = esc_html($label);

        if ($url !== '') {
            $link = sprintf('<a class="bvi-menu-item-link"%s>%s</a>', $link_attrs, $label_html);
        } else {
            $link = sprintf('<button type="button" class="bvi-menu-item-link"%s>%s</button>', $link_attrs, $label_html);
        }

        $toggle = $has_panel
            ? '<button type="button" class="bvi-menu-item-toggle" aria-label="' .
                esc_attr__('Toggle submenu', 'bvi-mega-menu') .
                '" aria-expanded="false"><span aria-hidden="true"></span></button>'
            : '';

        return sprintf('<li %s>%s%s%s</li>', $wrapper_attrs, $link, $toggle, $children);
    }

    /**
     * Render inner blocks, wrapping nested menu items in a panel.
     *
     * An `<li>` may not contain another `<li>` directly — browsers close the outer
     * element and the submenu flattens to top level — so nested items are grouped
     * into the same panel/sub-list structure Walker::start_lvl() emits. Items are
     * re-rendered from the block tree because the boundaries between children
     * cannot be recovered from the already-concatenated content string.
     *
     * @since 5.0.0
     *
     * @param string $content Rendered InnerBlocks content.
     * @param object $block   Block instance, when supplied by the render callback.
     * @return string
     */
    private static function render_children(string $content, $block = null): string
    {
        if (!($block instanceof \WP_Block) || count($block->inner_blocks) === 0) {
            return $content;
        }

        $names = [];
        foreach ($block->inner_blocks as $inner) {
            $names[] = $inner->name;
        }

        // without nested items the rendered content is already valid markup
        if (!in_array('bvi/menu-item', $names, true)) {
            return $content;
        }

        $rendered = [];
        foreach ($block->inner_blocks as $inner) {
            $rendered[] = ['name' => $inner->name, 'html' => $inner->render()];
        }

        return self::group_children($rendered);
    }

    /**
     * Group rendered children so runs of menu items sit inside one panel.
     *
     * @since 5.0.0
     *
     * @param array $children Ordered list of ['name' => block name, 'html' => rendered HTML].
     * @return string
     */
    public static function group_children(array $children): string
    {
        $out = '';
        $items = '';

        foreach ($children as $child) {
            $html = (string) ($child['html'] ?? '');

            if (($child['name'] ?? '') === 'bvi/menu-item') {
                $items .= $html;
                continue;
            }

            $out .= self::wrap_items($items) . $html;
            $items = '';
        }

        return $out . self::wrap_items($items);
    }

    /**
     * Wrap a run of menu items in the panel markup shared with the walker.
     *
     * @since 5.0.0
     *
     * @param string $items Concatenated `<li>` markup, possibly empty.
     * @return string
     */
    private static function wrap_items(string $items): string
    {
        if ($items === '') {
            return '';
        }

        return '<div class="bvi-mega-panel"><ul class="bvi-mega-menu-sub-list">' . $items . '</ul></div>';
    }

    /**
     * Find the shallowest depth at which a descendant menu item is the current page.
     *
     * @since 5.0.0
     *
     * @param array  $inner_blocks Parsed inner blocks to scan.
     * @param string $current_url  Request URL to match against.
     * @param int    $depth        Depth of the blocks being scanned.
     * @return int|null Depth of the shallowest match, or null when nothing matches.
     */
    public static function find_current_descendant_depth(array $inner_blocks, string $current_url, int $depth = 1): ?int
    {
        $found = null;

        foreach ($inner_blocks as $block) {
            $is_item = ($block['blockName'] ?? '') === 'bvi/menu-item';

            if ($is_item && self::matches((string) ($block['attrs']['url'] ?? ''), $current_url)) {
                return $depth;
            }

            // links written into panel content are children of the enclosing item
            if (!$is_item && self::has_current_link($block, $current_url)) {
                return $depth;
            }

            if (empty($block['innerBlocks'])) {
                continue;
            }

            // only menu items advance the depth counter; panels and wrappers are transparent
            $child_depth = self::find_current_descendant_depth(
                $block['innerBlocks'],
                $current_url,
                $is_item ? $depth + 1 : $depth,
            );

            if ($child_depth !== null && ($found === null || $child_depth < $found)) {
                $found = $child_depth;
            }
        }

        return $found;
    }

    /**
     * Whether any link a non-item block contributes addresses the current request.
     *
     * @since 5.0.0
     *
     * @param array  $block       Parsed block.
     * @param string $current_url Request URL to match against.
     * @return bool
     */
    private static function has_current_link(array $block, string $current_url): bool
    {
        foreach (BlockScanner::extract_links($block) as $link) {
            if (self::matches((string) ($link['url'] ?? ''), $current_url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect whether the rendered InnerBlocks content already contains a panel.
     *
     * @since 5.0.0
     *
     * @param string $content Rendered InnerBlocks content.
     * @return bool
     */
    private function content_has_panel(string $content): bool
    {
        return $content !== '' &&
            (strpos($content, 'bvi-mega-panel') !== false || strpos($content, 'bvi-menu-item') !== false);
    }
}
