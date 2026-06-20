<?php


/**
 * Admin metabox template: Menu item (embed another menu).
 *
 * @var int   $placeholder           The nav menu placeholder index.
 * @var int   $nav_menu_selected_id  Currently selected menu ID.
 * @var array $nav_menus             Available navigation menus.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="menudiv" id="menudiv">
    <input type="hidden"
            value="menu"
            name="menu-item[<?php echo esc_attr((string) $placeholder); ?>][menu-item-type]" />
    <p id="menu-item-menu-wrap">
        <label class="howto" for="menu-menu-item-menu">
            <span><?php esc_html_e('Menu', 'bvi-mega-menu'); ?></span>
            <select name="menu-item[<?php echo esc_attr((string) $placeholder); ?>][menu]"
                    id="menu-menu-item-menu">
                <option value="0" selected="selected">
                    <?php esc_html_e('-- Select --', 'bvi-mega-menu'); ?>
                </option>
                <?php foreach ((array) $nav_menus as $navMenu) : ?>
                    <?php if ((int) $navMenu->term_id === (int) $nav_menu_selected_id) {
                        continue;
                    } ?>
                    <option value="<?php echo esc_attr((string) $navMenu->term_id); ?>">
                        <?php echo esc_html($navMenu->truncated_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>
    <p id="menu-item-title-wrap">
        <label class="howto" for="menu-menu-item-title">
            <span><?php esc_html_e('Title', 'bvi-mega-menu'); ?></span>
            <input id="menu-menu-item-title"
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
                    class="button-secondary submit-add-menu-to-menu right"
                    value="<?php esc_attr_e('Add to Menu', 'bvi-mega-menu'); ?>"
                    name="add-menu-menu-item"
                    id="submit-menudiv" />
            <span class="spinner"></span>
        </span>
    </p>
</div>
