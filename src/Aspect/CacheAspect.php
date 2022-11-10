<?php

declare(strict_types=1);

namespace BY\HyperfCache\Aspect;

use BY\HyperfCache\Annotations\CacheAnnotation;
use BY\HyperfCache\Cache\CacheDriverFactory;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Psr\Log\LoggerInterface;

/**
 * @Aspect
 */
class CacheAspect extends AbstractAspect
{
    protected $container;

    /**
     * @var LoggerInterface;
     */
    protected $logger;

    public $classes = [
    ];


    public $annotations = [
        CacheAnnotation::class
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger    = $container->get(LoggerFactory::class)->get('cache-aspect');
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $config = $this->container->get(ConfigInterface::class);
        $enable = $config->get('byzhiyuan_cache.enable') ? true : false;

        if (!$enable) {
            return $proceedingJoinPoint->process();
        }

        $annotationData = $proceedingJoinPoint->getAnnotationMetadata();

        $cacheAnnotation = $annotationData->method[CacheAnnotation::class];

        $arguments = $proceedingJoinPoint->getArguments();

        $method = $proceedingJoinPoint->getReflectMethod();

        $parameters = $method->getParameters();

        $arguments = $this->buildArguments($parameters, $arguments);

        $drivers = $cacheAnnotation->getDrivers();

        $driverFactory = $this->container->get(CacheDriverFactory::class);

        $pipeline = function () use ($proceedingJoinPoint) {
            $result = $proceedingJoinPoint->process();
            return $result;
        };

        $options = $this->parseCacheOptions($arguments['cacheOptions'] ?? []);

        foreach (array_reverse($drivers) as $driver) {
            $ttl = $driver['ttl'];
            $argumentStrVal = empty($driver['key']) ? '' : ':' . $this->parseKey($driver['key'], $arguments);
            $key    = "$proceedingJoinPoint->className:$proceedingJoinPoint->methodName" . $argumentStrVal;
            $driver = $driverFactory->get($driver['driver'], $driver);

            $pipeline = function () use ($pipeline, $driver, $key, $options, $ttl) {
                $res = !$options['cacheFlush'] ? $driver->get($key) : NULL;
                if (is_null($res)) {
                    $res = $pipeline();
                    if ($res || $driver->cacheEmpty() == true) {
                        $driver->set($key, $res, $ttl);
                    }
                }

                return $res;
            };
        }

        return $pipeline();

    }

    protected function buildArguments(array $parameters, array $arguments)
    {
        $s = [];

        foreach ($parameters as $index => $parameter) {
            $parameterName     = $parameter->getName();
            $s[$parameterName] = $arguments[$index];
        }
        return $s;
    }

    protected function parseKey(array $cacheKey, array $arguments)
    {
        $s = [];
        foreach ($cacheKey as $key) {
            $s[] = $arguments[$key];
        }

        return implode(':', $s);
    }

    private function parseCacheOptions(array $options = [])
    {
        $options['cacheFlush'] = $options['cacheFlush'] ?? false;
        return $options;
    }
}
