<?php

namespace Bvi\Plugin\MegaMenu\Tests;

use Bvi\Plugin\MegaMenu\Blocks\MenuItem;
use Bvi\Plugin\MegaMenu\Blocks\Menus\BlockScanner;
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
     * A link written into panel content counts as a direct child of the item.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_link_in_panel_content_reports_depth_one(): void
    {
        $panel = [
            'blockName' => 'bvi/mega-panel',
            'attrs' => [],
            'innerBlocks' => [$this->paragraph('<a href="/services/design/">Design</a>')],
        ];

        $this->assertSame(1, MenuItem::find_current_descendant_depth([$panel], '/services/design'));
    }

    /**
     * A panel link under a nested item is that item's child, so depth two from the top.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_link_in_nested_item_panel_reports_depth_two(): void
    {
        $panel = [
            'blockName' => 'bvi/mega-panel',
            'attrs' => [],
            'innerBlocks' => [$this->paragraph('<a href="/services/design/branding/">Branding</a>')],
        ];

        $inner = [$this->item('/services/design', [$panel])];

        $this->assertSame(2, MenuItem::find_current_descendant_depth($inner, '/services/design/branding'));
    }

    /**
     * A navigation block in panel content that references a saved menu counts its links as direct children.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_saved_navigation_menu_in_panel_content_reports_depth_one(): void
    {
        $GLOBALS['bvi_test_blocks']['wp_block:77'] = [
            [
                'blockName' => 'core/navigation-link',
                'attrs' => ['label' => 'FAQ', 'url' => '/resources/faq/'],
                'innerBlocks' => [],
            ],
        ];
        $panel = [
            'blockName' => 'bvi/mega-panel',
            'attrs' => [],
            'innerBlocks' => [['blockName' => 'core/navigation', 'attrs' => ['ref' => 77], 'innerBlocks' => []]],
        ];

        $inner = BlockScanner::resolve([$panel]);

        $this->assertSame(1, MenuItem::find_current_descendant_depth($inner, '/resources/faq'));
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
     * Build a core/paragraph parsed block around the given inline markup.
     *
     * @since 5.0.0
     *
     * @param string $inline Inline HTML for the paragraph body.
     * @return array
     */
    private function paragraph(string $inline): array
    {
        return [
            'blockName' => 'core/paragraph',
            'attrs' => [],
            'innerHTML' => "\n<p>" . $inline . "</p>\n",
            'innerBlocks' => [],
        ];
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
