<?php

namespace BY\HyperfCache\Collectors;


use Hyperf\Di\MetadataCollector;

class CacheCollector extends MetadataCollector
{

    public static function clear(?string $className = null): void
    {
        if (!$className) {
            static::$container = [];
        } else {
            foreach (static::$container as $listener => $targets) {
                if (isset($targets[$className])) {
                    unset(static::$container[$listener][$className]);
                }
            }
        }
    }

    public static function appendListener($listener, array $target)
    {
        $class                                                                                 = $target['class'];
        $method                                                                                = $target['method'];
        static::$container[$listener][$class][$method . ':' . $target['annotation']['driver']] = $target;
    }

    public static function getListener($listener)
    {
        return static::$container[$listener] ?? [];
    }
}
