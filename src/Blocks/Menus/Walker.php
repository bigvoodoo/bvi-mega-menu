<?php

namespace Bvi\Plugin\MegaMenu\Blocks\Menus;

/**
 * Custom walker for mega menu rendering.
 *
 * Extends Walker_Nav_Menu to handle mega menu-specific item types:
 * posts/pages, shortcodes, columns/sections, and sub-menus.
 *
 * @package bvi-mega-menu
 */
class Walker extends \Walker_Nav_Menu
{
    /** @var array */
    public $tree_type = ['mega_menu'];

    /** @var array */
    public $db_fields = [
        'parent' => 'parent_id',
        'id' => 'ID',
    ];

    /**
     * Starts the element output.
     *
     * @param string   $output Used to append additional content (passed by reference).
     * @param object   $item   Menu item data object.
     * @param int      $depth  Depth of menu item.
     * @param object   $args   Walker arguments.
     * @param int      $current_object_id Current object ID.
     * @return void
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
    public function start_el(&$output, $item, $depth = 0, $args = null, $current_object_id = 0)
    {
        $indent = $depth ? str_repeat("\t", $depth) : '';

        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-depth-' . $depth;

        $class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';

        $output .= $indent . '<li' . $id . $class_names . '>';

        $item_output = '';

        if ($item->post_id || isset($item->url)) {
            // Post/page or custom URL item.
            $url = $item->post_id ? get_permalink($item->post_id) : $item->url ?? '';

            $attributes = '';
            $attributes .= !empty($url) ? ' href="' . esc_attr($url) . '"' : '';
            $attributes .= !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
            $attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
            $attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';

            if ($depth === 0) {
                $attributes .= ' aria-haspopup="true" aria-expanded="false"';
            }

            $item_output =
                '<a' .
                $attributes .
                '>' .
                $args->link_before .
                apply_filters('the_title', $item->post_title, $item->ID) .
                $args->link_after .
                '</a>';

            if ($depth === 0 && !empty($args->aria_button) && $args->aria_button === 'true') {
                $item_output .= '<button class="aria-button"><span></span></button>';
            }
        } elseif (isset($item->type) && $item->type === 'shortcode') {
            // Shortcode item.
            $item_output = do_shortcode(htmlspecialchars_decode($item->post_title, ENT_QUOTES));
        } elseif (isset($item->post_title)) {
            // Column/section header.
            $item_output = '<span>' . $item->post_title . '</span>';
        }

        $item_output = $args->before . $item_output . $args->after;

        // Inject mega wrapper after top-level items.
        if (!empty($args->mega_wrapper) && $depth === 0) {
            $item_output .= $args->mega_wrapper;
        }

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Ends the element output.
     *
     * @param string $output Used to append additional content (passed by reference).
     * @param object $item   Menu item data object.
     * @param int    $depth  Depth of menu item.
     * @param object $args   Walker arguments.
     * @return void
     */
    public function end_el(&$output, $item, $depth = 0, $args = null)
    {
        $output .= apply_filters('walker_nav_menu_end_el', '', $item, $depth, $args);

        if (!empty($args->mega_wrapper_end) && $depth === 0) {
            $output .= $args->mega_wrapper_end;
        }

        parent::end_el($output, $item, $depth, $args);
    }
}
