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
$field_class = BVI_PLUGIN_MEGAMENU_NAMESPACE . '-textarea-field';

// check for multi field rendering
if (isset($count_key) || $is_template) {
    $is_multi = true;
    $count_key = intval($count_key);
    $index = $is_template ? $template_index : $count_key;

    $field_id = $id . '_' . $index . '_' . $field_key;
    $field_name = $page_database_id . '[' . $parent_id . '][' . $id . '][' . $index . '][' . $field_key . ']';
    $field_value = $is_template ? '' : ($value[$field_key] ?? '');
    $field_class .= ' ' . BVI_PLUGIN_MEGAMENU_NAMESPACE . '-multi-field';
}

if ($is_template) { echo '<script type="text/template" id="multi-field-template-' . $id . '">'; }
?>
<div class="<?php echo BVI_PLUGIN_MEGAMENU_NAMESPACE; ?>-field <?php echo $field_class; ?>" id="<?php echo BVI_PLUGIN_MEGAMENU_NAMESPACE; ?>_<?php echo $id; ?>">
    <?php if ($is_multi): ?>
    <div class="multi-field-header">
        <span class="multi-field-label"><?php echo $label; ?> <?php echo $is_template ? '{LABEL_INDEX}' : ($count_key + 1); ?></span>
        <button type="button" class="remove-item button">×</button>
    </div>
    <?php endif; ?>
    <textarea id="<?php echo $id; ?>_id" name="<?php echo $page_database_id; ?>[<?php echo $id; ?>]"><?php echo $value; ?></textarea>
    <?php include(BVI_PLUGIN_MEGAMENU_DIR_PATH . 'templates/admin/field-footer.php'); ?>
</div>
<?php if ($is_template) { echo '</script>'; } ?>
