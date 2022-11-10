<?php

declare(strict_types=1);

namespace BY\HyperfCache\Listener;

use BY\HyperfCache\Cache\CacheDriverFactory;
use BY\HyperfCache\Collectors\CacheCollector;
use BY\HyperfCache\Event\CacheEvent;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Event\Annotation\Listener;


/**
 * @Listener
 *
 * Class CacheDelListener
 * @package BY\HyperfCache\Listener
 */
class CacheDelListener implements ListenerInterface
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function listen(): array
    {
        return [
            CacheEvent::class
        ];
    }

    public function process(object $event)
    {
        $listener = $event->listener;

        $arguments = $event->arguments;

        $annotationClassContainers = CacheCollector::getListener($listener);

        $driverFactory = $this->container->get(CacheDriverFactory::class);

        foreach ($annotationClassContainers as $driverContainer) {
            foreach ($driverContainer as $annotation) {
                $argumentStrVal = empty($annotation['annotation']['key']) ? '' : ':' . $this->parseKey($annotation['annotation']['key'], $arguments);
                $key = $annotation['class'] . ':' . $annotation['method'] . $argumentStrVal;
                $driver = $driverFactory->get($annotation['annotation']['driver']);
                $driver->del($key);
            }
        }
    }

    protected function parseKey(array $cacheKey, array $arguments)
    {
        $s = [];
        foreach ($cacheKey as $key) {
            $s[] = $arguments[$key];
        }

        return implode(':', $s);
    }
}