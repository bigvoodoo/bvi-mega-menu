/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const { useState, useEffect } = wp.element;
const apiFetch = wp.apiFetch;

/**
 * Internal dependencies
 */
import metadata from './block.json';

registerBlockType(metadata.name, {
	edit: function Edit({ attributes, setAttributes }) {
		const { menuSlug } = attributes;
		const blockProps = useBlockProps();

		// Fetch all registered nav menus from the custom REST endpoint.
		const [menus, setMenus] = useState([]);
		const [isLoading, setIsLoading] = useState(true);
		useEffect(() => {
			let cancelled = false;
			apiFetch({ path: '/bvi/v1/menus' })
				.then((data) => {
					if (cancelled) {
						return;
					}
					setMenus(Array.isArray(data) ? data : []);
					setIsLoading(false);
				})
				.catch(() => {
					if (cancelled) {
						return;
					}
					setMenus([]);
					setIsLoading(false);
				});
			return () => {
				cancelled = true;
			};
		}, []);

		// Build menu options for the dropdown.
		const menuOptions = [
			{
				label: __('— Auto-detect from Page —', 'bvi-mega-menu'),
				value: 'autodetect',
			},
			{
				label: __('— Default Menu in Settings —', 'bvi-mega-menu'),
				value: 'defaultsettings',
			},
			...menus.map((menu) => ({
				label: menu.name,
				value: menu.slug,
			})),
		];

		const isAutoDetect = menuSlug === '' || menuSlug === 'autodetect';
		const isDefaultSettings = menuSlug === 'defaultsettings';

		return (
			<div {...blockProps}>
				<InspectorControls>
					<PanelBody title={__('Menu Source', 'bvi-mega-menu')}>
						<SelectControl
							label={__('Select Menu', 'bvi-mega-menu')}
							help={__(
								'Leave as "Auto Detect" to inherit from a Mega Menu block on this' +
									' page, or as Default to pull from the default menu in' +
									' Settings. If Auto Detect is set and no mega menu block is' +
									' detected, it will pull from the default menu instead.',
								'bvi-mega-menu'
							)}
							value={menuSlug || 'autodetect'}
							options={menuOptions}
							onChange={(value) =>
								setAttributes({ menuSlug: value })
							}
						/>
					</PanelBody>
				</InspectorControls>

				<Placeholder
					icon="editor-ul"
					label={__('Related Links', 'bvi-mega-menu')}
					instructions={
						isAutoDetect || isDefaultSettings
							? __(
									'Will auto-detect the Mega Menu block on this page, or use' +
										' the default menu from Settings.',
									'bvi-mega-menu'
								)
							: __(
									'Configured to use a specific menu. Adjust in block settings.',
									'bvi-mega-menu'
								)
					}
				>
					{isLoading ? (
						<Spinner />
					) : (
						<SelectControl
							value={menuSlug || 'autodetect'}
							options={menuOptions}
							onChange={(value) =>
								setAttributes({ menuSlug: value })
							}
						/>
					)}
				</Placeholder>
			</div>
		);
	},
});
