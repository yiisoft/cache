<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Yiisoft\Cache\Dependency\ValueDependency;

final class ValueDependencyTest extends DependencyTestCase
{
    public static function valueDataProvider(): array
    {
        return [
            'int' => [1],
            'float' => [1.1],
            'string' => ['a'],
            'array' => [[]],
            'bool' => [true],
            'null' => [null],
            'callable' => [fn() => null],
            'object' => [new stdClass()],
        ];
    }

    #[DataProvider('valueDataProvider')]
    public function testMatchingValue(mixed $value): void
    {
        $dependency = new ValueDependency($value);

        $this->setInaccessibleProperty($dependency, 'data', $value);
        $this->assertDependencyNotChanged($dependency);

        $this->setInaccessibleProperty($dependency, 'data', false);
        $this->assertDependencyChanged($dependency);
    }
}
