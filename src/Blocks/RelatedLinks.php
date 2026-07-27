<?php

namespace Bvi\Plugin\MegaMenu\Blocks;

use Bvi\Plugin\MegaMenu\Blocks\Menus\BlockScanner;
use Bvi\Plugin\MegaMenu\Blocks\Menus\Helper;
use Bvi\Plugin\MegaMenu\Service\Shortcode\RelatedLinksShortcode;
use Bvi\Plugin\MegaMenu\Utils\Traits\Urls;

/**
 * Class RelatedLinks
 *
 * @package bvi-mega-menu
 */
class RelatedLinks extends AbstractBlock
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
        parent::__construct('bvi/related-links');
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

        add_action('rest_api_init', [Helper::get_instance(), 'register_rest_routes']);
    }

    /**
     * Server-side render callback.
     *
     * @since 5.0.0
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block inner content (unused).
     * @return string Rendered HTML.
     */
    public function render(array $attributes, string $content): string
    {
        unset($content);

        $items = $this->resolve_items($attributes);

        if (empty($items)) {
            return '';
        }

        $display = $this->select_contextual_items($items);

        if (empty($display)) {
            return '';
        }

        return $this->render_list($display, $attributes);
    }

    /**
     * Build the flat item list from whichever source wins.
     *
     * @since 5.0.0
     *
     * @param array $attributes Block attributes.
     * @return array
     */
    private function resolve_items(array $attributes): array
    {
        // first, check for per-page overrides (custom pairs on the current post)
        $custom = $this->get_custom_override_items();
        if (!empty($custom)) {
            return $custom;
        }

        $selection = (string) ( $attributes['menuSlug'] ?? '' );

        // next, check for explicit selections on the block (skip both auto-detect and default sentinels)
        if ($selection !== '' && $selection !== 'autodetect' && $selection !== 'defaultsettings') {
            $items = $this->items_from_classic_menu($selection);
            if (!empty($items)) {
                return $items;
            }
        }

        // next, auto-detect a mega menu block on the page or active templates
        if ($selection === '' || $selection === 'autodetect') {
            $items = $this->items_from_mega_menu_blocks();
            if (!empty($items)) {
                return $items;
            }
        }

        // otherwise, fall back to the default related-links menu from plugin settings
        $default = $this->get_default_menu_slug();
        if ($default !== '') {
            $items = $this->items_from_classic_menu($default);
            if (!empty($items)) {
                return $items;
            }
        }

        return [];
    }

    /**
     * Select the contextual items to display for the current page.
     *
     * @since 5.0.0
     *
     * @param array $items Flat item list.
     * @return array
     */
    private function select_contextual_items(array $items): array
    {
        $current = null;
        foreach ($items as $item) {
            if (!empty($item['current'])) {
                $current = $item;
                break;
            }
        }

        // current page isn't in the menu, show top-level items
        if ($current === null) {
            return $this->children_of($items, '0');
        }

        // first, check for children of the current item
        $children = $this->children_of($items, (string) $current['id']);
        if (!empty($children)) {
            return $children;
        }

        // if no children, check for siblings of the current item
        $siblings = $this->children_of($items, (string) $current['parent_id']);
        if (!empty($siblings) && count($siblings) > 1) {
            return $siblings;
        }

        // if no children or siblings, check for direct parent and it's siblings
        if ((string) $current['parent_id'] !== '0') {
            $parent = null;
            foreach ($items as $item) {
                if ((string) $item['id'] === (string) $current['parent_id']) {
                    $parent = $item;
                    break;
                }
            }
            if ($parent !== null) {
                $parent_siblings = $this->children_of($items, (string) $parent['parent_id']);
                if (!empty($parent_siblings)) {
                    return $parent_siblings;
                }
            }
        }

        // otherwise, fallback to display the top level of the mega menu
        return $this->children_of($items, '0');
    }

    /**
     * Return items whose parent_id matches the given parent.
     *
     * @since 5.0.0
     *
     * @param array  $items     Flat item list.
     * @param string $parent_id Parent id to match against.
     * @return array
     */
    private function children_of(array $items, string $parent_id): array
    {
        return array_values(
            array_filter($items, function ($item) use ($parent_id) {
                return (string) $item['parent_id'] === $parent_id;
            }),
        );
    }

    /**
     * Render the final `<ul>` list from the selected items.
     *
     * @since 5.0.0
     *
     * @param array $items      Items to render.
     * @param array $attributes Block attributes (for wrapper attrs).
     * @return string
     */
    private function render_list(array $items, array $attributes): string
    {
        unset($attributes);

        $lis = '';
        foreach ($items as $item) {
            $title = (string) ( $item['title'] ?? '' );
            $url = (string) ( $item['url'] ?? '' );

            if ($title === '') {
                continue;
            }

            $classes = ['bvi-related-link'];
            if (!empty($item['current'])) {
                $classes[] = 'is-current';
            }
            $classes = array_merge($classes, (array) ( $item['classes'] ?? [] ));

            $class_attr = 'class="' . esc_attr(implode(' ', array_unique($classes))) . '"';

            if ($url !== '') {
                $lis .= sprintf('<li %s><a href="%s">%s</a></li>', $class_attr, esc_url($url), esc_html($title));
            } else {
                $lis .= sprintf('<li %s><span>%s</span></li>', $class_attr, esc_html($title));
            }
        }

        if ($lis === '') {
            return '';
        }

        $wrapper_attrs = get_block_wrapper_attributes([
            'class' => 'bvi-related-links',
        ]);

        return sprintf('<div %s><ul class="bvi-related-links__list">%s</ul></div>', $wrapper_attrs, $lis);
    }

    /**
     * Build items from the per-page Related Links metabox (if set).
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function get_custom_override_items(): array
    {
        global $post;

        if (empty($post) || empty($post->ID)) {
            return [];
        }

        $raw = RelatedLinksShortcode::get_custom_related_links((int) $post->ID);
        if (empty($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $entry) {
            $items[] = [
                'id' => 'custom-' . (string) $entry->ID,
                'parent_id' => '0',
                'url' => (string) ( $entry->url ?? '' ),
                'title' => (string) ( $entry->post_title ?? '' ),
                'current' => false,
                'classes' => (array) ( $entry->classes ?? [] ),
            ];
        }

        // mark this as the current page
        $this->mark_current($items);

        return $items;
    }

    /**
     * Build items by walking `bvi/mega-menu` and `bvi/menu-item` blocks.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function items_from_mega_menu_blocks(): array
    {
        $blocks = BlockScanner::collect_page_blocks();
        if (empty($blocks)) {
            return [];
        }

        $items = [];
        $this->walk_blocks_for_mega_menu($blocks, $items);

        if (empty($items)) {
            return [];
        }

        $this->mark_current($items);

        return $items;
    }

    /**
     * Parse a classic menu (or a wp_navigation post) into the flat item shape.
     *
     * @since 5.0.0
     *
     * @param string $menu_source Classic menu slug/id or `wp_navigation:{id}` identifier.
     * @return array
     */
    private function items_from_classic_menu(string $menu_source): array
    {
        if (strpos($menu_source, 'wp_navigation:') === 0) {
            $nav_post_id = (int) substr($menu_source, strlen('wp_navigation:'));
            $nav_post = $nav_post_id > 0 ? get_post($nav_post_id) : null;
            if ($nav_post === null || empty($nav_post->post_content)) {
                return [];
            }

            $blocks = parse_blocks($nav_post->post_content);
            $items = [];
            $this->walk_navigation_blocks($blocks, '0', $items);

            if (!empty($items)) {
                $this->mark_current($items);
            }

            return $items;
        }

        // classic menu by slug or id
        $menu_object = null;
        if (ctype_digit($menu_source)) {
            $menu_object = wp_get_nav_menu_object((int) $menu_source);
        }
        if ($menu_object === null || $menu_object === false) {
            $menu_object = wp_get_nav_menu_object($menu_source);
        }
        if (empty($menu_object) || is_wp_error($menu_object)) {
            return [];
        }

        $menu_items = wp_get_nav_menu_items($menu_object->term_id, ['update_post_term_cache' => false]);
        if (empty($menu_items) || is_wp_error($menu_items)) {
            return [];
        }

        $items = [];
        foreach ($menu_items as $menu_item) {
            // skip non-navigable custom item types added by the plugin (columns, shortcodes, menus)
            $type = isset($menu_item->type) ? (string) $menu_item->type : '';
            if (in_array($type, ['column', 'shortcode', 'menu'], true)) {
                continue;
            }

            $items[] = [
                'id' => (string) $menu_item->ID,
                'parent_id' => (string) ( $menu_item->menu_item_parent ?? '0' ),
                'url' => (string) ( $menu_item->url ?? '' ),
                'title' => (string) ( $menu_item->title ?? '' ),
                'current' => false,
                'classes' => (array) ( $menu_item->classes ?? [] ),
            ];
        }

        $this->mark_current($items);

        return $items;
    }

    /**
     * Find `bvi/mega-menu` blocks anywhere in the tree and flatten their descendants.
     *
     * @since 5.0.0
     *
     * @param array $blocks Parsed block array to scan.
     * @param array $items  Accumulator, passed by reference.
     * @return void
     */
    private function walk_blocks_for_mega_menu(array $blocks, array &$items): void
    {
        foreach ($blocks as $block) {
            $name = $block['blockName'] ?? '';

            if ($name === 'bvi/mega-menu') {
                $this->walk_menu_items($block['innerBlocks'] ?? [], '0', $items);
                continue;
            }

            if (!empty($block['innerBlocks'])) {
                $this->walk_blocks_for_mega_menu($block['innerBlocks'], $items);
            }
        }
    }

    /**
     * Flatten `bvi/menu-item` children (and nested children) under the given parent.
     *
     * @since 5.0.0
     *
     * @param array  $blocks    Parsed block array to scan.
     * @param string $parent_id Parent item id for nesting.
     * @param array  $items     Accumulator, passed by reference.
     * @return void
     */
    private function walk_menu_items(array $blocks, string $parent_id, array &$items): void
    {
        foreach ($blocks as $block) {
            if (( $block['blockName'] ?? '' ) !== 'bvi/menu-item') {
                continue;
            }

            $attrs = $block['attrs'] ?? [];
            $id = $this->synthetic_id($attrs, (string) ( $attrs['label'] ?? '' ), count($items));

            $items[] = [
                'id' => $id,
                'parent_id' => $parent_id,
                'url' => (string) ( $attrs['url'] ?? '' ),
                'title' => (string) ( $attrs['label'] ?? '' ),
                'current' => false,
                'classes' => [],
            ];

            if (!empty($block['innerBlocks'])) {
                $this->walk_menu_items($block['innerBlocks'], $id, $items);
            }
        }
    }

    /**
     * Walk a core/navigation block tree, contributing link and submenu items.
     *
     * @since 5.0.0
     *
     * @param array  $blocks    Parsed block array to scan.
     * @param string $parent_id Parent item id for nesting.
     * @param array  $items     Accumulator, passed by reference.
     * @return void
     */
    private function walk_navigation_blocks(array $blocks, string $parent_id, array &$items): void
    {
        foreach ($blocks as $block) {
            $name = $block['blockName'] ?? '';

            if ($name === 'core/navigation-link' || $name === 'core/navigation-submenu') {
                $attrs = $block['attrs'] ?? [];
                $id = $this->synthetic_id($attrs, (string) ( $attrs['label'] ?? '' ), count($items));

                $items[] = [
                    'id' => $id,
                    'parent_id' => $parent_id,
                    'url' => (string) ( $attrs['url'] ?? '' ),
                    'title' => (string) ( $attrs['label'] ?? '' ),
                    'current' => false,
                    'classes' => [],
                ];

                if (!empty($block['innerBlocks'])) {
                    $this->walk_navigation_blocks($block['innerBlocks'], $id, $items);
                }

                continue;
            }

            // core/navigation itself, or nested groups
            // recurse without adding an item.
            if (!empty($block['innerBlocks'])) {
                $this->walk_navigation_blocks($block['innerBlocks'], $parent_id, $items);
            }
        }
    }

    /**
     * Build a stable synthetic id for a block-sourced item.
     *
     * @since 5.0.0
     *
     * @param array  $attrs          Block attributes.
     * @param string $label          Item label.
     * @param int    $fallback_index Index used when no id/url/label is available.
     * @return string
     */
    private function synthetic_id(array $attrs, string $label, int $fallback_index): string
    {
        if (!empty($attrs['id'])) {
            return 'item-' . (string) $attrs['id'];
        }
        if (!empty($attrs['url'])) {
            return 'url-' . md5((string) $attrs['url']);
        }
        if ($label !== '') {
            return 'label-' . md5($label);
        }
        return 'idx-' . $fallback_index;
    }

    /**
     * Flag the item whose URL matches the current request.
     *
     * @since 5.0.0
     *
     * @param array $items Flat item list, passed by reference.
     * @return void
     */
    private function mark_current(array &$items): void
    {
        $current_url = self::current_url();
        if ($current_url === '') {
            return;
        }

        foreach ($items as &$item) {
            if (self::matches((string) ( $item['url'] ?? '' ), $current_url)) {
                $item['current'] = true;
            }
        }
        unset($item);
    }

    /**
     * Default related links menu slug (from the plugin's General settings).
     *
     * @since 5.0.0
     *
     * @return string
     */
    private function get_default_menu_slug(): string
    {
        $options = get_option(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_general_database_settings', []);
        if (!is_array($options)) {
            return '';
        }

        $default = (string) ( $options['default_related_links_menu'] ?? '' );

        // if setting stores values like `classic:{id}` or `block:{id}`, strip the prefix
        if (strpos($default, 'classic:') === 0) {
            return (string) substr($default, strlen('classic:'));
        }
        if (strpos($default, 'block:') === 0) {
            return 'wp_navigation:' . substr($default, strlen('block:'));
        }

        return $default;
    }
}
