<?php

namespace Bvi\Plugin\MegaMenu\Interfaces;

/**
 * Interface for custom block classes.
 *
 * All custom blocks must implement this interface to be
 * discoverable by the Discovery utility during registration.
 *
 * @since 0.1.0
 */
interface Block
{
    /**
     * Returns the block name.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Returns the absolute path to the block directory containing block.json.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function get_path(): string;

    /**
     * Registers the block type and any dependencies.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function register(): void;
}
