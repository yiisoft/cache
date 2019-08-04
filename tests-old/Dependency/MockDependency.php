<?php
namespace Yiisoft\CacheOld\Tests\Dependency;

use Yiisoft\CacheOld\CacheInterface;
use Yiisoft\CacheOld\Dependency\Dependency;

/**
 * Class MockDependency
 */
class MockDependency extends Dependency
{
    protected function generateDependencyData(CacheInterface $cache)
    {
        return $this->data;
    }
}
