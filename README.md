# BVI Mega Menu

Enhanced WordPress navigation menu plugin with mega menu dropdowns, related links, block editor support, and a customizable admin menu interface.

**Author:** [Big Voodoo Interactive](https://www,bigvoodoo.com)
**License:** GPLv3
**Repository:** [github.com/bviigital/bvi-mega-menu](https://github.com/bviigital/bvi-mega-menu)

## Requirements

- WordPress 6.8+
- PHP 8.2+
- Node.js 18+ / npm 9+ (for development)
- Composer (for development)

## Features

### Block Editor (Gutenberg / FSE) Blocks

The plugin registers four blocks:

- **Mega Menu** (`bvi/mega-menu`) — Full mega-menu block with hover/click triggers, mobile modes (dropdown, popup), configurable breakpoint, panel alignment, hamburger icon styles, slide animations, and rich colour controls per section.
- **Related Links** (`bvi/related-links`) — Contextual link list sourced from a classic menu or navigation block. Auto-marks the current page. Falls back to the configured default menu.
- **Responsive Content** (`bvi/responsive-content`) — Visibility wrapper with per-breakpoint show/hide toggles (mobile, tablet, desktop).
- **Menu Item** (`bvi/menu-item`) — Individual menu item for building menus directly inside the block editor, supporting nested sub-items and Mega Panel dropdowns.

#### Mega Menu Block Settings

| Setting | Options | Description |
|---|---|---|
| Menu Source | Classic menu / navigation block / inner blocks | Where menu items are drawn from |
| Dropdown Trigger | Hover / Click | How panels open on desktop |
| Close Delay | ms | Grace period before a hover panel closes |
| Show Dropdown Arrow | On / Off | Toggle chevron on items with panels |
| Span Parent Width | On / Off | Stretch panels to the parent container width |
| Panel Alignment | Left / Center / Right | Desktop panel position relative to the trigger item |
| Mobile Mode | None / Dropdown / Popup | Collapse behaviour below the breakpoint |
| Mobile Breakpoint | px | Width at which mobile mode activates |
| Mobile Levels | Number | Nesting levels expandable on mobile |
| Mobile Dropdown Alignment | Viewport / Left / Right | Alignment of the mobile nav panel |
| Hamburger Style | Bars / SVG / Custom | Icon type for the mobile toggle button |

#### Colour Panels (block sidebar — Styles tab)

Colours are organised into five grouped panels:

- **Link Colors** — top-level link hover text and background
- **Dropdown Colors** — panel background and text (default + hover)
- **Submenu Colors** — nested submenu background and text (default + hover)
- **Hamburger Colors** *(mobile only)* — icon colour, background, hover, and open states
- **Mobile Colors** *(mobile only)* — overlay, menu background, link background, link text (default + hover), item border

All values are stored as CSS custom properties on the block wrapper (see [CSS Custom Properties](#css-custom-properties)).

### Admin Menu Interface

- Add shortcodes and custom HTML to any menu item
- Add columns/sections for logical division of menu items with optional headers and links
- Embed an existing menu within another menu for reusable structures
- Add page descendants as submenu items in one click
- Per-page related links metabox for custom link overrides on any post or page

### Admin Settings

**BVI Mega Menu > Settings**

| Setting | Description |
|---|---|
| Include Default CSS | Load the plugin's default stylesheet on the frontend |
| Mobile Menu Override | Render an alternate classic menu or navigation block on mobile |
| Instant Dropdown | Open/close dropdowns without animation; when off, panels use a slide animation |
| Default Related Links Menu | Fallback menu for the Related Links block |

## Installation

1. Install and activate the plugin in WordPress.
2. [Register](https://developer.wordpress.org/reference/functions/register_nav_menu/) a menu location in your theme.
3. Set up the menu hierarchy under Appearance > Menus.
4. Assign the menu to a registered location.
5. Use blocks (FSE/Gutenberg) or shortcodes (classic themes) to display menus.

### Block Editor (FSE / Gutenberg)

Add the **Mega Menu** or **Related Links** block in the block editor. For FSE themes, add the Mega Menu block inside a Header template part.

### Classic Theme Shortcodes

**`[mega_menu]`** — Renders a mega menu for a classic WordPress menu.

| Attribute | Description |
|---|---|
| `menu` | Classic menu slug or numeric ID. **Required.** |
| `mobile_toggle` | Label text for the mobile toggle button (e.g. `"Menu"`). |
| `aria_button` | Set to `"true"` to add accessible toggle buttons to top-level items. |
| `before` / `after` | HTML before/after each link's `<a>` tag. |
| `link_before` / `link_after` | HTML inside the `<a>` tag, before/after the link text. |

Example:

```
[mega_menu menu="main-menu" mobile_toggle="Menu" aria_button="true"]
```

**`[related_links]`** — Renders a related links list from a classic menu or navigation block.

| Attribute | Description |
|---|---|
| `menu` | Classic menu slug, numeric ID, or `wp_navigation:{id}`. **Required.** |
| `before` / `after` | HTML before/after each link's `<a>` tag. |
| `link_before` / `link_after` | HTML inside the `<a>` tag, before/after the link text. |

Example:

```
[related_links menu="footer-links"]
```

## Development

### Setup

```bash
composer install
npm install
```

### Build

```bash
# Full production build (styles, scripts, blocks)
npm run build

# Build blocks only
npm run block:build
```

### Watch (development)

```bash
npm start
```

### Linting

```bash
# All linters
npm run lint

# Individual
npm run lint:js
npm run lint:css
composer lint
```

### Testing

```bash
composer test
```

Tests run across PHP 8.2–8.4 in CI.

### Formatting

```bash
npm run format          # JS + PHP
npm run format:js       # JS only
npm run format:php      # PHP only
```

## Architecture

### Block Discovery

Blocks are auto-discovered using `Utils\Discovery`. To add a new block:

1. Create a PHP class in `src/Blocks/` that extends `AbstractBlock` and implements the `register()` method.
2. Create a source directory in `assets/src/blocks/<slug>/` with `block.json`, `index.jsx`, and optionally `view.jsx` and `style.scss`.
3. Run `npm run block:build` to compile the block assets.

The `Blocks` orchestrator scans `src/Blocks/` at runtime, instantiates any concrete class implementing the `Interfaces\Block` interface, and calls `register()` on each.

### Admin Feature Discovery

Admin features use the same Discovery pattern. Classes in `src/Admin/Features/` implementing `Interfaces\Feature` are auto-discovered and registered.

### Build System

The plugin uses **Gulp** with **esbuild** for block compilation:

- Block JSX is compiled with WordPress externals mapped to browser globals (`@wordpress/*` → `window.wp.*`)
- `.asset.php` dependency manifests are auto-generated per block
- Block SCSS is compiled to `style-index.css` per block
- `block.json` is copied to the dist output for each block
- General scripts are bundled and minified via esbuild
- SCSS stylesheets are compiled with Dart Sass, autoprefixed, and minified

### Project Structure

```
bvi-mega-menu/
├── bvi-mega-menu.php          # Plugin entry point
├── src/
│   ├── Main.php                # Plugin bootstrap
│   ├── Admin.php               # Admin orchestrator (features, menu editor, metaboxes)
│   ├── Frontend.php            # Frontend (styles, shortcodes, AJAX handler)
│   ├── Blocks.php              # Block discovery and registration
│   ├── Blocks/
│   │   ├── AbstractBlock.php   # Base block class
│   │   ├── MegaMenu.php        # Mega Menu block
│   │   ├── RelatedLinks.php    # Related Links block
│   │   └── ResponsiveContent.php
│   ├── Interfaces/
│   │   ├── Block.php           # Block interface (get_name, get_path, register)
│   │   └── Feature.php         # Feature interface
│   ├── Service/
│   │   ├── MenuLoader.php      # Database menu loading, resolution, active marking
│   │   ├── Renderer.php        # HTML rendering via Walker
│   │   ├── Walker.php          # Custom Walker_Nav_Menu for mega menus
│   │   ├── Ajax.php            # AJAX rewrite rule and request handler
│   │   └── Shortcode/
│   │       ├── MegaMenuShortcode.php      # [mega_menu] shortcode
│   │       └── RelatedLinksShortcode.php  # [related_links] shortcode
│   ├── Admin/
│   │   ├── Features/
│   │   │   └── General.php     # General settings feature
│   │   ├── MenuEditor.php      # Nav menu editor customizations
│   │   ├── MetaBox.php         # Metabox orchestrator
│   │   ├── MetaBox/            # Metabox renderer and sanitizer
│   │   ├── Settings.php        # Settings page orchestrator
│   │   └── Settings/           # Settings registrar, renderer, sanitizer, menu
│   ├── Database/
│   │   └── Schema.php          # Custom table management
│   └── Utils/
│       ├── Discovery.php       # Dynamic class discovery
│       └── Traits/             # Singleton, Feature, Security, Strings
├── assets/
│   ├── src/
│   │   ├── blocks/             # Block source (JSX, block.json, view.jsx)
│   │   ├── sass/               # SCSS stylesheets
│   │   └── scripts/            # Admin and frontend JS
│   └── dist/                   # Compiled output (gitignored)
├── templates/                  # Admin PHP templates (settings, metaboxes, fields)
├── tests/                      # PHPUnit integrity tests
├── gulpfile.mjs                # Build system
├── composer.json               # PHP dependencies (PHPUnit, PHPCS)
└── package.json                # Node dependencies (Gulp, esbuild, WP scripts)
```

### Database

The plugin creates a `{prefix}_mega_menu` table on activation to cache menu structure data for frontend performance. The table is removed on uninstall.

## Styling

Enable the default CSS in Settings > BVI Mega Menu, or target the following CSS classes in your own stylesheet.

### Block Menu Classes

| Class | Element |
|---|---|
| `.bvi-mega-menu` | Block wrapper `<nav>` |
| `.bvi-mega-menu-hamburger` | Mobile hamburger button |
| `.bvi-mega-menu-nav` | Nav element containing the menu list |
| `.bvi-mega-menu-list` | Top-level `<ul>` |
| `.bvi-menu-item` | Each menu item `<li>` |
| `.bvi-menu-item.has-panel` | Items with a dropdown panel |
| `.bvi-menu-item-link` | The `<a>` or `<button>` inside a menu item |
| `.bvi-mega-panel` | Dropdown panel container |
| `.bvi-related-links` | Related Links block wrapper |
| `.bvi-related-links .is-current` | Current-page link in a Related Links list |
| `.bvi-responsive-content` | Responsive Content block wrapper |
| `.bvi-hide-mobile` / `.bvi-hide-tablet` / `.bvi-hide-desktop` | Visibility classes |

### State Classes

| Class | Applied to | Meaning |
|---|---|---|
| `is-open` | `.bvi-mega-menu` | Mobile menu is open |
| `is-open` | `.bvi-menu-item` | Item's dropdown panel is open |
| `is-mobile` | `.bvi-mega-menu` | Mobile breakpoint is active |
| `is-instant-dropdown` | `.bvi-mega-menu` | Instant dropdown mode is on (no animation) |

### CSS Custom Properties

All visual configuration is stored as CSS custom properties on `.bvi-mega-menu`. Override them in a child theme stylesheet:

```css
.bvi-mega-menu {
    --bvi-mm-dropdown-bg: #1a1a2e;
    --bvi-mm-dropdown-color: #ffffff;
    --bvi-mm-item-gap: 2rem;
}
```

| Property | Description |
|---|---|
| `--bvi-mm-breakpoint` | Mobile breakpoint |
| `--bvi-mm-item-gap` | Gap between top-level items |
| `--bvi-mm-dropdown-item-gap` | Gap between dropdown items |
| `--bvi-mm-dropdown-bg` / `--bvi-mm-dropdown-bg-hover` | Dropdown background |
| `--bvi-mm-dropdown-color` / `--bvi-mm-dropdown-color-hover` | Dropdown text |
| `--bvi-mm-submenu-bg` / `--bvi-mm-submenu-bg-hover` | Submenu background |
| `--bvi-mm-submenu-color` / `--bvi-mm-submenu-color-hover` | Submenu text |
| `--bvi-mm-overlay-bg` | Popup mode overlay |
| `--bvi-mm-hamburger-color` / `--bvi-mm-hamburger-color-hover` | Hamburger icon |
| `--bvi-mm-hamburger-bg` / `--bvi-mm-hamburger-bg-hover` / `--bvi-mm-hamburger-bg-open` | Hamburger background |
| `--bvi-mm-mobile-bg` | Mobile menu background |
| `--bvi-mm-mobile-color` / `--bvi-mm-mobile-color-hover` | Mobile menu text |
| `--bvi-mm-mobile-nav-padding` | Mobile nav panel padding |
| `--bvi-mm-link-color-hover` / `--bvi-mm-link-bg-hover` | Top-level link hover |
| `--bvi-mm-panel-width` | Per-item panel width |
| `--bvi-mm-panel-max-width` | Mega panel max-width |

### PHP Filter

```php
// Inject a custom hamburger icon (requires hamburgerStyle = "custom" on the block)
add_filter( 'bvi_nav_hamburger_open_icon', function() {
    return '<svg>...</svg>';
} );
```

## Changelog

### 5.0.0

- Complete plugin rewrite with modern PHP architecture (PSR-4, namespaces, traits)
- Added Gutenberg block editor support with four custom blocks: Mega Menu, Related Links, Responsive Content, Menu Item
- Block auto-discovery via `Utils\Discovery` and `Interfaces\Block`
- Admin feature auto-discovery via `Interfaces\Feature`
- Replaced wp-scripts block build with Gulp + esbuild pipeline
- Block frontend JS moved to vanilla JS `view.jsx` (no jQuery dependency for blocks)
- Added per-page related links metabox
- Added support for wp_navigation block menus in settings
- CI/CD with PHPUnit, PHPCS, ESLint, and Stylelint across PHP 8.2–8.4
- Updated requirements to WordPress 6.8+ and PHP 8.2+
- Block menu: dropdown panels slide open/closed via CSS `clip-path` animation when instant dropdown is off; instant mode retains `display` toggling
- AJAX menu: removed redundant `.fadeOut()` chained after `.slideUp()` in the hide handler
- Block editor: split "Navigation Colors" into five grouped colour panels — Link Colors, Dropdown Colors, Submenu Colors, Hamburger Colors, Mobile Colors
- Fixed: `hamburgerBackgroundColorOpen` was never written to `--bvi-mm-hamburger-bg-open` in the editor `styleVars`
- Fixed: bare `requestAnimationFrame` in `view.jsx` changed to `window.requestAnimationFrame`

### 4.2.0

- Fixed issue where menus declared by menu ID were not providing the dropdowns while using AJAX

### 4.1.10

- Fixed placement of localize_script for instant dropdown

### 4.1.9

- Added option in admin settings for instant dropdown without sliding animation

### 4.0.0

- Added accessibility functionality (aria labels, flyouts)
- Added active class to children classes for CSS targeting
- Changed mobile-toggle from link to button per Lighthouse recommendations

### 3.0.0

- Overhaul for WordPress 4.5.3 compatibility
- Added unique class to related links container
- Added default JS for non-AJAX hover functionality
- Added custom mobile menu override option
- Fixed mobile toggle styling and multiple active parent issues

### 0.2.0

- Complete rewrite from the ground up

### 0.1.0

- Initial release
