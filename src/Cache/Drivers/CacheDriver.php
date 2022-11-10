<?php
declare(strict_types=1);

namespace BY\HyperfCache\Cache\Drivers;


use Hyperf\Cache\CacheManager;
use Psr\SimpleCache\CacheInterface;

class CacheDriver
{
    protected $options;
    /**
     * @var CacheInterface
     */
    protected $cache;

    public function __construct(array $options, CacheManager $manager)
    {
        $this->options = $options;
        $this->cache   = $manager->getDriver($options['connection'] ?? 'default');
    }

    public function get($key)
    {
        $values = $this->cache->get($key);
        return $values ? json_decode($values, true) : NULL;
    }

    public function set($key, $value, $ttl = 10)
    {
        return $this->cache->set($key, json_encode($value), $ttl > 0 ? $ttl : NULL);
    }

    public function del($key)
    {
        return $this->cache->delete($key);
    }

    public function clear($key)
    {
        return $this->cache->clear($key);
    }

    public function cacheEmpty()
    {
        return $this->options['cacheEmpty'] ?? false;
    }

    public function gc()
    {

    }
}
