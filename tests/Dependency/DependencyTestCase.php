<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\NullCache;
use Yiisoft\Cache\Tests\TestCase;

abstract class DependencyTestCase extends TestCase
{
    private CacheInterface $cache;

    protected function getCache(): CacheInterface
    {
        return $this->cache ??= $this->createCache();
    }

    protected function createCache(): CacheInterface
    {
        return new Cache(new NullCache());
    }

    protected function assertDependencyChanged(Dependency $dependency): void
    {
        $this->assertTrue($dependency->isChanged($this->getCache()), 'Dependency data was not changed');
    }

    protected function assertDependencyNotChanged(Dependency $dependency): void
    {
        $this->assertFalse($dependency->isChanged($this->getCache()), 'Dependency data was changed');
    }

    protected function createMockDependency(): Dependency
    {
        return new class () extends Dependency {
            protected function generateDependencyData(CacheInterface $cache)
            {
                return $this->data;
            }
        };
    }
}
