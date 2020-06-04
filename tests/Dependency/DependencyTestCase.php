<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\NullCache;
use Yiisoft\Cache\Tests\TestCase;

abstract class DependencyTestCase extends TestCase
{
    private $cache;

    protected function getCache(): CacheInterface
    {
        return $this->cache ?? $this->cache = $this->createCache();
    }

    protected function createCache(): CacheInterface
    {
        return new NullCache();
    }

    protected function assertDependencyChanged(Dependency $dependency): void
    {
        $this->assertTrue($dependency->isChanged($this->getCache()), 'Dependency data was not changed');
    }

    protected function assertDependencyNotChanged(Dependency $dependency): void
    {
        $this->assertFalse($dependency->isChanged($this->getCache()), 'Dependency data was changed');
    }
}
