<?php
/**
 * Sets up the Admin section for our Mega Menu.
 * @author Joey Line <joey@bigvoodoo.com>
 */

if (!function_exists('add_action')) {
	echo 'No direct access.';
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

/**
 * Sets up the admin menu interface with some fanciness!
 */
class Mega_Menu_Admin {
	private $menu_items = array();
	private $nav_id_to_post_id = array();

	/**
	 * Constructor: set up actions/filters
	 */
	public function __construct() {
		// register activation & uninstallation hooks
		// see http://wordpress.stackexchange.com/a/25979
		register_activation_hook(dirname(__FILE__) . '/mega-menu.php', array('Mega_Menu_Admin', 'activate'));
		register_uninstall_hook(__FILE__, array('Mega_Menu_Admin', 'uninstall'));

		// nav menu page customizations
		add_action('admin_head-nav-menus.php', array(&$this, 'customize_nav_menu_page'));

		// AJAX calls related to nav menu page customizations
		add_action('wp_ajax_nav_menu_get_post_descendants', array(&$this, 'get_post_descendants'), 1);
		add_action('wp_ajax_nav_menu_duplicate_item', array(&$this, 'duplicate_item'), 1);

		// save menu changes to our own SPESHUL table
		add_action('wp_update_nav_menu', array(&$this, 'update_nav_menu'), 1, 2);
		add_action('wp_update_nav_menu_item', array(&$this, 'update_nav_menu_item'), 1, 3);
	}

	/**
	 * TODO: documentation
	 */
	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . Mega_Menu::$table_name;
		$query = 'CREATE TABLE IF NOT EXISTS `' . $table_name . '` (
			`ID` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`menu_id` bigint(20) NOT NULL,
			`post_id` bigint(20) NOT NULL,
			`parent_id` bigint(20) NOT NULL,
			`position` bigint(20) NOT NULL,
			`data` longtext NOT NULL
		);';
		$wpdb->query($query);

		// flush the rewrite rules so the new rewrite rule defined in
		// Mega_Menu::add_ajax_rewrite_rules() will take effect
		flush_rewrite_rules();
	}

	/**
	 * TODO: documentation
	 */
	public static function uninstall() {
		global $wpdb;
		$table_name = $wpdb->prefix . Mega_Menu::$table_name;
		$query = 'DROP TABLE IF EXISTS `' . $table_name . '`;';
		$wpdb->query($query);
	}

	/**
	 * adds a metabox for shortcodes and includes our special JS & CSS
	 * TODO: documentation
	 *
	 * @action admin_head-nav-menus.php
	 */
	public function customize_nav_menu_page() {
		wp_register_script('nav-menu-column-js', plugins_url('js/nav-menu-column.js', __FILE__));
		wp_register_script('nav-menu-menu-js', plugins_url('js/nav-menu-menu.js', __FILE__));
		wp_register_script('nav-menu-shortcode-js', plugins_url('js/nav-menu-shortcode.js', __FILE__));

		wp_enqueue_script('nav-menu-column-js');
		wp_enqueue_script('nav-menu-menu-js');
		wp_enqueue_script('nav-menu-shortcode-js');

		wp_register_style('nav-menu-shortcode-css', plugins_url('css/nav-menu-shortcode.css', __FILE__));
		wp_register_style('nav-menu-buttons-css', plugins_url('css/nav-menu-buttons.css', __FILE__));
		wp_register_style('nav-menu-menu-css', plugins_url('css/nav-menu-menu.css', __FILE__));

		wp_enqueue_style('nav-menu-shortcode-css');
		wp_enqueue_style('nav-menu-buttons-css');
		wp_enqueue_style('nav-menu-menu-css');

		add_meta_box('add-shortcode', 'Shortcode/HTML', array(&$this, 'shortcode_metabox_content'), 'nav-menus', 'side', 'default');
		add_meta_box('add-column', 'Column/Section', array(&$this, 'column_metabox_content'), 'nav-menus', 'side', 'default');
		add_meta_box('add-menu', 'Menu', array(&$this, 'menu_metabox_content'), 'nav-menus', 'side', 'default');
	}

	/**
	 * sets up the content for the add shortcode metabox.
	 */
	public function shortcode_metabox_content() {
		global $_nav_menu_placeholder, $nav_menu_selected_id;
		$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

		?>
		<div class="shortcodediv" id="shortcodediv">
			<input type="hidden" value="shortcode" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" />
			<p id="menu-item-shortcode-wrap">
				<textarea id="shortcode-menu-item" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" class="code menu-item-textbox"></textarea>
			</p>

			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php disabled($nav_menu_selected_id, 0); ?> class="button-secondary submit-add-shortcode-to-menu right" value="<?php esc_attr_e('Add to Menu'); ?>" name="add-shortcode-menu-item" id="submit-shortcodediv" />
					<span class="spinner"></span>
				</span>
			</p>

		</div><!-- /.shortcodediv -->
		<?php
	}

	/**
	 * sets up the content for the add column metabox.
	 */
	public function column_metabox_content() {
		global $_nav_menu_placeholder, $nav_menu_selected_id;
		$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

		?>
		<div class="columndiv" id="columndiv">
			<input type="hidden" value="column" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" />
			<p id="menu-item-url-wrap">
				<label class="howto" for="column-menu-item-url">
					<span><?php _e('URL'); ?></span>
					<input id="column-menu-item-url" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" type="text" class="regular-text menu-item-textbox input-with-default-title" title="<?php esc_attr_e('(optional)'); ?>" />
				</label>
			</p>

			<p id="menu-item-title-wrap">
				<label class="howto" for="column-menu-item-title">
					<span><?php _e('Title'); ?></span>
					<input id="column-menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" type="text" class="regular-text menu-item-textbox input-with-default-title" title="<?php esc_attr_e('(optional)'); ?>" />
				</label>
			</p>

			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php disabled($nav_menu_selected_id, 0); ?> class="button-secondary submit-add-column-to-menu right" value="<?php esc_attr_e('Add to Menu'); ?>" name="add-column-menu-item" id="submit-columndiv" />
					<span class="spinner"></span>
				</span>
			</p>

		</div><!-- /.columndiv -->
		<?php
	}

	/**
	 * sets up the content for the add menu metabox.
	 */
	public function menu_metabox_content() {
		global $_nav_menu_placeholder, $nav_menu_selected_id, $nav_menus;
		$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

		?>
		<div class="menudiv" id="menudiv">
			<input type="hidden" value="menu" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" />
			<p id="menu-item-menu-wrap">
				<label class="howto" for="menu-menu-item-menu">
					<span><?php _e('Title'); ?></span>
					<select name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu]" id="menu-menu-item-menu">
						<option value="0" selected="selected"><?php _e('-- Select --'); ?></option>
						<?php foreach((array) $nav_menus as $_nav_menu) : ?>
						<?php if($_nav_menu->term_id == $nav_menu_selected_id) continue; ?>
						<option value="<?php echo esc_attr($_nav_menu->term_id); ?>">
							<?php echo esc_html($_nav_menu->truncated_name); ?>
						</option>
						<?php endforeach; ?>
					</select>
				</label>
			</p>

			<p id="menu-item-title-wrap">
				<label class="howto" for="menu-menu-item-title">
					<span><?php _e('Title'); ?></span>
					<input id="menu-menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" type="text" class="regular-text menu-item-textbox input-with-default-title" title="<?php esc_attr_e('(optional)'); ?>" />
				</label>
			</p>

			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php disabled($nav_menu_selected_id, 0); ?> class="button-secondary submit-add-menu-to-menu right" value="<?php esc_attr_e('Add to Menu'); ?>" name="add-menu-menu-item" id="submit-menudiv" />
					<span class="spinner"></span>
				</span>
			</p>

		</div><!-- /.menudiv -->
		<?php
	}

	/**
	 * TODO: documentation
	 */
	public function get_post_descendants() {
		check_ajax_referer('add-menu_item', 'menu-settings-column-nonce');

		if (!current_user_can('edit_theme_options'))
			wp_die(-1);

		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

		$descendants = Mega_Menu::flatten_menu_structure(Mega_Menu::load_hierarchy($_GET['post_id']), false);

		$object_to_menu_map = array();
		$menu_items = array();

		array_walk($descendants, function($descendant) use (&$object_to_menu_map, &$menu_items) {
			$menu_item = array(
				'menu-item-object' => $descendant->post_type,
				'menu-item-object-id' => $descendant->ID,
				'menu-item-parent-id' => $_GET['post_id'] == $descendant->post_parent ? $_GET['db_id'] : $object_to_menu_map[$descendant->post_parent],
				'menu-item-type' => 'post_type',
				'menu-item-title' => $descendant->post_title,
				'menu-item-url' => get_permalink($descendant->ID),
			);

			$item_ids = wp_save_nav_menu_items($_GET['menu'], array($menu_item));
			if (is_wp_error($item_ids))
				wp_die(0);

			$object_to_menu_map[$descendant->ID] = $item_ids[0];

			$menu_obj = get_post($item_ids[0]);
			if (!empty($menu_obj->ID)) {
				$menu_obj = wp_setup_nav_menu_item($menu_obj);
				$menu_obj->label = $menu_obj->title; // don't show "(pending)" in ajax-added items
				$menu_items[] = $menu_obj;
			}
		});

		$walker_class_name = apply_filters('wp_edit_nav_menu_walker', 'Walker_Nav_Menu_Edit', $_GET['menu']);

		if (!class_exists($walker_class_name))
			wp_die(0);

		if (!empty($menu_items)) {
			$args = array(
				'after' => '',
				'before' => '',
				'link_after' => '',
				'link_before' => '',
				'walker' => new $walker_class_name,
			);

			$output = walk_nav_menu_tree($menu_items, 0, (object) $args);

			$output = preg_replace_callback('/(menu-item-depth-)([0-9]+)/', function($matches) {
				return $matches[1] . ($matches[2] + $_GET['depth'] + 1);
			}, $output);

			echo $output;
			wp_die();
		}
	}

	/**
	 * TODO: documentation
	 */
	public function duplicate_item() {
		check_ajax_referer('add-menu_item', 'menu-settings-column-nonce');

		if (!current_user_can('edit_theme_options'))
			wp_die(-1);

		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

		$item = wp_setup_nav_menu_item(clone get_post($_GET['db_id']));

		$menu_items = array();

		$menu_item = array(
			'menu-item-object' => $item->object,
			'menu-item-object-id' => $item->object_id,
			'menu-item-parent-id' => isset($_GET['parent_id']) ? $_GET['parent_id'] : $item->menu_item_parent,
			'menu-item-type' => $item->type,
			'menu-item-title' => $item->title,
			'menu-item-url' => $item->url,
		);

		$item_ids = wp_save_nav_menu_items($_GET['menu'], array($menu_item));
		if (is_wp_error($item_ids))
			wp_die(0);

		$menu_obj = get_post($item_ids[0]);
		if (!empty($menu_obj->ID)) {
			$menu_obj = wp_setup_nav_menu_item($menu_obj);
			$menu_obj->label = $menu_obj->title; // don't show "(pending)" in ajax-added items
			$menu_items[] = $menu_obj;
		}

		$walker_class_name = apply_filters('wp_edit_nav_menu_walker', 'Walker_Nav_Menu_Edit', $_GET['menu']);

		if (!class_exists($walker_class_name))
			wp_die(0);

		if (!empty($menu_items)) {
			$args = array(
				'after' => '',
				'before' => '',
				'link_after' => '',
				'link_before' => '',
				'walker' => new $walker_class_name,
			);

			$output = walk_nav_menu_tree($menu_items, 0, (object) $args);

			$output = preg_replace_callback('/(menu-item-depth-)([0-9]+)/', function($matches) {
				return $matches[1] . ($matches[2] + 1);
			}, $output);

			echo $output;
			wp_die();
		}
	}

	/**
	 * save the nav menu structure to our special table. this...sucks.
	 * @param int the id of the menu that we're updating
	 * @param mixed this is stupid.
	 */
	public function update_nav_menu($menu_id, $dont_do_anything_if_this_param_is_not_null = null) {
		// suck it, WordPress!
		if($dont_do_anything_if_this_param_is_not_null == null) {
			global $wpdb;
			$table_name = $wpdb->prefix . Mega_Menu::$table_name;

			// clear out the old entries
			$wpdb->delete($table_name, array('menu_id' => $menu_id), '%d');

			// insert the new entries
			foreach($this->menu_items as $menu_item) {
				$wpdb->insert($table_name, $menu_item);
			}
		}
	}

	/**
	 * TODO: documentation
	 */
	public function update_nav_menu_item($menu_id, $menu_item_db_id, $menu_item) {
		if($menu_item['menu-item-status'] != 'draft') {
			$item = array();
			$item['ID'] = $menu_item['menu-item-db-id'];
			$item['menu_id'] = $menu_id;
			if($menu_item['menu-item-object'] == 'page' || $menu_item['menu-item-object'] == 'post'){
				$item['post_id'] = (int) $menu_item['menu-item-object-id'];
			}
			$item['parent_id'] = (int) $menu_item['menu-item-parent-id'];
			$item['position'] = (int) $menu_item['menu-item-position'];

			$menu_item['menu-item-title'] = stripcslashes($menu_item['menu-item-title']);
			$data = json_decode($menu_item['menu-item-title']);
			if(is_object($data)) {
				foreach($data as $key => $value) {
					$menu_item['menu-item-' . $key] = $value;
				}
			}

			$item['data'] = json_encode($menu_item);
			$this->menu_items[] = $item;
		}
	}
}

// create the Mega_Menu_Admin object!
new Mega_Menu_Admin;
