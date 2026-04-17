<?php

namespace Bvi\Plugin\MegaMenu\Service\Shortcode;

use Bvi\Plugin\MegaMenu\Blocks\Menus\Renderer;

/**
 * [mega_menu] shortcode for classic theme support.
 *
 * Kept for sites that still use `[mega_menu menu="some-slug"]` in template
 * files or post content. The FSE/block replacement is `bvi/mega-menu` with a
 * `menuSlug` attribute.
 *
 * Accepted attributes:
 *   menu           — classic menu slug or numeric id (required).
 *   mobile_toggle  — label text for an optional mobile-only toggle button.
 *   aria_button    — 'true' to add aria-haspopup buttons on top-level items.
 *
 * @package bvi-mega-menu
 */
class MegaMenuShortcode
{
    public function __construct()
    {
        add_shortcode('mega_menu', [$this, 'render']);
    }

    /**
     * Render the mega menu shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function render($atts = []): string
    {
        $atts = (array) $atts;

        $args = (object) shortcode_atts(
            [
                'menu' => '',
                'before' => '',
                'after' => '',
                'link_before' => '',
                'link_after' => '',
                'mobile_toggle' => '',
                'aria_button' => 'false',
            ],
            $atts,
            'mega_menu',
        );

        $menu_slug = (string) $args->menu;
        if ($menu_slug === '') {
            return '<!-- [mega_menu] needs a menu="slug" attribute. -->';
        }

        $menu_object = wp_get_nav_menu_object($menu_slug);
        if (empty($menu_object) || is_wp_error($menu_object)) {
            return '<!-- [mega_menu] could not find menu "' . esc_html($menu_slug) . '". -->';
        }

        $menu_items = wp_get_nav_menu_items($menu_object->term_id);
        if (empty($menu_items) || is_wp_error($menu_items)) {
            return '';
        }

        // Adapt classic menu items to the Walker's expected shape.
        foreach ($menu_items as $menu_item) {
            $menu_item->parent_id = (int) ($menu_item->menu_item_parent ?? 0);
            $menu_item->post_id = in_array($menu_item->object ?? '', ['page', 'post'], true)
                ? (int) ($menu_item->object_id ?? 0)
                : 0;
            if (!isset($menu_item->url) && !$menu_item->post_id) {
                $menu_item->url = '';
            }
        }

        $id_name = 'mega-menu-' . sanitize_key($menu_slug) . '-' . wp_rand(1, 10000);

        $html = Renderer::render($id_name, $menu_items, -1, $args);

        // Optional mobile override from settings.
        $options = defined('BVI_PLUGIN_MEGAMENU_NAMESPACE')
            ? (array) get_option(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_general_database_settings', [])
            : [];
        $mobile_override = (string) ($options['mobile_override_val'] ?? '');

        if ($mobile_override !== '' && $mobile_override !== '1') {
            $override_menu =
                strpos($mobile_override, 'classic:') === 0
                    ? substr($mobile_override, strlen('classic:'))
                    : $mobile_override;

            $resolved = ctype_digit($override_menu)
                ? wp_get_nav_menu_object((int) $override_menu)
                : wp_get_nav_menu_object($override_menu);

            if (!empty($resolved) && !is_wp_error($resolved)) {
                $mobile_html = wp_nav_menu([
                    'menu' => $resolved->term_id,
                    'menu_class' => 'bvi-mega-menu-custom-mobile-menu',
                    'container' => '',
                    'echo' => false,
                    'fallback_cb' => '__return_empty_string',
                ]);
                if (is_string($mobile_html)) {
                    $html .= $mobile_html;
                }
            }
        }

        return $html;
    }
}
