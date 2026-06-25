<?php

namespace Bvi\Plugin\MegaMenu;

use Bvi\Plugin\MegaMenu\Service\Ajax;
use Bvi\Plugin\MegaMenu\Service\Shortcode\MegaMenuShortcode;
use Bvi\Plugin\MegaMenu\Service\Shortcode\RelatedLinksShortcode;
use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

/**
 * Class Frontend
 *
 * @package bvi-mega-menu
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
     * Enqueue the structural block assets needed by a classic-menu render.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public static function enqueue_menu_assets()
    {
        // Each menu block and whether it ships a view script. Style is implicit
        // for all three.
        $blocks = [
            'mega-menu' => true,
            'menu-item' => false,
            'mega-panel' => false,
        ];

        $registry = class_exists('WP_Block_Type_Registry') ? \WP_Block_Type_Registry::get_instance() : null;

        foreach ($blocks as $slug => $has_view) {
            $block_type = $registry ? $registry->get_registered('bvi/' . $slug) : null;

            // if blocks can be registered, proceed as normal with asset loading
            if ($block_type) {
                foreach ((array) ( $block_type->style_handles ?? [] ) as $handle) {
                    wp_enqueue_style($handle);
                }

                foreach ((array) ( $block_type->view_script_handles ?? [] ) as $handle) {
                    wp_enqueue_script($handle);
                }

                continue;
            }

            // backwards compatibility for themes with disabled block functionality
            self::enqueue_block_asset_fallback($slug, $has_view);
        }
    }

    /**
     * Enqueue a block's compiled style/script when block registration is not available.
     *
     * @since 5.0.0
     *
     * @param string $slug     Block slug under assets/dist/blocks/ (e.g. 'mega-menu').
     * @param bool   $has_view Whether the block ships a view.js script.
     * @return void
     */
    private static function enqueue_block_asset_fallback(string $slug, bool $has_view)
    {
        $base_url = BVI_PLUGIN_MEGAMENU_DIR_URL . 'assets/dist/blocks/' . $slug . '/';
        $base_path = BVI_PLUGIN_MEGAMENU_DIR_PATH . 'assets/dist/blocks/' . $slug . '/';
        $handle = BVI_PLUGIN_MEGAMENU_NAMESPACE . '-block-' . $slug;

        if (file_exists($base_path . 'style-index.css')) {
            wp_enqueue_style($handle, $base_url . 'style-index.css', [], BVI_PLUGIN_MEGAMENU_VERSION);
        }

        if (!$has_view || !file_exists($base_path . 'view.js')) {
            return;
        }

        $asset = file_exists($base_path . 'view.asset.php') ? (array) require $base_path . 'view.asset.php' : [];

        wp_enqueue_script(
            $handle . '-view',
            $base_url . 'view.js',
            (array) ( $asset['dependencies'] ?? [] ),
            (string) ( $asset['version'] ?? BVI_PLUGIN_MEGAMENU_VERSION ),
            true,
        );
    }
}
