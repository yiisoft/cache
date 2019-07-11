<?php
namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\Dependencies\Dependency;

/**
 * Class MockDependency.
 *
 * @author Boudewijn Vahrmeijer <vahrmeijer@gmail.com>
 */
class MockDependency extends Dependency
{
    protected function generateDependencyData($cache)
    {
        return $this->data;
    }
}
