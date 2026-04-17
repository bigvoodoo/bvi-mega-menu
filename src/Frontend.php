<?php

namespace Bvi\Plugin\MegaMenu;

use Bvi\Plugin\MegaMenu\Service\Ajax;
use Bvi\Plugin\MegaMenu\Service\Shortcode\MegaMenuShortcode;
use Bvi\Plugin\MegaMenu\Service\Shortcode\RelatedLinksShortcode;
use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

/**
 * Class Frontend
 *
 * @package bvimegamenu/src
 */
class Frontend
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
     * Initializes the frontend class.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function init()
    {
        // conditionally enqueue the default CSS based on the plugin setting
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_default_styles']);

        // register shortcodes (legacy/classic support)
        add_action('init', [$this, 'register_shortcodes'], 1);

        // register AJAX handler for mega menu dropdowns
        add_action('init', [$this, 'register_ajax_handler'], 1);
    }

    /**
     * Conditionally enqueue the default CSS based on the plugin setting.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function maybe_enqueue_default_styles()
    {
        $options = get_option(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_general_database_settings', []);

        if (!empty($options['css_val'])) {
            wp_enqueue_style(
                BVI_PLUGIN_MEGAMENU_NAMESPACE . '-default',
                BVI_PLUGIN_MEGAMENU_DIR_URL . 'assets/dist/css/mega-menu-default.css',
                [],
                BVI_PLUGIN_MEGAMENU_VERSION,
            );
        }
    }

    /**
     * Register shortcodes.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_shortcodes()
    {
        new MegaMenuShortcode();
        new RelatedLinksShortcode();
    }

    /**
     * Register the custom AJAX handler for mega menu dropdowns.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_ajax_handler()
    {
        new Ajax();
    }

    /**
     * Enqueue the AJAX-based front-end script.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public static function enqueue_ajax_script()
    {
        wp_enqueue_script(
            BVI_PLUGIN_MEGAMENU_NAMESPACE . '-ajax',
            BVI_PLUGIN_MEGAMENU_DIR_URL . 'assets/dist/scripts/frontend/mega-menu-ajax.min.js',
            ['jquery'],
            BVI_PLUGIN_MEGAMENU_VERSION,
            true,
        );

        $options = get_option(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_general_database_settings', []);

        wp_localize_script(BVI_PLUGIN_MEGAMENU_NAMESPACE . '-ajax', 'DropdownSpeed', [
            'instant_dropdown' => !empty($options['dropdown_val']),
        ]);
    }

    /**
     * Enqueue the non-AJAX front-end script.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public static function enqueue_standard_script()
    {
        wp_enqueue_script(
            BVI_PLUGIN_MEGAMENU_NAMESPACE . '-frontend',
            BVI_PLUGIN_MEGAMENU_DIR_URL . 'assets/dist/scripts/frontend/mega-menu.min.js',
            ['jquery'],
            BVI_PLUGIN_MEGAMENU_VERSION,
            true,
        );
    }
}
