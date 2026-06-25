<?php

namespace Bvi\Plugin\MegaMenu\Blocks;

/**
 * Class MegaPanel
 *
 * @package bvi-mega-menu
 */
class MegaPanel extends AbstractBlock
{
    /**
     * Constructor.
     *
     * @since 5.0.0
     * @return void
     */
    public function __construct()
    {
        parent::__construct('bvi/mega-panel');
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
        if (trim($content) === '') {
            return '';
        }

        $background = (string) ( $attributes['panelBackground'] ?? '' );
        $padding = (string) ( $attributes['panelPadding'] ?? '' );
        $max_width = (string) ( $attributes['panelMaxWidth'] ?? '' );
        $alignment = (string) ( $attributes['panelAlign'] ?? 'start' );

        $classes = ['bvi-mega-panel'];
        $classes[] = 'is-align-' . preg_replace('/[^a-z]/', '', strtolower($alignment) ?: 'start');

        $styles = [];
        if ($background !== '') {
            $styles[] = '--bvi-mm-panel-bg: ' . $background;
        }
        if ($padding !== '') {
            $styles[] = '--bvi-mm-panel-padding: ' . $padding;
        }
        if ($max_width !== '') {
            $styles[] = '--bvi-mm-panel-max-width: ' . $max_width;
        }

        $wrapper_attrs = get_block_wrapper_attributes([
            'class' => implode(' ', $classes),
            'style' => implode(';', $styles),
        ]);

        return sprintf('<div %s><div class="bvi-mega-panel-inner">%s</div></div>', $wrapper_attrs, $content);
    }
}
