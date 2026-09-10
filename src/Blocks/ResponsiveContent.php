<?php

namespace Bvi\Plugin\MegaMenu\Blocks;

/**
 * Gutenberg block: Responsive Content.
 *
 * Wraps inner content with responsive visibility classes.
 * Only available as a child of the Mega Menu block.
 *
 * @package bvi-mega-menu
 */
class ResponsiveContent extends AbstractBlock
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('bvi/responsive-content');
    }

    /**
     * Register the block type.
     *
     * @since 0.1.0
     *
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
     * @param array  $attributes Block attributes.
     * @param string $content    Inner block content.
     * @return string Rendered HTML.
     */
    public function render(array $attributes, string $content): string
    {
        if (empty(trim($content))) {
            return '';
        }

        $show_mobile = $attributes['showMobile'] ?? true;
        $show_tablet = $attributes['showTablet'] ?? true;
        $show_desktop = $attributes['showDesktop'] ?? true;

        $classes = ['bvi-responsive-content'];

        if (!$show_mobile) {
            $classes[] = 'bvi-hide-mobile';
        }
        if (!$show_tablet) {
            $classes[] = 'bvi-hide-tablet';
        }
        if (!$show_desktop) {
            $classes[] = 'bvi-hide-desktop';
        }

        $wrapper_attrs = get_block_wrapper_attributes([
            'class' => implode(' ', $classes),
        ]);

        return sprintf('<div %s>%s</div>', $wrapper_attrs, $content);
    }
}
