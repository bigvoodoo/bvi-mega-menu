<?php

namespace Bvi\Plugin\MegaMenu\Tests;

use Bvi\Plugin\MegaMenu\Blocks\Menus\Walker;
use PHPUnit\Framework\TestCase;

/**
 * Tests for current/ancestor class mapping in the menu Walker.
 *
 * @package Bvi\Plugin\MegaMenu\Tests
 */
class WalkerCurrentStateTest extends TestCase
{
    /**
     * The exact-match item is flagged current.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_exact_match_gets_current_menu_item(): void
    {
        $map = Walker::build_current_map($this->elements(), '/about');

        $this->assertContains('current-menu-item', $map[4]);
        $this->assertContains('is-current', $map[4]);
    }

    /**
     * The immediate parent of the current item is flagged parent and ancestor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_immediate_parent_gets_parent_and_ancestor(): void
    {
        $map = Walker::build_current_map($this->elements(), '/services/design');

        $this->assertContains('current-menu-parent', $map[1]);
        $this->assertContains('current-menu-ancestor', $map[1]);
    }

    /**
     * A grandparent is flagged ancestor but not parent.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_grandparent_gets_ancestor_only(): void
    {
        $map = Walker::build_current_map($this->elements(), '/services/design/branding');

        $this->assertContains('current-menu-ancestor', $map[1]);
        $this->assertNotContains('current-menu-parent', $map[1]);
        $this->assertContains('current-menu-parent', $map[2]);
    }

    /**
     * Unrelated branches receive no classes.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_unrelated_items_get_no_classes(): void
    {
        $map = Walker::build_current_map($this->elements(), '/about');

        $this->assertArrayNotHasKey(1, $map);
        $this->assertArrayNotHasKey(2, $map);
    }

    /**
     * A current URL matching nothing yields an empty map.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_no_match_yields_empty_map(): void
    {
        $this->assertSame([], Walker::build_current_map($this->elements(), '/contact'));
    }

    /**
     * Build the three-level fixture menu used by every test here.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function elements(): array
    {
        return [
            (object) ['ID' => 1, 'parent_id' => 0, 'url' => '/services'],
            (object) ['ID' => 2, 'parent_id' => 1, 'url' => '/services/design'],
            (object) ['ID' => 3, 'parent_id' => 2, 'url' => '/services/design/branding'],
            (object) ['ID' => 4, 'parent_id' => 0, 'url' => '/about'],
        ];
    }
}
