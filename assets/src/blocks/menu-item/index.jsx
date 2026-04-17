/**
 * WordPress dependencies
 */
import { registerBlockType, createBlock } from '@wordpress/blocks';
import {
	useBlockProps,
	useInnerBlocksProps,
	InnerBlocks,
	InspectorControls,
	RichText,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	Notice,
	Button,
	Spinner,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { pencil, linkOff } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';

const ALLOWED_BLOCKS = ['bvi/mega-panel', 'bvi/menu-item'];
const TEMPLATE = [];

/**
 * @param  val
 * @return {boolean}
 */
const looksLikeUrl = (val) => /^https?:\/\//i.test(val) || val.startsWith('/');

/** @param {string} str @returns {string} */
const decodeEntities = (str) => {
	const el = document.createElement('div');
	el.innerHTML = str;
	return el.textContent || str;
};

/**
 * Custom link picker: searches pages with top-level indicator, falls back to raw URL entry.
 *
 * @param {Object}   props
 * @param {string}   props.url
 * @param {string}   props.title        Display title for the preview (the block label).
 * @param {number}   props.id
 * @param {string}   props.kind
 * @param {string}   props.type
 * @param {boolean}  props.openInNewTab
 * @param {Function} props.onChange
 * @param {Function} props.onRemove
 * @return {JSX.Element}
 */
function LinkPicker({
	url,
	title,
	id,
	kind,
	type,
	openInNewTab,
	onChange,
	onRemove,
}) {
	const [isEditing, setIsEditing] = useState(!url);
	const [searchVal, setSearchVal] = useState('');
	const [results, setResults] = useState([]);
	const [isLoading, setIsLoading] = useState(false);

	useEffect(() => {
		const trimmed = searchVal.trim();
		if (!trimmed || looksLikeUrl(trimmed)) {
			setResults([]);
			setIsLoading(false);
			return;
		}
		setIsLoading(true);
		let cancelled = false;
		const timer = setTimeout(() => {
			const encoded = encodeURIComponent(trimmed);
			Promise.all([
				apiFetch({
					path: `/wp/v2/pages?search=${encoded}&per_page=20&status=publish&orderby=relevance`,
				}),
				apiFetch({
					path: `/wp/v2/pages?search=${encoded}&per_page=10&parent=0&status=publish&orderby=relevance`,
				}),
			])
				.then(([general, topLevel]) => {
					if (cancelled) {
						return;
					}
					const seen = new Set();
					const merged = [];
					for (const page of [...topLevel, ...general]) {
						if (!seen.has(page.id)) {
							seen.add(page.id);
							merged.push(page);
						}
					}
					const needle = trimmed.toLowerCase();
					const sorted = merged.sort((a, b) => {
						const aTitle = decodeEntities(
							a.title.rendered
						).toLowerCase();
						const bTitle = decodeEntities(
							b.title.rendered
						).toLowerCase();
						const aExact = aTitle === needle;
						const bExact = bTitle === needle;
						if (aExact !== bExact) {
							return aExact ? -1 : 1;
						}
						const aStarts = aTitle.startsWith(needle);
						const bStarts = bTitle.startsWith(needle);
						if (aStarts !== bStarts) {
							return aStarts ? -1 : 1;
						}
						if ((a.parent === 0) !== (b.parent === 0)) {
							return a.parent === 0 ? -1 : 1;
						}
						return 0;
					});
					setResults(sorted);
				})
				.catch(() => {
					if (!cancelled) {
						setResults([]);
					}
				})
				.finally(() => {
					if (!cancelled) {
						setIsLoading(false);
					}
				});
		}, 300);
		return () => {
			cancelled = true;
			clearTimeout(timer);
		};
	}, [searchVal]);

	const handleSelect = (page) => {
		onChange({
			url: page.link,
			title: decodeEntities(page.title.rendered),
			id: page.id,
			kind: 'post-type',
			type: 'page',
			openInNewTab,
		});
		setIsEditing(false);
		setSearchVal('');
		setResults([]);
	};

	const handleCustomUrl = () => {
		const trimmed = searchVal.trim();
		if (!trimmed) {
			return;
		}
		onChange({
			url: trimmed,
			title: '',
			id: 0,
			kind: '',
			type: '',
			openInNewTab,
		});
		setIsEditing(false);
		setSearchVal('');
		setResults([]);
	};

	const handleRemove = () => {
		onRemove();
		setIsEditing(true);
		setSearchVal('');
		setResults([]);
	};

	if (!isEditing && url) {
		return (
			<div className="bvi-link-picker">
				<div className="bvi-link-picker__preview">
					<div className="bvi-link-picker__preview-info">
						<span className="bvi-link-picker__preview-title">
							{title || url}
						</span>
						<span className="bvi-link-picker__preview-url">
							{url}
						</span>
					</div>
					<div className="bvi-link-picker__preview-actions">
						<Button
							icon={pencil}
							size="small"
							onClick={() => setIsEditing(true)}
							label={__('Edit link', 'bvi-mega-menu')}
						/>
						<Button
							icon={linkOff}
							size="small"
							onClick={handleRemove}
							label={__('Remove link', 'bvi-mega-menu')}
							isDestructive
						/>
					</div>
				</div>
				<ToggleControl
					label={__('Open in new tab', 'bvi-mega-menu')}
					checked={!!openInNewTab}
					onChange={(val) =>
						onChange({
							url,
							title,
							id,
							kind,
							type,
							openInNewTab: val,
						})
					}
				/>
			</div>
		);
	}

	return (
		<div className="bvi-link-picker">
			<TextControl
				label={__('Search pages or enter URL', 'bvi-mega-menu')}
				value={searchVal}
				onChange={setSearchVal}
				placeholder={__('Search\u2026', 'bvi-mega-menu')}
				autoComplete="off"
			/>
			{isLoading && <Spinner />}
			{!isLoading && results.length > 0 && (
				<ul className="bvi-link-picker__results">
					{results.map((page) => (
						<li key={page.id}>
							<button
								type="button"
								className="bvi-link-picker__result"
								onClick={() => handleSelect(page)}
							>
								<span className="bvi-link-picker__result-title">
									{decodeEntities(page.title.rendered)}
								</span>
								{page.parent === 0 && (
									<span className="bvi-link-picker__badge">
										{__('Top level', 'bvi-mega-menu')}
									</span>
								)}
							</button>
						</li>
					))}
				</ul>
			)}
			{looksLikeUrl(searchVal.trim()) && searchVal.trim() && (
				<Button
					variant="secondary"
					size="small"
					onClick={handleCustomUrl}
				>
					{__('Use this URL', 'bvi-mega-menu')}
				</Button>
			)}
			{url && (
				<Button
					variant="tertiary"
					size="small"
					onClick={() => {
						setIsEditing(false);
						setSearchVal('');
						setResults([]);
					}}
				>
					{__('Cancel', 'bvi-mega-menu')}
				</Button>
			)}
			<p className="components-base-control__help">
				{__(
					'Leave empty to make the label a dropdown toggle only.',
					'bvi-mega-menu'
				)}
			</p>
		</div>
	);
}

registerBlockType(metadata.name, {
	edit: function Edit({ attributes, setAttributes, clientId }) {
		const {
			label,
			url,
			kind,
			id,
			type,
			openInNewTab,
			labelColor,
			panelWidth,
		} = attributes;

		const { replaceInnerBlocks } = useDispatch('core/block-editor');
		const [childPages, setChildPages] = useState(null);

		useEffect(() => {
			if (!id || type !== 'page') {
				setChildPages(null);
				return;
			}
			let cancelled = false;
			apiFetch({
				path: `/wp/v2/pages?parent=${id}&per_page=100&status=publish`,
			})
				.then((pages) => {
					if (!cancelled) {
						setChildPages(pages);
					}
				})
				.catch(() => {
					if (!cancelled) {
						setChildPages([]);
					}
				});
			return () => {
				cancelled = true;
			};
		}, [id, type]);

		const handleAddChildrenPanel = () => {
			const paragraphs = childPages.map((page) =>
				createBlock('core/paragraph', {
					content: `<a href="${page.link}">${decodeEntities(page.title.rendered)}</a>`,
				})
			);
			const column = createBlock('core/column', {}, paragraphs);
			const columns = createBlock('core/columns', {}, [column]);
			const panel = createBlock('bvi/mega-panel', {}, [columns]);
			replaceInnerBlocks(clientId, [panel], false);
		};

		const handleLinkChange = ({
			url: newUrl = '',
			title = '',
			id: newId = 0,
			kind: newKind = '',
			type: newType = '',
			openInNewTab: newTab = false,
		}) => {
			setAttributes({
				url: newUrl,
				id: newId,
				kind: newKind,
				type: newType,
				openInNewTab: newTab,
				...(!label && title ? { label: title } : {}),
			});
		};

		const blockProps = useBlockProps({
			className: 'bvi-menu-item is-editor',
			style: {
				'--bvi-mm-item-color': labelColor || undefined,
				'--bvi-mm-panel-width': panelWidth || undefined,
			},
		});

		const innerBlocksProps = useInnerBlocksProps(
			{ className: 'bvi-menu-item-children' },
			{
				allowedBlocks: ALLOWED_BLOCKS,
				template: TEMPLATE,
			}
		);

		return (
			<>
				<InspectorControls>
					<PanelBody
						title={__('Link', 'bvi-mega-menu')}
						initialOpen={true}
					>
						<LinkPicker
							url={url}
							title={label || ''}
							id={id}
							kind={kind}
							type={type}
							openInNewTab={openInNewTab}
							onChange={handleLinkChange}
							onRemove={() =>
								setAttributes({
									url: '',
									id: 0,
									kind: '',
									type: '',
									openInNewTab: false,
								})
							}
						/>
						{childPages && childPages.length > 0 && (
							<Notice status="info" isDismissible={false}>
								<p>
									{sprintf(
										/* translators: %d: number of child pages */
										__(
											'This page has %d child page(s).',
											'bvi-mega-menu'
										),
										childPages.length
									)}
								</p>
								<Button
									variant="secondary"
									size="small"
									onClick={handleAddChildrenPanel}
								>
									{__('Add as mega panel', 'bvi-mega-menu')}
								</Button>
							</Notice>
						)}
					</PanelBody>

					<PanelBody
						title={__('Item Style', 'bvi-mega-menu')}
						initialOpen={false}
					>
						<TextControl
							label={__('Label Color', 'bvi-mega-menu')}
							value={labelColor}
							onChange={(value) =>
								setAttributes({ labelColor: value })
							}
							help={__(
								'CSS color. Overrides the mega menu default link color for this item.',
								'bvi-mega-menu'
							)}
						/>
						<TextControl
							label={__('Panel Width', 'bvi-mega-menu')}
							value={panelWidth}
							onChange={(value) =>
								setAttributes({ panelWidth: value })
							}
							help={__(
								'CSS value with unit (e.g. 480px, 32rem, 100%). Controls this' +
									' item\u2019s dropdown width.',
								'bvi-mega-menu'
							)}
						/>
					</PanelBody>
				</InspectorControls>

				<li {...blockProps}>
					<div className="bvi-menu-item-label-row">
						<RichText
							tagName="span"
							className="bvi-menu-item-link"
							value={label}
							onChange={(value) =>
								setAttributes({ label: value })
							}
							placeholder={__('Menu item', 'bvi-mega-menu')}
							allowedFormats={[]}
							identifier="label"
						/>
					</div>
					<div {...innerBlocksProps} />
				</li>
			</>
		);
	},
	save: () => <InnerBlocks.Content />,
});
