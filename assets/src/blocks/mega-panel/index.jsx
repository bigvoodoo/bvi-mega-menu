/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import {
  useBlockProps,
  useInnerBlocksProps,
  InnerBlocks,
  InspectorControls,
  PanelColorSettings,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, __experimentalUnitControl as UnitControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const UNITS_SPACING = [
  { value: 'px', label: 'px', default: 0 },
  { value: 'rem', label: 'rem', default: 0 },
  { value: 'em', label: 'em', default: 0 },
  { value: '%', label: '%', default: 0 },
];

const UNITS_SIZE = [
  { value: 'px', label: 'px', default: 0 },
  { value: 'rem', label: 'rem', default: 0 },
  { value: 'em', label: 'em', default: 0 },
  { value: '%', label: '%', default: 0 },
  { value: 'vw', label: 'vw', default: 0 },
];

/**
 * Internal dependencies
 */
import metadata from './block.json';

const ALLOWED_BLOCKS = [
  'core/columns',
  'core/column',
  'core/group',
  'core/heading',
  'core/paragraph',
  'core/list',
  'core/list-item',
  'core/image',
  'core/buttons',
  'core/button',
  'core/separator',
  'core/spacer',
  'core/navigation-link',
  'bvi/responsive-content',
];

const TEMPLATE = [
  [
    'core/columns',
    {},
    [
      ['core/column', {}, [['core/heading', { level: 4, placeholder: 'Section' }]]],
      ['core/column', {}, [['core/paragraph', { placeholder: 'Panel content…' }]]],
    ],
  ],
];

registerBlockType(metadata.name, {
  edit: function Edit({ attributes, setAttributes }) {
    const { panelBackground, panelPadding, panelMaxWidth, panelAlign } = attributes;

    const blockProps = useBlockProps({
      className: `bvi-mega-panel is-editor is-align-${panelAlign || 'start'}`,
      style: {
        '--bvi-mm-panel-bg': panelBackground || undefined,
        '--bvi-mm-panel-padding': panelPadding || undefined,
        '--bvi-mm-panel-max-width': panelMaxWidth || undefined,
      },
    });

    const innerBlocksProps = useInnerBlocksProps(
      { className: 'bvi-mega-panel-inner' },
      {
        allowedBlocks: ALLOWED_BLOCKS,
        template: TEMPLATE,
        templateLock: false,
      }
    );

    return (
      <>
        <InspectorControls group="styles">
          <PanelColorSettings
            title={__('Panel Background', 'bvi-mega-menu')}
            initialOpen={true}
            colorSettings={[
              {
                value: panelBackground,
                onChange: (value) =>
                  setAttributes({
                    panelBackground: value || '',
                  }),
                label: __('Background Color', 'bvi-mega-menu'),
              },
            ]}
          />
        </InspectorControls>
        <InspectorControls>
          <PanelBody title={__('Panel Layout', 'bvi-mega-menu')} initialOpen={true}>
            <UnitControl
              label={__('Padding', 'bvi-mega-menu')}
              value={panelPadding}
              units={UNITS_SPACING}
              onChange={(value) => setAttributes({ panelPadding: value || '' })}
              help={__('Inner padding on all sides.', 'bvi-mega-menu')}
            />
            <UnitControl
              label={__('Max Width', 'bvi-mega-menu')}
              value={panelMaxWidth}
              units={UNITS_SIZE}
              onChange={(value) => setAttributes({ panelMaxWidth: value || '' })}
              help={__('Maximum width of the panel.', 'bvi-mega-menu')}
            />
            <SelectControl
              label={__('Horizontal Align', 'bvi-mega-menu')}
              value={panelAlign}
              options={[
                {
                  label: __('Start', 'bvi-mega-menu'),
                  value: 'start',
                },
                {
                  label: __('Center', 'bvi-mega-menu'),
                  value: 'center',
                },
                {
                  label: __('End', 'bvi-mega-menu'),
                  value: 'end',
                },
                {
                  label: __('Stretch', 'bvi-mega-menu'),
                  value: 'stretch',
                },
              ]}
              onChange={(value) => setAttributes({ panelAlign: value })}
            />
          </PanelBody>
        </InspectorControls>

        <div {...blockProps}>
          <div {...innerBlocksProps} />
        </div>
      </>
    );
  },
  save: () => <InnerBlocks.Content />,
});
