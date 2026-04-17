<?php

namespace Bvi\Plugin\MegaMenu\Admin\Settings;

/**
 * Class Menu
 *
 * @package bvimegamenu/src
 */
class Menu
{
    /**
     * Adds a top-level menu page.
     *
     * @since 0.1.0
     *
     * @param string $page_id The page ID
     * @param array $options The page options
     * @param callable $callback The callback function to render the page
     * @return void
     */
    public function add_menu_page($page_id, $options, $callback)
    {
        if (empty($page_id) || empty($options['page_title']) || empty($options['menu_title'])) {
            return;
        }

        add_menu_page(
            $options['page_title'],
            $options['menu_title'],
            $options['capability'] ?? 'edit_theme_options',
            $page_id,
            $callback,
            $options['menu_icon'] ?? 'dashicons-generic',
            $options['position'] ?? 99,
        );

        $parent_page_id = $page_id;

        if (!empty($options['submenu_title'])) {
            $options['menu_title'] = $options['submenu_title'];
        }

        $options['position'] = 1;

        $this->add_submenu_page($parent_page_id, $page_id, $options, $callback);
    }

    /**
     * Adds a submenu page to the given parent page
     *
     * @since 0.1.0
     *
     * @param string $parent_page_id The parent page ID
     * @param string $page_id The page ID
     * @param array $options The page options
     * @param callable $callback The callback function to render the page
     * @return void
     */
    public function add_submenu_page($parent_page_id, $page_id, $options, $callback)
    {
        if (
            empty($parent_page_id) ||
            empty($page_id) ||
            empty($options['page_title']) ||
            empty($options['menu_title'])
        ) {
            return;
        }

        add_submenu_page(
            $parent_page_id,
            $options['page_title'],
            $options['menu_title'],
            $options['capability'] ?? 'manage_options',
            $page_id,
            $callback,
            $options['position'] ?? 99,
        );
    }
}
