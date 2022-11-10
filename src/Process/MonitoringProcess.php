<?php

declare(strict_types=1);

namespace BY\HyperfCache\Process;

use BY\HyperfCache\Cache\CacheDriverFactory;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;

/**
 * @Process(name="MonitoringProcess", num=1, enableCoroutine=true)
 */
class MonitoringProcess extends AbstractProcess
{
    /**
     * @Inject()
     * @var ContainerInterface
     */

    protected $container;

    public function handle(): void
    {
        $factory = $this->container->get(LoggerFactory::class);
        $logger  = $factory->get('monitoring');

        $cacheFactory = $this->container->get(CacheDriverFactory::class);

        $drivers = $this->container->get(ConfigInterface::class)->get('byzhiyuan_cache.drivers');


        $s = [];
        foreach ($drivers as $name => $driver) {
            if ($driver['driver'] == 'swooleTable') {
                $s[] = $name;
            }
        }
        $gcNum = 30000;
        while (1) {
            foreach ($s as $name) {
                $table = $cacheFactory->get($name);
                $add   = $gcNum;
                //每3秒回收一次 每次回收30000条, 如果回收30000条后还有未完全回收完成 则在进行一次回收
                if ($num = $table->gc($gcNum)) {
                    $add += $gcNum;
                    $num += $table->gc($gcNum);
                }
                $gc = $add - $num;


                $logger->info('gc:success', [
                    'driver'            => $name,
                    'memory_size'       => $table->memorySize(),
                    'memory_size_human' => number_format($table->memorySize() / 1024 / 1024, 2) . 'M',
                    'gc'                => $gc,
                ]);
            }


            \Swoole\Coroutine\System::sleep(3);
        }
    }

    public function isEnable($server): bool
    {
        return $this->container->get(ConfigInterface::class)->get('byzhiyuan_cache.enable') ?: false;
    }
}
