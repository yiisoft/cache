<?php
namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\Dependency\AnyDependency;
use Yiisoft\Cache\Dependency\CallbackDependency;

class AnyDependencyTest extends DependencyTestCase
{
    public function test(): void
    {
        $data1 = new class {
            public $data = 1;
        };

        $data2 = new class {
            public $data = 2;
        };

        $dependency1 = new CallbackDependency(static function () use ($data1) {
            return $data1->data;
        });

        $dependency2 = new CallbackDependency(static function () use ($data2) {
            return $data2->data;
        });

        $anyDependency = new AnyDependency([$dependency1, $dependency2]);
        $anyDependency->evaluateDependency($this->getCache());

        $this->assertDependencyNotChanged($anyDependency);

        $data2->data = 42;

        $this->assertDependencyChanged($anyDependency);
    }
}
