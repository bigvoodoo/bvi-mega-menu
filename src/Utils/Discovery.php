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
     * Scans PHP files in the directory, builds fully qualified class names using
     * the provided namespace segments, and returns instances of all non-abstract
     * classes that implement the specified interface.
     *
     * @since 0.1.0
     *
     * @param string $directory        Absolute path to the directory to scan.
     * @param string $primary_namespace The root namespace prefix (e.g. 'Bvi\Theme\Minimal\').
     * @param string $class_namespace   The sub-namespace relative to root (e.g. 'Admin\Features\').
     * @param string $interface         Fully qualified interface name to filter by.
     *
     * @return array Array of instantiated class objects.
     */
    public static function discover($directory, $primary_namespace, $class_namespace, $interface)
    {
        $instances = [];

        // create the full namespace
        $namespace = $primary_namespace . $class_namespace;

        foreach (glob($directory . '/*.php') as $file) {
            $class_name = $namespace . basename($file, '.php');

            // if the class doesnt exist, skip
            if (!class_exists($class_name)) {
                continue;
            }

            // if the class is abstract or an interface, skip
            $ref = new \ReflectionClass($class_name);
            if ($ref->isAbstract() || $ref->isInterface()) {
                continue;
            }

            // if the class implements the required interface, instantiate it
            if ($ref->implementsInterface($interface)) {
                $instances[] = $ref->newInstance();
            }
        }

        return $instances;
    }
}
