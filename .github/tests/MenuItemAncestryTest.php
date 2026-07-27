<?php

namespace Bvi\Plugin\MegaMenu\Tests;

use Bvi\Plugin\MegaMenu\Blocks\MenuItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for descendant-depth detection in the MenuItem block.
 *
 * @package Bvi\Plugin\MegaMenu\Tests
 */
class MenuItemAncestryTest extends TestCase
{
    /**
     * A direct child match reports depth 1.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_direct_child_match_reports_depth_one(): void
    {
        $inner = [$this->item('/services/design')];

        $this->assertSame(1, MenuItem::find_current_descendant_depth($inner, '/services/design'));
    }

    /**
     * A grandchild match reports depth 2.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_grandchild_match_reports_depth_two(): void
    {
        $inner = [$this->item('/services/design', [$this->item('/services/design/branding')])];

        $this->assertSame(2, MenuItem::find_current_descendant_depth($inner, '/services/design/branding'));
    }

    /**
     * A panel wrapper between items does not inflate the reported depth.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_panel_wrapper_does_not_count_as_a_level(): void
    {
        $panel = [
            'blockName' => 'bvi/mega-panel',
            'attrs' => [],
            'innerBlocks' => [$this->item('/services/design')],
        ];

        $this->assertSame(1, MenuItem::find_current_descendant_depth([$panel], '/services/design'));
    }

    /**
     * No descendant match returns null.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_no_match_returns_null(): void
    {
        $inner = [$this->item('/services/design')];

        $this->assertNull(MenuItem::find_current_descendant_depth($inner, '/contact'));
    }

    /**
     * The shallowest match wins when the same URL appears twice.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_shallowest_match_wins(): void
    {
        $inner = [$this->item('/a', [$this->item('/target')]), $this->item('/target')];

        $this->assertSame(1, MenuItem::find_current_descendant_depth($inner, '/target'));
    }

    /**
     * Build a bvi/menu-item parsed block.
     *
     * @since 5.0.0
     *
     * @param string $url   Item URL.
     * @param array  $inner Inner blocks.
     * @return array
     */
    private function item(string $url, array $inner = []): array
    {
        return [
            'blockName' => 'bvi/menu-item',
            'attrs' => ['url' => $url, 'label' => $url],
            'innerBlocks' => $inner,
        ];
    }
}
