<?php
namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\Dependency\CallbackDependency;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\NullCache;
use Yiisoft\Cache\Tests\TestCase;

class CallbackDependencyTest extends TestCase
{
    private function getDependency(callable $callback, $dependencyData): Dependency
    {
        $dependency = new CallbackDependency($callback);
        $this->setInaccessibleProperty($dependency, 'data',  $dependencyData);
        return $dependency;
    }

    private function assertDependencyChanged(Dependency $dependency): void
    {
        $cache = new NullCache();
        $this->assertTrue($dependency->isChanged($cache), 'Dependecy data was not changed');
    }

    private function assertDependencyNotChanged(Dependency $dependency): void
    {
        $cache = new NullCache();
        $this->assertFalse($dependency->isChanged($cache), 'Dependecy data was changed');
    }

    public function testPlainClosure(): void
    {
        $dependency = $this->getDependency(static function () {
            return true;
        }, true);

        $this->assertDependencyNotChanged($dependency);
    }

    public function testScopeWithObject(): void
    {
        $dataObject = new class {
            public $value = 42;
        };

        $dependency = $this->getDependency(static function () use ($dataObject) {
            return $dataObject->value;
        }, 42);

        $this->assertDependencyNotChanged($dependency);

        $dataObject->value = 13;

        $this->assertDependencyChanged($dependency);
    }
}
