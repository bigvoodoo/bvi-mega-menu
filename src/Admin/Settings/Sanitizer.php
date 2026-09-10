<?php

namespace Bvi\Plugin\MegaMenu\Admin\Settings;

use Bvi\Plugin\MegaMenu\Utils\Traits\Strings;

/**
 * Class Sanitizer
 *
 * @package bviassess/src
 */
class Sanitizer
{
    use Strings;

    private $field_configs = [];
    private $field_types = [];

    /**
     * Store field configurations for sanitization context
     *
     * @since 5.0.0
     *
     * @param array $fields Field configurations
     * @return void
     */
    public function set_field_configs($fields)
    {
        $this->field_configs = $fields;
    }

    /**
     * Sanitizes all input fields from the form before they are saved into the database
     *
     * @since 5.0.0
     *
     * @param array $fields The input fields from the form
     * @return array The sanitized fields
     */
    public function sanitize($fields)
    {
        // do pre-sanitize actions if needed
        do_action(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_pre_sanitize_settings_form_inputs', $fields);

        // only process if input is an array
        if (empty($fields) || !is_array($fields)) {
            return false;
        }

        // extract field types
        $this->field_types = $this->extract_field_types($fields);

        // for every value, lets sanitize the value being sent to the database
        $sanitized_fields = [];

        foreach ($fields as $key => $value) {
            $sanitized_fields[$key] = $this->sanitize_field_value($value, $key);
        }

        // do post-sanitize actions if needed
        do_action(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_post_sanitize_settings_form_inputs', $fields);

        // return sanitized fields
        return $sanitized_fields;
    }

    /**
     * Sanitize individual field value based on field type
     *
     * @since 5.0.0
     *
     * @param string $field_key The field key
     * @param mixed $field_value The field value
     * @return mixed Sanitized value
     */
    private function sanitize_field_value($value, $key)
    {
        // if the value is still an array, recurse
        if (is_array($value)) {
            $result = [];

            foreach ($value as $child_key => $child_value) {
                $result[$child_key] = $this->sanitize_field_value($child_value, $child_key);
            }

            return $result;
        }

        $type = $this->field_type_map[$key] ?? 'text';

        // skip multi fields because they are handled in the recursive function
        if ($type === 'multi' || $type === 'composite') {
            return $value;
        }

        if ($type === 'checkbox' || $type === 'radio') {
            if (is_array($value)) {
                // handle multiple checkboxes/radios
                $cleaned = [];

                foreach ($value as $box_key => $box_value) {
                    if ($box_value) {
                        $cleaned[$box_key] = 1;
                    }
                }

                return $cleaned;
            } else {
                // single checkbox/radio
                return $value ? 1 : 0;
            }
        }

        return $this->clean_string($value, ['type' => $type]);
    }

    /**
     * Get field configuration by key
     *
     * @since 5.0.0
     *
     * @param string $field_key The field key
     * @return array|null Field configuration or null if not found
     */
    private function get_field_config($field_key)
    {
        foreach ($this->field_configs as $config) {
            if (isset($config['label_for']) && $config['label_for'] === $field_key) {
                return $config;
            }
        }
        return null;
    }

    /**
     * Recursively extract label_for => type pairs from a nested field array.
     *
     * @since 5.0.0
     *
     * @param array $items The nested field array
     * @return array The extracted field type map
     */
    private function extract_field_types(array $items): array
    {
        $map = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            // found a field definition — grab the pair
            if (isset($item['label_for'], $item['type'])) {
                $map[$item['label_for']] = $item['type'];
            }

            // recurse into any nested arrays
            foreach ($item as $child) {
                if (is_array($child)) {
                    $map = [...$map, ...$this->extract_field_types($child)];
                }
            }
        }

        return $map;
    }
}
