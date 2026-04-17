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
     * @param string $string The string to clean.
     * @param array  $options Validation/formatting options:
     *   - type: alpha|alphanumeric|email|url|attribute|html|date|query_param
     *   - length, min_length, max_length: int constraints
     *   - trim, lowercase, uppercase, strip_tags: bool
     *   - comma_delimited, remove_quotes: bool
     *   - convert_spaces: 'strip' or replacement string
     *   - convert_returns: 'strip'|'windows'|'spaces'
     * @return string|false Cleaned string or false on validation failure.
     */
    public function clean_string($string, array $options = [])
    {
        $string = (string) $string;

        if ($string === '') {
            return false;
        }

        $string = $this->apply_formatting($string, $options);

        if (!$this->validate_length($string, $options)) {
            return false;
        }

        if (!$this->validate_type($string, $options)) {
            return false;
        }

        $string = $this->process_returns($string, $options);

        if (isset($options['remove_quotes']) && $options['remove_quotes'] === false) {
            $string = str_replace(['"', '"'], '', $string);
        }

        if (!empty($options['convert_spaces']) && is_string($options['convert_spaces'])) {
            $string =
                $options['convert_spaces'] === 'strip'
                    ? str_replace(' ', '', $string)
                    : str_replace(' ', $options['convert_spaces'], $string);
        }

        return $string;
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
    private function apply_formatting(string $string, array $options): string
    {
        if (!empty($options['trim'])) {
            $string = trim($string);
        }

        if (!empty($options['lowercase'])) {
            $string = strtolower($string);
        }

        if (!empty($options['uppercase'])) {
            $string = strtoupper($string);
        }

        if (!empty($options['strip_tags'])) {
            if (is_bool($options['strip_tags'])) {
                $string = wp_strip_all_tags($string);
            } elseif (is_array($options['strip_tags'])) {
                $string = wp_kses($string, $options['strip_tags']);
            }
        }

        return $string;
    }

    /**
     * Validate string length. Returns false if length constraints fail.
     * Used by clean_string() for strict validation.
     */
    private function validate_length(string $string, array $options): bool
    {
        if (isset($options['length']) && $options['length'] > 0 && strlen($string) > $options['length']) {
            return false;
        }

        if (isset($options['min_length']) && $options['min_length'] > 0 && strlen($string) < $options['min_length']) {
            return false;
        }

        if (isset($options['max_length']) && $options['max_length'] > 0 && strlen($string) > $options['max_length']) {
            return false;
        }

        return true;
    }

    /**
     * Apply length constraints by truncating or padding.
     * Used by convert_to_string() for coercion.
     */
    private function apply_length_constraints(string $string, array $options): string
    {
        $pad_char = $options['pad_char'] ?? ' ';

        if (isset($options['length']) && $options['length'] > 0) {
            if (strlen($string) > $options['length']) {
                return substr($string, 0, $options['length']);
            }
            if (strlen($string) < $options['length']) {
                return str_pad($string, $options['length'], $pad_char);
            }
            return $string;
        }

        if (isset($options['min_length']) && $options['min_length'] > 0 && strlen($string) < $options['min_length']) {
            $string = str_pad($string, $options['min_length'], $pad_char);
        }

        if (isset($options['max_length']) && $options['max_length'] > 0 && strlen($string) > $options['max_length']) {
            $string = substr($string, 0, $options['max_length']);
        }

        return $string;
    }

    /**
     * Validate string against type rules. Returns false on failure.
     * Used by clean_string() for strict validation.
     *
     * @return string|false
     */
    private function validate_type(string $string, array $options)
    {
        $type = $options['type'] ?? null;

        if (empty($type)) {
            return sanitize_text_field($string);
        }

        switch ($type) {
            case 'alpha':
                if (!preg_match('/^[a-zA-Z-]+$/i', $string)) {
                    return false;
                }
                break;

            case 'alphanumeric':
                if (!preg_match('/^[a-zA-Z0-9-]+$/i', $string)) {
                    return false;
                }
                break;

            case 'email':
                if (!filter_var($string, FILTER_VALIDATE_EMAIL)) {
                    return false;
                }
                break;

            case 'url':
                if (!filter_var($string, FILTER_VALIDATE_URL)) {
                    return false;
                }
                $string = esc_url($string);
                break;

            case 'query_param':
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $string)) {
                    return false;
                }
                $string = sanitize_key($string);
                break;

            case 'attribute':
                $string = esc_attr($string);
                break;

            case 'html':
                $string = esc_html($string);
                break;

            case 'date':
                $string = date_i18n('Y-m-d H:i:s', strtotime($string));
                break;

            default:
                $string = sanitize_text_field($string);
                break;
        }

        return $string;
    }

    /**
     * Sanitize string by type, coercing rather than rejecting.
     * Used by convert_to_string().
     */
    private function convert_type(string $string, array $options): string
    {
        $type = $options['type'] ?? null;

        if (empty($type)) {
            return sanitize_text_field($string);
        }

        return match ($type) {
            'alpha' => sanitize_text_field(preg_replace('/[^a-zA-Z]/i', '', $string)),
            'alphanumeric' => sanitize_text_field(preg_replace('/[^a-zA-Z0-9]/i', '', $string)),
            'int' => (string) intval(preg_replace('/[^0-9-]/', '', $string)),
            'float' => (string) floatval(preg_replace('/[^0-9.-]/', '', $string)),
            'absint' => (string) absint(preg_replace('/[^0-9]/', '', $string)),
            'email' => sanitize_email($string),
            'url' => sanitize_url(filter_var($string, FILTER_SANITIZE_URL)),
            'query_param' => sanitize_key(
                trim(
                    preg_replace(
                        '/_+/',
                        '_',
                        preg_replace('/[^a-z0-9_-]/', '_', strtolower(wp_strip_all_tags($string))),
                    ),
                    '_',
                ),
            ),
            'attribute' => esc_attr(
                trim(
                    preg_replace(
                        '/-+/',
                        '-',
                        preg_replace('/[^a-z0-9_-]/', '-', strtolower(wp_strip_all_tags($string))),
                    ),
                    '-',
                ),
            ),
            'html', 'textarea' => wp_kses_post($string),
            'bool', 'string' => sanitize_text_field($string),
            'date' => date_i18n('Y-m-d H:i:s', strtotime($string) ?: time()),
            default => sanitize_text_field($string),
        };
    }

    /**
     * Process line returns in a string.
     */
    private function process_returns(string $string, array $options): string
    {
        $return_chars = ["\t", "\r\n", "\n", "\r"];
        $replacement = ' ';

        if (isset($options['comma_delimited']) && $options['comma_delimited'] === true) {
            $string = str_replace($return_chars, ', ', $string);
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

        return str_replace($return_chars, $replacement, $string);
    }
}
