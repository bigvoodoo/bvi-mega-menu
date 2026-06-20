<?php

namespace Bvi\Plugin\Sherloq\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Class PluginIntegrityTest
 *
 * Static structural tests that run without WordPress loaded:
 * verifies plugin header completeness, Composer autoload coverage,
 * and version consistency between the plugin header and composer.json.
 *
 * @since 1.0.1
 *
 * @package Bvi\Plugin\Sherloq\Tests
 */
class PluginIntegrityTest extends TestCase
{
    /** @var string */
    private const MAIN_FILE = 'sherloq-api-form-feeds.php';

    /**
     * The main plugin file must exist at the repository root.
     *
     * @since 1.0.1
     *
     * @return void
     */
    public function test_main_plugin_file_exists(): void
    {
        $path = BVI_PLUGIN_SHERLOQ_ROOT_DIR . '/' . self::MAIN_FILE;
        $this->assertFileExists($path, 'Main plugin file is missing.');
    }

    /**
     * The plugin header must define every field required.
     *
     * @since 1.0.1
     *
     * @return void
     */
    public function test_plugin_header_has_required_fields(): void
    {
        $header = $this->read_plugin_header();

        $required = [
            'Plugin Name',
            'Description',
            'Version',
            'Requires at least',
            'Requires PHP',
            'Author',
            'License',
            'Text Domain',
        ];

        foreach ($required as $field) {
            $this->assertArrayHasKey($field, $header, "Plugin header is missing required field: {$field}");
            $this->assertNotSame('', $header[$field], "Plugin header field is empty: {$field}");
        }
    }

    /**
     * The Requires PHP value in the plugin header must match the
     * php constraint in composer.json so runtime and dependency
     * resolution agree on the minimum supported PHP version.
     *
     * @since 1.0.1
     *
     * @return void
     */
    public function test_php_version_matches_composer_json(): void
    {
        $header = $this->read_plugin_header();
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $composer_json = file_get_contents(BVI_PLUGIN_SHERLOQ_ROOT_DIR . '/composer.json');
        $composer = json_decode($composer_json, true);

        $header_version = $header['Requires PHP'] ?? '';
        $composer_constraint = $composer['require']['php'] ?? '';

        $this->assertNotSame('', $header_version, 'Requires PHP missing from plugin header.');
        $this->assertNotSame('', $composer_constraint, 'php constraint missing from composer.json.');
        $this->assertStringContainsString(
            $header_version,
            $composer_constraint,
            "Plugin header Requires PHP ({$header_version}) does not appear in composer.json php constraint ({$composer_constraint}).",
        );
    }

    /**
     * The Composer autoloader must resolve every public plugin class
     * under the Bvi\Plugin\Sherloq\ namespace.
     *
     * @since 1.0.1
     *
     * @return void
     */
    public function test_autoloader_resolves_core_classes(): void
    {
        $classes = [
            'Bvi\\Plugin\\Sherloq\\Base',
            'Bvi\\Plugin\\Sherloq\\Main',
            'Bvi\\Plugin\\Sherloq\\Admin',
            'Bvi\\Plugin\\Sherloq\\Migration',
            'Bvi\\Plugin\\Sherloq\\Admin\\Settings',
            'Bvi\\Plugin\\Sherloq\\Admin\\Config\\Config',
            'Bvi\\Plugin\\Sherloq\\Admin\\Config\\Feature',
            'Bvi\\Plugin\\Sherloq\\Admin\\Config\\General',
            'Bvi\\Plugin\\Sherloq\\Admin\\Config\\Backleads',
            'Bvi\\Plugin\\Sherloq\\Admin\\Config\\Logs',
            'Bvi\\Plugin\\Sherloq\\Forms\\Forms',
            'Bvi\\Plugin\\Sherloq\\Forms\\ContactForm7',
            'Bvi\\Plugin\\Sherloq\\Forms\\GravityForms',
            'Bvi\\Plugin\\Sherloq\\Interfaces\\Form',
            'Bvi\\Plugin\\Sherloq\\Sherloq\\Api',
            'Bvi\\Plugin\\Sherloq\\Sherloq\\Backleads',
            'Bvi\\Plugin\\Sherloq\\Sherloq\\Logs',
            'Bvi\\Plugin\\Sherloq\\Sherloq\\LogsTable',
            'Bvi\\Plugin\\Sherloq\\Sherloq\\Send',
            'Bvi\\Plugin\\Sherloq\\Sherloq\\Tracking',
        ];

        foreach ($classes as $class) {
            $this->assertTrue(
                class_exists($class) || trait_exists($class) || interface_exists($class),
                "Autoloader failed to resolve: {$class}",
            );
        }
    }

    /**
     * Parse the plugin header block from the main plugin file.
     *
     * @since 1.0.1
     *
     * @return array<string, string> Map of header field name to value.
     */
    private function read_plugin_header(): array
    {
        $path = BVI_PLUGIN_SHERLOQ_ROOT_DIR . '/' . self::MAIN_FILE;
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $contents = file_get_contents($path);
        $this->assertIsString($contents, 'Could not read plugin main file.');

        $header = [];

        if (preg_match_all('/^\s*\*\s*([A-Za-z][A-Za-z0-9 ]+):\s*(.+?)\s*$/m', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $header[trim($m[1])] = trim($m[2]);
            }
        }

        return $header;
    }
}
