<?php

namespace Bvi\Plugin\MegaMenu\Admin\MetaBox;

use Bvi\Plugin\MegaMenu\Utils\Traits\Strings;

/**
 * Class Renderer
 *
 * @package Bvi\Plugin\MegaMenu\src
 */
class Renderer
{
    use Strings;

    /**
     * Renders the after section content with sanitization.
     *
     * @since 0.1.0
     *
     * @param string $text The text to be rendered at a specific point in the content.
     * @return void
     */
    public function render_section_text($text)
    {
        if (empty($text)) {
            return;
        }

        // output the after section content with sanitization
        echo wp_kses_post($text);
    }

    /**
     * Renders a collection of fields based on provided configuration.
     *
     * @since 0.1.0
     *
     * @param array $config An array of configuration for the fields, which
     * contains an array of field definitions.
     * @return void
     */
    public function render_fields($config, $values)
    {
        if (empty($config) || empty($config['fields']) || !is_array($config['fields'])) {
            return '';
        }

        echo '<table class="form-table" role="presentation"><tbody>';

        foreach ($config['fields'] as $field) {
            $field_id = $field['label_for'] ?? ($field['id'] ?? '');
            $field['prefix'] = $config['prefix'];
            $field['value'] = $values[$field_id] ?? ($field['default'] ?? '');

            $label = $field['title'] ?? '';
            $type = $field['type'] ?? 'text';
            $for = $field_id . '_id'; // matches the id used inside each field template

            echo '<tr>';
            echo '<th scope="row">';

            if ($type === 'checkbox') {
                // checkboxes label themselves inline; the th label would
                // duplicate the field's own label
                echo esc_html($label);
            } else {
                echo '<label for="' . esc_attr($for) . '">' . esc_html($label) . '</label>';
            }

            echo '</th>';
            echo '<td>';
            $this->render_field($field);
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Renders a field based on provided arguments and type.
     *
     * @since 0.1.0
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
            'id' => $this->convert_to_string($args['id'] ?? ($args['label_for'] ?? null), ['type' => 'string']),
            'page_database_id' => $this->convert_to_string($args['prefix'] ?? '', ['type' => 'string']),
            'label' => $this->convert_to_string($args['title'] ?? '', ['type' => 'string']),
            'description' => $this->convert_to_string($args['description'] ?? '', ['type' => 'html']),
            'type' => $this->convert_to_string($args['type'] ?? 'text', ['type' => 'string']),
            'value' => $args['values'] ?? [],
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
        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- intentional exposure of field vars to template
        extract($field_vars);

        // if we are missing the bare minimum, skip
        if (empty($id) || empty($type)) {
            return;
        }

        // get option value if database id is provided
        if (array_key_exists('value', $args)) {
            $value = $args['value'];
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
                    $option_id = $option['label_for'] ?? ($option['id'] ?? $index);
                    $option_type = $option['type'] ?? ($option['field_type'] ?? 'text');
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
        if ((!is_array($value) && $type === 'checkbox') || $type === 'radio') {
            $value = intval($value);
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
     * @since 0.1.0
     *
     * @param string $template_id The id of the template to render, e.g. 'settings-page'
     * @param string $class_name If provided, the class name of the data class to provide to the template
     * @return void
     */
    public function render_box($config, $post = null)
    {
        wp_nonce_field($config['nonce'] . '_action', $config['nonce']);

        // get currently saved values
        $values = $this->get_meta_values($post->ID, $config);

        // extract configuration variables for use in template
        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- intentional exposure of config to template
        extract($config);

        $template_path = BVI_PLUGIN_MEGAMENU_DIR_PATH . 'templates/admin/';
        $template_id = $config['template_id'];

        // build the template filename, e.g., settings-page.php
        $template_file = $template_path . $template_id . '.php';
        $class_name = $config['class_name'] ?? null;

        // provide data class if required by the template
        if (!empty($class_name) && class_exists($class_name)) {
            $data_class = $class_name::get_instance();
        }

        // check if template override is requested
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, no state change
        $template_override_id = sanitize_file_name(wp_unslash($_GET['template'] ?? ''));

        if ($template_override_id) {
            $template_file = $template_path . $template_override_id . '.php';
            $template_id = $template_override_id;
        }

        // if no valid template file was provided, switch to the default
        if (!file_exists($template_file)) {
            $template_file = $template_path . 'metabox.php';
        }

        include_once $template_file;
    }

    /**
     * Renders a multi-subfield field.
     *
     * @since 0.1.0
     *
     * @param array $field An associative array containing information about the field.
     * @param array $value An associative array containing the values of the multi-subfield.
     * @param array $args An associative array containing information about the page being rendered.
     * @return string The rendered multi-subfield field.
     */
    private function generate_multi_subfield($field, $value, $args)
    {
        $field_type = $field['type'] ?? ($field['field_type'] ?? 'text');
        $field_parent_id = $args['label_for'] ?? ($args['id'] ?? null);
        $field_id = $field['label_for'] ?? ($field['id'] ?? null);
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
            'prefix' => $args['prefix'],
            'page_database_id' => $args['prefix'],
            'parent_id' => $field_parent_id,
            'id' => $field_id,
            'title' => $field['title'] ?? ($field['field_label'] ?? ''),
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

    private function generate_multi_subfield_template($field, $args)
    {
        $field_type = $field['type'] ?? ($field['field_type'] ?? 'text');
        $field_parent_id = $args['label_for'] ?? ($args['id'] ?? null);
        $field_id = $field['label_for'] ?? ($field['id'] ?? null);

        $field_vars = [
            'prefix' => $args['prefix'],
            'page_database_id' => $args['prefix'],
            'parent_id' => $field_parent_id,
            'id' => $field_id,
            'title' => $field['title'] ?? ($field['field_label'] ?? ''),
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

    private function get_meta_values(int $post_id, $config): array
    {
        $values = [];

        // if improperly configured, return
        if (empty($config) || empty($config['fields']) || !is_array($config['fields'])) {
            return $values;
        }

        // loop through fields and find their set values
        foreach ($config['fields'] as $field) {
            $field_id = $field['label_for'] ?? ($field['id'] ?? '');
            $meta_key = $config['prefix'] . $field_id;
            $meta_value = get_post_meta($post_id, $meta_key, true);

            if ($meta_value !== '') {
                $values[$field_id] = $meta_value;
            }
        }

        return $values;
    }
}
