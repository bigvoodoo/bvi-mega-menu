<?php

namespace Bvi\Plugin\MegaMenu\Blocks\Menus;

use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

/**
 * Class Editor
 *
 * @package bvi-mega-menu
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

    /**
     * Constructor.
     *
     * @since 5.0.0
     * @return void
     */
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

        // menu save hooks
        add_action('wp_update_nav_menu', [$this, 'on_menu_save'], 1, 2);
        add_action('wp_update_nav_menu_item', [$this, 'on_menu_item_save'], 1, 3);
    }

    /**
     * Set up the nav-menus page: enqueue assets, add metaboxes.
     *
     * @since 5.0.0
     * @return void
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
     *
     * @since 5.0.0
     * @return void
     */
    public function render_shortcode_metabox(): void
    {
        $this->render_add_items_box('templates/admin/metabox-shortcode.php');
    }

    /**
     * Render the Column/Section metabox.
     *
     * @since 5.0.0
     * @return void
     */
    public function render_column_metabox(): void
    {
        $this->render_add_items_box('templates/admin/metabox-column.php');
    }

    /**
     * Render the Menu metabox.
     *
     * @since 5.0.0
     * @return void
     */
    public function render_menu_metabox(): void
    {
        $this->render_add_items_box('templates/admin/metabox-menu.php');
    }

    /**
     * Render a nav-menu "Add items" box template in an isolated scope.
     *
     * @since 5.0.0
     *
     * @param string $template Template path relative to the plugin directory.
     * @return void
     */
    private function render_add_items_box(string $template): void
    {
        global $nav_menu_selected_id, $nav_menus;

        $placeholder = $this->next_placeholder();

        include BVI_PLUGIN_MEGAMENU_DIR_PATH . $template;
    }

    /**
     * Decrement and return WordPress's nav-menu item placeholder index.
     *
     * @since 5.0.0
     *
     * @return int Next placeholder index (always negative).
     */
    private function next_placeholder(): int
    {
        global $_nav_menu_placeholder;

        $current = (int) $_nav_menu_placeholder;

        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- core's documented nav-menu placeholder
        $_nav_menu_placeholder = 0 > $current ? $current - 1 : -1;

        return $_nav_menu_placeholder;
    }

    /**
     * Enqueue admin scripts and styles for the nav-menus page.
     *
     * @since 5.0.0
     * @return void
     */
    private function enqueue_admin_assets(): void
    {
        $scripts = [
            BVI_PLUGIN_MEGAMENU_NAMESPACE . '-nav-menu-column' => 'assets/dist/scripts/nav-menu-column.min.js',
            BVI_PLUGIN_MEGAMENU_NAMESPACE . '-nav-menu-menu' => 'assets/dist/scripts/nav-menu-menu.min.js',
            BVI_PLUGIN_MEGAMENU_NAMESPACE . '-nav-menu-shortcode' => 'assets/dist/scripts/nav-menu-shortcode.min.js',
            BVI_PLUGIN_MEGAMENU_NAMESPACE .
            '-nav-menu-collapsing' => 'assets/dist/scripts/nav-menu-collapsing-items.min.js',
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
     * Save the full menu structure to the custom table.
     *
     * @since 5.0.0
     *
     * @param int   $menu_id   Menu term ID being saved.
     * @param mixed $menu_data Non-null means WordPress is updating menu settings, not items.
     * @return void
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
     * @since 5.0.0
     *
     * @param int   $menu_id         Menu term ID being saved.
     * @param int   $menu_item_db_id Saved menu item post ID.
     * @param array $menu_item_data  Submitted menu item data.
     * @return void
     */
    public function on_menu_item_save(int $menu_id, int $menu_item_db_id, array $menu_item_data): void
    {
        if (($menu_item_data['menu-item-status'] ?? '') === 'draft') {
            return;
        }

        $item = [
            'ID' => (int) $menu_item_data['menu-item-db-id'],
            'menu_id' => $menu_id,
            'post_id' => 0,
            'parent_id' => (int) ($menu_item_data['menu-item-parent-id'] ?? 0),
            'position' => (int) ($menu_item_data['menu-item-position'] ?? 0),
        ];

        $object = $menu_item_data['menu-item-object'] ?? '';
        if (in_array($object, ['page', 'post'], true)) {
            $item['post_id'] = (int) ($menu_item_data['menu-item-object-id'] ?? 0);
        }

        // parse json-encoded title for special types (column, menu)
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
}
