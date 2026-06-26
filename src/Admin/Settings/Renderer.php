<?php

namespace Bvi\Plugin\MegaMenu\Admin\Settings;

use Bvi\Plugin\MegaMenu\Utils\Traits\Strings;

/**
 * Class Renderer
 *
 * @package bvimegamenu/src
 */
class Renderer
{
    use Strings;

    /**
     * Renders a field based on provided arguments and type.
     *
     * This function accepts an array of arguments that define the field's
     * properties, such as id, label, description, type, default value, and
     * database ID. It fetches the current value of the field from the database
     * if a database ID is provided. Depending on the field type (text, checkbox,
     * radio, select), it includes the appropriate template for rendering the field.
     * It also handles the rendering of field descriptions and wraps the field in
     * a styled div for consistent presentation.
     *
     * @since 5.0.0
     *
     * @param array $args An array of arguments for the field configuration.
     * @param boolean $disabled Optional. If true, the field is rendered as disabled.
     * @return string The rendered field HTML.
     */
    public function render_field($args, $disabled = false)
    {
        if (empty($args)) {
            return;
        }

        $subfields_html = '';
        $subfields_template_html = '';

        // ensure all data is sanitized before rendering
        $field_vars = [
            'page_id' => $this->convert_to_string($args['page_id'], ['type' => 'attribute']),
            'page_database_id' => $this->convert_to_string($args['db_id'], ['type' => 'attribute']),
            'parent_page_id' => $this->convert_to_string($args['parent_page_id'], ['type' => 'attribute']),
            'id' => $this->convert_to_string($args['id'] ?? ( $args['label_for'] ?? null ), ['type' => 'string']),
            'label' => $this->convert_to_string($args['title'] ?? '', ['type' => 'string']),
            'description' => $this->convert_to_string($args['description'] ?? '', ['type' => 'html']),
            'type' => $this->convert_to_string($args['type'] ?? 'text', ['type' => 'string']),
            'default' => $this->convert_to_string($args['default'] ?? '', ['type' => 'string']),
            'options' => $args['options'] ?? [],
            'fields' => $args['fields'] ?? [],
            'disabled' => $this->convert_to_string($disabled, ['type' => 'bool']),
            'is_template' => $args['is_template'] ?? false,
            'template_index' => $args['template_index'] ?? null,
            'count_key' => $args['count_key'] ?? null,
            'parent_id' => $this->convert_to_string($args['parent_id'] ?? null, ['type' => 'string']),
        ];

        // convert the array into variables
        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- intentional exposure of field vars to the field template
        extract($field_vars);

        // if we are missing the bare minimum, skip
        if (empty($page_id) || empty($page_database_id) || empty($id) || empty($type)) {
            return;
        }

        // get option value if database id is provided
        if (array_key_exists('value', $args)) {
            $value = $args['value'];
        } else {
            $values = $page_database_id ? get_option($page_database_id) : null;
            $value = isset($values[$id]) ? $values[$id] : $default;
        }

        // if there isnt a value set yet, set to an empty array
        if (empty($value)) {
            $value = [];
        }

        if ($type === 'multi') {
            // ensure value is array for multi-input
            if (!is_array($value)) {
                $value = !empty($value) ? [$value] : [];
            }

            if (!empty($options) && is_array($options)) {
                $templates = [];

                foreach ($options as $index => $option) {
                    $option_id = $option['label_for'] ?? ( $option['id'] ?? $index );
                    $option_type = $option['type'] ?? ( $option['field_type'] ?? 'text' );
                    $option_value = $value[$option_id] ?? null;

                    if ($option_type === 'composite') {
                        // composite: multiple instances
                        $instances = is_array($option_value) ? $option_value : [];

                        if (empty($instances)) {
                            $instances = [0 => []];
                        }

                        foreach ($instances as $instance_index => $instance_value) {
                            $option['count_key'] = $instance_index;
                            $subfields_html .= $this->generate_multi_subfield($option, $instance_value, $args);
                        }
                    } else {
                        // non-composite: single value, render once
                        $subfields_html .= $this->generate_multi_subfield($option, $option_value ?? '', $args);
                    }

                    // js template generation
                    if (!isset($templates[$option_id])) {
                        $option['count_key'] = $template_index;
                        $templates[$option_id] = $this->generate_multi_subfield_template($option, $args);
                    }
                }

                $subfields_template_html = implode('', $templates);
            }
        }

        // for checkbox/radio, cast to int
        if (( !is_array($value) && $type === 'checkbox' ) || $type === 'radio') {
            $value = intval($value);
        }

        if ($type === 'select') {
            $value = is_array($value) ? $value : [$value];
        }

        // render field type
        $field_template = sprintf('%stemplates/admin/fields/%s.php', BVI_PLUGIN_MEGAMENU_DIR_PATH, $type);

        // if the field template file doesnt exist, switch to the default
        if (!file_exists($field_template)) {
            $field_template = sprintf('%stemplates/admin/fields/input.php', BVI_PLUGIN_MEGAMENU_DIR_PATH);
        }

        include $field_template;
    }

    /**
     * Render the admin settings form template based on provided template id
     *
     * @since 5.0.0
     *
     * @param string $template_id The id of the template to render, e.g. 'settings-page'
     * @param string $class_name If provided, the class name of the data class to provide to the template
     * @return void
     */
    public function render_form($template_id, $class_name = null, $page_id = null, $page_options = null)
    {
        // check user capabilities based on feature setting
        if (!current_user_can($page_options['capability'])) {
            return;
        }

        $template_path = BVI_PLUGIN_MEGAMENU_DIR_PATH . 'templates/admin/';

        // build the template filename, e.g., settings-page.php
        $template_file = $template_path . $template_id . '.php';

        // provide data class if required by the template
        if (!empty($class_name) && class_exists($class_name)) {
            $data_class = $class_name::get_instance();
        }

        // check if template override is requested
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only template selection, no state change
        $template_override_id = sanitize_file_name(wp_unslash($_GET['template'] ?? ''));

        if ($template_override_id) {
            $template_file = $template_path . $template_override_id . '.php';
            $template_id = $template_override_id;
        }

        // if no valid template file was provided, switch to the default
        if (!file_exists($template_file)) {
            $template_file = $template_path . 'settings.php';
        }

        include_once $template_file;
    }

    /**
     * Renders content after a section, if provided.
     *
     * This method accepts an associative array with a single key, 'content', which
     * contains the content to be rendered. The content is sanitized using wp_kses_post()
     * before being echoed.
     *
     * @since 5.0.0
     *
     * @param array $after_section An associative array with a single key, 'content', which
     * contains the content to be rendered.
     * @return string The rendered after_section HTML.
     */
    public function render_after_section($after_section)
    {
        if (empty($after_section['content'])) {
            return;
        }

        // output the after section content with sanitization
        echo wp_kses_post($after_section['content']);
    }

    /**
     * Renders a multi-subfield field.
     *
     * This method takes an associative array $field, which contains the following keys:
     * - field_type: The type of the field. One of 'text', 'composite', or 'select'.
     * - field_label: The label of the field.
     * - max_items: The maximum number of items allowed in the field.
     * - min_items: The minimum number of items required in the field.
     * - options: An associative array of options for the field, where the key is the option
     *   value and the value is the option label.
     * - fields: An associative array of fields for composite type fields.
     * - disabled: A boolean indicating whether the field should be disabled.
     *
     * The method also takes an associative array $value, which contains the values of the
     * multi-subfield, and an associative array $args, which contains the following keys:
     * - page_id: The ID of the page being rendered.
     * - parent_page_id: The ID of the parent page of the page being rendered.
     * - db_id: The database ID of the page being rendered.
     *
     * @since 5.0.0
     *
     * @param array $field An associative array containing information about the field.
     * @param array $value An associative array containing the values of the multi-subfield.
     * @param array $args An associative array containing information about the page being rendered.
     * @return string The rendered multi-subfield field.
     */
    private function generate_multi_subfield($field, $value, $args)
    {
        $field_type = $field['type'] ?? ( $field['field_type'] ?? 'text' );
        $field_parent_id = $args['label_for'] ?? ( $args['id'] ?? null );
        $field_id = $field['label_for'] ?? ( $field['id'] ?? null );
        $max_items = $field['max_items'] ?? null;
        $min_items = $field['min_items'] ?? null;

        $is_composite = $field_type === 'composite';

        if ($is_composite && !empty($min_items) && $min_items > 0 && count($value) < $min_items) {
            $value_count = count($value);
            while ($value_count < $min_items) {
                $value[] = [];
                $value_count++;
            }
        }

        $field_vars = [
            'page_id' => $args['page_id'],
            'db_id' => $args['db_id'],
            'parent_page_id' => $args['parent_page_id'],
            'parent_id' => $field_parent_id,
            'id' => $field_id,
            'title' => $field['title'] ?? ( $field['field_label'] ?? '' ),
            'description' => $field['description'] ?? '',
            'type' => $field_type,
            'default' => $field['default'] ?? '',
            'options' => $field['options'] ?? [],
            'fields' => $field['fields'] ?? [],
            'count_key' => $field['count_key'] ?? '',
            'value' => $value,
        ];

        ob_start();
        $this->render_field($field_vars, $field['disabled'] ?? false);
        return ob_get_clean();
    }

    /**
     * Generates a multi-subfield field template.
     *
     * This method takes an associative array $field, which contains the following keys:
     * - field_type: The type of the field. One of 'text', 'composite', or 'select'.
     * - field_label: The label of the field.
     * - max_items: The maximum number of items allowed in the field.
     * - min_items: The minimum number of items required in the field.
     * - options: An associative array of options for the field, where the key is the option
     *   value and the value is the option label.
     * - fields: An associative array of fields for composite type fields.
     * - disabled: A boolean indicating whether the field should be disabled.
     *
     * The method also takes an associative array $args, which contains the following keys:
     * - page_id: The ID of the page being rendered.
     * - parent_page_id: The ID of the parent page of the page being rendered.
     * - db_id: The database ID of the page being rendered.
     *
     * @since 5.0.0
     *
     * @param array $field An associative array containing information about the field.
     * @param array $args An associative array containing information about the page being rendered.
     * @return string The rendered multi-subfield field template.
     */
    private function generate_multi_subfield_template($field, $args)
    {
        $field_type = $field['type'] ?? ( $field['field_type'] ?? 'text' );
        $field_parent_id = $args['label_for'] ?? ( $args['id'] ?? null );
        $field_id = $field['label_for'] ?? ( $field['id'] ?? null );

        $field_vars = [
            'page_id' => $args['page_id'],
            'db_id' => $args['db_id'],
            'parent_page_id' => $args['parent_page_id'],
            'parent_id' => $field_parent_id,
            'id' => $field_id,
            'title' => $field['title'] ?? ( $field['field_label'] ?? '' ),
            'description' => $field['description'] ?? '',
            'type' => $field_type,
            'default' => $field['default'] ?? '',
            'options' => $field['options'] ?? [],
            'fields' => $field['fields'] ?? [],
            'is_template' => true,
            'template_index' => '{INDEX}',
        ];

        ob_start();
        $this->render_field($field_vars, $field['disabled'] ?? false);
        return ob_get_clean();
    }
}
