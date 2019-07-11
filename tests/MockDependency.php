<?php
namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\Dependencies\Dependency;

/**
 * Class MockDependency
 */
class MockDependency extends Dependency
{
    protected function generateDependencyData($cache)
    {
        return $this->data;
    }
}
