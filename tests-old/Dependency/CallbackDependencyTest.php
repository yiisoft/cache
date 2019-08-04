<?php
namespace Yiisoft\CacheOld\Tests\Dependency;

use Yiisoft\CacheOld\Dependency\CallbackDependency;
use Yiisoft\CacheOld\Dependency\Dependency;

class CallbackDependencyTest extends DependencyTestCase
{
    private function createDependency(callable $callback, $dependencyData): Dependency
    {
        $dependency = new CallbackDependency($callback);
        $this->setInaccessibleProperty($dependency, 'data', $dependencyData);
        return $dependency;
    }

    public function testPlainClosure(): void
    {
        $dependency = $this->createDependency(static function () {
            return true;
        }, true);

        $this->assertDependencyNotChanged($dependency);
    }

    public function testScopeWithObject(): void
    {
        $dataObject = new class {
            public $value = 42;
        };

        $dependency = $this->createDependency(static function () use ($dataObject) {
            return $dataObject->value;
        }, 42);

        $this->assertDependencyNotChanged($dependency);

        $dataObject->value = 13;

        $this->assertDependencyChanged($dependency);
    }
}
