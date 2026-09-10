<?php

namespace Bvi\Plugin\MegaMenu\Utils\Traits;

/**
 * Trait Strings
 *
 * String sanitization, validation, and conversion utilities.
 */
trait Strings
{
    /**
     * Translate text using a specified domain, but only after init.
     */
    public function safe_translation(string $text, string $domain): string
    {
        if (did_action('init')) {
            return __($text, $domain);
        }

        return $text;
    }

    /**
     * Generate a random string of alphanumeric characters.
     */
    public function generate_random_string(int $size = 9): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random_string = '';

        for ($i = 0; $i < $size; $i++) {
            $random_byte = ord(random_bytes(1));
            $random_index = $random_byte % strlen($characters);
            $random_string .= $characters[$random_index];
        }

        return $random_string;
    }

    /**
     * Sanitize a string for use as an HTML attribute value.
     */
    public function create_safe_attribute_field_value(string $value): string
    {
        $clean = sanitize_title_with_dashes($value);
        $clean = strtolower(str_replace('-', '_', $clean));

        return esc_attr($clean);
    }

    /**
     * Validate and sanitize a string value.
     *
     * Returns false if the value fails validation (length, type).
     *
     * @param string $string_to_clean The string to clean.
     * @param array  $options Validation/formatting options:
     *   - type: alpha|alphanumeric|email|url|attribute|html|date|query_param
     *   - length, min_length, max_length: int constraints
     *   - trim, lowercase, uppercase, strip_tags: bool
     *   - comma_delimited, remove_quotes: bool
     *   - convert_spaces: 'strip' or replacement string
     *   - convert_returns: 'strip'|'windows'|'spaces'
     * @return string|false Cleaned string or false on validation failure.
     */
    public function clean_string($string_to_clean, array $options = [])
    {
        $string_to_clean = (string) $string_to_clean;

        if ($string_to_clean === '') {
            return false;
        }

        $string_to_clean = $this->apply_formatting($string_to_clean, $options);

        if (!$this->validate_length($string_to_clean, $options)) {
            return false;
        }

        if (!$this->validate_type($string_to_clean, $options)) {
            return false;
        }

        $string_to_clean = $this->process_returns($string_to_clean, $options);

        if (isset($options['remove_quotes']) && $options['remove_quotes'] === false) {
            $string_to_clean = str_replace(['"', '"'], '', $string_to_clean);
        }

        if (!empty($options['convert_spaces']) && is_string($options['convert_spaces'])) {
            $string_to_clean =
                $options['convert_spaces'] === 'strip'
                    ? str_replace(' ', '', $string_to_clean)
                    : str_replace(' ', $options['convert_spaces'], $string_to_clean);
        }

        return $string_to_clean;
    }

    /**
     * Convert any value to a sanitized string.
     *
     * Unlike clean_string(), this coerces rather than rejects.
     *
     * @param mixed $value   Value to convert.
     * @param array $options Conversion/formatting options (same as clean_string plus):
     *   - type: also supports 'int', 'float', 'absint', 'bool', 'string'
     *   - pad_char: character for padding (default: space)
     * @return string The converted and sanitized string.
     */
    public function convert_to_string($value, array $options = []): string
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
        } elseif (is_object($value)) {
            $value = method_exists($value, '__toString') ? (string) $value : '';
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = '';
        } else {
            $value = (string) $value;
        }

        $value = $this->apply_formatting($value, $options);
        $value = $this->apply_length_constraints($value, $options);
        $value = $this->convert_type($value, $options);
        $value = $this->process_returns($value, $options);

        if (isset($options['remove_quotes']) && $options['remove_quotes'] === true) {
            $value = str_replace(['"', '"', "'"], '', $value);
        }

        if (!empty($options['convert_spaces']) && is_string($options['convert_spaces'])) {
            $value =
                $options['convert_spaces'] === 'strip'
                    ? str_replace(' ', '', $value)
                    : str_replace(' ', $options['convert_spaces'], $value);
        }

        return $value;
    }

    /**
     * Validate and sanitize a numeric value.
     *
     * @return int|float|false
     */
    public function clean_number($number, ?int $max = null, ?int $min = null)
    {
        $options = ['options' => ['default' => false]];

        if ($max !== null) {
            $options['options']['max_range'] = $max;
        }
        if ($min !== null) {
            $options['options']['min_range'] = $min;
        }

        $number = (float) $number;
        $filtered = filter_var($number, FILTER_VALIDATE_FLOAT, $options);

        if ($filtered !== false && $filtered === (float) (int) $filtered) {
            return (int) $filtered;
        }

        return $filtered;
    }

    /**
     * Recursively sanitize form values by type.
     *
     * @return mixed
     */
    public function clean_form_values($values, string $type = 'string')
    {
        if (empty($values)) {
            return '';
        }

        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $values[$key] = $this->clean_form_values($value, $type);
            }
            return $values;
        }

        return match ($type) {
            'html', 'textarea' => wp_kses_post($values),
            'email' => sanitize_email($values),
            'url' => esc_url_raw($values),
            'int', 'number' => intval($values),
            'float' => floatval($values),
            'checkbox', 'radio' => $values ? 1 : 0,
            'attribute' => esc_attr($values),
            default => $this->clean_string($values, ['type' => $type]),
        };
    }

    // -- Private helpers ----------------------------------------------

    /**
     * Apply trim, case, and tag-stripping formatting.
     * Shared by clean_string() and convert_to_string().
     */
    private function apply_formatting(string $string_to_clean, array $options): string
    {
        if (!empty($options['trim'])) {
            $string_to_clean = trim($string_to_clean);
        }

        if (!empty($options['lowercase'])) {
            $string_to_clean = strtolower($string_to_clean);
        }

        if (!empty($options['uppercase'])) {
            $string_to_clean = strtoupper($string_to_clean);
        }

        if (!empty($options['strip_tags'])) {
            if (is_bool($options['strip_tags'])) {
                $string_to_clean = wp_strip_all_tags($string_to_clean);
            } elseif (is_array($options['strip_tags'])) {
                $string_to_clean = wp_kses($string_to_clean, $options['strip_tags']);
            }
        }

        return $string_to_clean;
    }

    /**
     * Validate string length. Returns false if length constraints fail.
     * Used by clean_string() for strict validation.
     */
    private function validate_length(string $string_to_clean, array $options): bool
    {
        if (isset($options['length']) && $options['length'] > 0 && strlen($string_to_clean) > $options['length']) {
            return false;
        }

        if (
            isset($options['min_length']) &&
            $options['min_length'] > 0 &&
            strlen($string_to_clean) < $options['min_length']
        ) {
            return false;
        }

        if (
            isset($options['max_length']) &&
            $options['max_length'] > 0 &&
            strlen($string_to_clean) > $options['max_length']
        ) {
            return false;
        }

        return true;
    }

    /**
     * Apply length constraints by truncating or padding.
     * Used by convert_to_string() for coercion.
     */
    private function apply_length_constraints(string $string_to_clean, array $options): string
    {
        $pad_char = $options['pad_char'] ?? ' ';

        if (isset($options['length']) && $options['length'] > 0) {
            if (strlen($string_to_clean) > $options['length']) {
                return substr($string_to_clean, 0, $options['length']);
            }
            if (strlen($string_to_clean) < $options['length']) {
                return str_pad($string_to_clean, $options['length'], $pad_char);
            }
            return $string_to_clean;
        }

        if (
            isset($options['min_length']) &&
            $options['min_length'] > 0 &&
            strlen($string_to_clean) < $options['min_length']
        ) {
            $string_to_clean = str_pad($string_to_clean, $options['min_length'], $pad_char);
        }

        if (
            isset($options['max_length']) &&
            $options['max_length'] > 0 &&
            strlen($string_to_clean) > $options['max_length']
        ) {
            $string_to_clean = substr($string_to_clean, 0, $options['max_length']);
        }

        return $string_to_clean;
    }

    /**
     * Validate string against type rules. Returns false on failure.
     * Used by clean_string() for strict validation.
     *
     * @return string|false
     */
    private function validate_type(string $string_to_clean, array $options)
    {
        $type = $options['type'] ?? null;

        if (empty($type)) {
            return sanitize_text_field($string_to_clean);
        }

        switch ($type) {
            case 'alpha':
                if (!preg_match('/^[a-zA-Z-]+$/i', $string_to_clean)) {
                    return false;
                }
                break;

            case 'alphanumeric':
                if (!preg_match('/^[a-zA-Z0-9-]+$/i', $string_to_clean)) {
                    return false;
                }
                break;

            case 'email':
                if (!filter_var($string_to_clean, FILTER_VALIDATE_EMAIL)) {
                    return false;
                }
                break;

            case 'url':
                if (!filter_var($string_to_clean, FILTER_VALIDATE_URL)) {
                    return false;
                }
                $string_to_clean = esc_url($string_to_clean);
                break;

            case 'query_param':
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $string_to_clean)) {
                    return false;
                }
                $string_to_clean = sanitize_key($string_to_clean);
                break;

            case 'attribute':
                $string_to_clean = esc_attr($string_to_clean);
                break;

            case 'html':
                $string_to_clean = esc_html($string_to_clean);
                break;

            case 'date':
                $string_to_clean = date_i18n('Y-m-d H:i:s', strtotime($string_to_clean));
                break;

            default:
                $string_to_clean = sanitize_text_field($string_to_clean);
                break;
        }

        return $string_to_clean;
    }

    /**
     * Sanitize string by type, coercing rather than rejecting.
     * Used by convert_to_string().
     */
    private function convert_type(string $string_to_clean, array $options): string
    {
        $type = $options['type'] ?? null;

        if (empty($type)) {
            return sanitize_text_field($string_to_clean);
        }

        return match ($type) {
            'alpha' => sanitize_text_field(preg_replace('/[^a-zA-Z]/i', '', $string_to_clean)),
            'alphanumeric' => sanitize_text_field(preg_replace('/[^a-zA-Z0-9]/i', '', $string_to_clean)),
            'int' => (string) intval(preg_replace('/[^0-9-]/', '', $string_to_clean)),
            'float' => (string) floatval(preg_replace('/[^0-9.-]/', '', $string_to_clean)),
            'absint' => (string) absint(preg_replace('/[^0-9]/', '', $string_to_clean)),
            'email' => sanitize_email($string_to_clean),
            'url' => sanitize_url(filter_var($string_to_clean, FILTER_SANITIZE_URL)),
            'query_param' => sanitize_key(
                trim(
                    preg_replace(
                        '/_+/',
                        '_',
                        preg_replace('/[^a-z0-9_-]/', '_', strtolower(wp_strip_all_tags($string_to_clean))),
                    ),
                    '_',
                ),
            ),
            'attribute' => esc_attr(
                trim(
                    preg_replace(
                        '/-+/',
                        '-',
                        preg_replace('/[^a-z0-9_-]/', '-', strtolower(wp_strip_all_tags($string_to_clean))),
                    ),
                    '-',
                ),
            ),
            'html', 'textarea' => wp_kses_post($string_to_clean),
            'bool', 'string' => sanitize_text_field($string_to_clean),
            'date' => date_i18n('Y-m-d H:i:s', strtotime($string_to_clean) ?: time()),
            default => sanitize_text_field($string_to_clean),
        };
    }

    /**
     * Process line returns in a string.
     */
    private function process_returns(string $string_to_clean, array $options): string
    {
        $return_chars = ["\t", "\r\n", "\n", "\r"];
        $replacement = ' ';

        if (isset($options['comma_delimited']) && $options['comma_delimited'] === true) {
            $string_to_clean = str_replace($return_chars, ', ', $string_to_clean);
        }

        if (isset($options['convert_returns'])) {
            $replacement = match ($options['convert_returns']) {
                'strip' => '',
                'windows' => '',
                'spaces' => ' ',
                default => ' ',
            };

            if ($options['convert_returns'] === 'windows') {
                $return_chars = ["\r"];
            }
        }

        return str_replace($return_chars, $replacement, $string_to_clean);
    }
}
