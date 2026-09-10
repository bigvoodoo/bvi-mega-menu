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
$field_class = BVI_PLUGIN_MEGAMENU_NAMESPACE . '-radio-field';

// check for multi field rendering
if (isset($count_key) || $is_template) {
    $is_multi = true;
    $count_key = intval($count_key);
    $index = $is_template ? $template_index : $count_key;

    $field_id = $id . '_' . $index . '_' . $field_key;
    $field_name = $page_database_id . '[' . $parent_id . '][' . $id . '][' . $index . '][' . $field_key . ']';
    $field_value = $is_template ? 0 : ( $value[$field_key] ?? 0 );
    $field_class .= ' ' . BVI_PLUGIN_MEGAMENU_NAMESPACE . '-multi-field';
}

if (empty($field_value) || !is_string($field_value)) {
    $field_value = 0;
}

if ($is_template) {
echo '<script type="text/template" id="multi-field-template-' . esc_attr($id) . '">'; }
?>

<div class="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-field <?php echo esc_attr($field_class); ?>">
    <div class="field field-<?php echo esc_attr($name); ?>">
        <?php if ( $value === 0 ) : ?>
        <input type="hidden"
            name="<?php echo esc_attr($page_database_id); ?>[<?php echo esc_attr($fields['id']); ?>][<?php echo esc_attr($name); ?>]"
            value="0" />
        <?php endif; ?>
        <input type="radio" id="<?php echo esc_attr($fields['id']); ?>_<?php echo esc_attr($name); ?>_id"
            name="<?php echo esc_attr($page_database_id); ?>[<?php echo esc_attr($fields['id']); ?>][<?php echo esc_attr($name); ?>]"
            value="1" <?php if ( $value == 1 ) {
?>checked<?php } ?> /> <label
            for="<?php echo esc_attr($page_database_id); ?>[<?php echo esc_attr($fields['id']); ?>][<?php echo esc_attr($name); ?>]"><?php esc_html_e( $subfield_label ); ?></label>
    </div>
    <?php require BVI_PLUGIN_MEGAMENU_DIR_PATH . 'templates/admin/field-footer.php'; ?>
</div>
<?php if ($is_template) {
echo '</script>'; } ?>
