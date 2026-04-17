<?php

namespace Bvi\Plugin\MegaMenu\Utils\Traits;

/**
 * Trait Feature
 *
 * @package bvimegamenu/src
 */
trait Feature
{
    public $name;

    public function get_name(): string
    {
        return $this->name;
    }

    public function set_name($name): void
    {
        $this->name = $name;
    }
}
