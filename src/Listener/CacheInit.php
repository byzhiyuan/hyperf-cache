<?php

declare(strict_types=1);

namespace BY\HyperfCache\Listener;

use BY\HyperfCache\Annotations\CacheAnnotation;
use BY\HyperfServiceCommand\Event\FrameworkStartsEvent;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Memory\TableManager;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Swoole\Table;
use BY\HyperfCache\Collectors\CacheCollector;

/**
 * @Listener
 */
class CacheInit implements ListenerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    const SUB_CACHE_NAME = 'subCache';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function listen(): array
    {
        return [
            FrameworkStartsEvent::class
        ];
    }

    public function process(object $event)
    {
        $config = $this->container->get(ConfigInterface::class);

        if (!($config->get('byzhiyuan_cache.enable') ?: false)) {
            return;
        }

        $this->swooleTable($config);
        $this->annotationCollector($config);
    }

    protected function swooleTable($config)
    {
        $drivers = $config->get('byzhiyuan_cache.drivers') ?: [];
        foreach ($drivers as $driver) {
            if ($driver['driver'] == 'swooleTable') {
                $table = TableManager::initialize($driver['table_name'] ?? 'swoole_table', (int)$driver['size']);
                $table->column('values', Table::TYPE_STRING, (int)$driver['column_size']);
                $table->column('expire_at', Table::TYPE_INT, 8);
                $table->create();
            }
        }
    }

    protected function annotationCollector()
    {
        $annotations = AnnotationCollector::getMethodByAnnotation(CacheAnnotation::class);

        foreach ($annotations as $annotation) {
            $cacheAnnotation = $annotation['annotation'];
            $drivers         = $cacheAnnotation->getDrivers();
            foreach (array_reverse($drivers) as $driver) {
                if ($driver['listener']) {
                    CacheCollector::appendListener($driver['listener'], [
                        'class'      => $annotation['class'],
                        'method'     => $annotation['method'],
                        'annotation' => $driver,
                    ]);
                }
            }
        }
    }
}
