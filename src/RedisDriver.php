<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace BY\HyperfCache;

use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\Packer\PhpSerializerPacker;
use Psr\Container\ContainerInterface;
use Hyperf\Cache\Driver\RedisDriver as OfficialRedisDriver;

class RedisDriver extends OfficialRedisDriver
{

    public function __construct(ContainerInterface $container, array $config)
    {
        $this->container = $container;
        $this->config = $config;
        $this->prefix = config('app_name') . ':' . ($config['prefix'] ?? 'cache:');

        $packerClass = $config['packer'] ?? PhpSerializerPacker::class;
        $this->packer = $container->get($packerClass);
        //配置pool    config/autoload/redis.php
        if (!isset($config['pool'])) {
            $config['pool'] = 'default';
        }
        $this->redis = $container->get(RedisFactory::class)->get($config['pool']);
    }
}
