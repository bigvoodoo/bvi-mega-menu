<?php
if (! defined('ABSPATH')) {
    exit;
}

// admin notices are surfaced via a redirect query arg; no state change occurs here, so no nonce is required.
// phpcs:disable WordPress.Security.NonceVerification.Recommended
if (isset($_GET['notice_message']) && isset($_GET['notice_type'])) {
    $notice_message = sanitize_text_field(wp_unslash($_GET['notice_message']));
    $notice_type = sanitize_text_field(wp_unslash($_GET['notice_type']));

    add_settings_error(
        BVI_PLUGIN_MEGAMENU_NAMESPACE . '_settings_messages',
        'ajax_notice',
        $notice_message,
        $notice_type
    );
}
// phpcs:enable WordPress.Security.NonceVerification.Recommended
?>
<div
    class="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-settings-<?php echo esc_attr($page_id); ?>-form wrap">
    <h1><?php echo esc_html($page_options['page_title']); ?>
    </h1>
    <div class="notices">
        <?php settings_errors(); ?>
    </div>
    <div class="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>">
        <form method="post" action="options.php" id="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>_settings_form" class="<?php echo esc_attr(BVI_PLUGIN_MEGAMENU_NAMESPACE); ?>-form-fields" onkeydown="return event.key != 'Enter';">
            <?php
            // access all potential existing form values
            settings_fields($page_id);
            // loop through and display all potential field options
            do_settings_sections($page_id);
            submit_button(__('Save Settings', BVI_PLUGIN_MEGAMENU_NAMESPACE));
            ?>
            <hr>
            <?php do_action(BVI_PLUGIN_MEGAMENU_NAMESPACE . '_add_' . $page_id . '_form_html'); ?>
        </form>
    </div>
</div>
