<?php

namespace Bvi\Plugin\MegaMenu\Tests;

use Bvi\Plugin\MegaMenu\Blocks\MenuItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for child grouping in the MenuItem block.
 *
 * @package Bvi\Plugin\MegaMenu\Tests
 */
class MenuItemChildrenTest extends TestCase
{
    /** @var string */
    private const PANEL_OPEN = '<div class="bvi-mega-panel"><ul class="bvi-mega-menu-sub-list">';

    /** @var string */
    private const PANEL_CLOSE = '</ul></div>';

    /**
     * Nested menu items are wrapped in a panel so the markup stays valid.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_menu_items_are_wrapped_in_a_panel(): void
    {
        $grouped = MenuItem::group_children([
            ['name' => 'bvi/menu-item', 'html' => '<li>a</li>'],
            ['name' => 'bvi/menu-item', 'html' => '<li>b</li>'],
        ]);

        $this->assertSame(self::PANEL_OPEN . '<li>a</li><li>b</li>' . self::PANEL_CLOSE, $grouped);
    }

    /**
     * A run of items produces one panel, not one per item.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_a_run_of_items_produces_a_single_panel(): void
    {
        $grouped = MenuItem::group_children([
            ['name' => 'bvi/menu-item', 'html' => '<li>a</li>'],
            ['name' => 'bvi/menu-item', 'html' => '<li>b</li>'],
            ['name' => 'bvi/menu-item', 'html' => '<li>c</li>'],
        ]);

        $this->assertSame(1, substr_count($grouped, 'bvi-mega-menu-sub-list'));
    }

    /**
     * A mega panel child is passed through untouched.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_mega_panel_is_left_alone(): void
    {
        $panel = '<div class="bvi-mega-panel"><div class="bvi-mega-panel-inner">x</div></div>';

        $grouped = MenuItem::group_children([['name' => 'bvi/mega-panel', 'html' => $panel]]);

        $this->assertSame($panel, $grouped);
    }

    /**
     * Order is preserved when a panel sits between item runs.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_mixed_children_keep_their_order(): void
    {
        $grouped = MenuItem::group_children([
            ['name' => 'bvi/menu-item', 'html' => '<li>a</li>'],
            ['name' => 'bvi/mega-panel', 'html' => '<div class="bvi-mega-panel">p</div>'],
            ['name' => 'bvi/menu-item', 'html' => '<li>b</li>'],
        ]);

        $expected =
            self::PANEL_OPEN .
            '<li>a</li>' .
            self::PANEL_CLOSE .
            '<div class="bvi-mega-panel">p</div>' .
            self::PANEL_OPEN .
            '<li>b</li>' .
            self::PANEL_CLOSE;

        $this->assertSame($expected, $grouped);
    }

    /**
     * No children yields an empty string rather than an empty panel.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_no_children_yields_nothing(): void
    {
        $this->assertSame('', MenuItem::group_children([]));
    }
}
