<?php

namespace Bvi\Plugin\MegaMenu\Blocks;

/**
 * Class MenuItem
 *
 * @package bvi-mega-menu
 */
class MenuItem extends AbstractBlock
{
    /**
     * Constructor.
     *
     * @since 5.0.0
     * @return void
     */
    public function __construct()
    {
        parent::__construct('bvi/menu-item');
    }

    /**
     * Register the block type.
     *
     * @since 5.0.0
     * @return void
     */
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
     * @since 5.0.0
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Rendered InnerBlocks content.
     * @return string Rendered HTML.
     */
    public function render(array $attributes, string $content): string
    {
        $label = (string) ( $attributes['label'] ?? '' );
        $url = (string) ( $attributes['url'] ?? '' );
        $target = !empty($attributes['openInNewTab']) ? '_blank' : '';
        $rel = $target === '_blank' ? 'noopener noreferrer' : '';
        $label_color = (string) ( $attributes['labelColor'] ?? '' );
        $panel_width = (string) ( $attributes['panelWidth'] ?? '' );

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
     * Detect whether the rendered InnerBlocks content already contains a panel.
     *
     * @since 5.0.0
     *
     * @param string $content Rendered InnerBlocks content.
     * @return bool
     */
    private function content_has_panel(string $content): bool
    {
        return $content !== '' &&
            ( strpos($content, 'bvi-mega-panel') !== false || strpos($content, 'bvi-menu-item') !== false );
    }
}
