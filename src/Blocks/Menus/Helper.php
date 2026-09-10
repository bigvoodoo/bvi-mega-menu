<?php

namespace Bvi\Plugin\MegaMenu\Blocks\Menus;

use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

/**
 * Loads and processes menu items from the mega_menu database table.
 *
 * Shared by both blocks and shortcodes.
 *
 * @package bvi-mega-menu
 */
class Helper
{
    use Singleton;

    /** @var array Cached menu structures keyed by menu_id. */
    private static $menu_cache = [];

    /** @var bool Whether REST routes have been registered. */
    private static $routes_registered = false;

    public function __construct() {}

    /**
     * Exposes block nav menus to the REST API for the editor's menu picker.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_rest_routes()
    {
        // verify if the routes have already been registered or not
        if (self::$routes_registered) {
            return;
        }

        self::$routes_registered = true;

        register_rest_route('bvi/v1', '/menus', [
            'methods' => 'GET',
            'callback' => function () {
                $data = [];

                // get the menu objects through the helper class
                $data = Helper::get_menu_objects();

                return rest_ensure_response($data);
            },
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    /**
     * Retrieves an array of all menu objects, including both classic nav menus and block-based navigation posts.
     *
     * @since 5.0.0
     *
     * @return array $data An array of menu objects with id, name, slug, and count keys.
     */
    public static function get_menu_objects(string $type = 'all'): array
    {
        $classic_data = [];
        $block_data = [];

        // classic nav menus (Appearance > Menus)
        $menus = wp_get_nav_menus();
        foreach ($menus as $menu) {
            $classic_data[] = [
                'id' => $menu->term_id,
                'name' => $menu->name,
                'slug' => $menu->slug,
                'count' => $menu->count,
            ];
        }

        // block-based navigation posts (Site Editor)
        $nav_posts = get_posts([
            'post_type' => 'wp_navigation',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        foreach ($nav_posts as $post) {
            $block_data[] = [
                'id' => $post->ID,
                'name' => $post->post_title ?: __('(untitled)', 'bvi-minimal'),
                'slug' => 'wp_navigation:' . $post->ID,
                'count' => 0,
            ];
        }

        // return specific data only if requested
        if ($type === 'classic') {
            return $classic_data;
        } elseif ($type === 'block') {
            return $block_data;
        }

        // otherwise, return it all
        return array_merge($classic_data, $block_data);
    }

    /**
     * Retrieves a menu object by its slug.
     *
     * @since 5.0.0
     *
     * @param string $menu_slug Slug of the menu to retrieve.
     * @return object|null The menu object if found, or null.
     */
    public function get_menu_object_by_slug(string $menu_slug): ?object
    {
        $data = $this->get_menu_objects();

        return $data
            ? array_filter($data, function ($menu) use ($menu_slug) {
                return $menu['slug'] === $menu_slug;
            })[0]
            : null;
    }

    /**
     * Builds an array of menu options (classic and block) for the default related links menu select.
     *
     * @since 5.0.0
     *
     * @return array $menu_options An array of menu options with value and label keys.
     */
    public function build_menu_options()
    {
        $menu_options = [];

        // retrieve and add all classic menu options
        $classic_menus = $this->get_menu_objects('classic');
        if (!empty($classic_menus)) {
            $menu_options[] = [
                'value' => '',
                'label' => __('── Classic Menus ──', 'bvi-mega-menu'),
            ];

            foreach ($classic_menus as $menu) {
                $menu_options[] = [
                    'value' => 'classic:' . $menu->term_id,
                    'label' => $menu->name,
                ];
            }
        }

        // retrieve and add all block navigation options
        $nav_blocks = $this->get_menu_objects('block');
        if (!empty($nav_blocks)) {
            $menu_options[] = [
                'value' => '',
                'label' => __('── Navigation Blocks ──', 'bvi-mega-menu'),
            ];

            foreach ($nav_blocks as $nav) {
                $menu_options[] = [
                    'value' => 'block:' . $nav->ID,
                    'label' => $nav->post_title ?: __('(no title)', 'bvi-mega-menu'),
                ];
            }
        }

        // based on what was found, prefill the first value of the dropdown
        if (empty($menu_options)) {
            array_unshift($menu_options, [
                'value' => '',
                'label' => __('No menus found.', 'bvi-mega-menu'),
            ]);
        } else {
            array_unshift($menu_options, [
                [
                    'value' => '',
                    'label' => '— Select a Menu —',
                ],
            ]);
        }

        // return the options array for the dropdown
        return $menu_options;
    }
}
