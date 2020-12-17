<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\Dependency\CallbackDependency;
use Yiisoft\Cache\Dependency\Dependency;

final class CallbackDependencyTest extends DependencyTestCase
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
        $dataObject = new class() {
            public int $value = 42;
        };

        $dependency = $this->createDependency(static function () use ($dataObject) {
            return $dataObject->value;
        }, 42);

        $this->assertDependencyNotChanged($dependency);

        $dataObject->value = 13;

        $this->assertDependencyChanged($dependency);
    }
}
