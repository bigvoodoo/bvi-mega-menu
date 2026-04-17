<?php


/**
 * Admin metabox template: Shortcode/HTML item.
 *
 * @var int $placeholder  The nav menu placeholder index.
 * @var int $nav_menu_selected_id  Currently selected menu ID.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="shortcodediv" id="shortcodediv">
    <input type="hidden"
           value="shortcode"
           name="menu-item[<?php echo esc_attr((string) $placeholder); ?>][menu-item-type]" />
    <p id="menu-item-shortcode-wrap">
        <textarea id="shortcode-menu-item"
                  name="menu-item[<?php echo esc_attr((string) $placeholder); ?>][menu-item-url]"
                  class="code menu-item-textbox"></textarea>
    </p>
    <p class="button-controls">
        <span class="add-to-menu">
            <input type="submit"
                   <?php disabled($nav_menu_selected_id, 0); ?>
                   class="button-secondary submit-add-shortcode-to-menu right"
                   value="<?php esc_attr_e('Add to Menu', 'bvi-mega-menu'); ?>"
                   name="add-shortcode-menu-item"
                   id="submit-shortcodediv" />
            <span class="spinner"></span>
        </span>
    </p>
</div>
