<?php
if (! defined('ABSPATH')) {
    exit;
}

// do not render if there are no fields
if (empty($fields) || !is_array($fields)) {
    return;
}
?>
<div class="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-metabox <?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-<?php echo esc_attr($id); ?>-metabox">
    <?php $this->render_section_text($config['before_content']); ?>
    <div class="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-metabox-fields <?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-form-fields">
        <?php $this->render_fields($config, $values); ?>
    </div>
    <?php $this->render_section_text($config['after_content']); ?>
</div>
