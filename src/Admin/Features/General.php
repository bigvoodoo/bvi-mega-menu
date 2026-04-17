<?php

namespace Bvi\Plugin\MegaMenu\Admin\Features;

use Bvi\Plugin\MegaMenu\Admin\Settings;
use Bvi\Plugin\MegaMenu\Interfaces\Feature as FeatureInterface;
use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;
use Bvi\Plugin\MegaMenu\Utils\Traits\Feature;
use Bvi\Plugin\MegaMenu\Utils\Traits\Strings;

/**
 * Class General
 *
 * @package bvimegamenu/src
 */
class General implements FeatureInterface
{
    use Feature;
    use Singleton;
    use Strings;

    /** @var Settings */
    private $settings;

    /** @var bool */
    protected $enabled;

    /** @var string */
    protected $slug;

    /** @var string */
    protected $page_id;

    /** @var string */
    protected $db_id;

    /** @var string */
    protected $parent_page_id;

    /** @var string */
    protected $class_name;

    /**
     * Construct the General feature
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct()
    {
        $name = 'General';

        // set the name of this feature
        $this->set_name($name);

        // register new admin settings related to the feature
        $this->register_new_admin_settings();
    }

    /**
     * Register new admin settings page.
     *
     * @since 5.0.0
     *
     * @return object The Settings object
     */
    public function register_new_admin_settings(): object
    {
        try {
            $safe_slug = $this->create_safe_attribute_field_value($this->name);
            // create the ids and class related to this feature
            $slug = $this->set_slug($safe_slug);
            $page_id = $this->set_page_id(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_' . $slug . '_settings');
            $db_id = $this->set_db_id(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_' . $slug . '_database_settings');
            $parent_page_id = '';
            $class_name = $this->set_class_name(self::class);

            // set the menu specific settings and options for this feature
            $menu_type = 'menu';
            $page_title = $this->safe_translation(BVI_PLUGIN_MEGAMENU_NAME, 'bvi-mega-menu');
            $menu_title = $this->safe_translation(BVI_PLUGIN_MEGAMENU_NAME, 'bvi-mega-menu');
            $submenu_title = $this->safe_translation('Mega Menu', 'bvi-mega-menu');
            $menu_icon = 'dashicons-menu';
            $capability = 'manage_options';
            $position = 80;

            $options = [
                'menu_type' => $menu_type,
                'page_title' => $page_title,
                'menu_title' => $menu_title,
                'submenu_title' => $submenu_title,
                'menu_icon' => $menu_icon,
                'capability' => $capability,
                'class_name' => $class_name,
                'position' => $position,
            ];

            // build the menu options for the default related links menu select
            $menus = wp_get_nav_menus();
            $menu_options = [
                [
                    'value' => '',
                    'label' => '— Select a Menu —',
                ],
            ];

            // classic nav menus
            if (!empty($menus)) {
                $menu_options[] = [
                    'value' => '',
                    'label' => '── Classic Menus ──',
                    'disabled' => true,
                ];

                foreach ($menus as $menu) {
                    $menu_options[] = [
                        'value' => 'classic:' . $menu->term_id,
                        'label' => $menu->name,
                    ];
                }
            }

            // wp_navigation block menus (FSE)
            $nav_blocks = get_posts([
                'post_type' => 'wp_navigation',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
            ]);

            if (!empty($nav_blocks)) {
                $menu_options[] = [
                    'value' => '',
                    'label' => '── Navigation Blocks ──',
                    'disabled' => true,
                ];

                foreach ($nav_blocks as $nav) {
                    $menu_options[] = [
                        'value' => 'block:' . $nav->ID,
                        'label' => $nav->post_title ?: __('(no title)', 'bvi-mega-menu'),
                    ];
                }
            }

            // generate the general settings fields
            $general_fields = [
                [
                    'label_for' => 'css_val',
                    'title' => 'Include Default CSS',
                    'type' => 'checkbox',
                    'description' => 'Load the default mega menu stylesheet on the frontend.',
                ],
                [
                    'label_for' => 'mobile_override_val',
                    'title' => 'Mobile Menu Override',
                    'type' => 'select',
                    'description' =>
                        'Render an alternate menu alongside the Mega Menu block when mobile mode is' .
                        ' active. Leave empty to let the Mega Menu handle mobile itself.',
                    'options' => $menu_options,
                ],
                [
                    'label_for' => 'dropdown_val',
                    'title' => 'Instant Dropdown',
                    'type' => 'checkbox',
                    'description' => 'Show/hide mega menu dropdowns instantly instead of animating.',
                ],
                [
                    'label_for' => 'default_related_links_menu',
                    'title' => 'Default Related Links Menu',
                    'type' => 'select',
                    'description' =>
                        'Fallback menu for the Related Links block when no Mega Menu block is found' . ' on the page.',
                    'options' => $menu_options,
                ],
            ];

            // build the general section
            $sections[] = [
                'section_id' => 'general_settings_section',
                'section_details' => [
                    'section_title' => 'General Settings',
                    'section_description' => 'Configure general mega menu options.',
                    'fields' => $general_fields,
                ],
            ];

            // build IDs array for orchestrator
            $ids = [
                'page_settings_id' => $page_id,
                'page_settings_database_id' => $db_id,
                'parent_page_id' => '',
            ];

            if ($parent_page_id) {
                $ids['parent_page_id'] = $parent_page_id;
            }

            // configure this feature's settings page
            $this->settings = new Settings();
            $this->settings->configure($ids, $options, $sections);
        } catch (\Exception $e) {
            add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>';
            });
        }

        return $this;
    }

    /**
     * Gets whether the feature is enabled.
     *
     * @since 5.0.0
     *
     * @return bool True if the feature is enabled, false otherwise.
     */
    public function get_enabled(): bool
    {
        return true;
    }

    /**
     * Sets the enabled status of the feature.
     *
     * @since 5.0.0
     *
     * @param bool $enabled The enabled status to set.
     */
    public function set_enabled($enabled)
    {
        $this->enabled = $enabled;
        return $this->enabled;
    }

    /**
     * Gets the slug of the feature.
     *
     * @since 5.0.0
     *
     * @return string The slug of the feature.
     */
    public function get_slug(): string
    {
        return $this->slug;
    }

    /**
     * Sets the slug of the feature.
     *
     * @since 5.0.0
     *
     * @param string $slug The slug to set.
     */
    public function set_slug($slug)
    {
        $this->slug = $slug;
        return $this->slug;
    }

    /**
     * Gets the page ID for the feature's settings page.
     *
     * @since 5.0.0
     *
     * @return string The page ID for the feature's settings page.
     */
    public function get_page_id(): string
    {
        return $this->page_id;
    }

    /**
     * Sets the page ID of the feature's settings page.
     *
     * @since 5.0.0
     *
     * @param string $page_id The page ID to set.
     */
    public function set_page_id($page_id)
    {
        $this->page_id = $page_id;
        return $this->page_id;
    }

    /**
     * Gets the database ID of the feature's settings page.
     *
     * @since 5.0.0
     *
     * @return string The database ID of the feature's settings page.
     */
    public function get_db_id(): string
    {
        return $this->db_id;
    }

    /**
     * Sets the database ID of the feature's settings page.
     *
     * @since 5.0.0
     *
     * @param string $db_id The database ID to set.
     */
    public function set_db_id($db_id)
    {
        $this->db_id = $db_id;
        return $this->db_id;
    }

    /**
     * Gets the current database option value saved for this feature.
     *
     * @since 5.0.0
     *
     * @return array The current database option value for this feature.
     */
    public function get_db_option(): array
    {
        $db_id = $this->get_db_id();

        if (empty($db_id) || !get_option($db_id)) {
            return [];
        }

        return get_option($db_id);
    }

    /**
     * Gets the parent page ID for the feature's settings page.
     *
     * @since 5.0.0
     *
     * @return string The parent page ID for the feature's settings page.
     */
    public function get_parent_page_id(): string
    {
        return $this->parent_page_id;
    }

    /**
     * Sets the parent page ID for the feature's settings page.
     *
     * @since 5.0.0
     *
     * @param string $parent_page_id The parent page ID to set.
     */
    public function set_parent_page_id($parent_page_id)
    {
        $this->parent_page_id = $parent_page_id;
        return $this->parent_page_id;
    }

    /**
     * Gets the class name for the feature.
     *
     * @since 5.0.0
     *
     * @return string The class name for the feature.
     */
    public function get_class_name()
    {
        return $this->class_name;
    }

    /**
     * Sets the class name for the feature.
     *
     * @since 5.0.0
     *
     * @param string $class_name The class name to set.
     *
     * @return string The class name for the feature.
     */
    public function set_class_name($class_name)
    {
        $this->class_name = $class_name;
        return $this->class_name;
    }
}
