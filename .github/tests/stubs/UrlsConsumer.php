<?php

namespace Bvi\Plugin\MegaMenu\Tests;

use Bvi\Plugin\MegaMenu\Utils\Traits\Urls;

/**
 * Concrete consumer of the Urls trait.
 *
 * PHPUnit\Framework\Assert already declares a matches() method, so a TestCase
 * cannot use the trait directly without an incompatible-signature fatal.
 *
 * @package Bvi\Plugin\MegaMenu\Tests
 */
class UrlsConsumer
{
    use Urls;
}
