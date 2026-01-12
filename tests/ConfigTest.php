<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Cache\CacheInterface;

use function dirname;

final class ConfigTest extends TestCase
{
    public function testBase(): void
    {
        $container = $this->createContainer();

        $yiiCache = $container->get(CacheInterface::class);
        $psrCache = $container->get(\Psr\SimpleCache\CacheInterface::class);

        $this->assertInstanceOf(Cache::class, $yiiCache);
        $this->assertInstanceOf(ArrayCache::class, $psrCache);
    }

    private function createContainer(): Container
    {
        return new Container(
            ContainerConfig::create()->withDefinitions(
                $this->getDiConfig(),
            ),
        );
    }

    private function getDiConfig(): array
    {
        return require dirname(__DIR__) . '/config/di.php';
    }
}
