<?php
class Mega_Menu_Admin_Settings {
	/**
	 * Constructor: adds the shortcodes & includes required files.
	 * @see add_shortcode()
	 */
	public function __construct() {
		if(is_admin()){
			add_action('admin_menu', array(&$this, 'add_plugin_page'));
			add_action('admin_init', array(&$this, 'register_settings'));
		}
	}

	/**
	 * this creates the options page under the title "BVI Mega Menu"
	 */
	public function add_plugin_page() {
		// This page will be under "BVI Settings"
		add_options_page('BVI Mega Menu', 'BVI Mega Menu', 'manage_options', 'bvi_mega_menu_settings_admin', array($this, 'create_admin_page'));
	}

	/**
	 * this is the html/functionality you see on the admin page
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<h2>Big Voodoo Interactive Mega Menu Settings</h2>
			<form method="post" action="options.php">
				<?php
				// pulls all existing values from fields registered under 'bvi_settings_options'
				settings_fields('bvi_settings_options');
				// this is saying, get all the sections that have been assigned to 'bvi_mega_menu_settings_admin'
				do_settings_sections('bvi_mega_menu_settings_admin');
				submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * this function is where all the "magic" happens
	 */
	public function register_settings() {
		// this registers the settings array that will contain all of your option values
		// ie. everything will get saved in array_key[option_name_you_used]
		// the first value is the id of this functionality, so you can access it with settings_fields() function later
		register_setting('bvi_mega_menu_options', 'array_key', array($this, 'validateThis'));

		// sets up sections
		// 1 => section id, 2 => section H1, 3 => the function callback to show the section's description text, 4 => name of assigned settings sections
		// ie. 4 is what gets called by do_settings_sections - so if you assign them all to the same one, like bvi_mega_menu_settings_admin
		// then all of these sections will appear when you call do_settings_sections('bvi_mega_menu_settings_admin')
		// guess it gives you the options to separate the sections if you were doing some crazy front-end display
		$sections = array(
			'default' => array('bvi_mega_menu_section_default', 'Default Styling', array($this, 'bvi_mega_menu_section_text_default'), 'bvi_mega_menu_settings_admin'),
			'override' => array('bvi_mega_menu_section_override', 'Override Mobile Menu', array($this, 'bvi_mega_menu_section_text_override'), 'bvi_mega_menu_settings_admin'),
			'instant_dropdown' => array('bvi_mega_menu_section_instant_dropdown', 'Instant Mega Menu Dropdown', array($this, 'bvi_mega_menu_section_text_instant_dropdown'), 'bvi_mega_menu_settings_admin')
		);

		// sets up fields
		// 1 => field id (NOT the id="" of the input field), 2 => field label, 3 => what input function to use
		// ie. &this means to use the 6th part of the array to assign the names to a generic input function
		// this lets you have a single function for each different kind of input, like input type="text"
		// and then define unique name, ids, values for the field without repeating the function
		// 4 => like the settings section, this will tell it to appear in that same sections call do_settings_section (so redundant) because ...
		// 5 => what section it should appear under that you define in sections (thats why 4 is redundant)
		// 6 => this allows you to set a unique name for the id, name, and value for the input field (in conjunction with the 3th array value)
		$fields = array(
			'default_css' => array('bvi_mega_menu_css_input', 'Enable Default CSS?', array(&$this, 'bvi_mega_menu_input_checkbox'), 'bvi_mega_menu_settings_admin', 'bvi_mega_menu_section_default', array('field' => 'bvi_mega_menu_css_val')),
			'override_mobile' => array('bvi_mega_menu_override_select', 'Override Mobile Menu with Custom Menu', array(&$this, 'bvi_mega_menu_select'), 'bvi_mega_menu_settings_admin', 'bvi_mega_menu_section_override', array('field' => 'bvi_mega_menu_override_val')),
			'instant_dropdown_field' => array('bvi_mega_menu_instant_dropdown', 'Enable Instant Dropdown?', array(&$this, 'bvi_mega_menu_input_checkbox'), 'bvi_mega_menu_settings_admin', 'bvi_mega_menu_section_instant_dropdown', array('field' => 'bvi_mega_menu_dropdown_val'))
		);

		// yeah, we're not calling add_settings_section() 5 times over and over -
		// parses through the sections array you just made, yay!
		foreach($sections as $section) {
			add_settings_section($section[0], $section[1], $section[2], $section[3]);
		}

		// same thing - not going to call this over and over
		// parses through the fields array you just made, yay!
		foreach($fields as $field) {
			add_settings_field($field[0], $field[1], $field[2], $field[3], $field[4], $field[5]);
		}
	}

	/**
	 * this is a redundant function, but I guess you should check that the field is valid...
	 * regardless, this is the validation function that HAS to be called, or WordPress will cry
	 */
	public function validateThis($input) {
		$valid = array();

		// checkboxes not checked will just not return anything,
		// so to make sure the value gets updated,
		// this will add the zero value for these options
		// when they are submitted blank
		if(!array_key_exists('bvi_mega_menu_css_val', $input)) {
			$input['bvi_mega_menu_css_val'] = '0';
		}
		if(!array_key_exists('bvi_mega_menu_dropdown_val', $input)) {
			$input['bvi_mega_menu_dropdown_val'] = '0';
		}

		if(!empty($input)) {
			// checks each input that has been added
			foreach($input as $key => $value) {
				// does a basic check to make sure that the database value is there
				if(get_option($key === FALSE)) {
					// adds the field if its not there
					add_option($key, $value);
				} else {
					// updates the field if its already there
					update_option($key, $value);
				}

				// you have to return the value or WordPress will cry
				$valid[$key] = $value;
			}
		}

		// return it and prevent WordPress depression
		return $valid;
	}

	/**
	 * the actual input field for type checkbox
	 */
	public function bvi_mega_menu_input_checkbox($data) {
		?><input type="checkbox" id="<?php echo $data['field']; ?>" name="array_key[<?php echo $data['field']; ?>]" value="1" <?php checked(true, get_option($data['field'])); ?> /><?php
	}

	/**
	 * the actual input field for type select
	 */
	public function bvi_mega_menu_select($data) {
		// retrieve all user-created wordpress menus
		$wordpress_menus = get_terms('nav_menu', array('hide_empty' => true));

		?><select id="<?php echo $data['field']; ?>" name="array_key[<?php echo $data['field']; ?>]">
			<option value="">Do Not Override</option><?php
			// parse through each user-created wordpress menu and make an option field for it
			foreach($wordpress_menus as $wordpress_menu){
				?><option value="<?php echo $wordpress_menu->slug; ?>"><?php echo $wordpress_menu->name; ?></option><?php
			}
		?></select><?php
	}

	/**
	 * text label that appears in default CSS/JS section under the section title
	 * the rest of the functions under this are the exact same thing
	 */
	public function bvi_mega_menu_section_text_default() {
		?><p>When checked, the default CSS file will be included. <strong>If you have existing styles in place for your menu, these will conflict and cause unintended results!</strong></p>
		<p>If you want to use your own styling, grab the CSS guide from <a href="https://github.com/bigvoodoo/mega-menu">the GitHub page</a>, keep this option unchecked, and use the CSS guide in your own stylesheet.</p><?php
	}

	public function bvi_mega_menu_section_text_override() {
		?><p>If you want a WordPress menu to override the mobile menu that gets created when using the mega menu, select the menu here. This will affect every page that the mega menu is called on mobile. Please note, if you select the same menu being used as the mega menu, nothing will be changed.</p><?php
	}

	public function bvi_mega_menu_section_text_instant_dropdown() {
		?><p>Select the Checkbox if you would like the mega menu to dropdown instantly, without a sliding animation. Leave empty if you would like the mega menu dropdown to occur with a sliding animation.</p><?php
	}
}

// create the Mega_Menu_Admin_Settings object!
new Mega_Menu_Admin_Settings();
