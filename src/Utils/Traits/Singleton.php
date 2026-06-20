<?php

namespace Bvi\Plugin\MegaMenu\Utils\Traits;

/**
 * Trait Singleton
 *
 * @package bvimegamenu/src
 */
trait Singleton
{
    /**
     * The single instance of the class.
     *
     * @var Class|null
     */
    private static $instances = [];

    /**
     * Get the single instance of the class.
     *
     * @return Class|null
     */
    public static function get_instance()
    {
        $class = get_called_class();

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();

            // call init method if it exists
            if (method_exists(self::$instances[$class], 'init')) {
                self::$instances[$class]->init();
            }
        }

        return self::$instances[$class];
    }

    /**
     * Prevent direct instantiation
     */
    public function __construct() {}

    /**
     * Prevent cloning
     */
    public function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {}
}
