<?php

namespace Bvi\Plugin\MegaMenu\Blocks\Menus;

/**
 * Custom walker for mega menu rendering.
 *
 * Emits the same markup contract as the `bvi/menu-item` and `bvi/mega-panel`
 * blocks so a single stylesheet and view script drive both the block-composed
 * menu and classic-menu (slug / shortcode) rendering:
 *
 *   <li class="bvi-menu-item has-panel">
 *     <a class="bvi-menu-item-link" aria-haspopup="true" aria-expanded="false">…</a>
 *     <button class="bvi-menu-item-toggle" aria-expanded="false"><span></span></button>
 *     <div class="bvi-mega-panel">
 *       <ul class="bvi-mega-menu-sub-list"> … nested items … </ul>
 *     </div>
 *   </li>
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

    /** @var array<int|string, bool> Map of item IDs that have at least one child. */
    private $has_children_map = [];

    /**
     * Build a parent lookup before walking so start_el can flag items with children.
     *
     * @since 5.0.0
     *
     * @param array $elements  Menu item objects.
     * @param int   $max_depth Maximum depth (-1 for unlimited).
     * @param mixed ...$args   Additional walker arguments.
     * @return string Rendered HTML.
     */
    public function walk($elements, $max_depth, ...$args)
    {
        $this->has_children_map = [];

        foreach ((array) $elements as $element) {
            $parent_id = (int) ( $element->parent_id ?? 0 );
            if ($parent_id) {
                $this->has_children_map[$parent_id] = true;
            }
        }

        return parent::walk($elements, $max_depth, ...$args);
    }

    /**
     * Starts the list before the elements are added (the dropdown panel wrapper).
     *
     * @since 5.0.0
     *
     * @param string $output Used to append additional content (passed by reference).
     * @param int    $depth  Depth of menu item.
     * @param object $args   Walker arguments.
     * @return void
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '<div class="bvi-mega-panel"><ul class="bvi-mega-menu-sub-list">';
    }

    /**
     * Ends the list of after the elements are added.
     *
     * @since 5.0.0
     *
     * @param string $output Used to append additional content (passed by reference).
     * @param int    $depth  Depth of menu item.
     * @param object $args   Walker arguments.
     * @return void
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '</ul></div>';
    }

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

        $has_children = !empty($this->has_children_map[$item->ID]);

        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'bvi-menu-item';
        $classes[] = 'menu-item-depth-' . $depth;
        if ($has_children) {
            $classes[] = 'has-panel';
        }

        $class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';

        $output .= $indent . '<li' . $id . $class_names . '>';

        $output .= apply_filters(
            'walker_nav_menu_start_el',
            $this->build_item_inner($item, $depth, $args, $has_children),
            $item,
            $depth,
            $args,
        );
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
        parent::end_el($output, $item, $depth, $args);
    }

    /**
     * Build the link/label and optional toggle button for a menu item.
     *
     * @since 5.0.0
     *
     * @param object $item         Menu item data object.
     * @param int    $depth        Depth of menu item.
     * @param object $args         Walker arguments.
     * @param bool   $has_children Whether the item has child items.
     * @return string
     */
    private function build_item_inner($item, int $depth, $args, bool $has_children): string
    {
        // Shortcode item: render its output verbatim, no link wrapper.
        if (isset($item->type) && $item->type === 'shortcode') {
            $shortcode = do_shortcode(htmlspecialchars_decode($item->post_title, ENT_QUOTES));
            return ( $args->before ?? '' ) . $shortcode . ( $args->after ?? '' );
        }

        $url = $item->post_id ? get_permalink($item->post_id) : $item->url ?? '';
        $label =
            ( $args->link_before ?? '' ) .
            apply_filters('the_title', $item->post_title ?? '', $item->ID) .
            ( $args->link_after ?? '' );

        $attributes = '';
        $attributes .= !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
        $attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
        $attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
        if ($has_children) {
            $attributes .= ' aria-haspopup="true" aria-expanded="false"';
        }

        if (!empty($url)) {
            $item_output =
                '<a class="bvi-menu-item-link" href="' . esc_attr($url) . '"' . $attributes . '>' . $label . '</a>';
        } elseif ($has_children) {
            // No destination but has a panel: render a button so it toggles.
            $item_output =
                '<button type="button" class="bvi-menu-item-link"' . $attributes . '>' . $label . '</button>';
        } else {
            // Plain label (e.g. a column/section header).
            $item_output = '<span class="bvi-menu-item-link">' . $label . '</span>';
        }

        if ($has_children) {
            $item_output .=
                '<button type="button" class="bvi-menu-item-toggle" aria-label="' .
                esc_attr__('Toggle submenu', 'bvi-mega-menu') .
                '" aria-expanded="false"><span aria-hidden="true"></span></button>';
        }

        return ( $args->before ?? '' ) . $item_output . ( $args->after ?? '' );
    }
}
