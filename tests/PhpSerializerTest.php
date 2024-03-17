<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Db\Tests;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Cache\Serializer\PhpSerializer;

use const PHP_INT_MAX;

final class PhpSerializerTest extends TestCase
{
    public static function serializeDataProvider(): array
    {
        $object = new stdClass();
        $object->foo = 'bar';
        $object->bar = 'foo';

        return [
            [
                true,
            ],
            [
                false,
            ],
            [
                null,
            ],
            [
                PHP_INT_MAX,
            ],
            [
                M_PI,
            ],
            [
                'string',
            ],
            [
                [
                    'key' => 'value',
                    'foo' => 'bar',
                    'true' => true,
                    'false' => false,
                    'array' => (array) $object,
                    'int' => 8_000,
                ],
            ],
            [
                $object,
            ],
        ];
    }

    /**
     * @dataProvider serializeDataProvider
     */
    public function testSerialize(mixed $data): void
    {
        $serializer = new PhpSerializer();
        $result = $serializer->serialize($data);

        if (is_object($data)) {
            self::assertEquals($data, $serializer->unserialize($result));
        } else {
            self::assertSame($data, $serializer->unserialize($result));
        }
    }
}
