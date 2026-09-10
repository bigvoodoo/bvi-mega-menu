<?php

namespace Bvi\Plugin\MegaMenu\Utils;

/**
 * Class Discovery
 *
 * @package bvimegamenu/src
 */
class Discovery
{
    /**
     * Discovers classes within the specified directory that implement a given interface.
     *
     * @since 0.1.0
     *
     * @param string $directory Absolute path to the directory to scan.
     * @param string $primary_namespace The root namespace prefix (e.g. 'Bvi\Theme\Minimal\').
     * @param string $class_namespace The sub-namespace relative to root (e.g. 'Admin\Features\').
     * @param string $interface_path Fully qualified interface name to filter by.
     *
     * @return array Array of instantiated class objects.
     */
    public static function discover($directory, $primary_namespace, $class_namespace, $interface_path)
    {
        $discovered = [];

        if (!is_dir($directory)) {
            return $discovered;
        }

        // build the full interface FQCN
        $interface_fqcn = rtrim($interface_path, '\\');

        if (!interface_exists($interface_fqcn)) {
            return $discovered;
        }

        // create the full namespace for discovered classes
        $namespace = $primary_namespace . $class_namespace;

        foreach (glob($directory . '/*.php') as $file) {
            $class_name = $namespace . basename($file, '.php');

            if (!class_exists($class_name)) {
                continue;
            }

            $ref = new \ReflectionClass($class_name);

            // skip abstract classes and interfaces
            if ($ref->isAbstract() || $ref->isInterface()) {
                continue;
            }

            if (!$ref->implementsInterface($interface_fqcn)) {
                continue;
            }

            $discovered[] = $ref->newInstance();
        }

        return $discovered;
    }
}
