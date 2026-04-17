<?php

/**
 * Plugin Name:       BVI Mega Menu
 * Plugin URI:        https://github.com/bviigital/bvi-mega-menu
 * Author:            Big Voodoo Interactive
 * Author URI:        https://www,bigvoodoo.com
 * Description:       Enhanced WordPress navigation menu with related links, columns, shortcodes, and block editor support.
 * Version:           5.0.0
 * Requires at least: 6.8
 * Tested up to:      6.9.4
 * Requires PHP:      8.2
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * GitHub Update URI: https://github.com/bviigital/bvi-mega-menu
 * Primary Branch:    main
 * Text Domain:       bvi-mega-menu
 */

if (!defined('ABSPATH')) {
    exit(); // exit if accessed directly
}

/**
 * Plugin name and supported PHP/WP versions.
 *
 * @since 0.1.0
 */
if (!defined('BVI_PLUGIN_MEGAMENU_NAME')) {
    define('BVI_PLUGIN_MEGAMENU_NAME', 'BVI Mega Menu');
}

if (!defined('BVI_PLUGIN_MEGAMENU_PHP_VERSION')) {
    define('BVI_PLUGIN_MEGAMENU_PHP_VERSION', '8.2');
}

if (!defined('BVI_PLUGIN_MEGAMENU_WP_VERSION')) {
    define('BVI_PLUGIN_MEGAMENU_WP_VERSION', '6.8');
}

$current_php_version = PHP_VERSION;
$current_wp_version = get_bloginfo('version');

/**
 * Check PHP version requirement
 *
 * @since 0.1.0
 */
if (version_compare($current_php_version, BVI_PLUGIN_MEGAMENU_PHP_VERSION, '<')) {
    add_action('admin_notices', function () use ($current_php_version) {
        echo '<div class="error"><p>';
        printf(
            '%s requires PHP %s or higher. You are running PHP %s. Please upgrade PHP to use this plugin.',
            BVI_PLUGIN_MEGAMENU_NAME,
            BVI_PLUGIN_MEGAMENU_PHP_VERSION,
            $current_php_version,
        );
        echo '</p></div>';
    });

    return;
}

/**
 * Check WordPress version requirement
 *
 * @since 0.1.0
 */
if (version_compare($current_wp_version, BVI_PLUGIN_MEGAMENU_WP_VERSION, '<')) {
    add_action('admin_notices', function () use ($current_wp_version) {
        echo '<div class="error"><p>';
        printf(
            '%s requires WordPress %s or higher. You are running WordPress %s. Please upgrade WordPress to use this plugin.',
            BVI_PLUGIN_MEGAMENU_NAME,
            BVI_PLUGIN_MEGAMENU_WP_VERSION,
            $current_wp_version,
        );
        echo '</p></div>';
    });

    return;
}

/**
 * Additional plugin constants.
 *
 * @since 0.1.0
 */
if (!defined('BVI_PLUGIN_MEGAMENU_NAMESPACE')) {
    define('BVI_PLUGIN_MEGAMENU_NAMESPACE', 'bvi-mega-menu');
}

if (!defined('BVI_PLUGIN_MEGAMENU_BASENAME')) {
    define('BVI_PLUGIN_MEGAMENU_BASENAME', plugin_basename(__FILE__));
}

if (!defined('BVI_PLUGIN_MEGAMENU_VERSION')) {
    define('BVI_PLUGIN_MEGAMENU_VERSION', wp_get_theme()->get('Version') ?? '');
}

if (!defined('BVI_PLUGIN_MEGAMENU_DB_VERSION')) {
    define('BVI_PLUGIN_MEGAMENU_DB_VERSION', '0.1.0');
}

if (!defined('BVI_PLUGIN_MEGAMENU_DIR_PATH')) {
    define('BVI_PLUGIN_MEGAMENU_DIR_PATH', plugin_dir_path(__FILE__));
}

if (!defined('BVI_PLUGIN_MEGAMENU_DIR_URL')) {
    define('BVI_PLUGIN_MEGAMENU_DIR_URL', plugin_dir_url(__FILE__));
}

$bvimm_plugin_loader = BVI_PLUGIN_MEGAMENU_DIR_PATH . '/vendor/autoload.php';

if (file_exists($bvimm_plugin_loader)) {
    require_once $bvimm_plugin_loader;
} else {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>';
        printf(
            '%s has had a critical issue. Please contact <a href="mailto:support@bigvoodoo.com">the plugin author</a> to resolve this issue.',
            'bvi-megamenu',
            BVI_PLUGIN_MEGAMENU_NAME,
        );
        echo '</p></div>';
    });
}

// verify class exists before continuing
if (class_exists('Bvi\Plugin\MegaMenu\Main')) {
    $bvimm_main_class = Bvi\Plugin\MegaMenu\Main::get_instance();

    /**
     * Hooks the code that runs when the plugin is activated
     *
     */
    register_activation_hook(__FILE__, [$bvimm_main_class, 'activate']);

    /**
     * Hooks the code that runs when the plugin is deactivated
     *
     */
    register_deactivation_hook(__FILE__, [$bvimm_main_class, 'deactivate']);
}
