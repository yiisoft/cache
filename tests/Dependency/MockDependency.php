<?php

namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;

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
