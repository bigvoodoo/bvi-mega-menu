<?php

namespace Bvi\Plugin\MegaMenu\Tests;

use Bvi\Plugin\MegaMenu\Blocks\RelatedLinks;
use Bvi\Plugin\MegaMenu\Blocks\Menus\BlockScanner;
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
     * Links written into panel content (what "Add as mega panel" generates) are children
     * of the item that owns the panel, not invisible.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_links_inside_panel_content_are_children_of_the_owning_item(): void
    {
        $blocks = [$this->mega_menu([$this->practice_areas_item()])];

        $items = $this->walk($blocks);
        $parent_id = $this->find_by_title($items, 'Practice Areas')['id'];

        $this->assertSame(['Practice Areas', 'Car Accidents', 'Slip & Fall'], array_column($items, 'title'));
        $this->assertSame('/practice-areas/car-accidents/', $this->find_by_title($items, 'Car Accidents')['url']);
        $this->assertSame($parent_id, $this->find_by_title($items, 'Car Accidents')['parent_id']);
        $this->assertSame($parent_id, $this->find_by_title($items, 'Slip & Fall')['parent_id']);
    }

    /**
     * On a page linked from panel content, the block shows that page's siblings rather
     * than falling back to the top level.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_current_page_inside_panel_content_selects_its_siblings(): void
    {
        $blocks = [$this->mega_menu([$this->practice_areas_item(), $this->menu_item('Contact', '/contact/')])];

        $items = $this->walk($blocks);
        foreach ($items as &$item) {
            $item['current'] = $item['url'] === '/practice-areas/car-accidents/';
        }
        unset($item);

        $titles = array_column($this->select($items), 'title');

        $this->assertSame(['Car Accidents', 'Slip & Fall'], $titles);
    }

    /**
     * core/navigation blocks dropped into a panel keep their own nesting.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_navigation_blocks_inside_a_panel_keep_their_nesting(): void
    {
        $submenu = [
            'blockName' => 'core/navigation-submenu',
            'attrs' => ['label' => 'Injuries', 'url' => '/injuries/'],
            'innerBlocks' => [
                [
                    'blockName' => 'core/navigation-link',
                    'attrs' => ['label' => 'Burns', 'url' => '/injuries/burns/'],
                    'innerBlocks' => [],
                ],
            ],
        ];

        $blocks = [$this->mega_menu([$this->menu_item('Services', '/services/', [$this->mega_panel([$submenu])])])];

        $items = $this->walk($blocks);

        $this->assertSame(
            $this->find_by_title($items, 'Services')['id'],
            $this->find_by_title($items, 'Injuries')['parent_id'],
        );
        $this->assertSame(
            $this->find_by_title($items, 'Injuries')['id'],
            $this->find_by_title($items, 'Burns')['parent_id'],
        );
    }

    /**
     * A navigation block in a panel that references a saved menu contributes that menu's links as children.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_saved_navigation_menu_inside_a_panel_contributes_children(): void
    {
        $GLOBALS['bvi_test_blocks']['wp_block:77'] = [
            [
                'blockName' => 'core/navigation-link',
                'attrs' => ['label' => 'FAQ', 'url' => '/resources/faq/'],
                'innerBlocks' => [],
            ],
        ];
        $navigation = [
            'blockName' => 'core/navigation',
            'attrs' => ['ref' => 77],
            'innerHTML' => '',
            'innerBlocks' => [],
        ];

        $blocks = BlockScanner::resolve([
            $this->mega_menu([$this->menu_item('Resources', '/resources/', [$this->mega_panel([$navigation])])]),
        ]);

        $items = $this->walk($blocks);

        $this->assertSame(
            $this->find_by_title($items, 'Resources')['id'],
            $this->find_by_title($items, 'FAQ')['parent_id'],
        );
    }

    /**
     * Call-to-action links in a panel (tel:, mailto:, in-page anchors) are not related pages.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_non_navigational_panel_links_are_skipped(): void
    {
        $panel = $this->mega_panel([
            $this->paragraph('<a href="tel:5555555555">Call Now</a>'),
            $this->paragraph('<a href="mailto:hi@example.com">Email</a>'),
            $this->paragraph('<a href="#top">Top</a>'),
            $this->paragraph('<a href="/about/">About</a>'),
        ]);

        $items = $this->walk([$this->mega_menu([$this->menu_item('Firm', '/firm/', [$panel])])]);

        $this->assertSame(['Firm', 'About'], array_column($items, 'title'));
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
     * Invoke the private select_contextual_items() method.
     *
     * @since 5.0.0
     *
     * @param array $items Flat item list with `current` already marked.
     * @return array
     */
    private function select(array $items): array
    {
        $instance = (new ReflectionClass(RelatedLinks::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(RelatedLinks::class, 'select_contextual_items');
        $method->setAccessible(true);

        return $method->invoke($instance, $items);
    }

    /**
     * Build a top-level item whose panel holds child-page links the way "Add as mega panel" writes them.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function practice_areas_item(): array
    {
        $column = [
            'blockName' => 'core/column',
            'attrs' => [],
            'innerHTML' => "\n<div class=\"wp-block-column\">\n\n</div>\n",
            'innerBlocks' => [
                $this->paragraph('<a href="/practice-areas/car-accidents/">Car Accidents</a>'),
                $this->paragraph('<a href="/practice-areas/slip-and-fall/">Slip &amp; Fall</a>'),
            ],
        ];
        $columns = [
            'blockName' => 'core/columns',
            'attrs' => [],
            'innerHTML' => "\n<div class=\"wp-block-columns\">\n\n</div>\n",
            'innerBlocks' => [$column],
        ];

        return $this->menu_item('Practice Areas', '/practice-areas/', [$this->mega_panel([$columns])]);
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
