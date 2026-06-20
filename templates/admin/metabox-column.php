<?php


/**
 * Admin metabox template: Column/Section item.
 *
 * @var int $placeholder  The nav menu placeholder index.
 * @var int $nav_menu_selected_id  Currently selected menu ID.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="columndiv" id="columndiv">
    <input type="hidden"
            value="column"
            name="menu-item[<?php echo esc_attr((string) $placeholder); ?>][menu-item-type]" />
    <p id="menu-item-url-wrap">
        <label class="howto" for="column-menu-item-url">
            <span><?php esc_html_e('URL', 'bvi-mega-menu'); ?></span>
            <input id="column-menu-item-url"
                    name="menu-item[<?php echo esc_attr((string) $placeholder); ?>][menu-item-url]"
                    type="text"
                    class="regular-text menu-item-textbox input-with-default-title"
                    title="<?php esc_attr_e('(optional)', 'bvi-mega-menu'); ?>" />
        </label>
    </p>
    <p id="menu-item-title-wrap">
        <label class="howto" for="column-menu-item-title">
            <span><?php esc_html_e('Title', 'bvi-mega-menu'); ?></span>
            <input id="column-menu-item-title"
                    name="menu-item[<?php echo esc_attr((string) $placeholder); ?>][menu-item-title]"
                    type="text"
                    class="regular-text menu-item-textbox input-with-default-title"
                    title="<?php esc_attr_e('(optional)', 'bvi-mega-menu'); ?>" />
        </label>
    </p>
    <p class="button-controls">
        <span class="add-to-menu">
            <input type="submit"
                    <?php disabled($nav_menu_selected_id, 0); ?>
                    class="button-secondary submit-add-column-to-menu right"
                    value="<?php esc_attr_e('Add to Menu', 'bvi-mega-menu'); ?>"
                    name="add-column-menu-item"
                    id="submit-columndiv" />
            <span class="spinner"></span>
        </span>
    </p>
</div>
