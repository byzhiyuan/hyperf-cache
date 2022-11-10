<?php

declare(strict_types=1);

namespace BY\HyperfCache\Annotations;

use BY\HyperfCache\Collectors\CacheCollector;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class CacheAnnotation extends AbstractAnnotation
{
    public $ttl = 0;

    public $cacheEmpty = true;

    public $key = [];

    public $driver = "cache";

    public $listener = "";

    public $drivers = [];

    public function __construct($value = null)
    {
        parent::__construct($value);
    }

    public function getDrivers()
    {
        $arguments = $this->getDefaultArguments();

        $drivers = [];

        if ($this->drivers) {
            foreach ($this->parseDrivers($this->drivers) as $driver) {
                $drivers[] = array_merge($arguments, $driver);
            }
        } else {
            $drivers[] = $arguments;
        }

        return $drivers;
    }

    public function getDefaultArguments()
    {
        $ps = $this->toArray();
        unset($ps['drivers']);

        return $ps;
    }

    private function parseDrivers($drivers)
    {
        if (is_array($drivers)) {
            return $drivers;
        } else if (is_string($drivers) && strpos('config.', $drivers) !== 0) {
            return config(substr($drivers, 7));
        }

        throw new \Exception('drivers 配置错误', 500);
    }
}
