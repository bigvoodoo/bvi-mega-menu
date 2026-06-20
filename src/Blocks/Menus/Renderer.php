<?php

namespace Bvi\Plugin\MegaMenu\Blocks\Menus;

use Bvi\Plugin\MegaMenu\Blocks\Menus\Walker;
use Bvi\Plugin\MegaMenu\Frontend;

/**
 * Renders a complete `.bvi-mega-menu` nav from an array of menu item objects.
 *
 * Used by the classic `[mega_menu]` shortcode. Emits the same wrapper markup
 * and data attributes as the `bvi/mega-menu` block so the shared view script
 * and stylesheets drive the menu identically.
 *
 * @package bvi-mega-menu
 */
class Renderer
{
    /**
     * Render a classic menu as a full mega-menu nav element.
     *
     * @since 5.0.0
     *
     * @param string $ul_id      The id attribute for the list element.
     * @param array  $menu_items Array of menu item objects.
     * @param int    $depth      Maximum depth (-1 for unlimited).
     * @param object $args       Display options (see defaults below).
     * @return string Rendered HTML.
     */
    public static function render(string $ul_id, array $menu_items, int $depth, object $args): string
    {
        // Ensure the menu-item / mega-panel styles and the view script load.
        Frontend::enqueue_menu_assets();

        $mobile_mode = (string) ( $args->mobile_mode ?? 'none' );
        $mobile_breakpoint = (int) ( $args->mobile_breakpoint ?? 960 );
        $dropdown_trigger = ( $args->dropdown_trigger ?? 'hover' ) === 'click' ? 'click' : 'hover';
        $close_delay = max(0, (int) ( $args->close_delay ?? 300 ));
        $instant_dropdown = !empty($args->instant_dropdown);
        $span_parent = !empty($args->dropdown_span_parent);
        $panel_alignment = (string) ( $args->dropdown_panel_alignment ?? 'left' );
        if (!in_array($panel_alignment, ['left', 'center', 'right'], true)) {
            $panel_alignment = 'left';
        }
        $mobile_alignment = (string) ( $args->mobile_dropdown_alignment ?? 'viewport' );
        if (!in_array($mobile_alignment, ['left', 'right', 'viewport'], true)) {
            $mobile_alignment = 'viewport';
        }
        $mobile_levels = max(0, (int) ( $args->mobile_levels ?? 1 ));

        $classes = [
            'bvi-mega-menu',
            'bvi-mega-menu-mobile-' . $mobile_mode,
            'dropdown-trigger-' . $dropdown_trigger,
            'bvi-mm-panel-align-' . $panel_alignment,
            'bvi-mm-mobile-align-' . $mobile_alignment,
        ];
        if ($instant_dropdown) {
            $classes[] = 'is-instant-dropdown';
        }

        $data = sprintf(
            ' data-mobile-mode="%s" data-mobile-breakpoint="%d" data-dropdown-trigger="%s"' .
                ' data-close-delay="%d" data-instant-dropdown="%s" data-dropdown-span-parent="%s"' .
                ' data-dropdown-panel-alignment="%s" data-mobile-dropdown-alignment="%s" data-mobile-levels="%d"',
            esc_attr($mobile_mode),
            $mobile_breakpoint,
            esc_attr($dropdown_trigger),
            $close_delay,
            $instant_dropdown ? 'true' : 'false',
            $span_parent ? 'true' : 'false',
            esc_attr($panel_alignment),
            esc_attr($mobile_alignment),
            $mobile_levels,
        );

        $hamburger = $mobile_mode !== 'none' ? self::render_hamburger() : '';

        $overlay = $mobile_mode === 'popup' ? '<div class="bvi-mega-menu-overlay" aria-hidden="true"></div>' : '';

        $walker = new Walker();
        $items_html = $walker->walk($menu_items, $depth, $args);

        $list =
            '<ul id="' .
            esc_attr($ul_id) .
            '" class="bvi-mega-menu-list bvi-mega-menu-container" data-home="' .
            esc_url(home_url()) .
            '">' .
            $items_html .
            '</ul>';

        return sprintf(
            '<nav class="%s"%s>%s<div class="bvi-mega-menu-nav">%s</div>%s</nav>',
            esc_attr(implode(' ', $classes)),
            $data,
            $hamburger,
            $list,
            $overlay,
        );
    }

    /**
     * Default three-bar hamburger toggle for the mobile menu.
     *
     * @since 5.0.0
     *
     * @return string
     */
    private static function render_hamburger(): string
    {
        return sprintf(
            '<button type="button" class="bvi-mega-menu-hamburger" aria-expanded="false" aria-label="%s">' .
                '<span class="bvi-mega-menu-hamburger-bar" aria-hidden="true"></span>' .
                '<span class="bvi-mega-menu-hamburger-bar" aria-hidden="true"></span>' .
                '<span class="bvi-mega-menu-hamburger-bar" aria-hidden="true"></span>' .
                '</button>',
            esc_attr__('Toggle menu', 'bvi-mega-menu'),
        );
    }
}
