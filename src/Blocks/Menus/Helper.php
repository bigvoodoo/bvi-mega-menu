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

    public function __construct()
    {
    }

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

        // Classic nav menus (Appearance > Menus).
        $menus = wp_get_nav_menus();
        foreach ($menus as $menu) {
            $classic_data[] = [
                'id' => $menu->term_id,
                'name' => $menu->name,
                'slug' => $menu->slug,
                'count' => $menu->count,
            ];
        }

        // Block-based navigation posts (Site Editor).
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

    // /**
    //  * Load menu items from the database and unpack their JSON data.
    //  *
    //  * Results are cached per menu_id for the duration of the request.
    //  *
    //  * @param int      $menu_id            The menu term ID.
    //  * @param int|null $override_parent_id Optional parent ID override (for sub-menu loading).
    //  * @return array Array of menu item objects.
    //  */
    // public static function load(int $menu_id, ?int $override_parent_id = null): array
    // {
    //     $cache_key = $menu_id . ':' . ($override_parent_id ?? 'null');

    //     if (isset(self::$menu_cache[$cache_key])) {
    //         return self::$menu_cache[$cache_key];
    //     }

    //     $raw_items = self::query_items($menu_id, $override_parent_id);
    //     $items = self::unpack_items($raw_items, $menu_id);

    //     self::$menu_cache[$cache_key] = $items;

    //     return $items;
    // }

    // /**
    //  * Query menu items from the mega_menu table.
    //  *
    //  * @param int      $menu_id            The menu term ID.
    //  * @param int|null $override_parent_id Optional parent ID to inject.
    //  * @return array Raw database result objects.
    //  */
    // private static function query_items(int $menu_id, ?int $override_parent_id = null): array
    // {
    //     global $wpdb;

    //     $table_name = $wpdb->prefix . 'mega_menu';
    //     $parent_col = $override_parent_id !== null
    //         ? $wpdb->prepare('%d as `parent_id`', $override_parent_id)
    //         : "`{$table_name}`.`parent_id`";

    //     // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    //     $results = $wpdb->get_results(
    //         $wpdb->prepare(
    //             "SELECT `{$table_name}`.`ID`, `{$table_name}`.`post_id`, `{$wpdb->posts}`.`post_title`,
    //                 {$parent_col}, `{$table_name}`.`position`, `{$table_name}`.`data`
    //             FROM `{$table_name}`
    //             LEFT JOIN `{$wpdb->posts}` ON `{$table_name}`.`post_id` = `{$wpdb->posts}`.`ID`
    //             WHERE `{$table_name}`.`menu_id` = %d
    //             ORDER BY `{$table_name}`.`position`",
    //             $menu_id
    //         )
    //     );

    //     return $results ?: [];
    // }

    // /**
    //  * Unpack JSON data fields into menu item properties.
    //  *
    //  * Also handles recursive sub-menu (type=menu) expansion.
    //  *
    //  * @param array $items   Raw menu item objects.
    //  * @param int   $menu_id The root menu ID (for sub-menu caching).
    //  * @return array Processed menu items.
    //  */
    // private static function unpack_items(array $items, int $menu_id): array
    // {
    //     $children_counts = [];

    //     for ($i = 0; $i < count($items); $i++) {
    //         $item = &$items[$i];

    //         // Decode JSON data and merge into item properties.
    //         $data = json_decode($item->data, true) ?: [];
    //         foreach ($data as $key => $value) {
    //             $key = str_replace('menu-item-', '', $key);
    //             $key = str_replace('-', '_', $key);
    //             if ($value && !isset($item->$key)) {
    //                 $item->$key = $value;
    //             }
    //         }

    //         // Normalize title.
    //         if (isset($item->title)) {
    //             $item->post_title = $item->title;
    //             unset($item->title);
    //         }

    //         // Normalize classes.
    //         if (isset($item->classes)) {
    //             if (is_string($item->classes)) {
    //                 $item->classes = explode(' ', trim($item->classes));
    //             }
    //         } else {
    //             $item->classes = [];
    //         }

    //         $object_type = $item->object ?? 'unknown';
    //         $item->classes[] = 'menu-item-' . $object_type;

    //         // Track child counts for CSS class numbering.
    //         $parent = $item->parent_id ?? 0;
    //         if (!isset($children_counts[$parent])) {
    //             $children_counts[$parent] = [];
    //         }
    //         if (!isset($children_counts[$parent][$object_type])) {
    //             $children_counts[$parent][$object_type] = 0;
    //         }
    //         $item->classes[] = 'menu-item-' . $object_type . '-' . $children_counts[$parent][$object_type];
    //         $children_counts[$parent][$object_type]++;

    //         // Recursively expand sub-menus (type=menu).
    //         if (isset($item->type) && $item->type === 'menu' && !empty($item->menu)) {
    //             $sub_items = self::query_items((int) $item->menu, (int) $item->ID);
    //             array_splice($items, $i + 1, 0, $sub_items);
    //         }
    //     }

    //     return $items;
    // }

    // /**
    //  * Mark active items based on the current post ID.
    //  *
    //  * Walks backwards through the menu to find the current page and its
    //  * ancestors, adding the 'active' CSS class.
    //  *
    //  * @param array    $menu_items     Menu item objects.
    //  * @param int|null $current_post_id The current post ID.
    //  * @return array Menu items with active classes applied.
    //  */
    // public static function mark_active_items(array $menu_items, ?int $current_post_id): array
    // {
    //     if ($current_post_id === null) {
    //         return $menu_items;
    //     }

    //     $parent_id = null;
    //     for ($i = count($menu_items) - 1; $i >= 0; $i--) {
    //         $item = &$menu_items[$i];

    //         if ((int) $item->post_id === $current_post_id) {
    //             $parent_id = (int) $item->parent_id;
    //             $item->classes[] = 'active';
    //         } elseif ($parent_id !== null && (int) $item->ID === $parent_id) {
    //             // Stop if we hit a sub-menu boundary.
    //             if (isset($item->type) && $item->type === 'menu') {
    //                 break;
    //             }
    //             $parent_id = (int) $item->parent_id;
    //             $item->classes[] = 'active';
    //         }

    //         if ($parent_id === 0) {
    //             break;
    //         }
    //     }

    //     return $menu_items;
    // }

    // /**
    //  * Filter menu items by a property value.
    //  *
    //  * When $children is true, also includes all descendants of matched items.
    //  *
    //  * @param array  $menu_items Menu item objects.
    //  * @param string $key        Property name to filter on.
    //  * @param mixed  $value      Value to match.
    //  * @param bool   $children   Whether to include descendants.
    //  * @return array Filtered menu items.
    //  */
    // public static function filter_items(array $menu_items, string $key, $value, bool $children = false): array
    // {
    //     $values = [$value];

    //     return array_filter($menu_items, function ($item) use ($key, &$values, $children) {
    //         if (in_array($item->$key, $values)) {
    //             if ($children) {
    //                 $values[] = $item->ID;
    //             }
    //             return true;
    //         }
    //         return false;
    //     });
    // }
}
