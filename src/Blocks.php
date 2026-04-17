<?php

namespace Bvi\Plugin\MegaMenu;

use Bvi\Plugin\MegaMenu\Utils\Discovery;
use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

/**
 * Class Blocks
 *
 * Discovers and registers all custom block classes found in src/Blocks/.
 *
 * To add a new block:
 *   1. Create a source directory in assets/src/blocks/<slug>/
 *      with block.json, index.js, and optionally view.js + style.scss
 *   2. Create a PHP class in src/Blocks/ that extends AbstractBlock
 *      and implements the register() method
 *   3. Run `npm run block:build` to compile the block assets
 *
 * @package bvi-mega-menu
 */
class Blocks
{
    use Singleton;

    /** @var array<\Bvi\Plugin\MegaMenu\Interfaces\Block> */
    private $blocks = [];

    /**
     * Initializes block discovery on the init hook.
     *
     * Called automatically by the Singleton trait after construction.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function init()
    {
        add_action('init', [$this, 'register_blocks'], 10);
    }

    /**
     * Discovers all block classes in src/Blocks/ and registers them.
     *
     * Each block class must implement the Block interface. Discovery
     * instantiates valid classes, then this method explicitly calls
     * register() on each one.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function register_blocks()
    {
        $blocks_dir = BVI_PLUGIN_MEGAMENU_DIR_PATH . 'src/Blocks';
        $primary_namespace = 'Bvi\\Plugin\\MegaMenu\\';
        $blocks_namespace = 'Blocks\\';
        $blocks_interface = 'Bvi\\Plugin\\MegaMenu\\Interfaces\\Block';

        $this->blocks = Discovery::discover($blocks_dir, $primary_namespace, $blocks_namespace, $blocks_interface);

        foreach ($this->blocks as $block) {
            $block->register();
        }
    }

    /**
     * Returns all discovered block instances.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function get_blocks(): array
    {
        return $this->blocks;
    }
}
