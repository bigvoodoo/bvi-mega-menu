<?php

namespace Bvi\Plugin\MegaMenu\Admin;

use Bvi\Plugin\MegaMenu\Admin\Settings\Menu;
use Bvi\Plugin\MegaMenu\Admin\Settings\Registrar;
use Bvi\Plugin\MegaMenu\Admin\Settings\Renderer;
use Bvi\Plugin\MegaMenu\Admin\Settings\Sanitizer;

/**
 * Class Settings
 *
 * @package bvimegamenu/src
 */
class Settings
{
    private $menu;
    private $registrar;
    private $renderer;
    private $sanitizer;
    private $page_id;
    private $db_id;
    private $options;
    private $sections;
    private $parent_page_id;
    private $template_id;
    private $class_name;

    /**
     * Configure the settings page by creating supporting classes.
     *
     * @since 5.0.0
     *
     * @var Menu      $menu      The object that creates the menu item.
     * @var Registrar $registrar The object that registers the settings fields.
     * @var Renderer  $renderer  The object that renders the page.
     * @var Sanitizer $sanitizer The object that sanitizes the fields.
     * @return void
     */
    public function __construct()
    {
    }

    public function init()
    {
        $this->menu = new Menu();
        $this->registrar = new Registrar();
        $this->renderer = new Renderer();
        $this->sanitizer = new Sanitizer();
    }

    /**
     * Configure the settings page.
     *
     * This method sets the page id, database id, parent page id, options, and sections.
     * It also sets the template id and class name if provided.
     * Additionally, it registers the admin menu and admin init actions.
     *
     * @since 5.0.0
     *
     * @param array $ids An associative array of page settings ids.
     * @param array $options An associative array of page settings options.
     * @param array $sections An associative array of page settings sections.
     * @return void
     */
    public function configure($ids, $options, $sections = null)
    {
        // initialize supporting classes
        $this->menu = new Menu();
        $this->registrar = new Registrar();
        $this->renderer = new Renderer();
        $this->sanitizer = new Sanitizer();

        $this->page_id = $ids['page_settings_id'];
        $this->db_id = $ids['page_settings_database_id'];
        $this->parent_page_id = $ids['parent_page_id'] ?? $this->page_id;
        $this->options = $options;
        $this->sections = $sections;
        $this->template_id = str_replace([BVI_PLUGIN_MEGAMENU_NAMESPACE . '_', '_'], ['', '-'], $this->page_id);
        $this->class_name = $options['class_name'] ?? null;

        add_action('admin_menu', [$this, 'register_menu'], 99);
        add_action('admin_init', [$this, 'register_settings_and_sections']);
    }

    /**
     * Register the menu item.
     *
     * This method registers the menu item under the appropriate parent.
     * If the menu type is 'menu', it adds a top-level menu item.
     * If the menu type is 'submenu', it adds a submenu item under the parent page id.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_menu()
    {
        $callback = function () {
            $this->renderer->render_form($this->template_id, $this->class_name, $this->page_id, $this->options);
        };

        if ($this->options['menu_type'] === 'menu') {
            $this->menu->add_menu_page($this->page_id, $this->options, $callback);
        } else {
            $this->menu->add_submenu_page($this->parent_page_id, $this->page_id, $this->options, $callback);
        }
    }

    /**
     * Registers settings and sections for the admin page.
     *
     * This method utilizes the registrar to register the settings associated with the page ID and database ID,
     * ensuring they are sanitized using the specified callback. If sections are provided, it iterates over each
     * section and its fields to append database ID and class name for renderer usage. Finally, it adds the sections
     * to the page using the registrar.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_settings_and_sections()
    {
        // collect all field configs for sanitizer context
        $all_field_configs = [];

        if (!empty($this->sections)) {
            foreach ($this->sections as $section) {
                if (!empty($section['section_details']['fields'])) {
                    $all_field_configs = array_merge($all_field_configs, $section['section_details']['fields']);
                }
            }
        }

        // pass field configs to sanitizer for context-aware sanitization
        $this->sanitizer->set_field_configs($all_field_configs);

        $sanitize_callback = [$this->sanitizer, 'sanitize'];
        $this->registrar->register_settings($this->page_id, $this->db_id, $sanitize_callback);

        if (!empty($this->sections)) {
            // add db_id and class_name to each field for renderer use
            foreach ($this->sections as &$section) {
                if (!empty($section['section_details']['fields'])) {
                    foreach ($section['section_details']['fields'] as &$field) {
                        $field['page_id'] = $this->page_id;
                        $field['parent_page_id'] = $this->parent_page_id;
                        $field['db_id'] = $this->db_id;
                        $field['class_name'] = $this->class_name;
                    }
                }
            }

            unset($section, $field);

            $this->registrar->add_sections($this->page_id, $this->db_id, $this->sections, $this->renderer);
        }
    }
}
