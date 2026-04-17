<?php

namespace Bvi\Plugin\MegaMenu\Blocks\Menus;

use Bvi\Plugin\MegaMenu\Blocks\Menus\Walker;

/**
 * Renders menu HTML from an array of menu item objects.
 *
 * Shared by both blocks and shortcodes.
 *
 * @package bvi-mega-menu
 */
class Renderer
{
    /**
     * Render a menu as an HTML list.
     *
     * @param string $ul_id      The id attribute for the <ul> element.
     * @param array  $menu_items Array of menu item objects.
     * @param int    $depth      Maximum depth (-1 for unlimited).
     * @param object $args       Walker arguments.
     * @return string Rendered HTML.
     */
    public static function render(string $ul_id, array $menu_items, int $depth, object $args): string
    {
        // Build data attributes from args.
        $data = '';
        foreach ($args as $k => $v) {
            if ($v === '' || $k === 'mega_wrapper' || $k === 'mega_wrapper_end') {
                continue;
            }
            $data .= ' data-' . esc_attr($k) . '="' . esc_attr($v === true ? 'true' : $v) . '"';
        }

        $walker = new Walker();
        $html = '';

        // Mobile toggle button.
        if (!empty($args->mobile_toggle)) {
            $html .=
                '<button class="mobile-toggle" aria-label="Main Menu">' . esc_html($args->mobile_toggle) . '</button>';
        }

        // Container class differs for related links vs mega menu.
        $class =
            strpos($ul_id, 'related-links') !== false
                ? 'bvi-mega-menu-related-links-container'
                : 'bvi-mega-menu-container';

        $html .=
            PHP_EOL .
            '<ul id="' .
            esc_attr($ul_id) .
            '" class="' .
            esc_attr($class) .
            '"' .
            $data .
            ' data-home="' .
            esc_url(home_url()) .
            '">' .
            PHP_EOL .
            $walker->walk($menu_items, $depth, $args) .
            PHP_EOL .
            '</ul>' .
            PHP_EOL;

        return $html;
    }
}
