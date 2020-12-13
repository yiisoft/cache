<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use stdClass;
use Yiisoft\Cache\Dependency\AnyDependency;
use Yiisoft\Cache\Dependency\CallbackDependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;

final class AnyDependencyTest extends DependencyTestCase
{
    public function test(): void
    {
        $data1 = new class() {
            public int $data = 1;
        };

        $data2 = new class() {
            public int $data = 2;
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

    public function invalidDependenciesProvider(): array
    {
        return [
            'int' => [[1]],
            'float' => [[1.1]],
            'string' => [['a']],
            'array' => [[[]]],
            'bool' => [[true]],
            'null' => [[null]],
            'callable' => [[fn () => null]],
            'object' => [[new stdClass()]],
        ];
    }

    /**
     * @dataProvider invalidDependenciesProvider
     *
     * @param array $dependencies
     */
    public function testConstructorExceptions(array $dependencies): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AnyDependency($dependencies);
    }
}
