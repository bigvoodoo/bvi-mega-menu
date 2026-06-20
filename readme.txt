=== BVI Mega Menu ===
Contributors: geekmenina
Tags: menu, mega menu, navigation, block editor, fse
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: trunk
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Enhanced WordPress navigation menu with mega menu dropdowns, related links, block editor support, and a customizable admin menu interface.

== Description ==

BVI Mega Menu enhances the WordPress navigation system with two rendering paths: a modern block editor path for FSE and Gutenberg themes, and a classic shortcode path for traditional themes.

= Block Editor Blocks =

* **Mega Menu** (`bvi/mega-menu`) — Full mega-menu with hover/click triggers, dropdown panel alignment, mobile modes (dropdown, popup), configurable breakpoint, hamburger icon styles, slide animations, and grouped colour controls per section.
* **Menu Item** (`bvi/menu-item`) — Individual menu item for composing menus inside the block editor, supporting nested sub-items and Mega Panel dropdowns.
* **Related Links** (`bvi/related-links`) — Contextual link list sourced from a classic menu or navigation block. Auto-marks the current page.
* **Responsive Content** (`bvi/responsive-content`) — Visibility wrapper with per-breakpoint show/hide toggles (mobile, tablet, desktop).

= Admin Menu Interface Enhancements =

* Add shortcodes and custom HTML to any menu item
* Add columns/sections for logical division of menu items with optional headers and links
* Embed an existing menu within another for reusable structures
* Add page descendants as submenu items in one click
* Per-page related links metabox for custom link overrides

= Admin Settings =

Under BVI Mega Menu > Settings:

* **Include Default CSS** — Load the plugin's default stylesheet on the frontend
* **Mobile Menu Override** — Render an alternate classic menu or navigation block on mobile
* **Instant Dropdown** — Open/close dropdowns without animation; when off, panels use a slide animation
* **Default Related Links Menu** — Fallback menu for the Related Links block

= Requirements =

* WordPress 6.8+
* PHP 8.2+

== Installation ==

1. Install and activate the plugin in WordPress.
2. [Register](https://developer.wordpress.org/reference/functions/register_nav_menu/) a menu location in your theme.
3. Set up the menu hierarchy under Appearance > Menus.
4. Assign the menu to a registered location.
5. Use blocks (FSE/Gutenberg) or shortcodes (classic themes) to display menus.

= Block Editor =

Add the **Mega Menu** or **Related Links** block in the block editor. For FSE themes, place the Mega Menu block inside a Header template part.

= Shortcodes =

**[mega_menu]**

Renders a mega menu for a classic WordPress menu.

Options:

* `menu`: Classic menu slug or numeric ID. **Required.**
* `mobile_toggle`: Label for the mobile toggle button (e.g. `"Menu"`).
* `aria_button`: Set to `"true"` to add accessible toggle buttons to top-level items.
* `before` / `after`: HTML before/after each link's `<a>` tag.
* `link_before` / `link_after`: HTML inside the `<a>` tag, before/after the link text.

Example: `[mega_menu menu="main-menu" mobile_toggle="Menu" aria_button="true"]`

**[related_links]**

Renders a related links list from a classic menu or navigation block.

Options:

* `menu`: Classic menu slug, numeric ID, or `wp_navigation:{id}`. **Required.**
* `before` / `after`: HTML before/after each link's `<a>` tag.
* `link_before` / `link_after`: HTML inside the `<a>` tag, before/after the link text.

Example: `[related_links menu="footer-links"]`

= Filters =

**bvi_nav_hamburger_open_icon**

Inject custom hamburger icon markup when the block's hamburger style is set to "Custom".

`add_filter( 'bvi_nav_hamburger_open_icon', function() {
    return '<svg>...</svg>';
} );`

**walker_nav_menu_start_el** / **walker_nav_menu_end_el**

Standard WordPress Walker filters, called for each menu item in the classic shortcode rendering path.

= Styling =

Enable the default CSS under Settings > BVI Mega Menu, or target the plugin's CSS classes directly in your own stylesheet.

Key classes for the block menu:

* `.bvi-mega-menu` — Block wrapper `<nav>`
* `.bvi-menu-item` — Each menu item `<li>`
* `.bvi-menu-item.has-panel` — Items with a dropdown panel
* `.bvi-menu-item.is-open` — Item whose panel is currently visible
* `.bvi-mega-panel` — Dropdown panel container
* `.bvi-mega-menu-hamburger` — Mobile hamburger button
* `.bvi-mega-menu.is-mobile` — Mobile breakpoint is active
* `.bvi-mega-menu.is-instant-dropdown` — Instant dropdown mode is on
* `.bvi-related-links` — Related Links block wrapper
* `.bvi-hide-mobile` / `.bvi-hide-tablet` / `.bvi-hide-desktop` — Responsive Content visibility classes

All colours are stored as CSS custom properties on `.bvi-mega-menu` (e.g. `--bvi-mm-dropdown-bg`, `--bvi-mm-hamburger-bg-open`) and can be overridden in a child theme stylesheet.

== Changelog ==

= 5.0.0 =

* Tests against WordPress 7.0
* Full plugin rewrite with modern PHP architecture (PSR-4, namespaces, traits)
* Added block editor support with four custom blocks: Mega Menu, Menu Item, Related Links, Responsive Content
* Block and admin feature auto-discovery via `Utils\Discovery`
* Replaced wp-scripts build with Gulp + esbuild pipeline
* Block frontend JS moved to vanilla JS `view.jsx` (no jQuery dependency for blocks)
* Added per-page related links metabox
* Added support for wp_navigation block menus in settings
* CI/CD with PHPUnit, PHPCS, ESLint, and Stylelint across PHP 8.2–8.4
* Updated requirements to WordPress 6.8+ and PHP 8.2+
* Block menu: dropdown panels slide open/closed via CSS clip-path animation when instant dropdown is off
* AJAX menu: removed redundant fadeOut chained after slideUp in the hide handler
* Block editor: split "Navigation Colors" into five grouped colour panels — Link Colors, Dropdown Colors, Submenu Colors, Hamburger Colors, Mobile Colors
* Fixed: hamburgerBackgroundColorOpen was never written to --bvi-mm-hamburger-bg-open in the editor styleVars
* Fixed: bare requestAnimationFrame in view.jsx changed to window.requestAnimationFrame

= 4.2.0 =

* Fixed issue where menus declared by menu ID were not providing the dropdowns while using AJAX

= 4.1.10 =

* Fixed placement of localize_script for instant dropdown

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
* Added logic to apply a unique class to the related links container
* Added logic to use default JS for the mega menu's hover functionality when AJAX is set to false
* Fixed the default CSS to provide styling for the mobile toggle
* Added custom mobile menu override option

= 0.2.0 =

* Complete rewrite from the ground up

= 0.1.0 =

* Initial release
