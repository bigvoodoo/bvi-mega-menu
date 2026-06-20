<?php

namespace Bvi\Plugin\MegaMenu;

use Bvi\Plugin\MegaMenu\Admin;
use Bvi\Plugin\MegaMenu\Frontend;
use Bvi\Plugin\MegaMenu\Database\Schema;
use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

/**
 * Class Main
 *
 * @package bvimegamenu/src
 */
class Main
{
    use Singleton;

    /**
     * Constructor
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Activate the plugin. Placeholder for future use.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public static function activate() {}

    /**
     * Deactivates the plugin. Placeholder for future use.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public static function deactivate() {}

    /**
     * Initialize the plugin. Placeholder for future use.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function init()
    {
        // registers database schema (activation/uninstall hooks)
        add_action('init', [$this, 'register_database_schema'], 1);

        $is_block_theme = false;

        // checks if this is a full site editing supported theme, and registers support accordingly
        if (wp_is_block_theme()) {
            $is_block_theme = true;
        }

        // activate the administrative functionality
        if (is_admin()) {
            Admin::get_instance($is_block_theme);
        }

        // verify this is a block theme before registering blocks
        if ($is_block_theme) {
            // discover and register all blocks
            Blocks::get_instance();
        }

        // activate the frontend functionality
        if (!is_admin()) {
            Frontend::get_instance($is_block_theme);
        }
    }

    /**
     * Registers database schema activation/uninstall hooks.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_database_schema()
    {
        Schema::get_instance();
    }
}
