<?php

namespace Bvi\Plugin\MegaMenu\Blocks\Menus;

use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

/**
 * Customizes the WordPress nav-menus.php admin page.
 *
 * Adds metaboxes for special item types (Shortcode, Column, Menu)
 * and handles saving menu data to the custom table.
 */
class Editor
{
    use Singleton;

    /**
     * Collected menu items during save, before bulk insert.
     *
     * @var array<int, array>
     */
    private array $pending_items = [];

    public function __construct() {}

    /**
     * Initialize the admin menu editor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function init()
    {
        add_action('admin_head-nav-menus.php', [$this, 'setup_menu_page']);

        // AJAX handlers.
        add_action('wp_ajax_nav_menu_get_post_descendants', [$this, 'ajax_get_post_descendants']);
        add_action('wp_ajax_nav_menu_duplicate_item', [$this, 'ajax_duplicate_item']);

        // Save hooks.
        add_action('wp_update_nav_menu', [$this, 'on_menu_save'], 1, 2);
        add_action('wp_update_nav_menu_item', [$this, 'on_menu_item_save'], 1, 3);
    }

    /**
     * Set up the nav-menus page: enqueue assets, add metaboxes.
     */
    public function setup_menu_page(): void
    {
        $this->enqueue_admin_assets();

        add_meta_box(
            'add-shortcode',
            __('Shortcode/HTML', 'bvi-mega-menu'),
            [$this, 'render_shortcode_metabox'],
            'nav-menus',
            'side',
        );

        add_meta_box(
            'add-column',
            __('Column/Section', 'bvi-mega-menu'),
            [$this, 'render_column_metabox'],
            'nav-menus',
            'side',
        );

        add_meta_box('add-menu', __('Menu', 'bvi-mega-menu'), [$this, 'render_menu_metabox'], 'nav-menus', 'side');
    }

    /**
     * Render the Shortcode/HTML metabox.
     */
    public function render_shortcode_metabox(): void
    {
        global $_nav_menu_placeholder, $nav_menu_selected_id;
        $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
        $placeholder = $_nav_menu_placeholder;

        include BVI_PLUGIN_MEGAMENU_DIR_PATH . 'templates/admin/metabox-shortcode.php';
    }

    /**
     * Render the Column/Section metabox.
     */
    public function render_column_metabox(): void
    {
        global $_nav_menu_placeholder, $nav_menu_selected_id;
        $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
        $placeholder = $_nav_menu_placeholder;

        include BVI_PLUGIN_MEGAMENU_DIR_PATH . 'templates/admin/metabox-column.php';
    }

    /**
     * Render the Menu metabox.
     */
    public function render_menu_metabox(): void
    {
        global $_nav_menu_placeholder, $nav_menu_selected_id, $nav_menus;
        $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
        $placeholder = $_nav_menu_placeholder;

        include BVI_PLUGIN_MEGAMENU_DIR_PATH . 'templates/admin/metabox-menu.php';
    }

    /**
     * Enqueue admin scripts and styles for the nav-menus page.
     */
    private function enqueue_admin_assets(): void
    {
        $scripts = [
            BVI_PLUGIN_MEGAMENU_NAMESPACE . '-nav-menu-column' => 'assets/dist/scripts/admin/nav-menu-column.min.js',
            BVI_PLUGIN_MEGAMENU_NAMESPACE . '-nav-menu-menu' => 'assets/dist/scripts/admin/nav-menu-menu.min.js',
            BVI_PLUGIN_MEGAMENU_NAMESPACE .
            '-nav-menu-shortcode' => 'assets/dist/scripts/admin/nav-menu-shortcode.min.js',
            BVI_PLUGIN_MEGAMENU_NAMESPACE .
            '-nav-menu-collapsing' => 'assets/dist/scripts/admin/nav-menu-collapsing-items.min.js',
        ];

        foreach ($scripts as $handle => $path) {
            wp_enqueue_script(
                $handle,
                BVI_PLUGIN_MEGAMENU_DIR_URL . $path,
                ['jquery'],
                BVI_PLUGIN_MEGAMENU_VERSION,
                true,
            );
        }
    }

    /**
     * AJAX: Add all descendants of a post to the menu.
     */
    public function ajax_get_post_descendants(): void
    {
        check_ajax_referer('add-menu_item', 'menu-settings-column-nonce');

        if (!current_user_can('edit_theme_options')) {
            wp_die(-1);
        }

        require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

        $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
        $db_id = isset($_GET['db_id']) ? absint($_GET['db_id']) : 0;
        $menu_id = isset($_GET['menu']) ? absint($_GET['menu']) : 0;
        $depth = isset($_GET['depth']) ? absint($_GET['depth']) : 0;

        $descendants = get_pages(['child_of' => $post_id, 'sort_column' => 'menu_order']);

        if (empty($descendants)) {
            wp_die(0);
        }

        $object_to_menu_map = [];
        $menu_items = [];

        foreach ($descendants as $descendant) {
            $parent_menu_id =
                (int) $descendant->post_parent === $post_id
                    ? $db_id
                    : $object_to_menu_map[$descendant->post_parent] ?? $db_id;

            $menu_item = [
                'menu-item-object' => $descendant->post_type,
                'menu-item-object-id' => $descendant->ID,
                'menu-item-parent-id' => $parent_menu_id,
                'menu-item-type' => 'post_type',
                'menu-item-title' => $descendant->post_title,
                'menu-item-url' => get_permalink($descendant->ID),
            ];

            $item_ids = wp_save_nav_menu_items($menu_id, [$menu_item]);
            if (is_wp_error($item_ids)) {
                wp_die(0);
            }

            $object_to_menu_map[$descendant->ID] = $item_ids[0];

            $menu_obj = get_post($item_ids[0]);
            if (!empty($menu_obj->ID)) {
                $menu_obj = wp_setup_nav_menu_item($menu_obj);
                $menu_obj->label = $menu_obj->title;
                $menu_items[] = $menu_obj;
            }
        }

        $this->output_walker_markup($menu_items, $menu_id, $depth + 1);
    }

    /**
     * AJAX: Duplicate a menu item.
     */
    public function ajax_duplicate_item(): void
    {
        check_ajax_referer('add-menu_item', 'menu-settings-column-nonce');

        if (!current_user_can('edit_theme_options')) {
            wp_die(-1);
        }

        require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

        $db_id = isset($_GET['db_id']) ? absint($_GET['db_id']) : 0;
        $menu_id = isset($_GET['menu']) ? absint($_GET['menu']) : 0;

        $item = wp_setup_nav_menu_item(clone get_post($db_id));

        $menu_item = [
            'menu-item-object' => $item->object,
            'menu-item-object-id' => $item->object_id,
            'menu-item-parent-id' => isset($_GET['parent_id']) ? absint($_GET['parent_id']) : $item->menu_item_parent,
            'menu-item-type' => $item->type,
            'menu-item-title' => $item->title,
            'menu-item-url' => $item->url,
        ];

        $item_ids = wp_save_nav_menu_items($menu_id, [$menu_item]);
        if (is_wp_error($item_ids)) {
            wp_die(0);
        }

        $menu_items = [];
        $menu_obj = get_post($item_ids[0]);
        if (!empty($menu_obj->ID)) {
            $menu_obj = wp_setup_nav_menu_item($menu_obj);
            $menu_obj->label = $menu_obj->title;
            $menu_items[] = $menu_obj;
        }

        $this->output_walker_markup($menu_items, $menu_id, 1);
    }

    /**
     * Save the full menu structure to the custom table.
     *
     * This fires after all individual items have been processed.
     *
     * @param int $menu_id
     * @param mixed $menu_data Non-null means WordPress is updating menu settings, not items.
     */
    public function on_menu_save(int $menu_id, $menu_data = null): void
    {
        if ($menu_data !== null) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mega_menu';

        $wpdb->delete($table, ['menu_id' => $menu_id], '%d');

        foreach ($this->pending_items as $item) {
            $wpdb->insert($table, $item);
        }

        $this->pending_items = [];
    }

    /**
     * Collect a single menu item's data during the save process.
     *
     * @param int   $menu_id
     * @param int   $menu_item_db_id
     * @param array $menu_item_data
     */
    public function on_menu_item_save(int $menu_id, int $menu_item_db_id, array $menu_item_data): void
    {
        if (( $menu_item_data['menu-item-status'] ?? '' ) === 'draft') {
            return;
        }

        $item = [
            'ID' => (int) $menu_item_data['menu-item-db-id'],
            'menu_id' => $menu_id,
            'post_id' => 0,
            'parent_id' => (int) ( $menu_item_data['menu-item-parent-id'] ?? 0 ),
            'position' => (int) ( $menu_item_data['menu-item-position'] ?? 0 ),
        ];

        $object = $menu_item_data['menu-item-object'] ?? '';
        if (in_array($object, ['page', 'post'], true)) {
            $item['post_id'] = (int) ( $menu_item_data['menu-item-object-id'] ?? 0 );
        }

        // Parse JSON-encoded title for special types (column, menu).
        $title = stripcslashes($menu_item_data['menu-item-title'] ?? '');
        $decoded = json_decode($title);
        if (is_object($decoded)) {
            foreach ($decoded as $key => $value) {
                $menu_item_data['menu-item-' . $key] = $value;
            }
        }

        $item['data'] = wp_json_encode($menu_item_data);
        $this->pending_items[] = $item;
    }

    /**
     * Output walker markup for AJAX responses (descendants, duplicates).
     *
     * @param object[] $menu_items
     */
    private function output_walker_markup(array $menu_items, int $menu_id, int $depth_offset): void
    {
        $walker_class = apply_filters('wp_edit_nav_menu_walker', 'Walker_Nav_Menu_Edit', $menu_id);

        if (!class_exists($walker_class)) {
            wp_die(0);
        }

        if (empty($menu_items)) {
            wp_die(0);
        }

        $args = (object) [
            'after' => '',
            'before' => '',
            'link_after' => '',
            'link_before' => '',
            'walker' => new $walker_class(),
        ];

        $output = walk_nav_menu_tree($menu_items, 0, $args);

        // Adjust depth classes.
        $output = preg_replace_callback(
            '/(menu-item-depth-)([0-9]+)/',
            fn($matches) => $matches[1] . ( (int) $matches[2] + $depth_offset ),
            $output,
        );

        echo $output;
        wp_die();
    }
}
