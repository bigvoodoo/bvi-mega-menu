/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import {
  useBlockProps,
  useInnerBlocksProps,
  InnerBlocks,
  InspectorControls,
  PanelColorSettings,
} from '@wordpress/block-editor';
import {
  PanelBody,
  SelectControl,
  RangeControl,
  ToggleControl,
  Button,
  Placeholder,
  Spinner,
  Notice,
  // eslint-disable-next-line @wordpress/no-unsafe-wp-apis
  __experimentalUnitControl as UnitControl,
  // eslint-disable-next-line @wordpress/no-unsafe-wp-apis
  __experimentalToggleGroupControl as ToggleGroupControl,
  // eslint-disable-next-line @wordpress/no-unsafe-wp-apis
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

const { useState, useEffect } = wp.element;
const apiFetch = wp.apiFetch;

/**
 * Internal dependencies
 */
import metadata from './block.json';

const ALLOWED_INNER_BLOCKS = ['bvi/menu-item'];

const TEMPLATE = [
  ['bvi/menu-item', { label: 'Home' }],
  ['bvi/menu-item', { label: 'About' }],
];

// unit sets reused across UnitControl instances.
const UNITS_SPACING = [
  { value: 'px', label: 'px', default: 0 },
  { value: 'rem', label: 'rem', default: 0 },
  { value: 'em', label: 'em', default: 0 },
  { value: '%', label: '%', default: 0 },
  { value: 'vw', label: 'vw', default: 0 },
];

const UNITS_FONT = [
  { value: 'px', label: 'px', default: 16 },
  { value: 'rem', label: 'rem', default: 1 },
  { value: 'em', label: 'em', default: 1 },
  { value: 'vw', label: 'vw', default: 1 },
];

const UNITS_LETTER_SPACING = [
  { value: 'px', label: 'px', default: 0 },
  { value: 'em', label: 'em', default: 0 },
  { value: 'rem', label: 'rem', default: 0 },
];

const UNITS_BORDER = [
  { value: 'px', label: 'px', default: 1 },
  { value: 'em', label: 'em', default: 0 },
  { value: 'rem', label: 'rem', default: 0 },
];

registerBlockType(metadata.name, {
  edit: function Edit({ attributes, setAttributes }) {
    const {
      menuSlug,
      mobileMode,
      mobileBreakpoint,
      dropdownTrigger,
      showDropdownArrow,
      dropdownCloseDelay,
      hamburgerStyle,
      hamburgerSvgId,
      linkTextColorHover,
      linkBackgroundColorHover,
      linkTextColorActive,
      linkBackgroundColorActive,
      dropdownBackgroundColor,
      dropdownBackgroundColorHover,
      dropdownTextColor,
      dropdownTextColorHover,
      dropdownTextColorActive,
      dropdownBackgroundColorActive,
      submenuBackgroundColor,
      submenuBackgroundColorHover,
      submenuTextColor,
      submenuTextColorHover,
      submenuTextColorActive,
      submenuBackgroundColorActive,
      overlayBackgroundColor,
      hamburgerColor,
      hamburgerColorHover,
      hamburgerBackgroundColor,
      hamburgerBackgroundColorHover,
      mobileMenuBackgroundColor,
      mobileMenuLinkBackgroundColor,
      mobileMenuLinkBackgroundColorHover,
      mobileMenuTextColor,
      mobileMenuTextColorHover,
      mobileMenuTextColorActive,
      mobileMenuLinkBackgroundColorActive,
      highlightAncestors,
      itemGap,
      dropdownItemGap,
      popupItemGap,
      dropdownSpanParent,
      dropdownPanelAlignment,
      mobileDropdownAlignment,
      hamburgerBackgroundColorOpen,
      mobileFontSize,
      mobileFontWeight,
      mobileFontStyle,
      mobileTextTransform,
      mobileTextDecoration,
      mobileLetterSpacing,
      mobileTextAlign,
      mobileBorderWidth,
      mobileBorderStyle,
      mobileBorderColor,
      mobileLevels,
      mobileNavPadding,
    } = attributes;

    const hasMobile = mobileMode !== 'none';
    const hasMobilePopup = mobileMode === 'popup';

    // fetch available menus (classic + wp_navigation) for the menu picker.
    const [menus, setMenus] = useState([]);
    const [isLoadingMenus, setIsLoadingMenus] = useState(true);
    useEffect(() => {
      let cancelled = false;
      apiFetch({ path: '/bvi/v1/menus' })
        .then((data) => {
          if (cancelled) {
            return;
          }
          setMenus(Array.isArray(data) ? data : []);
          setIsLoadingMenus(false);
        })
        .catch(() => {
          if (cancelled) {
            return;
          }
          setMenus([]);
          setIsLoadingMenus(false);
        });
      return () => {
        cancelled = true;
      };
    }, []);

    const menuOptions = [
      {
        label: __('— Compose from Inner Blocks —', 'bvi-mega-menu'),
        value: '',
      },
      ...menus.map((menu) => ({
        label: menu.name,
        value: menu.slug,
      })),
    ];

    const isUsingInnerBlocks = !menuSlug;
    const selectedMenu = menuSlug ? menus.find((m) => m.slug === menuSlug) : null;

    const styleVars = {
      '--bvi-mm-breakpoint': `${mobileBreakpoint || 960}px`,
      '--bvi-mm-link-color-hover': linkTextColorHover || undefined,
      '--bvi-mm-link-bg-hover': linkBackgroundColorHover || undefined,
      '--bvi-mm-link-color-active': linkTextColorActive || undefined,
      '--bvi-mm-link-bg-active': linkBackgroundColorActive || undefined,
      '--bvi-mm-dropdown-bg': dropdownBackgroundColor || undefined,
      '--bvi-mm-dropdown-bg-hover': dropdownBackgroundColorHover || undefined,
      '--bvi-mm-dropdown-color': dropdownTextColor || undefined,
      '--bvi-mm-dropdown-color-hover': dropdownTextColorHover || undefined,
      '--bvi-mm-dropdown-color-active': dropdownTextColorActive || undefined,
      '--bvi-mm-dropdown-bg-active': dropdownBackgroundColorActive || undefined,
      '--bvi-mm-submenu-bg': submenuBackgroundColor || undefined,
      '--bvi-mm-submenu-bg-hover': submenuBackgroundColorHover || undefined,
      '--bvi-mm-submenu-color': submenuTextColor || undefined,
      '--bvi-mm-submenu-color-hover': submenuTextColorHover || undefined,
      '--bvi-mm-submenu-color-active': submenuTextColorActive || undefined,
      '--bvi-mm-submenu-bg-active': submenuBackgroundColorActive || undefined,
      '--bvi-mm-overlay-bg': overlayBackgroundColor || undefined,
      '--bvi-mm-hamburger-color': hamburgerColor || undefined,
      '--bvi-mm-hamburger-color-hover': hamburgerColorHover || undefined,
      '--bvi-mm-hamburger-bg': hamburgerBackgroundColor || undefined,
      '--bvi-mm-hamburger-bg-hover': hamburgerBackgroundColorHover || undefined,
      '--bvi-mm-hamburger-bg-open': hamburgerBackgroundColorOpen || undefined,
      '--bvi-mm-mobile-bg': mobileMenuBackgroundColor || undefined,
      '--bvi-mm-mobile-link-bg': mobileMenuLinkBackgroundColor || undefined,
      '--bvi-mm-mobile-link-bg-hover': mobileMenuLinkBackgroundColorHover || undefined,
      '--bvi-mm-mobile-color': mobileMenuTextColor || undefined,
      '--bvi-mm-mobile-color-hover': mobileMenuTextColorHover || undefined,
      '--bvi-mm-mobile-color-active': mobileMenuTextColorActive || undefined,
      '--bvi-mm-mobile-link-bg-active': mobileMenuLinkBackgroundColorActive || undefined,
      '--bvi-mm-item-gap': itemGap || undefined,
      '--bvi-mm-dropdown-item-gap': dropdownItemGap || undefined,
      '--bvi-mm-popup-item-gap': popupItemGap || undefined,
      '--bvi-mm-mobile-border-width': mobileBorderWidth || undefined,
      '--bvi-mm-mobile-border-style': mobileBorderStyle || undefined,
      '--bvi-mm-mobile-border-color': mobileBorderColor || undefined,
      '--bvi-mm-mobile-nav-padding': mobileNavPadding || undefined,
      // relay the core Typography panel's text-decoration so the editor preview
      // matches the front end (the link is an atomic inline-flex box, so the
      // wrapper value cannot reach it by inheritance).
      '--bvi-mm-text-decoration': attributes.style?.typography?.textDecoration || undefined,
    };

    // mirrors MegaMenu::active_state_classes() so the flyout colours preview here too;
    // the active-state gates are omitted because the editor has no current page
    const hasSubmenuColors = [
      submenuBackgroundColor,
      submenuBackgroundColorHover,
      submenuTextColor,
      submenuTextColorHover,
      submenuBackgroundColorActive,
      submenuTextColorActive,
    ].some(Boolean);

    const blockProps = useBlockProps({
      className:
        'bvi-mega-menu is-editor' +
        ` bvi-mega-menu-mobile-${mobileMode}` +
        ` bvi-mm-panel-align-${dropdownPanelAlignment}` +
        ` bvi-mm-mobile-align-${mobileDropdownAlignment}` +
        (hasSubmenuColors ? ' bvi-mm-has-submenu' : ''),
      style: styleVars,
      'data-dropdown-span-parent': dropdownSpanParent ? 'true' : 'false',
      'data-dropdown-panel-alignment': dropdownPanelAlignment,
      'data-mobile-dropdown-alignment': mobileDropdownAlignment,
    });

    const innerBlocksProps = useInnerBlocksProps(
      {
        className: 'bvi-mega-menu-list',
      },
      {
        allowedBlocks: ALLOWED_INNER_BLOCKS,
        template: TEMPLATE,
        orientation: 'horizontal',
      }
    );

    // svg media lookup (only when needed).
    const svgMedia = useSelect(
      (select) => (hamburgerSvgId ? select('core').getMedia(hamburgerSvgId) : null),
      [hamburgerSvgId]
    );

    const openMediaLibrary = () => {
      const frame = wp.media({
        title: __('Select SVG Icon', 'bvi-mega-menu'),
        library: { type: 'image/svg+xml' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        setAttributes({ hamburgerSvgId: attachment.id });
      });
      frame.open();
    };

    const hamburgerStyleHelp = (() => {
      if (hamburgerStyle === 'custom') {
        return __(
          'Let a child theme supply the icon via the' + ' bvi_nav_hamburger_open_icon filter.',
          'bvi-mega-menu'
        );
      }
      if (hamburgerStyle === 'svg') {
        return __(
          'Upload an SVG to use as the icon. Still animates to an X' + ' via CSS rotation on open.',
          'bvi-mega-menu'
        );
      }
      return __('Default three-bar icon; transforms into an X when open.', 'bvi-mega-menu');
    })();

    const colorGroup = (defs) =>
      defs.filter(Boolean).map(({ attr, label }) => ({
        value: attributes[attr],
        onChange: (value) => setAttributes({ [attr]: value }),
        label,
      }));

    return (
      <>
        <InspectorControls group="styles">
          <PanelColorSettings
            title={__('Link Colors', 'bvi-mega-menu')}
            initialOpen={false}
            colorSettings={colorGroup([
              {
                attr: 'linkTextColorHover',
                label: __('Color (Hover)', 'bvi-mega-menu'),
              },
              {
                attr: 'linkBackgroundColorHover',
                label: __('Background (Hover)', 'bvi-mega-menu'),
              },
              {
                attr: 'linkTextColorActive',
                label: __('Color (Active)', 'bvi-mega-menu'),
              },
              {
                attr: 'linkBackgroundColorActive',
                label: __('Background (Active)', 'bvi-mega-menu'),
              },
            ])}
          >
            <ToggleControl
              label={__('Highlight Parent Items', 'bvi-mega-menu')}
              checked={highlightAncestors !== false}
              onChange={(value) => setAttributes({ highlightAncestors: value })}
              help={__(
                'Apply the active colours to a top-level item when one of its' + ' child pages is the current page.',
                'bvi-mega-menu'
              )}
            />
          </PanelColorSettings>
          <PanelColorSettings
            title={__('Dropdown Colors', 'bvi-mega-menu')}
            initialOpen={false}
            colorSettings={colorGroup([
              {
                attr: 'dropdownBackgroundColor',
                label: __('Background', 'bvi-mega-menu'),
              },
              {
                attr: 'dropdownBackgroundColorHover',
                label: __('Background (Hover)', 'bvi-mega-menu'),
              },
              {
                attr: 'dropdownTextColor',
                label: __('Text', 'bvi-mega-menu'),
              },
              {
                attr: 'dropdownTextColorHover',
                label: __('Text (Hover)', 'bvi-mega-menu'),
              },
              {
                attr: 'dropdownBackgroundColorActive',
                label: __('Background (Active)', 'bvi-mega-menu'),
              },
              {
                attr: 'dropdownTextColorActive',
                label: __('Text (Active)', 'bvi-mega-menu'),
              },
            ])}
          />
          <PanelColorSettings
            title={__('Submenu Colors', 'bvi-mega-menu')}
            initialOpen={false}
            colorSettings={colorGroup([
              {
                attr: 'submenuBackgroundColor',
                label: __('Background', 'bvi-mega-menu'),
              },
              {
                attr: 'submenuBackgroundColorHover',
                label: __('Background (Hover)', 'bvi-mega-menu'),
              },
              {
                attr: 'submenuTextColor',
                label: __('Text', 'bvi-mega-menu'),
              },
              {
                attr: 'submenuTextColorHover',
                label: __('Text (Hover)', 'bvi-mega-menu'),
              },
              {
                attr: 'submenuBackgroundColorActive',
                label: __('Background (Active)', 'bvi-mega-menu'),
              },
              {
                attr: 'submenuTextColorActive',
                label: __('Text (Active)', 'bvi-mega-menu'),
              },
            ])}
          />
          {hasMobile && (
            <PanelColorSettings
              title={__('Hamburger Colors', 'bvi-mega-menu')}
              initialOpen={false}
              colorSettings={colorGroup([
                {
                  attr: 'hamburgerColor',
                  label: __('Color', 'bvi-mega-menu'),
                },
                {
                  attr: 'hamburgerColorHover',
                  label: __('Color (Hover)', 'bvi-mega-menu'),
                },
                {
                  attr: 'hamburgerBackgroundColor',
                  label: __('Background', 'bvi-mega-menu'),
                },
                {
                  attr: 'hamburgerBackgroundColorHover',
                  label: __('Background (Hover)', 'bvi-mega-menu'),
                },
                {
                  attr: 'hamburgerBackgroundColorOpen',
                  label: __('Background (Open)', 'bvi-mega-menu'),
                },
              ])}
            />
          )}
          {hasMobile && (
            <PanelColorSettings
              title={__('Mobile Colors', 'bvi-mega-menu')}
              initialOpen={false}
              colorSettings={colorGroup([
                hasMobilePopup && {
                  attr: 'overlayBackgroundColor',
                  label: __('Overlay', 'bvi-mega-menu'),
                },
                {
                  attr: 'mobileMenuBackgroundColor',
                  label: __('Menu Background', 'bvi-mega-menu'),
                },
                {
                  attr: 'mobileMenuLinkBackgroundColor',
                  label: __('Link Background', 'bvi-mega-menu'),
                },
                {
                  attr: 'mobileMenuLinkBackgroundColorHover',
                  label: __('Link Background (Hover)', 'bvi-mega-menu'),
                },
                {
                  attr: 'mobileMenuTextColor',
                  label: __('Link Text', 'bvi-mega-menu'),
                },
                {
                  attr: 'mobileMenuTextColorHover',
                  label: __('Link Text (Hover)', 'bvi-mega-menu'),
                },
                {
                  attr: 'mobileBorderColor',
                  label: __('Item Border', 'bvi-mega-menu'),
                },
                {
                  attr: 'mobileMenuLinkBackgroundColorActive',
                  label: __('Link Background (Active)', 'bvi-mega-menu'),
                },
                {
                  attr: 'mobileMenuTextColorActive',
                  label: __('Link Text (Active)', 'bvi-mega-menu'),
                },
              ])}
            />
          )}
          {hasMobile && (
            <PanelBody title={__('Mobile Styling', 'bvi-mega-menu')} initialOpen={true}>
              <p
                style={{
                  fontSize: '11px',
                  textTransform: 'uppercase',
                  fontWeight: 600,
                  marginBottom: '8px',
                  marginTop: 0,
                }}
              >
                {__('Typography', 'bvi-mega-menu')}
              </p>

              <UnitControl
                label={__('Font Size', 'bvi-mega-menu')}
                value={mobileFontSize}
                units={UNITS_FONT}
                onChange={(value) =>
                  setAttributes({
                    mobileFontSize: value || '',
                  })
                }
              />

              <SelectControl
                label={__('Font Weight', 'bvi-mega-menu')}
                value={mobileFontWeight}
                options={[
                  {
                    label: __('— Inherit —', 'bvi-mega-menu'),
                    value: '',
                  },
                  {
                    label: __('100 — Thin', 'bvi-mega-menu'),
                    value: '100',
                  },
                  {
                    label: __('200 — Extra Light', 'bvi-mega-menu'),
                    value: '200',
                  },
                  {
                    label: __('300 — Light', 'bvi-mega-menu'),
                    value: '300',
                  },
                  {
                    label: __('400 — Normal', 'bvi-mega-menu'),
                    value: '400',
                  },
                  {
                    label: __('500 — Medium', 'bvi-mega-menu'),
                    value: '500',
                  },
                  {
                    label: __('600 — Semi Bold', 'bvi-mega-menu'),
                    value: '600',
                  },
                  {
                    label: __('700 — Bold', 'bvi-mega-menu'),
                    value: '700',
                  },
                  {
                    label: __('800 — Extra Bold', 'bvi-mega-menu'),
                    value: '800',
                  },
                  {
                    label: __('900 — Black', 'bvi-mega-menu'),
                    value: '900',
                  },
                ]}
                onChange={(value) => setAttributes({ mobileFontWeight: value })}
              />

              <ToggleGroupControl
                label={__('Font Style', 'bvi-mega-menu')}
                value={mobileFontStyle}
                onChange={(value) =>
                  setAttributes({
                    mobileFontStyle: value || '',
                  })
                }
                isDeselectable
                isBlock
              >
                <ToggleGroupControlOption value="normal" label={__('Normal', 'bvi-mega-menu')} />
                <ToggleGroupControlOption value="italic" label={__('Italic', 'bvi-mega-menu')} />
              </ToggleGroupControl>

              <ToggleGroupControl
                label={__('Text Transform', 'bvi-mega-menu')}
                value={mobileTextTransform}
                onChange={(value) =>
                  setAttributes({
                    mobileTextTransform: value || '',
                  })
                }
                isDeselectable
                isBlock
              >
                <ToggleGroupControlOption
                  value="uppercase"
                  label="AB"
                  aria-label={__('Uppercase', 'bvi-mega-menu')}
                  showTooltip
                />
                <ToggleGroupControlOption
                  value="capitalize"
                  label="Ab"
                  aria-label={__('Capitalize', 'bvi-mega-menu')}
                  showTooltip
                />
                <ToggleGroupControlOption
                  value="lowercase"
                  label="ab"
                  aria-label={__('Lowercase', 'bvi-mega-menu')}
                  showTooltip
                />
                <ToggleGroupControlOption value="none" label="-" aria-label={__('None', 'bvi-mega-menu')} showTooltip />
              </ToggleGroupControl>

              <ToggleGroupControl
                label={__('Text Decoration', 'bvi-mega-menu')}
                value={mobileTextDecoration}
                onChange={(value) =>
                  setAttributes({
                    mobileTextDecoration: value || '',
                  })
                }
                isDeselectable
                isBlock
              >
                <ToggleGroupControlOption
                  value="underline"
                  label={__('Under', 'bvi-mega-menu')}
                  aria-label={__('Underline', 'bvi-mega-menu')}
                  showTooltip
                />
                <ToggleGroupControlOption
                  value="line-through"
                  label={__('Strike', 'bvi-mega-menu')}
                  aria-label={__('Strikethrough', 'bvi-mega-menu')}
                  showTooltip
                />
                <ToggleGroupControlOption
                  value="overline"
                  label={__('Over', 'bvi-mega-menu')}
                  aria-label={__('Overline', 'bvi-mega-menu')}
                  showTooltip
                />
                <ToggleGroupControlOption value="none" label="-" aria-label={__('None', 'bvi-mega-menu')} showTooltip />
              </ToggleGroupControl>

              <UnitControl
                label={__('Letter Spacing', 'bvi-mega-menu')}
                value={mobileLetterSpacing}
                units={UNITS_LETTER_SPACING}
                onChange={(value) =>
                  setAttributes({
                    mobileLetterSpacing: value || '',
                  })
                }
              />

              <ToggleGroupControl
                label={__('Text Align', 'bvi-mega-menu')}
                value={mobileTextAlign}
                onChange={(value) =>
                  setAttributes({
                    mobileTextAlign: value || '',
                  })
                }
                isDeselectable
                isBlock
              >
                <ToggleGroupControlOption value="left" label={__('Left', 'bvi-mega-menu')} />
                <ToggleGroupControlOption value="center" label={__('Center', 'bvi-mega-menu')} />
                <ToggleGroupControlOption value="right" label={__('Right', 'bvi-mega-menu')} />
              </ToggleGroupControl>

              <p
                style={{
                  fontSize: '11px',
                  textTransform: 'uppercase',
                  fontWeight: 600,
                  marginBottom: '4px',
                  marginTop: '16px',
                }}
              >
                {__('Item Borders', 'bvi-mega-menu')}
              </p>
              <p
                style={{
                  fontSize: '12px',
                  color: '#757575',
                  marginTop: 0,
                  marginBottom: '8px',
                }}
              >
                {__(
                  'Border-bottom between items; suppressed on the last item. Border color is set in Mobile Colors.',
                  'bvi-mega-menu'
                )}
              </p>

              <UnitControl
                label={__('Border Width', 'bvi-mega-menu')}
                value={mobileBorderWidth}
                units={UNITS_BORDER}
                onChange={(value) =>
                  setAttributes({
                    mobileBorderWidth: value || '',
                  })
                }
                help={__('Leave empty for no border.', 'bvi-mega-menu')}
              />

              <ToggleGroupControl
                label={__('Border Style', 'bvi-mega-menu')}
                value={mobileBorderStyle || 'solid'}
                onChange={(value) =>
                  setAttributes({
                    mobileBorderStyle: value || '',
                  })
                }
                isBlock
              >
                <ToggleGroupControlOption value="solid" label={__('Solid', 'bvi-mega-menu')} />
                <ToggleGroupControlOption value="dashed" label={__('Dashed', 'bvi-mega-menu')} />
                <ToggleGroupControlOption value="dotted" label={__('Dotted', 'bvi-mega-menu')} />
                <ToggleGroupControlOption value="double" label={__('Double', 'bvi-mega-menu')} />
              </ToggleGroupControl>
            </PanelBody>
          )}
        </InspectorControls>
        <InspectorControls>
          <PanelBody title={__('Menu Source', 'bvi-mega-menu')} initialOpen={true}>
            {isLoadingMenus ? (
              <Spinner />
            ) : (
              <SelectControl
                label={__('Menu', 'bvi-mega-menu')}
                help={__(
                  'Choose an existing WordPress menu to render with the' +
                    ' classic Mega Menu walker, or leave blank to compose' +
                    ' the menu from inner blocks.',
                  'bvi-mega-menu'
                )}
                value={menuSlug || ''}
                options={menuOptions}
                onChange={(value) => setAttributes({ menuSlug: value })}
              />
            )}
          </PanelBody>

          <PanelBody title={__('Layout', 'bvi-mega-menu')} initialOpen={true}>
            <UnitControl
              label={__('Item Gap', 'bvi-mega-menu')}
              value={itemGap}
              units={UNITS_SPACING}
              onChange={(value) => setAttributes({ itemGap: value || '' })}
              help={__('Space between top-level menu items.', 'bvi-mega-menu')}
            />
            <UnitControl
              label={__('Dropdown Item Gap', 'bvi-mega-menu')}
              value={dropdownItemGap}
              units={UNITS_SPACING}
              onChange={(value) => setAttributes({ dropdownItemGap: value || '' })}
            />
            <UnitControl
              label={__('Popup Item Gap', 'bvi-mega-menu')}
              value={popupItemGap}
              units={UNITS_SPACING}
              onChange={(value) => setAttributes({ popupItemGap: value || '' })}
            />
          </PanelBody>

          <PanelBody title={__('Dropdown Behavior', 'bvi-mega-menu')} initialOpen={true}>
            <ToggleGroupControl
              label={__('Dropdown Trigger', 'bvi-mega-menu')}
              value={dropdownTrigger}
              onChange={(value) => setAttributes({ dropdownTrigger: value })}
              isBlock
              help={__('How desktop dropdowns open. Mobile always uses tap to expand.', 'bvi-mega-menu')}
            >
              <ToggleGroupControlOption value="hover" label={__('Hover', 'bvi-mega-menu')} />
              <ToggleGroupControlOption value="click" label={__('Click', 'bvi-mega-menu')} />
            </ToggleGroupControl>

            <ToggleGroupControl
              label={__('Panel Alignment', 'bvi-mega-menu')}
              value={dropdownPanelAlignment}
              onChange={(value) => setAttributes({ dropdownPanelAlignment: value })}
              isBlock
              help={__('Which edge of the menu item the dropdown panel aligns to.', 'bvi-mega-menu')}
            >
              <ToggleGroupControlOption value="left" label={__('Left', 'bvi-mega-menu')} />
              <ToggleGroupControlOption value="center" label={__('Center', 'bvi-mega-menu')} />
              <ToggleGroupControlOption value="right" label={__('Right', 'bvi-mega-menu')} />
            </ToggleGroupControl>

            <ToggleControl
              label={__('Span Parent Width', 'bvi-mega-menu')}
              checked={!!dropdownSpanParent}
              onChange={(value) => setAttributes({ dropdownSpanParent: value })}
              help={__(
                "Stretch the dropdown to match the width of the block's parent container (e.g. a Group block)." +
                  ' Panel alignment is ignored when this is on.',
                'bvi-mega-menu'
              )}
            />
            <ToggleControl
              label={__('Show Dropdown Arrow', 'bvi-mega-menu')}
              checked={!!showDropdownArrow}
              onChange={(value) => setAttributes({ showDropdownArrow: value })}
              help={__('Show the small arrow indicator on menu items that have a dropdown.', 'bvi-mega-menu')}
            />
            <RangeControl
              label={__('Auto-close Delay (ms)', 'bvi-mega-menu')}
              value={dropdownCloseDelay}
              onChange={(value) => setAttributes({ dropdownCloseDelay: value })}
              min={0}
              max={10000}
              step={1}
              help={__(
                'How long an open dropdown stays visible after the user moves' +
                  ' off it (hover mode) or after it opens (click mode). 0 disables' +
                  ' auto-close.',
                'bvi-mega-menu'
              )}
            />
          </PanelBody>

          <PanelBody title={__('Mobile', 'bvi-mega-menu')} initialOpen={true}>
            <SelectControl
              label={__('Mobile Menu Mode', 'bvi-mega-menu')}
              value={mobileMode}
              options={[
                {
                  label: __('None (desktop only)', 'bvi-mega-menu'),
                  value: 'none',
                },
                {
                  label: __('Dropdown', 'bvi-mega-menu'),
                  value: 'dropdown',
                },
                {
                  label: __('Full-screen Popup', 'bvi-mega-menu'),
                  value: 'popup',
                },
              ]}
              onChange={(value) => setAttributes({ mobileMode: value })}
            />

            {hasMobile && (
              <>
                <RangeControl
                  label={__('Mobile Breakpoint (px)', 'bvi-mega-menu')}
                  value={mobileBreakpoint}
                  onChange={(value) =>
                    setAttributes({
                      mobileBreakpoint: value,
                    })
                  }
                  min={320}
                  max={1440}
                  step={1}
                />
                <RangeControl
                  label={__('Mobile Menu Depth', 'bvi-mega-menu')}
                  value={mobileLevels}
                  onChange={(value) => setAttributes({ mobileLevels: value })}
                  min={0}
                  max={5}
                  step={1}
                  help={__(
                    'Max levels of sub-menus expandable on mobile. 1 = top-level items only; 0 = unlimited.',
                    'bvi-mega-menu'
                  )}
                />

                <UnitControl
                  label={__('Nav Container Padding', 'bvi-mega-menu')}
                  value={mobileNavPadding}
                  units={UNITS_SPACING}
                  onChange={(value) =>
                    setAttributes({
                      mobileNavPadding: value || '',
                    })
                  }
                  help={__('Padding inside the mobile nav container.', 'bvi-mega-menu')}
                />

                {mobileMode === 'dropdown' && (
                  <ToggleGroupControl
                    label={__('Dropdown Alignment', 'bvi-mega-menu')}
                    value={mobileDropdownAlignment}
                    onChange={(value) =>
                      setAttributes({
                        mobileDropdownAlignment: value,
                      })
                    }
                    isBlock
                    help={__(
                      '"Viewport" spans the full screen width ' +
                        'and works correctly wherever the menu block sits in the layout.',
                      'bvi-mega-menu'
                    )}
                  >
                    <ToggleGroupControlOption value="left" label={__('Left', 'bvi-mega-menu')} />
                    <ToggleGroupControlOption value="viewport" label={__('Viewport', 'bvi-mega-menu')} />
                    <ToggleGroupControlOption value="right" label={__('Right', 'bvi-mega-menu')} />
                  </ToggleGroupControl>
                )}

                <SelectControl
                  label={__('Hamburger Style', 'bvi-mega-menu')}
                  value={hamburgerStyle}
                  options={[
                    {
                      label: __('Three Bars (animated to X)', 'bvi-mega-menu'),
                      value: 'bars',
                    },
                    {
                      label: __('Custom SVG', 'bvi-mega-menu'),
                      value: 'svg',
                    },
                    {
                      label: __('Filter-driven', 'bvi-mega-menu'),
                      value: 'custom',
                    },
                  ]}
                  onChange={(value) => setAttributes({ hamburgerStyle: value })}
                  help={hamburgerStyleHelp}
                />

                {hamburgerStyle === 'svg' && (
                  <div style={{ marginTop: '8px' }}>
                    {svgMedia ? (
                      <div
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          gap: '8px',
                        }}
                      >
                        <img
                          src={svgMedia.source_url}
                          alt=""
                          style={{
                            width: '32px',
                            height: '32px',
                          }}
                        />
                        <Button variant="secondary" size="small" onClick={openMediaLibrary}>
                          {__('Replace', 'bvi-mega-menu')}
                        </Button>
                        <Button
                          variant="tertiary"
                          size="small"
                          isDestructive
                          onClick={() =>
                            setAttributes({
                              hamburgerSvgId: 0,
                            })
                          }
                        >
                          {__('Remove', 'bvi-mega-menu')}
                        </Button>
                      </div>
                    ) : (
                      <Button variant="secondary" onClick={openMediaLibrary}>
                        {__('Upload SVG Icon', 'bvi-mega-menu')}
                      </Button>
                    )}
                  </div>
                )}
              </>
            )}
          </PanelBody>
        </InspectorControls>

        <nav {...blockProps}>
          {hasMobile && (
            <button
              type="button"
              className="bvi-mega-menu-hamburger is-editor-preview"
              aria-label={__('Toggle menu (preview)', 'bvi-mega-menu')}
              disabled
            >
              <span className="bvi-mega-menu-hamburger-bar" aria-hidden="true" />
              <span className="bvi-mega-menu-hamburger-bar" aria-hidden="true" />
              <span className="bvi-mega-menu-hamburger-bar" aria-hidden="true" />
            </button>
          )}
          <div className="bvi-mega-menu-nav">
            {isUsingInnerBlocks ? (
              <ul {...innerBlocksProps} />
            ) : (
              <Placeholder
                icon="menu"
                label={__('Mega Menu', 'bvi-mega-menu')}
                instructions={
                  selectedMenu
                    ? sprintf(
                        /* translators: %s is the menu name. */
                        __(
                          'This block will render the "%s" menu on the' +
                            ' frontend using the classic Mega Menu' +
                            ' walker. Edit its items under' +
                            ' Appearance → Menus.',
                          'bvi-mega-menu'
                        ),
                        selectedMenu.name
                      )
                    : __(
                        'A menu slug is configured that could not be' +
                          ' found. Pick another menu in the block' +
                          ' settings.',
                        'bvi-mega-menu'
                      )
                }
              >
                <Notice status="info" isDismissible={false}>
                  {__(
                    'Clear the Menu dropdown in block settings to compose' + ' from inner blocks instead.',
                    'bvi-mega-menu'
                  )}
                </Notice>
              </Placeholder>
            )}
          </div>
        </nav>
      </>
    );
  },
  save: () => <InnerBlocks.Content />,
});
