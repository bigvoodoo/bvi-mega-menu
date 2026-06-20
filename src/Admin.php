<?php

namespace Bvi\Plugin\MegaMenu;

use Bvi\Plugin\MegaMenu\Admin\MetaBox;
use Bvi\Plugin\MegaMenu\Blocks\Menus\Editor;
use Bvi\Plugin\MegaMenu\Utils\Discovery;
use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

/**
 * Class Admin
 *
 * @package bvimegamenu/src
 */
class Admin
{
    use Singleton;

    /** @var array */
    private $metaboxes = [];

    /** @var array */
    private $features = [];

    private $is_block_theme = false;

    /**
     * Constructor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct(bool $is_block_theme = false)
    {
        // set if this is a FSE block theme or not
        $this->is_block_theme = $is_block_theme;
    }

    /**
     * Initializes the admin features.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function init()
    {
        // enqueues admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // adds plugin action links
        add_action('plugin_action_links', [$this, 'add_plugin_page_action_links'], 10, 2);

        // registers admin settings pages
        add_action('init', [$this, 'register_features'], 1);

        // the per-page Related Links override metabox is available in both modes,
        // because Related Links is supported on both FSE and classic sites.
        add_action('after_setup_theme', [$this, 'register_related_links_meta_box'], 1);

        // classic themes still rely on the nav-menus screen to compose mega menus
        // (Column/Section, Shortcode/HTML, and Menu metaboxes). FSE themes compose
        // the mega menu from blocks, so they don't need those metaboxes.
        if (!$this->is_block_theme) {
            add_action('after_setup_theme', [$this, 'register_menu_editor'], 1);
        }
    }

    /**
     * Enqueues admin-specific files.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function enqueue_assets()
    {
        if (is_admin()) {
            wp_enqueue_style(
                BVI_PLUGIN_MEGAMENU_NAMESPACE . '-admin-style',
                BVI_PLUGIN_MEGAMENU_DIR_URL . 'assets/dist/css/admin.css',
                [],
                BVI_PLUGIN_MEGAMENU_VERSION,
                'all',
            );
        }
    }

    /**
     * Add a link to the settings page on the plugin screen
     *
     * @since 5.0.0
     *
     * @param array $links The default links array
     * @return array The $links array with our link added
     */
    public function add_plugin_page_action_links($links, $file)
    {
        if (!is_admin() || ( !empty($file) && $file !== BVI_PLUGIN_MEGAMENU_BASENAME )) {
            return $links;
        }

        $settings_url = esc_url(
            add_query_arg('page', BVI_PLUGIN_MEGAMENU_NAMESPACE . '_general_settings', admin_url('admin.php')),
        );

        $settings_link =
            '<a href="' . $settings_url . '">' . esc_html__('Settings', BVI_PLUGIN_MEGAMENU_NAMESPACE) . '</a>';
        array_unshift($links, $settings_link);

        return $links;
    }

    /**
     * Registers and initializes features discovered in the specified configuration directory.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_features()
    {
        $config_dir = BVI_PLUGIN_MEGAMENU_DIR_PATH . 'src/Admin/Features';
        $config_namespace = 'Bvi\\Plugin\\MegaMenu\\';
        $config_location = 'Admin\\Features\\';
        $config_interface = 'Bvi\\Plugin\\MegaMenu\\Interfaces\\Feature';

        $this->features = Discovery::discover($config_dir, $config_namespace, $config_location, $config_interface);
    }

    /**
     * Registers the nav menu editor customizations.
     *
     * Hooks the Shortcode/HTML, Column/Section, and Menu metaboxes onto the
     * Appearance > Menus screen for classic themes.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_menu_editor()
    {
        Editor::get_instance();
    }

    /**
     * Registers the per-page Related Links override metabox.
     *
     * Available in both FSE and classic modes, because Related Links is
     * supported in both contexts (as a block on FSE, as a shortcode on classic).
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_related_links_meta_box()
    {
        $related_links_metabox_fields = [
            [
                'label_for' => 'links',
                'title' => __('BVI Related Links', 'bvi-mega-menu'),
                'type' => 'multi',
                'description' => __(
                    'Add title/URL pairs that will display as related links on this page.',
                    'bvi-mega-menu',
                ),
                'options' => [
                    [
                        'label_for' => 'link_items',
                        'title' => '',
                        'type' => 'composite',
                        'field_type' => 'composite',
                        'max_items' => 20,
                        'min_items' => 0,
                        'fields' => [
                            [
                                'label_for' => 'title',
                                'title' => __('Title', 'bvi-mega-menu'),
                                'type' => 'text',
                            ],
                            [
                                'label_for' => 'url',
                                'title' => __('URL', 'bvi-mega-menu'),
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $related_links_metabox_config = [
            'id' => BVI_PLUGIN_MEGAMENU_NAMESPACE . '_related_links',
            'title' => __('BVI Related Links Overrides', 'bvi-mega-menu'),
            'screen' => ['post', 'page'],
            'context' => 'side',
            'priority' => 10,
            'fields' => $related_links_metabox_fields,
            'options' => [],
            'class_name' => self::class,
            'template_id' => 'metabox',
            'prefix' => '_' . BVI_PLUGIN_MEGAMENU_NAMESPACE . '_related_links_',
            'before_content' =>
                '<p class="description">' .
                esc_html__(
                    'Add custom related links for this page.' .
                        ' These override the auto-detected links from the menu.',
                    'bvi-mega-menu',
                ) .
                '</p>',
            'after_content' => '',
        ];

        $this->metaboxes['related_links'] = new MetaBox();
        $this->metaboxes['related_links']->configure($related_links_metabox_config);
    }
}
