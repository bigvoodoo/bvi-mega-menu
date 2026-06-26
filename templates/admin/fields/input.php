<?php
// prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$is_multi = false;
$field_id = $id;
$field_name = !empty($parent_id)
    ? $page_database_id . '[' . $parent_id . '][' . $id . ']'
    : $page_database_id . '[' . $id . ']';
$field_value = $value;
$field_class = BVI_PLUGIN_MEGAMENU_NAMESPACE . '-input-field';

// check for multi field rendering
if (isset($count_key) || $is_template) {
    $is_multi = true;
    $count_key = intval($count_key);
    $index = $is_template ? $template_index : $count_key;

    $field_id = $id . '_' . $index . '_' . $field_key;
    $field_name = $page_database_id . '[' . $parent_id . '][' . $id . '][' . $index . '][' . $field_key . ']';
    $field_value = $is_template ? '' : ( $value[$field_key] ?? '' );
    $field_class .= ' ' . BVI_PLUGIN_MEGAMENU_NAMESPACE . '-multi-field';
}

if (empty($field_value) || !is_string($field_value)) {
    $field_value = '';
}

if ($is_template) {
    echo '<script type="text/template" id="multi-field-template-' . esc_attr($id) . '">';
}
?>

<div class="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-field <?php echo esc_attr($field_class); ?>" id="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>_<?php echo esc_attr($id); ?>">
    <?php if ($is_multi) : ?>
    <div class="multi-field-header">
        <span class="multi-field-label"><?php echo esc_html($label); ?> <?php echo $is_template ? '{LABEL_INDEX}' : esc_html((string) ( $count_key + 1 )); ?></span>
        <button type="button" class="remove-item button">×</button>
    </div>
    <?php endif; ?>
    <input type="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($field_id); ?>_id" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($field_value); ?>" />
    <?php require BVI_PLUGIN_MEGAMENU_DIR_PATH . 'templates/admin/field-footer.php'; ?>
</div>
<?php if ($is_template) {
echo '</script>'; } ?>
