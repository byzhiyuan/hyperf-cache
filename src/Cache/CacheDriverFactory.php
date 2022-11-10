<?php
declare(strict_types=1);

namespace BY\HyperfCache\Cache;

use BY\HyperfCache\Cache\Drivers\CacheDriver;
use BY\HyperfCache\Cache\Drivers\SwooleTableDriver;
use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use BY\HyperfCache\Exceptions\BYCacheException;

class CacheDriverFactory
{
    protected $config;

    protected $container;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function get($name)
    {
        $drivers = $this->config->get('byzhiyuan_cache.drivers');

        $driverConfig = $drivers[$name] ?? NULL;

        if (!$driverConfig) {
            throw new BYCacheException("not found [$name] drivers in byzhiyuan_cache.drivers");
        }

        $driverName = $driverConfig['driver'];
        $driver     = $this->container[$driverName][$name] ?? NULL;
        if (!$driver) {
            $driver = $this->container[$driverName][$name] = $this->$driverName($driverConfig);
        }

        return $driver;
    }


    public function getDrivers($driver = '')
    {
        return $driver ? ($this->container[$driver] ?? []) : $this->container;
    }

    protected function swooleTable($options)
    {
        return make(SwooleTableDriver::class, [$options]);
    }

    protected function cache($options)
    {
        return make(CacheDriver::class, [$options]);
    }
}
