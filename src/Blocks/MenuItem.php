<?php

namespace Bvi\Plugin\MegaMenu\Blocks;

/**
 * Gutenberg block: Menu Item.
 *
 * Top-level and nested menu items inside a `bvi/mega-menu` block. Each
 * item renders as a `<li>` containing a link (or a plain label if no URL is
 * set) and any InnerBlocks content (typically a `bvi/mega-panel` or nested
 * `bvi/menu-item` blocks).
 *
 * All styling is driven by parent mega-menu block CSS custom properties so
 * no custom CSS is required to get a working menu.
 *
 * @package bvi-mega-menu
 */
class MenuItem extends AbstractBlock
{
    public function __construct()
    {
        parent::__construct('bvi/menu-item');
    }

    public function register(): void
    {
        if (file_exists($this->get_path() . '/block.json')) {
            register_block_type($this->get_path(), [
                'render_callback' => [$this, 'render'],
            ]);
        }
    }

    /**
     * Server-side render callback.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Rendered InnerBlocks content.
     * @return string Rendered HTML.
     */
    public function render(array $attributes, string $content): string
    {
        $label = (string) ($attributes['label'] ?? '');
        $url = (string) ($attributes['url'] ?? '');
        $target = !empty($attributes['openInNewTab']) ? '_blank' : '';
        $rel = $target === '_blank' ? 'noopener noreferrer' : '';
        $label_color = (string) ($attributes['labelColor'] ?? '');
        $panel_width = (string) ($attributes['panelWidth'] ?? '');

        $has_panel = $this->content_has_panel($content);

        $classes = ['bvi-menu-item'];
        if ($has_panel) {
            $classes[] = 'has-panel';
        }

        $styles = [];
        if ($label_color !== '') {
            $styles[] = '--bvi-mm-item-color: ' . $label_color;
        }
        if ($panel_width !== '') {
            $styles[] = '--bvi-mm-panel-width: ' . $panel_width;
        }

        $wrapper_attrs = get_block_wrapper_attributes([
            'class' => implode(' ', $classes),
            'style' => implode(';', $styles),
        ]);

        if ($label === '') {
            return '';
        }

        $link_attrs = '';
        if ($url !== '') {
            $link_attrs .= ' href="' . esc_url($url) . '"';
        }
        if ($target !== '') {
            $link_attrs .= ' target="' . esc_attr($target) . '"';
        }
        if ($rel !== '') {
            $link_attrs .= ' rel="' . esc_attr($rel) . '"';
        }
        if ($has_panel) {
            $link_attrs .= ' aria-haspopup="true" aria-expanded="false"';
        }

        $label_html = esc_html($label);

        if ($url !== '') {
            $link = sprintf('<a class="bvi-menu-item-link"%s>%s</a>', $link_attrs, $label_html);
        } else {
            $link = sprintf('<button type="button" class="bvi-menu-item-link"%s>%s</button>', $link_attrs, $label_html);
        }

        $toggle = $has_panel
            ? '<button type="button" class="bvi-menu-item-toggle" aria-label="' .
                esc_attr__('Toggle submenu', 'bvi-mega-menu') .
                '" aria-expanded="false"><span aria-hidden="true"></span></button>'
            : '';

        return sprintf('<li %s>%s%s%s</li>', $wrapper_attrs, $link, $toggle, $content);
    }

    /**
     * Detect whether the rendered InnerBlocks content already contains a panel
     * (either a bvi/mega-panel output or nested menu items).
     *
     * @param string $content
     * @return bool
     */
    private function content_has_panel(string $content): bool
    {
        return $content !== '' &&
            (strpos($content, 'bvi-mega-panel') !== false || strpos($content, 'bvi-menu-item') !== false);
    }
}
