<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\CallbackDependency;
use Yiisoft\Cache\Dependency\Dependency;

use function get_class;

final class CallbackDependencyTest extends DependencyTestCase
{
    public function testPlainClosure(): void
    {
        $dependency = $this->createDependency(static fn () => true, true);

        $this->assertDependencyNotChanged($dependency);
    }

    public function testClosureWithCache(): void
    {
        $dependency = $this->createDependency(static fn (CacheInterface $cache) => get_class($cache), Cache::class);

        $this->assertDependencyNotChanged($dependency);
    }

    public function testScopeWithObject(): void
    {
        $dataObject = new class () {
            public string $value = 'value';
        };

        $dependency = $this->createDependency(static fn () => $dataObject->value, 'value');

        $this->assertDependencyNotChanged($dependency);

        $dataObject->value = 'new-value';

        $this->assertDependencyChanged($dependency);
    }

    private function createDependency(callable $callback, $dependencyData): Dependency
    {
        $dependency = new CallbackDependency($callback);
        $this->setInaccessibleProperty($dependency, 'data', $dependencyData);
        return $dependency;
    }
}
