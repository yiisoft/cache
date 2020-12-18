<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use stdClass;
use Yiisoft\Cache\Dependency\ValueDependency;

final class ValueDependencyTest extends DependencyTestCase
{
    public function valueDataProvider(): array
    {
        return [
            'int' => [1],
            'float' => [1.1],
            'string' => ['a'],
            'array' => [[]],
            'bool' => [true],
            'null' => [null],
            'callable' => [fn () => null],
            'object' => [new stdClass()],
        ];
    }

    /**
     * @dataProvider valueDataProvider
     *
     * @param mixed $value
     */
    public function testMatchingValue($value): void
    {
        $dependency = new ValueDependency($value);

        $this->setInaccessibleProperty($dependency, 'data', $value);
        $this->assertDependencyNotChanged($dependency);

        $this->setInaccessibleProperty($dependency, 'data', false);
        $this->assertDependencyChanged($dependency);
    }
}
