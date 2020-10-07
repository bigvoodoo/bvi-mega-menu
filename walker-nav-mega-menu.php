<?php

/**
 * Create HTML list of Mega Menu items.
 * @see Walker_Nav_Menu
 * @see Walker
 * @author firejdl
 */
class Walker_Nav_Mega_Menu extends Walker_Nav_Menu {
	/**
	 * @see Walker::$tree_type
	 */
	var $tree_type = array('mega_menu');

	/**
	 * @see Walker::$db_fields
	 */
	var $db_fields = array(
		'parent' => 'parent_id',
		'id' => 'ID',
	);

	/**
	 * Overrides Walker_Nav_Menu::start_el() to display some of our special stuffs.
	 *
	 * @see Walker_Nav_Menu::start_el()
	 * @see Walker::start_el()
	 * @see Walker::walk()
	 */
	function start_el(&$output, $item, $depth = 0, $args = array(), $current_object_id = 0) {
		global $post;

		$indent = ($depth) ? str_repeat("\t", $depth) : '';

		$class_names = '';

		$classes = empty($item->classes) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-depth-' . $depth;

		// if this is the currently active page, OR if $depth is 0 and this item is stored as an
		// active page
		if($item->post_id == $post->ID || (in_array($item->ID, Mega_Menu::$active_pages) && $depth == 0)) {
			$classes[] = 'active';
		}

		$class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
		$class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

		$id = apply_filters('nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args);
		$id = $id ? ' id="' . esc_attr($id) . '"' : '';

		$output .= $indent . '<li' . $id . $class_names .'>';

		$item_output = '';
		if ($item->post_id || isset($item->url)) {
			if ($item->post_id) {
				$url = get_permalink($item->post_id);
			} else {
				$url = $item->url;
			}

			$attributes = '';
			$attributes .= ! empty($url)               ? ' href="'   . esc_attr($url            ) .'"' : '';
			$attributes .= ! empty($item->attr_title)  ? ' title="'  . esc_attr($item->attr_title) .'"' : '';
			$attributes .= ! empty($item->target)      ? ' target="' . esc_attr($item->target   ) .'"' : '';
			$attributes .= ! empty($item->xfn)         ? ' rel="'    . esc_attr($item->xfn      ) .'"' : '';

			$item_output = '<a'. $attributes .'>' . $args->link_before . apply_filters('the_title', $item->post_title, $item->ID) . $args->link_after . '</a>';
		} else if ($item->type == 'shortcode') {
			$item_output = do_shortcode(htmlspecialchars_decode($item->post_title, ENT_QUOTES));
		} else if (isset($item->post_title)) {
			$item_output = '<span>'.$item->post_title.'</span>';
		}

		$item_output = $args->before . $item_output . $args->after;

		if(!empty($args->mega_wrapper) && $depth == 0) {
			$item_output .= $args->mega_wrapper;
		}

		$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
	}

	function end_el(&$output, $item, $depth = 0, $args = array()) {
		$output .= apply_filters('walker_nav_menu_end_el', '', $item, $depth, $args);
		if(!empty($args->mega_wrapper_end) && $depth == 0) {
			$output .= $args->mega_wrapper_end;
		}
		parent::end_el($output, $item, $depth, $args);
	}
}
