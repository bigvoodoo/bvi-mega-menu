<?php
// this is for singular composite fields outside of the multi field instance
// prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$is_multi = false;
$field_class = BVI_PLUGIN_MEGAMENU_NAMESPACE . '-composite-field';

// check for multi field rendering
if (isset($count_key) || $is_template) {
    $is_multi = true;
    $field_class .= ' ' . BVI_PLUGIN_MEGAMENU_NAMESPACE . '-multi-field';
}

if ($is_template) {
echo '<script type="text/template" id="multi-field-template-' . esc_attr($id) . '">'; }
?>
<div class="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-field <?php echo esc_attr($field_class); ?>" id="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>_<?php echo esc_attr($id); ?>">
    <div class="composite-field field-<?php echo esc_attr($id); ?>">
    <?php if ($is_multi) : ?>
    <div class="multi-field-header">
        <span class="multi-field-label"><?php echo esc_html($label); ?> <?php echo $is_template ? '{LABEL_INDEX}' : esc_html((string) ( $count_key + 1 )); ?></span>
        <button type="button" class="remove-item button">×</button>
    </div>
    <?php endif; ?>
    <?php
    // loop through all sub fields inside the composite field
    foreach ($fields as $field) :
        $field_key = $field['label_for'];
        $field_input_type = $field['type'] ?? 'text';
        $field_label = $field['title'] ?? '';
        $field_description = $field['description'] ?? '';
        $field_default = $field['default'] ?? '';
        $field_required = $field['required'] ?? '';
        $field_placeholder = $field['placeholder'] ?? '';
        $field_rows = $field['rows'] ?? 3;
        $field_options = $field['options'] ?? [];
        $field_id = "{$id}_{$field_key}";
        $field_name = "{$page_database_id}[{$parent_id}][{$id}][{$field_key}]";
        $field_value = $is_template ? '' : ( $value[$field_key] ?? $field_default );

        // if this is part of a multi field rendering, add the count key
        if ($is_multi) {
            $index = $is_template ? $template_index : $count_key;

            $field_id = "{$id}_{$index}_{$field_key}";
            $field_name = "{$page_database_id}[{$parent_id}][{$id}][{$index}][{$field_key}]";
        }
    ?>
        <div class="composite-field-row composite-field-type-<?php echo esc_attr($field_input_type); ?> field-container">
            <?php if ($field_description) : ?>
            <p class="description"><?php echo esc_html($field_description); ?></p>
            <?php endif; ?>
            <div class="field-<?php echo esc_attr($field_input_type); ?> field-<?php echo esc_attr($field_key); ?>">
                <?php if ($field_input_type != 'checkbox' && $field_input_type != 'radio') : ?>
                <label
                    for="<?php echo esc_attr($field_name); ?>">
                    <?php echo esc_html($field_label); ?>
                    <?php if (!empty($field_required)) : ?>
                    <span class="required">*</span>
                    <?php endif; ?>
                </label>
                <?php endif; ?>
                <?php if ($field_input_type == 'textarea' || $field_input_type == 'html') : ?>
                <textarea
                    id="<?php echo esc_attr($field_id); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    <?php if (!empty($field_placeholder)) :
?>placeholder="<?php echo esc_attr($field_placeholder); ?>"<?php endif; ?>
                    rows="<?php echo esc_attr($field_rows); ?>"
                    class="<?php echo esc_attr($field_input_type); ?>-field"
                    <?php if (!empty($field_required)) :
?>required<?php endif; ?>><?php echo esc_textarea($field_value); ?></textarea>
                <?php elseif ($field_input_type == 'select') : ?>
                <select
                    id="<?php echo esc_attr($field_id); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="<?php echo esc_attr($field_input_type); ?>-field"
                    <?php if (!empty($field_required)) :
?>required<?php endif; ?>>
                    <?php if (!empty($field_placeholder)) : ?>
                    <option value="">
                        <?php echo esc_html($field_placeholder); ?>
                    </option>
                    <?php endif; ?>
                    <?php if (!empty($field_options) && is_array($field_options)) :
                        foreach ($field_options as $option) : ?>
                    <option
                        value="<?php echo esc_attr($option['value']); ?>"
                        <?php echo ( $field_value == $option['value'] ) ? 'selected' : ''; ?>><?php echo esc_html($option['label']); ?>
                    </option>
                    <?php endforeach;
                    endif; ?>
                </select>
                <?php elseif ($field_input_type == 'checkbox' || $field_input_type == 'radio') : ?>
                <input
                    type="<?php echo esc_attr($field_input_type); ?>"
                    id="<?php echo esc_attr($field_id); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    value="1"
                    class="<?php echo esc_attr($field_input_type); ?>-field"
                    <?php echo ( $field_value == '1' ) ? 'checked' : ''; ?>
                /> <label
                    for="<?php echo esc_attr($id); ?>_<?php echo esc_attr($field_key); ?>">
                    <?php echo esc_html($field_label); ?>
                    <?php if (!empty($field_required)) : ?>
                    <span class="required">*</span>
                    <?php endif; ?>
                </label>
                <?php else : ?>
                <input
                    type="<?php echo esc_attr($field_input_type); ?>"
                    id="<?php echo esc_attr($field_id); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    value="<?php echo esc_attr($field_value); ?>"
                    <?php if (!empty($field_placeholder)) :
?>placeholder="<?php echo esc_attr($field_placeholder); ?>"<?php endif; ?>
                    class="<?php echo esc_attr($field_input_type); ?>-field"
                    <?php if (!empty($field_required)) :
?>required<?php endif; ?>
                />
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<?php if ($is_template) {
echo '</script>'; } ?>
