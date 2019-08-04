<?php
namespace Yiisoft\CacheOld\Tests\Dependency;

use Yiisoft\CacheOld\CacheInterface;
use Yiisoft\CacheOld\Dependency\Dependency;
use Yiisoft\CacheOld\NullCache;
use Yiisoft\CacheOld\Tests\TestCase;

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
        $this->assertTrue($dependency->isChanged($this->getCache()), 'Dependecy data was not changed');
    }

    protected function assertDependencyNotChanged(Dependency $dependency): void
    {
        $this->assertFalse($dependency->isChanged($this->getCache()), 'Dependecy data was changed');
    }
}
