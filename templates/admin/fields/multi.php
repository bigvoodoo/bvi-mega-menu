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
<div class="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-field <?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-multi-field-container field-container" id="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>_<?php echo esc_attr($id); ?>" data-field-type="<?php echo esc_attr($field_type); ?>" data-max-items="<?php echo esc_attr($max_items); ?>" data-min-items="<?php echo esc_attr($min_items); ?>" data-field-name="<?php echo esc_attr($page_database_id . '[' . $id . ']'); ?>" data-field-label="<?php echo esc_attr($field_label); ?>">
    <div class="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-multi-subfields-container<?php echo $is_composite ? ' ' . esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE) . '-composite-field' : ''; ?>">
        <?php
        // $subfields_html is pre-rendered markup built from the escaped sub-field templates.
        echo $subfields_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
    </div>
    <button type="button" class="add-item button button-secondary">+ Add <?php echo esc_html($field_label); ?></button>
    <?php
    // $subfields_template_html is pre-rendered markup built from the escaped sub-field templates.
    echo $subfields_template_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
</div>
