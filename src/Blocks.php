<?php

namespace Bvi\Plugin\MegaMenu;

use Bvi\Plugin\MegaMenu\Utils\Discovery;
use Bvi\Plugin\MegaMenu\Utils\Traits\Singleton;

/**
 * Class Blocks
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
