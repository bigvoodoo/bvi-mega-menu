<?php

namespace Bvi\Plugin\MegaMenu\Tests;

use Bvi\Plugin\MegaMenu\Blocks\RelatedLinks;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for mega-menu block scanning in RelatedLinks.
 *
 * @package Bvi\Plugin\MegaMenu\Tests
 */
class RelatedLinksTest extends TestCase
{
    /**
     * Reset the fixture globals between tests.
     *
     * @since 5.0.0
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['bvi_test_nav_menus'] = [];
        $GLOBALS['bvi_test_nav_menu_items'] = [];
    }

    /**
     * A mega-panel wrapper between a top-level item and its children must stay
     * transparent, the same way MenuItem::find_current_descendant_depth treats it.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_children_nested_inside_a_mega_panel_are_found(): void
    {
        $blocks = [
            $this->mega_menu([
                $this->menu_item('Services', '/services', [
                    $this->mega_panel([
                        $this->menu_item('Design', '/services/design'),
                        $this->menu_item('Branding', '/services/branding'),
                    ]),
                ]),
            ]),
        ];

        $titles = array_column($this->walk($blocks), 'title');

        $this->assertContains('Design', $titles);
        $this->assertContains('Branding', $titles);
    }

    /**
     * Children found inside a panel are parented to the item the panel belongs to.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_panel_children_are_parented_to_the_owning_item(): void
    {
        $blocks = [
            $this->mega_menu([
                $this->menu_item('Services', '/services', [
                    $this->mega_panel([$this->menu_item('Design', '/services/design')]),
                ]),
            ]),
        ];

        $items = $this->walk($blocks);

        $this->assertSame(
            $this->find_by_title($items, 'Services')['id'],
            $this->find_by_title($items, 'Design')['parent_id'],
        );
    }

    /**
     * When the mega menu block is set to a classic-menu source, related links must
     * follow that source instead of leftover Compose-From-Inner-Blocks content.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_classic_menu_source_wins_over_leftover_inner_blocks(): void
    {
        $GLOBALS['bvi_test_nav_menus']['main-menu'] = (object) ['term_id' => 7];
        $GLOBALS['bvi_test_nav_menu_items'][7] = [
            (object) [
                'ID' => 101,
                'menu_item_parent' => 0,
                'url' => '/new-home',
                'title' => 'New Home',
                'type' => 'custom',
                'classes' => [],
            ],
        ];

        $blocks = [$this->mega_menu([$this->menu_item('Old Inner Block Item', '/old')], ['menuSlug' => 'main-menu'])];

        $titles = array_column($this->walk($blocks), 'title');

        $this->assertContains('New Home', $titles);
        $this->assertNotContains('Old Inner Block Item', $titles);
    }

    /**
     * Invoke the private walk_blocks_for_mega_menu() method and return the flat items.
     *
     * @since 5.0.0
     *
     * @param array $blocks Parsed block tree.
     * @return array
     */
    private function walk(array $blocks): array
    {
        $instance = (new ReflectionClass(RelatedLinks::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(RelatedLinks::class, 'walk_blocks_for_mega_menu');
        $method->setAccessible(true);

        $items = [];
        $args = [$blocks, &$items];
        $method->invokeArgs($instance, $args);

        return $items;
    }

    /**
     * Find the first item with the given title, failing the test if absent.
     *
     * @since 5.0.0
     *
     * @param array  $items Flat item list.
     * @param string $title Title to find.
     * @return array
     */
    private function find_by_title(array $items, string $title): array
    {
        foreach ($items as $item) {
            if ($item['title'] === $title) {
                return $item;
            }
        }

        $this->fail('No item titled "' . $title . '" found.');
    }

    /**
     * Build a bvi/mega-menu parsed block.
     *
     * @since 5.0.0
     *
     * @param array $inner Inner blocks.
     * @param array $attrs Block attributes.
     * @return array
     */
    private function mega_menu(array $inner, array $attrs = []): array
    {
        return ['blockName' => 'bvi/mega-menu', 'attrs' => $attrs, 'innerBlocks' => $inner];
    }

    /**
     * Build a bvi/mega-panel parsed block.
     *
     * @since 5.0.0
     *
     * @param array $inner Inner blocks.
     * @return array
     */
    private function mega_panel(array $inner): array
    {
        return ['blockName' => 'bvi/mega-panel', 'attrs' => [], 'innerBlocks' => $inner];
    }

    /**
     * Build a bvi/menu-item parsed block.
     *
     * @since 5.0.0
     *
     * @param string $label Item label.
     * @param string $url   Item URL.
     * @param array  $inner Inner blocks.
     * @return array
     */
    private function menu_item(string $label, string $url, array $inner = []): array
    {
        return [
            'blockName' => 'bvi/menu-item',
            'attrs' => ['label' => $label, 'url' => $url],
            'innerBlocks' => $inner,
        ];
    }
}
