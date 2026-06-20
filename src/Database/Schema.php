<?php

namespace Bvi\Plugin\MegaMenu\Database;

use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

/**
 * Handles database table creation and teardown.
 */
class Schema
{
    use Singleton;

    public function __construct()
    {
    }

    /**
     * Initializes the class functionality.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function init()
    {
        $plugin_file = BVI_PLUGIN_MEGAMENU_DIR_PATH . 'bvi-mega-menu.php';

        register_activation_hook($plugin_file, [$this, 'activate']);
        register_uninstall_hook($plugin_file, [self::class, 'uninstall']);
    }

    /**
     * Create the mega menu table on activation.
     *
     * @since 5.0.0
     *
     * @return void     *
     */
    public function activate(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mega_menu';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `menu_id` bigint(20) NOT NULL,
            `post_id` bigint(20) NOT NULL,
            `parent_id` bigint(20) NOT NULL,
            `position` bigint(20) NOT NULL,
            `data` longtext NOT NULL,
            PRIMARY KEY (`ID`)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        flush_rewrite_rules();
    }

    /**
     * Drop the mega menu table on uninstall.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public static function uninstall(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mega_menu';
        $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
    }
}
