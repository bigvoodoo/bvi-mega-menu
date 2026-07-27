<?php

namespace Bvi\Plugin\MegaMenu\Blocks;

use Bvi\Plugin\MegaMenu\Blocks\Menus\Helper;
use Bvi\Plugin\MegaMenu\Blocks\Menus\Walker;

/**
 * Class MegaMenu
 *
 * @package bvi-mega-menu
 */
class MegaMenu extends AbstractBlock
{
    /**
     * Constructor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('bvi/mega-menu');
    }

    /**
     * Register the block type and REST routes.
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
     * @param string $content    Rendered InnerBlocks content (menu items).
     * @return string Rendered HTML.
     */
    public function render(array $attributes, string $content): string
    {
        $menu_slug = (string) ( $attributes['menuSlug'] ?? '' );
        $mobile_mode = (string) ( $attributes['mobileMode'] ?? 'none' );
        $mobile_breakpoint = (int) ( $attributes['mobileBreakpoint'] ?? 960 );
        $hamburger_style = (string) ( $attributes['hamburgerStyle'] ?? 'bars' );
        $hamburger_svg_id = (int) ( $attributes['hamburgerSvgId'] ?? 0 );
        $dropdown_trigger = (string) ( $attributes['dropdownTrigger'] ?? 'hover' );
        if (!in_array($dropdown_trigger, ['hover', 'click'], true)) {
            $dropdown_trigger = 'hover';
        }
        $show_dropdown_arrow = !isset($attributes['showDropdownArrow']) || !empty($attributes['showDropdownArrow']);
        $highlight_ancestors = !isset($attributes['highlightAncestors']) || !empty($attributes['highlightAncestors']);
        $dropdown_close_delay = isset($attributes['dropdownCloseDelay'])
            ? max(0, (int) $attributes['dropdownCloseDelay'])
            : 300;
        $mobile_levels = isset($attributes['mobileLevels']) ? max(0, (int) $attributes['mobileLevels']) : 1;

        $dropdown_span_parent = !empty($attributes['dropdownSpanParent']);

        $dropdown_panel_alignment = (string) ( $attributes['dropdownPanelAlignment'] ?? 'left' );
        if (!in_array($dropdown_panel_alignment, ['left', 'center', 'right'], true)) {
            $dropdown_panel_alignment = 'left';
        }

        $mobile_dropdown_alignment = (string) ( $attributes['mobileDropdownAlignment'] ?? 'viewport' );
        if (!in_array($mobile_dropdown_alignment, ['left', 'right', 'viewport'], true)) {
            $mobile_dropdown_alignment = 'viewport';
        }

        $has_mobile = $mobile_mode !== 'none';

        $settings = $this->get_plugin_settings();
        $instant_dropdown = !empty($settings['dropdown_val']);

        $classes = ['bvi-mega-menu', 'bvi-mega-menu-mobile-' . $mobile_mode];
        $classes[] = 'dropdown-trigger-' . $dropdown_trigger;
        $classes[] = 'bvi-mm-panel-align-' . $dropdown_panel_alignment;
        $classes[] = 'bvi-mm-mobile-align-' . $mobile_dropdown_alignment;
        if (!$show_dropdown_arrow) {
            $classes[] = 'hide-dropdown-arrow';
        }
        if ($highlight_ancestors) {
            $classes[] = 'bvi-mm-highlight-ancestors';
        }
        $classes = array_merge($classes, self::active_state_classes($attributes));
        if ($instant_dropdown) {
            $classes[] = 'is-instant-dropdown';
        }

        $style_vars = $this->build_css_vars($attributes);

        $wrapper_attrs = get_block_wrapper_attributes([
            'class' => implode(' ', $classes),
            'style' => $style_vars,
            'data-mobile-mode' => $mobile_mode,
            'data-mobile-breakpoint' => (string) $mobile_breakpoint,
            'data-dropdown-trigger' => $dropdown_trigger,
            'data-close-delay' => (string) $dropdown_close_delay,
            'data-instant-dropdown' => $instant_dropdown ? 'true' : 'false',
            'data-dropdown-span-parent' => $dropdown_span_parent ? 'true' : 'false',
            'data-dropdown-panel-alignment' => $dropdown_panel_alignment,
            'data-mobile-dropdown-alignment' => $mobile_dropdown_alignment,
            'data-mobile-levels' => (string) $mobile_levels,
        ]);

        $hamburger_html = $has_mobile ? $this->render_hamburger($hamburger_style, $hamburger_svg_id) : '';

        // backwards compatibility for classic menus being rendered
        if ($menu_slug !== '') {
            $classic_html = $this->render_from_menu_slug($menu_slug);
            if ($classic_html !== '') {
                \Bvi\Plugin\MegaMenu\Frontend::enqueue_menu_assets();
                $nav_items = $classic_html;
            } else {
                $nav_items = trim($content) !== '' ? '<ul class="bvi-mega-menu-list">' . $content . '</ul>' : '';
            }
        } else {
            $nav_items = trim($content) !== '' ? '<ul class="bvi-mega-menu-list">' . $content . '</ul>' : '';
        }

        // optional mobile override for backwards compatibility
        $mobile_override_html = '';
        if ($has_mobile && !empty($settings['mobile_override_val'])) {
            $mobile_override_html = $this->render_mobile_override((string) $settings['mobile_override_val']);
        }

        $overlay = $mobile_mode === 'popup' ? '<div class="bvi-mega-menu-overlay" aria-hidden="true"></div>' : '';

        return sprintf(
            '<nav %s>%s<div class="bvi-mega-menu-nav">%s</div>%s%s</nav>',
            $wrapper_attrs,
            $hamburger_html,
            $nav_items,
            $mobile_override_html,
            $overlay,
        );
    }

    /**
     * Render a menu by slug or wp_navigation ID through the Walker.
     *
     * @since 5.0.0
     *
     * @param string $menu_slug Classic menu slug or `wp_navigation:{id}` identifier.
     * @return string Rendered HTML `<ul>` or empty string when the menu cannot be resolved.
     */
    private function render_from_menu_slug(string $menu_slug): string
    {
        if (strpos($menu_slug, 'wp_navigation:') === 0) {
            $nav_post_id = (int) substr($menu_slug, strlen('wp_navigation:'));
            $menu_items = $this->navigation_post_to_menu_items($nav_post_id);
        } else {
            $menu_object = wp_get_nav_menu_object($menu_slug);
            if (empty($menu_object) || is_wp_error($menu_object)) {
                return '';
            }

            $menu_items = wp_get_nav_menu_items($menu_object->term_id);
            if (empty($menu_items) || is_wp_error($menu_items)) {
                return '';
            }

            foreach ($menu_items as $menu_item) {
                $menu_item->parent_id = (int) ( $menu_item->menu_item_parent ?? 0 );
                // wp_setup_nav_menu_item() resolves the label into ->title; the Walker reads ->post_title.
                $menu_item->post_title = $menu_item->title ?? ( $menu_item->post_title ?? '' );
                $is_post_object =
                    isset($menu_item->object_id) && in_array($menu_item->object ?? '', ['page', 'post'], true);
                $menu_item->post_id = $is_post_object ? (int) $menu_item->object_id : 0;
                if (!isset($menu_item->url) && !$menu_item->post_id) {
                    $menu_item->url = '';
                }
            }
        }

        if (empty($menu_items)) {
            return '';
        }

        $args = (object) [
            'before' => '',
            'after' => '',
            'link_before' => '',
            'link_after' => '',
        ];

        $walker = new Walker();
        $output = $walker->walk($menu_items, 0, $args);

        if (!is_string($output) || $output === '') {
            return '';
        }

        return '<ul class="bvi-mega-menu-list bvi-mega-menu-container">' . $output . '</ul>';
    }

    /**
     * Convert a wp_navigation post's blocks into Walker-compatible menu item objects.
     *
     * @since 5.0.0
     *
     * @param int $nav_post_id Navigation post ID.
     * @return array<int, object>
     */
    private function navigation_post_to_menu_items(int $nav_post_id): array
    {
        if ($nav_post_id <= 0) {
            return [];
        }

        $nav_post = get_post($nav_post_id);
        if ($nav_post === null || empty($nav_post->post_content)) {
            return [];
        }

        $items = [];
        $next_id = 1;
        $this->walk_navigation_blocks(parse_blocks($nav_post->post_content), 0, $items, $next_id);

        return $items;
    }

    /**
     * Recursively convert core/navigation-* blocks to menu item objects.
     *
     * @since 5.0.0
     *
     * @param array              $blocks    Parsed block array from parse_blocks().
     * @param int                $parent_id Parent item ID for nesting.
     * @param array<int, object> $items     Accumulator array, passed by reference.
     * @param int                $next_id   Auto-increment counter, passed by reference.
     * @return void
     */
    private function walk_navigation_blocks(array $blocks, int $parent_id, array &$items, int &$next_id): void
    {
        foreach ($blocks as $block) {
            $name = $block['blockName'] ?? '';

            if ($name === 'core/navigation-link' || $name === 'core/navigation-submenu') {
                $attrs = $block['attrs'] ?? [];
                $id = $next_id++;

                $item = (object) [
                    'ID' => $id,
                    'parent_id' => $parent_id,
                    'post_id' => (int) ( $attrs['id'] ?? 0 ),
                    'url' => (string) ( $attrs['url'] ?? '' ),
                    'post_title' => (string) ( $attrs['label'] ?? '' ),
                    'attr_title' => (string) ( $attrs['title'] ?? '' ),
                    'target' => !empty($attrs['opensInNewTab']) ? '_blank' : '',
                    'xfn' => (string) ( $attrs['rel'] ?? '' ),
                    'classes' => [],
                    'type' => 'post_type',
                ];

                // if it has no post_id, the walker will use the url instead.
                if (!$item->post_id && $item->url === '') {
                    $item->url = '#';
                }

                $items[] = $item;

                if (!empty($block['innerBlocks'])) {
                    $this->walk_navigation_blocks($block['innerBlocks'], $id, $items, $next_id);
                }
                continue;
            }

            // recurse through containers without adding an item.
            if (!empty($block['innerBlocks'])) {
                $this->walk_navigation_blocks($block['innerBlocks'], $parent_id, $items, $next_id);
            }
        }
    }

    /**
     * Render the mobile-only override classic menu by identifier.
     *
     * @since 5.0.0
     *
     * @param string $menu_identifier Settings-style identifier (`classic:{id}`, `block:{id}`), slug, or term ID.
     * @return string Rendered HTML or empty string when the identifier cannot be resolved.
     */
    private function render_mobile_override(string $menu_identifier): string
    {
        // Block-theme navigation: render the core/navigation as a <ul>.
        if (strpos($menu_identifier, 'block:') === 0) {
            $nav_post_id = (int) substr($menu_identifier, strlen('block:'));
            $html = $this->render_navigation_post($nav_post_id);
            if ($html === '') {
                return '';
            }
            return '<div class="bvi-mega-menu-mobile-override">' . $html . '</div>';
        }

        // strip the `classic:` prefix if present; leave anything else alone.
        $menu =
            strpos($menu_identifier, 'classic:') === 0
                ? substr($menu_identifier, strlen('classic:'))
                : $menu_identifier;

        if ($menu === '' || $menu === '1') {
            return '';
        }

        // confirm the menu actually exists before asking wp_nav_menu to render it.
        $resolved = ctype_digit($menu) ? wp_get_nav_menu_object((int) $menu) : wp_get_nav_menu_object($menu);
        if (empty($resolved) || is_wp_error($resolved)) {
            return '';
        }

        $html = wp_nav_menu([
            'menu' => $resolved->term_id,
            'container' => 'div',
            'container_class' => 'bvi-mega-menu-mobile-override',
            'menu_class' => 'bvi-mega-menu-mobile-override-list',
            'echo' => false,
            'fallback_cb' => '__return_empty_string',
        ]);

        return is_string($html) ? $html : '';
    }

    /**
     * Render a wp_navigation post as a flat `<ul>` list using the Walker.
     *
     * @since 5.0.0
     *
     * @param int $nav_post_id Navigation post ID.
     * @return string Rendered HTML or empty string when items cannot be resolved.
     */
    private function render_navigation_post(int $nav_post_id): string
    {
        $menu_items = $this->navigation_post_to_menu_items($nav_post_id);
        if (empty($menu_items)) {
            return '';
        }

        $args = (object) [
            'before' => '',
            'after' => '',
            'link_before' => '',
            'link_after' => '',
        ];

        $walker = new Walker();
        $output = $walker->walk($menu_items, 0, $args);

        if (!is_string($output) || $output === '') {
            return '';
        }

        return '<ul class="bvi-mega-menu-mobile-override-list">' . $output . '</ul>';
    }

    /**
     * Read the plugin's General settings from the database.
     *
     * @since 5.0.0
     *
     * @return array<string, mixed>
     */
    private function get_plugin_settings(): array
    {
        if (!defined('BVI_PLUGIN_MEGAMENU_NAMESPACE')) {
            return [];
        }

        $options = get_option(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_general_database_settings', []);

        return is_array($options) ? $options : [];
    }

    /**
     * Wrapper classes marking which surfaces have colours configured.
     *
     * The flyout and active-state rules are scoped under these classes, so a menu
     * saved before those settings did anything gains no new declarations and renders
     * exactly as it did before. Submenu-active counts dropdown-active as configured
     * too, because the flyout variables fall back to the dropdown ones.
     *
     * @since 5.0.0
     *
     * @param array $attributes Block attributes array.
     * @return array<int, string>
     */
    private static function active_state_classes(array $attributes): array
    {
        $surfaces = [
            'bvi-mm-has-submenu' => [
                'submenuBackgroundColor',
                'submenuBackgroundColorHover',
                'submenuTextColor',
                'submenuTextColorHover',
                'submenuBackgroundColorActive',
                'submenuTextColorActive',
            ],
            'bvi-mm-has-link-active' => ['linkTextColorActive', 'linkBackgroundColorActive'],
            'bvi-mm-has-dropdown-active' => ['dropdownTextColorActive', 'dropdownBackgroundColorActive'],
            'bvi-mm-has-submenu-active' => [
                'submenuTextColorActive',
                'submenuBackgroundColorActive',
                'dropdownTextColorActive',
                'dropdownBackgroundColorActive',
            ],
            'bvi-mm-has-mobile-active' => ['mobileMenuTextColorActive', 'mobileMenuLinkBackgroundColorActive'],
        ];

        $classes = [];

        foreach ($surfaces as $class => $attrs) {
            foreach ($attrs as $attr) {
                if ((string) ( $attributes[$attr] ?? '' ) !== '') {
                    $classes[] = $class;
                    break;
                }
            }
        }

        return $classes;
    }

    /**
     * Assemble the CSS custom properties that drive the menu's styling.
     *
     * @since 5.0.0
     *
     * @param array $attributes Block attributes array.
     * @return string Semicolon-separated CSS custom property declarations.
     */
    private function build_css_vars(array $attributes): string
    {
        $map = [
            'linkTextColorHover' => '--bvi-mm-link-color-hover',
            'linkBackgroundColorHover' => '--bvi-mm-link-bg-hover',
            'linkTextColorActive' => '--bvi-mm-link-color-active',
            'linkBackgroundColorActive' => '--bvi-mm-link-bg-active',
            'dropdownBackgroundColor' => '--bvi-mm-dropdown-bg',
            'dropdownBackgroundColorHover' => '--bvi-mm-dropdown-bg-hover',
            'dropdownTextColor' => '--bvi-mm-dropdown-color',
            'dropdownTextColorHover' => '--bvi-mm-dropdown-color-hover',
            'dropdownTextColorActive' => '--bvi-mm-dropdown-color-active',
            'dropdownBackgroundColorActive' => '--bvi-mm-dropdown-bg-active',
            'submenuBackgroundColor' => '--bvi-mm-submenu-bg',
            'submenuBackgroundColorHover' => '--bvi-mm-submenu-bg-hover',
            'submenuTextColor' => '--bvi-mm-submenu-color',
            'submenuTextColorHover' => '--bvi-mm-submenu-color-hover',
            'submenuTextColorActive' => '--bvi-mm-submenu-color-active',
            'submenuBackgroundColorActive' => '--bvi-mm-submenu-bg-active',
            'overlayBackgroundColor' => '--bvi-mm-overlay-bg',
            'hamburgerColor' => '--bvi-mm-hamburger-color',
            'hamburgerColorHover' => '--bvi-mm-hamburger-color-hover',
            'hamburgerBackgroundColor' => '--bvi-mm-hamburger-bg',
            'hamburgerBackgroundColorHover' => '--bvi-mm-hamburger-bg-hover',
            'mobileMenuBackgroundColor' => '--bvi-mm-mobile-bg',
            'mobileMenuLinkBackgroundColor' => '--bvi-mm-mobile-link-bg',
            'mobileMenuLinkBackgroundColorHover' => '--bvi-mm-mobile-link-bg-hover',
            'mobileMenuTextColor' => '--bvi-mm-mobile-color',
            'mobileMenuTextColorHover' => '--bvi-mm-mobile-color-hover',
            'mobileMenuTextColorActive' => '--bvi-mm-mobile-color-active',
            'mobileMenuLinkBackgroundColorActive' => '--bvi-mm-mobile-link-bg-active',
            'hamburgerBackgroundColorOpen' => '--bvi-mm-hamburger-bg-open',
            'mobileFontSize' => '--bvi-mm-mobile-font-size',
            'mobileFontWeight' => '--bvi-mm-mobile-font-weight',
            'mobileFontStyle' => '--bvi-mm-mobile-font-style',
            'mobileTextTransform' => '--bvi-mm-mobile-text-transform',
            'mobileTextDecoration' => '--bvi-mm-mobile-text-decoration',
            'mobileLetterSpacing' => '--bvi-mm-mobile-letter-spacing',
            'mobileTextAlign' => '--bvi-mm-mobile-text-align',
            'mobileBorderWidth' => '--bvi-mm-mobile-border-width',
            'mobileBorderStyle' => '--bvi-mm-mobile-border-style',
            'mobileBorderColor' => '--bvi-mm-mobile-border-color',
            'mobileNavPadding' => '--bvi-mm-mobile-nav-padding',
            'itemGap' => '--bvi-mm-item-gap',
            'dropdownItemGap' => '--bvi-mm-dropdown-item-gap',
            'popupItemGap' => '--bvi-mm-popup-item-gap',
        ];

        $breakpoint = (int) ( $attributes['mobileBreakpoint'] ?? 960 );
        $parts = ['--bvi-mm-breakpoint: ' . $breakpoint . 'px'];

        foreach ($map as $attr => $var) {
            $value = (string) ( $attributes[$attr] ?? '' );
            if ($value === '') {
                continue;
            }
            $parts[] = $var . ': ' . $value;
        }

        $text_decoration = (string) ( $attributes['style']['typography']['textDecoration'] ?? '' );
        if (in_array($text_decoration, ['none', 'underline', 'overline', 'line-through'], true)) {
            $parts[] = '--bvi-mm-text-decoration: ' . $text_decoration;
        }

        /**
         * Do not remove the extra semi-colon! Please!
         *
         * get_block_wrapper_attributes() merges core-generated styles (typography, spacing)
         * onto this string with a bare space, so without it the last declaration swallows them
         * (e.g. `--bvi-mm-item-gap: 1rem letter-spacing: 2px`).
         */
        return implode(';', $parts) . ';';
    }

    /**
     * Render the hamburger toggle button markup.
     *
     * @since 5.0.0
     *
     * @param string $style   Icon style: 'bars', 'svg', or 'custom'.
     * @param int    $svg_id  Attachment ID for the SVG variant.
     * @return string Rendered button HTML.
     */
    private function render_hamburger(string $style, int $svg_id): string
    {
        $inner = '';

        if ($style === 'svg' && $svg_id > 0) {
            $svg_url = wp_get_attachment_url($svg_id);
            if (is_string($svg_url) && $svg_url !== '') {
                $inner = sprintf(
                    '<img class="bvi-mega-menu-hamburger-svg" src="%s" alt="" aria-hidden="true" />',
                    esc_url($svg_url),
                );
            }
        } elseif ($style === 'custom') {
            // child themes can override and filter their own markup
            $custom = apply_filters('bvi_nav_hamburger_open_icon', '');
            if (is_string($custom) && $custom !== '') {
                $inner = $custom;
            }
        }

        if ($inner === '') {
            // defaults to three lines transform to X
            $inner =
                '<span class="bvi-mega-menu-hamburger-bar" aria-hidden="true"></span>' .
                '<span class="bvi-mega-menu-hamburger-bar" aria-hidden="true"></span>' .
                '<span class="bvi-mega-menu-hamburger-bar" aria-hidden="true"></span>';
        }

        return sprintf(
            '<button type="button" class="bvi-mega-menu-hamburger" aria-expanded="false" aria-label="%s">%s</button>',
            esc_attr__('Toggle menu', 'bvi-mega-menu'),
            $inner,
        );
    }
}
