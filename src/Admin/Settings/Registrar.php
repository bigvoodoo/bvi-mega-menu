<?php

namespace Bvi\Plugin\MegaMenu\Admin\Settings;

/**
 * Class Registrar
 *
 * @package bvimegamenu/src
 */
class Registrar
{
    /**
     * Registers a settings section.
     *
     * @since 0.1.0
     *
     * @param string $page_id The slug-name of the settings page.
     * @param string $db_id The slug-name of the setting to register.
     * @param callable $sanitize_callback The callback function that will be used to sanitize the input data.
     * @return void
     *
     * @see register_setting()
     */
    public function register_settings($page_id, $db_id, $sanitize_callback)
    {
        register_setting($page_id, $db_id, $sanitize_callback);
    }

    /**
     * Adds sections to the settings form.
     *
     * Each section is represented by an associative array with the following keys:
     *   - section_key (string): The identifier of the section.
     *   - section_title (string): The title of the section.
     *   - section_description (string): The description of the section.
     *   - page_id (mixed): The ID of the page the section belongs to.
     *   - fields (array): An array of fields to be added to the section. Each field is represented by an
     *     associative array with the following keys:
     *     - label_for (string): The `label_for` attribute of the field.
     *     - title (string): The title of the field.
     *     - callback (string or callable): The callback to render the field. If a string, the class of the
     *       current object is used. If a callable, the callback is called with the argument $args.
     *     - args (array): Additional arguments to pass to the callback.
     *   - after_section (array): An associative array with the following keys:
     *     - title (string): The title of the after_section field.
     *     - callback (string or callable): The callback to render the after_section field. If a string, the
     *       class of the current object is used. If a callable, the callback is called with the argument $args.
     *     - args (array): Additional arguments to pass to the callback.
     *   - class (string): An optional class name to use for additional logic. The class must have a method
     *     init() to be called.
     *
     * @since 0.1.0
     *
     * @param string $page_id The ID of the page to add the sections to.
     * @param array $sections An array of sections to add to the form.
     * @param callable $renderer The callback to render the form.
     * @return void
     */
    public function add_sections($page_id, $db_id, $sections, $renderer)
    {
        if (!is_array($sections)) {
            return;
        }

        foreach ($sections as $section) {
            if (empty($section['section_id']) || empty($section['section_details'])) {
                continue;
            }

            $details = $section['section_details'];
            $fields = $details['fields'] ?? [];

            // register the section itself.
            add_settings_section(
                $section['section_id'],
                $details['section_title'] ?? '',
                function () use ($details) {
                    echo isset($details['section_description']) ? esc_html($details['section_description']) : '';
                },
                $page_id,
            );

            // register all fields for this section.
            foreach ($fields as $field) {
                $field_id = $field['label_for'];
                $field['section_id'] = $section['section_id'];
                // pass renderer and page_id in args for use in callback.
                $field['renderer'] = $renderer;
                $field['db_id'] = $db_id;

                add_settings_field(
                    $field_id,
                    $field['title'],
                    [$this, 'field_callback'],
                    $page_id,
                    $section['section_id'],
                    $field,
                );
            }

            // after_section field (for custom content after fields)
            if (!empty($details['after_section'])) {
                add_settings_field(
                    $section['section_id'] . '_after',
                    '',
                    [$this, 'after_section_callback'],
                    $page_id,
                    $section['section_id'],
                    $details['after_section'],
                );
            }
        }
    }

    /**
     * Callback function to render a field.
     *
     * This function checks if the provided renderer has a method named 'render_field'.
     * If so, it calls this method with the given field arguments.
     *
     * @since 0.1.0
     *
     * @param array $args An array of arguments for the field, including the renderer object.
     * @return string The rendered field HTML.
     */
    public function field_callback($args)
    {
        if (!empty($args['renderer']) && method_exists($args['renderer'], 'render_field')) {
            // pass all field args to renderer
            $args['renderer']->render_field($args);
        }
    }

    /**
     * Callback function to render a section's after_section content.
     *
     * This function takes an associative array with the key 'content' as argument.
     * If the 'content' key is set, it will be echoed after sanitizing using wp_kses_post.
     *
     * @since 0.1.0
     *
     * @param array $after_section An associative array with the key 'content'.
     * @return string The rendered after_section HTML.
     */
    public function after_section_callback($after_section)
    {
        if (!empty($after_section['content'])) {
            echo wp_kses_post($after_section['content']);
        }
    }
}
