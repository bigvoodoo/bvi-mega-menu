<?php

namespace Bvi\Plugin\MegaMenu\Admin\MetaBox;

use Bvi\Plugin\MegaMenu\Admin\BaseSanitizer;

/**
 * MetaBox-specific sanitizer.
 *
 * Extracts field types from the stored field configs
 * and applies wp_kses_post to html/textarea fields.
 */
class Sanitizer extends BaseSanitizer
{
    /**
     * Build the field type map from the stored field configs.
     */
    protected function extract_field_types(): array
    {
        return $this->build_field_type_map($this->field_configs);
    }

    /**
     * MetaBox sanitizes html/textarea fields with wp_kses_post.
     */
    protected function sanitize_by_type(string $type, mixed $value): mixed
    {
        if ($type === 'html' || $type === 'textarea') {
            return wp_kses_post($value);
        }

        return null;
    }
}
