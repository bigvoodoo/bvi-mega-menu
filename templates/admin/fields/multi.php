<?php
// prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$field_type = $options['field_type'] ?? 'text';
$field_label = $options['field_label'] ?? '';
$max_items = $options['max_items'] ?? null;
$min_items = $options['min_items'] ?? null;
$is_composite = ( $field_type === 'composite' );
$composite_fields = $is_composite ? ( $options['fields'] ?? [] ) : [];

?>
<div class="<?php echo BVI_PLUGIN_MEGAMENU_NAMESPACE; ?>-field <?php echo BVI_PLUGIN_MEGAMENU_NAMESPACE; ?>-multi-field-container field-container" id="<?php echo BVI_PLUGIN_MEGAMENU_NAMESPACE; ?>_<?php echo $id; ?>" data-field-type="<?php echo $field_type; ?>" data-max-items="<?php echo $max_items; ?>" data-min-items="<?php echo $min_items; ?>" data-field-name="<?php echo $page_database_id . '[' . $id . ']'; ?>" data-field-label="<?php echo $field_label; ?>">
    <div class="<?php echo BVI_PLUGIN_MEGAMENU_NAMESPACE; ?>-multi-subfields-container<?php echo $is_composite ? ' ' . BVI_PLUGIN_MEGAMENU_NAMESPACE . '-composite-field' : ''; ?>">
        <?php echo $subfields_html; ?>
    </div>
    <button type="button" class="add-item button button-secondary">+ Add <?php echo esc_html($field_label); ?></button>
    <?php echo $subfields_template_html; ?>
</div>
