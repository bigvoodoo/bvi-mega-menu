=== BVI Mega Menu ===
Contributors: bigvoodoo, firejdl, geekmenina
Tags: menu, mega menu, admin, shortcode
Requires at least: 5.0.0
Tested up to: 5.8
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enhancements to the wp-admin Menu interface that allow for faster, more robust, and easier to edit menus. Also includes related links based on the menu configuration.

== Description ==

This is a plugin for WordPress 3.5+ that enhances the Menu experience in several ways:

* adds enhancements to the wp-admin Menu interface (see below)
* saves menus to its own table to speed up generation of menus on the front-end
* adds two shortcodes to displays menus from the admin interface - `[mega_menu]` & `[related_links]` (see the Installation section)

License: [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)

= Menu Interface Enhancements =

* Adds the ability to add a Shortcode and/or custom HTML to any menu! Now you can display forms, widgets, anything you want inside of a menu.
* Adds the ability to add a "Column/Section" to any menu, which allows for logical division of menu items and both simpler and stronger styling.
  * Columns/Sections can have an optional header with an optional link.
* Adds the ability to add an existing menu to a menu. Menu items that repeat in several different places can be created as a menu and added multiple times.
  * Menus can have an optional header with an optional link.
* Adds a button to the menu item options to add descendents of page in the WP page hierarchy.

= Requirements =

* WordPress 5+
* PHP 7+

= TODO =

* i18n/L10n
* Add custom links option that you can set per page or post; also the option to override the existing related links or append to it
* Add custom menu option that you can set per page or post
* Add accessibility options to the menu - aria labels, etc.
* Active class needs to be added to the child pages for highlighting
* mobile-toggle link needs to be a button for lighthouse

= Inspirations & Thanks =

* [Gecka Submenu](https://github.com/Gecka-Apps/Wordpress_Gecka_Submenu)
* [Add Descendents as Submenu Items](http://wordpress.org/plugins/add-descendants-as-submenu-items)
* [Custom Post Type's Archive in WP Nav Menu](http://wordpress.org/plugins/add-custom-post-types-archive-to-nav-menus)
* [/wp-admin/includes/nav-menu.php:`wp_nav_menu_item_link_meta_box()`](http://core.trac.wordpress.org/browser/tags/3.3.1/wp-admin/includes/nav-menu.php#L573)
* [Big Voodoo Interactive](http://www.bigvoodoo.com) for letting me write and open-source this plugin :)

== Installation ==

1. Install the plugin in WordPress & activate it.
2. [register](http://codex.wordpress.org/Function_Reference/register_nav_menu) a menu location in your theme.
3. Setup the menu hierarchy under Appearance -> Menu.
4. Assign the menu from step 3 to the menu location in step 2.
5. Use one of the shortcodes to display a menu.

= Shortcodes =

**[mega_menu]**

Given a `theme_location` attribute, this shortcode displays a ul-style Mega Menu for the menu assigned to that location, which can be easily styled with CSS in your theme.
Options:

* `theme_location`: The location in the theme to be used - must be registered with [`register_nav_menu()`](http://codex.wordpress.org/Function_Reference/register_nav_menu) in order to be selectable by the user. **required**
* `before`: Output text before the `<a>` of the link
* `after`: Output text after the `</a>` of the link
* `link_before`: Output text before the link text
* `link_after`: Output text after the link text
* `ajax`: if "true", loads the Mega part of the menu via AJAX.

Example: `[mega_menu theme_location="mega" before="<div class='surround'>" after="</div>" link_before="<span>" link_after="</span>"]`

**[related_links]**

Given a `theme_location` attribute, the shortcode displays a Related Links Menu for the menu assigned to that location, which shows either children, siblings, or top-level pages (chosen in that order).
Options:

* `theme_location`: The location in the theme to be used - must be registered with [`register_nav_menu()`](http://codex.wordpress.org/Function_Reference/register_nav_menu) in order to be selectable by the user. **required**
* `before`: Output text before the `<a>` of the link
* `after`: Output text after the `</a>` of the link
* `link_before`: Output text before the link text
* `link_after`: Output text after the link text

Example: `[related_links theme_location="mega"]`

= Filters =

**walker_nav_menu_start_el**

Allows modification of the `$output`, called when the Walker has created an `<li>` and started populating it.

Arguments:

* `$output`: the output for the menu so far.
* `$item`: the current menu item.
* `$depth`: the current depth.
* `$args`: the arguments passed to `Walker_Nav_Mega_Menu`.

Example:

`function override_nav_menu_start_el($output, $item, $depth, $args) {
	if($args->menu_type == 'mega' && $depth == 0 && $args->ajax !== "true") {
		// add header
		$output .= '<h2>' . get_the_title($item->post_id) . '</h2>';
	}
	return $output;
}
add_filter('walker_nav_menu_start_el', 'override_nav_menu_start_el', 99, 4);`

**walker_nav_menu_end_el**

Allows modification of the `$output`, called before the Walker adds `</li>` and after any children are added to the `$output`.

Arguments:

* `$output`: the output for the menu so far.
* `$item`: the current menu item.
* `$depth`: the current depth.
* `$args`: the arguments passed to `Walker_Nav_Mega_Menu`.

Example:

`function override_nav_menu_end_el($output, $item, $depth, $args) {
	if($args->menu_type == 'mega' && $depth == 0 && $args->ajax !== "true") {
		// add footer
		$output .= '<div class="menu_footer">footer for ' . get_the_title($item->post_id) . '</div>';
	}
	return $output;
}
add_filter('walker_nav_menu_end_el', 'override_nav_menu_end_el', 99, 4);`

= Styling =

You can enable the default CSS in the WordPress Admin under Settings > BVI Mega Menu. If you want to customize this look, you can instead use this as a guide in your own stylesheet and have the default turned off.

`.bvi-mega-menu-container, .bvi-mega-menu-custom-mobile-menu{background:#CCC;display:table;margin:0;padding:0;position:relative;width:100%}
.bvi-mega-menu-custom-mobile-menu{display:none}
.bvi-mega-menu-container ul, .bvi-mega-menu-container ul li{margin:0;padding:0;list-style:none}
.bvi-mega-menu-container > .menu-item-depth-0, .bvi-mega-menu-custom-mobile-menu > li{display:table-cell;text-align:center;vertical-align:middle}
.bvi-mega-menu-container > .menu-item-depth-0 > a, .bvi-mega-menu-custom-mobile-menu > li > a{color:#000;display:block;font-size:14px;font-family:Arial;padding:15px 12px;-webkit-transition:background .2s ease-in;-moz-transition:background .2s ease-in;-o-transition:background .2s ease-in;transition:background .2s ease-in}
.bvi-mega-menu-container > .menu-item-depth-0:hover > a, .bvi-mega-menu-container > .menu-item-depth-0.active > a, .bvi-mega-menu-custom-mobile-menu > li:hover > a{background:#999;color:#1a1a1a;text-decoration:none}
.bvi-mega-menu-container > .menu-item-depth-0 > .mega-menu{background:#999;display:none;position:absolute;top:47px;left:0;right:0;padding:15px 0;text-align:left;width:100%;z-index:999}
.bvi-mega-menu-container .active .menu-item-page:hover > .sub-menu{display:block !important}
.bvi-mega-menu-container .menu-item-column{float:left;padding:0 3% 25px 1%;width:50%}
.bvi-mega-menu-container .menu-item-column .sub-menu li{font-family:Arial;font-size:12px;padding:6px 0}
.bvi-mega-menu-container .menu-item-column .sub-menu li a{display:block;color:#000;padding:0 5px;position:relative}
.bvi-mega-menu-container .menu-item-column .sub-menu li a:hover, .bvi-mega-menu-container .menu-item-column .sub-menu li.active a{background-color:#999;text-decoration:none}
.bvi-mega-menu-container .menu-item-column .sub-menu li a:hover:after, .bvi-mega-menu-container .menu-item-column .sub-menu li.active a:after{display:block}
.bvi-mega-menu-container .menu-item-column .sub-menu .sub-menu li{text-transform:none;padding-left:22px}

@media handheld, screen and (max-width:767px){
	.mobile-toggle{color:#fff;display:block !important;padding:10px 15px;text-align:right}
	.bvi-mega-menu-container, .bvi-mega-menu-custom-mobile-menu{display:none}
	.bvi-mega-menu-container > .menu-item-depth-0, .bvi-mega-menu-custom-mobile-menu > li{border-bottom:2px solid #333;display:block;text-align:left;width:100%}
	.bvi-mega-menu-container > .menu-item-depth-0 > .mega-menu{display:none !important}
}`

== Changelog ==

= 4.1.9 =

* Added option in the admin settings to have the mega menu and mobile menu drop down without a sliding animation

= 4.0.9 =

* Added aria-label="Mega Menu" attribute to mobile-toggle button to resolve Lighthouse issue

= 4.0.8 =

* Bug fix: Adds check for empty post IDs on 404 pages to remove PHP Notices
* Bug fix: Adds check for empty use of aria_button argument to remove PHP Warnings

= 4.0.7 =

* Bug fix: Adds additional check to drop the mega menu down after the initial AJAX call is completed when first hovering over a main menu item

= 4.0.6 =

* Bug fix: Switches async to explicit true to avoid blocks on the ajax calls on Chrome only

= 4.0.5 =

* Bug fix: Adjusts mega menu mobile-toggle visibility check to use explicit false checks instead of .not() functionality

= 4.0.4 =

* Bug fix: Changes check for mega menu dropdown element to use mouseenter() function

= 4.0.3 =

* Adds active class to child page if its the current page as well as the parent
* Adds optional aria-button for top level menu items for accessibility
* Bug Fix: Additional jQuery fixes

= 4.0.2 =

* Bug Fix: AJAX mega menu references to visible updated for latest jQuery

= 4.0.1 =

* Adds GitHub repository linking

= 4.0.0 =

* Adds accessibility functionality to the mega menu in the form of aria labels, flyouts, and more
* Adds active class to children classes for CSS targeting
* Changes mobile-toggle link from a link to a button, per Lighthouse recommendations

= 3.0.0 =

* Overhaul all around to make it compatible with WordPress 4.5.3
** Removed functionality to "clean up" mega menu in the database because it's no longer needed
** Added logic to apply a unique class to the related links container instead of the same class as the mega menu container (helpful for styling)
** Added logic to use default JS for the mega menu's hover functionality when AJAX is set to false or disabled
** Fixed the default CSS to provide styling for the mobile toggle when it's being used
** Added random int appended to the ID of related links and mega menu so it doesn't make HTML validators angry in case you use a mega menu or related links shortcode twice or more on the same page
** Fixed issue where multiple parent menu items were appearing as active when a menu item under multiple parents was active; now it chooses the first parent available
* Added custom links option that you can set per page or post; also the option to override the existing related links or append to it
* Added custom mobile menu option to override mobile menu setup by mega menu
* Added custom menu option that you can set per page or post

= 0.4.1 =

* Always include custom pages in related links menus

	Custom pages all have a post_id of 0, which will break our check for duplicate pages. Therefore, we should include all custom pages.

= 0.4.0 =

* fixed several bugs
* added simple timeout for moving the mouse into/out of the target

= 0.3.0 =

* added options to use some basic default JS & CSS
* added ability to load Mega part of the menu via AJAX
* fixed some bugs

= 0.2.0 =

* complete rewrite from the ground up

= 0.1.0 =

* initial release
