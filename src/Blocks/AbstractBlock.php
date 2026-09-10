<?php

namespace Bvi\Plugin\MegaMenu\Blocks;

use Bvi\Plugin\MegaMenu\Interfaces\Block;

/**
 * Class AbstractBlock
 *
 * Base class for all custom blocks. Handles the shared boilerplate:
 *   - Derives the build path from the block name slug
 *   - Implements get_name() and get_path()
 *
 * Subclasses must:
 *   - Call parent::__construct('bvi/block-name') in their constructor
 *   - Implement register() with their specific registration logic
 *
 * @package bvi-mega-menu
 */
abstract class AbstractBlock implements Block
{
    /** @var string Block name (e.g. 'bvi/mega-menu'). */
    protected $name;

    /** @var string Absolute path to the block directory. */
    protected $path;

    /**
     * Constructor.
     *
     * Derives the block directory path from the block name slug.
     * The slug is the portion after the namespace prefix (e.g. 'mega-menu'
     * from 'bvi/mega-menu'), and maps to blocks/<slug>/.
     *
     * @since 0.1.0
     *
     * @param string $name The block name (e.g. 'bvi/mega-menu').
     */
    public function __construct(string $name)
    {
        $this->name = $name;

        // Derive slug from block name: 'bvi/mega-menu' -> 'mega-menu'
        $slug = substr($name, strpos($name, '/') + 1);
        $this->path = BVI_PLUGIN_MEGAMENU_DIR_PATH . 'assets/dist/blocks/' . $slug;
    }

    /**
     * Returns the block name.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function get_name(): string
    {
        return $this->name;
    }

    /**
     * Returns the absolute path to the block directory containing block.json.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function get_path(): string
    {
        return $this->path;
    }
}
