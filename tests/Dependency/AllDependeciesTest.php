<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\Dependency\AllDependencies;
use Yiisoft\Cache\Dependency\CallbackDependency;

class AllDependeciesTest extends DependencyTestCase
{
    public function test(): void
    {
        $data1 = new class() {
            public $data = 1;
        };

        $data2 = new class() {
            public $data = 2;
        };

        $dependency1 = new CallbackDependency(static function () use ($data1) {
            return $data1->data;
        });

        $dependency2 = new CallbackDependency(static function () use ($data2) {
            return $data2->data;
        });

        $anyDependency = new AllDependencies([$dependency1, $dependency2]);
        $anyDependency->evaluateDependency($this->getCache());

        $this->assertDependencyNotChanged($anyDependency);

        $data1->data = 42;

        $this->assertDependencyNotChanged($anyDependency);

        $data2->data = 42;

        $this->assertDependencyChanged($anyDependency);
    }
}
