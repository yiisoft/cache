<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\CacheKeyNormalizer;

use function fclose;
use function fopen;
use function json_encode;
use function md5;

final class CacheKeyNormalizerTest extends TestCase
{
    public static function keyDataProvider(): array
    {
        return [
            'int' => [1, '1'],
            'string' => ['asd123', 'asd123'],
            'string-md5' => [$string = 'asd_123-{z4x}', md5($string)],
            'null' => [null, self::encode(null)],
            'bool' => [true, self::encode(true)],
            'float' => [$float = 1.1, self::encode($float)],
            'array' => [
                $array = [1, 'key' => 'value', 'nested' => [1, 2, 'asd_123-{z4x}']],
                self::encode($array),
            ],
            'empty-array' => [$array = [], self::encode($array)],
            'object' => [
                $object = new class () {
                    public string $name = 'object';
                },
                self::encode($object),
            ],
            'empty-object' => [$object = new stdClass(), self::encode($object)],
            'callable' => [$callable = fn () => null, self::encode($callable)],
        ];
    }

    #[DataProvider('keyDataProvider')]
    public function testNormalize(mixed $key, string $excepted): void
    {
        $this->assertSame($excepted, CacheKeyNormalizer::normalize($key));
    }

    public function testNormalizeThrowExceptionForInvalidKey(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->expectException(InvalidArgumentException::class);
        CacheKeyNormalizer::normalize($resource);
        fclose($resource);
    }

    private static function encode(mixed $key): string
    {
        return md5(json_encode($key, JSON_THROW_ON_ERROR));
    }
}
