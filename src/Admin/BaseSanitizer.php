<?php

namespace Bvi\Plugin\MegaMenu\Admin;

use Bvi\Plugin\MegaMenu\Utils\Traits\Strings;

/**
 * Base sanitizer with shared sanitization logic for Settings and MetaBox.
 *
 * Subclasses override extract_field_types() to control how field type
 * maps are built, and sanitize_by_type() to add context-specific rules.
 */
abstract class BaseSanitizer
{
    use Strings;

    /** @var array */
    protected array $field_configs = [];

    /** @var array<string, string> */
    protected array $field_types = [];

    /**
     * Store field configurations for sanitization context.
     */
    public function set_field_configs(array $fields): void
    {
        $this->field_configs = $fields;
    }

    /**
     * Sanitize all input fields before they are saved to the database.
     *
     * @param array $fields The input fields from the form.
     * @return array|false Sanitized fields or false if invalid.
     */
    public function sanitize($fields)
    {
        do_action(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_pre_sanitize_settings_form_inputs', $fields);

        if (empty($fields) || !is_array($fields)) {
            return false;
        }

        $this->field_types = $this->extract_field_types();

        $sanitized = [];
        foreach ($fields as $key => $value) {
            $sanitized[$key] = $this->sanitize_field_value($value, $key);
        }

        do_action(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_post_sanitize_settings_form_inputs', $fields);

        return $sanitized;
    }

    /**
     * Build the field type map. Subclasses provide the source array.
     *
     * @return array<string, string>
     */
    abstract protected function extract_field_types(): array;

    /**
     * Hook for subclass-specific type handling (e.g. html/textarea).
     * Return null to fall through to default handling.
     *
     * @return mixed|null
     */
    protected function sanitize_by_type(string $type, mixed $value): mixed
    {
        return null;
    }

    /**
     * Recursively sanitize a single field value based on its type.
     */
    protected function sanitize_field_value(mixed $value, string $key): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $child_key => $child_value) {
                $result[$child_key] = $this->sanitize_field_value($child_value, $child_key);
            }
            return $result;
        }

        $type = $this->field_types[$key] ?? 'text';

        if ($type === 'multi' || $type === 'composite') {
            return $value;
        }

        // Let subclass handle type-specific logic first.
        $subclass_result = $this->sanitize_by_type($type, $value);
        if ($subclass_result !== null) {
            return $subclass_result;
        }

        if ($type === 'checkbox' || $type === 'radio') {
            if (is_array($value)) {
                $cleaned = [];
                foreach ($value as $box_key => $box_value) {
                    if ($box_value) {
                        $cleaned[$box_key] = 1;
                    }
                }
                return $cleaned;
            }
            return $value ? 1 : 0;
        }

        return $this->clean_string($value, ['type' => $type]);
    }

    /**
     * Get field configuration by key.
     *
     * @return array|null Field configuration or null if not found.
     */
    protected function get_field_config(string $field_key): ?array
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
     */
    protected function build_field_type_map(array $items): array
    {
        $map = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (isset($item['label_for'], $item['type'])) {
                $map[$item['label_for']] = $item['type'];
            }

            foreach ($item as $child) {
                if (is_array($child)) {
                    $map = [...$map, ...$this->build_field_type_map($child)];
                }
            }
        }

        return $map;
    }
}
