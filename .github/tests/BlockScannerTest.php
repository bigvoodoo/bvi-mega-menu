<?php

namespace Bvi\Plugin\MegaMenu\Tests;

use Bvi\Plugin\MegaMenu\Blocks\Menus\BlockScanner;
use PHPUnit\Framework\TestCase;

/**
 * Tests for pattern expansion in BlockScanner.
 *
 * @package Bvi\Plugin\MegaMenu\Tests
 */
class BlockScannerTest extends TestCase
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
        $GLOBALS['bvi_test_blocks'] = [];
        $GLOBALS['bvi_test_patterns'] = [];
        $GLOBALS['bvi_test_filters'] = [];
        $GLOBALS['_wp_current_template_content'] = '';
    }

    /**
     * The active template's blocks are searched.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_collect_page_blocks_reads_the_active_template(): void
    {
        $GLOBALS['_wp_current_template_content'] = 'template';
        $GLOBALS['bvi_test_blocks']['template'] = [$this->block('bvi/mega-menu')];

        $names = $this->flatten_names(BlockScanner::collect_page_blocks());

        $this->assertContains('bvi/mega-menu', $names);
    }

    /**
     * A template part nothing on this page references is not searched.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_collect_page_blocks_ignores_unreferenced_template_parts(): void
    {
        $GLOBALS['_wp_current_template_content'] = 'template';
        $GLOBALS['bvi_test_blocks']['template'] = [$this->block('core/group')];
        $GLOBALS['bvi_test_blocks']['orphan-part'] = [$this->block('bvi/mega-menu')];

        $names = $this->flatten_names(BlockScanner::collect_page_blocks());

        $this->assertNotContains('bvi/mega-menu', $names);
    }

    /**
     * The page-blocks filter can append a tree, and patterns inside it still expand.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_page_blocks_filter_can_append_trees(): void
    {
        $GLOBALS['_wp_current_template_content'] = 'template';
        $GLOBALS['bvi_test_blocks']['template'] = [$this->block('core/group')];
        $GLOBALS['bvi_test_blocks']['wp_block:9'] = [$this->block('bvi/mega-menu')];

        $extra = $this->block('core/block', ['ref' => 9]);
        $GLOBALS['bvi_test_filters']['bvi_mega_menu_page_blocks'][] = function (array $blocks) use ($extra): array {
            $blocks[] = $extra;
            return $blocks;
        };

        $names = $this->flatten_names(BlockScanner::collect_page_blocks());

        $this->assertContains('bvi/mega-menu', $names);
    }

    /**
     * A synced pattern's wp_block content is expanded into inner blocks.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_resolve_expands_synced_pattern(): void
    {
        $GLOBALS['bvi_test_blocks']['wp_block:42'] = [$this->block('bvi/mega-menu')];

        $resolved = BlockScanner::resolve([$this->block('core/block', ['ref' => 42])]);

        $this->assertContains('bvi/mega-menu', $this->flatten_names($resolved));
    }

    /**
     * An unsynced pattern's registered content is expanded into inner blocks.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_resolve_expands_unsynced_pattern(): void
    {
        $GLOBALS['bvi_test_patterns']['bvi/header'] = 'pattern:bvi/header';
        $GLOBALS['bvi_test_blocks']['pattern:bvi/header'] = [$this->block('bvi/mega-menu')];

        $resolved = BlockScanner::resolve([$this->block('core/pattern', ['slug' => 'bvi/header'])]);

        $this->assertContains('bvi/mega-menu', $this->flatten_names($resolved));
    }

    /**
     * A pattern nested inside another pattern is reached.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_resolve_expands_nested_patterns(): void
    {
        $GLOBALS['bvi_test_blocks']['wp_block:1'] = [$this->block('core/block', ['ref' => 2])];
        $GLOBALS['bvi_test_blocks']['wp_block:2'] = [$this->block('bvi/mega-menu')];

        $resolved = BlockScanner::resolve([$this->block('core/block', ['ref' => 1])]);

        $this->assertContains('bvi/mega-menu', $this->flatten_names($resolved));
    }

    /**
     * A self-referential pattern terminates instead of recursing forever.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_resolve_stops_on_self_referential_pattern(): void
    {
        $GLOBALS['bvi_test_blocks']['wp_block:7'] = [
            $this->block('core/block', ['ref' => 7]),
            $this->block('bvi/mega-menu'),
        ];

        $resolved = BlockScanner::resolve([$this->block('core/block', ['ref' => 7])]);

        $this->assertContains('bvi/mega-menu', $this->flatten_names($resolved));
    }

    /**
     * A two-pattern cycle terminates instead of recursing forever.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_resolve_stops_on_pattern_cycle(): void
    {
        $GLOBALS['bvi_test_blocks']['wp_block:1'] = [$this->block('core/block', ['ref' => 2])];
        $GLOBALS['bvi_test_blocks']['wp_block:2'] = [$this->block('core/block', ['ref' => 1])];

        $resolved = BlockScanner::resolve([$this->block('core/block', ['ref' => 1])]);

        $this->assertIsArray($resolved);
    }

    /**
     * Expansion stops once the depth cap is reached.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_resolve_honours_depth_cap(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $GLOBALS['bvi_test_blocks']['wp_block:' . $i] = [$this->block('core/block', ['ref' => $i + 1])];
        }
        $GLOBALS['bvi_test_blocks']['wp_block:15'] = [$this->block('bvi/mega-menu')];

        $resolved = BlockScanner::resolve([$this->block('core/block', ['ref' => 1])]);

        $this->assertNotContains('bvi/mega-menu', $this->flatten_names($resolved));
    }

    /**
     * A block with no pattern involvement is returned untouched.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function test_resolve_leaves_ordinary_blocks_alone(): void
    {
        $tree = [$this->block('core/group', [], [$this->block('bvi/mega-menu')])];

        $this->assertSame($tree, BlockScanner::resolve($tree));
    }

    /**
     * Build a parsed-block array entry.
     *
     * @since 5.0.0
     *
     * @param string $name  Block name.
     * @param array  $attrs Block attributes.
     * @param array  $inner Inner blocks.
     * @return array
     */
    private function block(string $name, array $attrs = [], array $inner = []): array
    {
        return ['blockName' => $name, 'attrs' => $attrs, 'innerBlocks' => $inner];
    }

    /**
     * Collect every block name present anywhere in a resolved tree.
     *
     * @since 5.0.0
     *
     * @param array $blocks Parsed block tree.
     * @return array
     */
    private function flatten_names(array $blocks): array
    {
        $names = [];

        foreach ($blocks as $block) {
            $names[] = $block['blockName'] ?? '';
            if (!empty($block['innerBlocks'])) {
                $names = array_merge($names, $this->flatten_names($block['innerBlocks']));
            }
        }

        return $names;
    }
}
