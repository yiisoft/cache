<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use stdClass;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\CacheKeyNormalizer;

use function fclose;
use function fopen;
use function json_encode;
use function md5;

final class CacheKeyNormalizerTest extends TestCase
{
    public function keyDataProvider(): array
    {
        return [
            'int' => [1, '1'],
            'string' => ['asd123', 'asd123'],
            'string-md5' => [$string = 'asd_123-{z4x}', md5($string)],
            'null' => [null, $this->encode(null)],
            'bool' => [true, $this->encode(true)],
            'float' => [$float = 1.1, $this->encode($float)],
            'array' => [
                $array = [1, 'key' => 'value', 'nested' => [1, 2, 'asd_123-{z4x}']],
                $this->encode($array),
            ],
            'empty-array' => [$array = [], $this->encode($array)],
            'object' => [
                $object = new class () {
                    public string $name = 'object';
                },
                $this->encode($object),
            ],
            'empty-object' => [$object = new stdClass(), $this->encode($object)],
            'callable' => [$callable = fn () => null, $this->encode($callable)],
        ];
    }

    /**
     * @dataProvider keyDataProvider
     */
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

    private function encode(mixed $key): string
    {
        return md5(json_encode($key, JSON_THROW_ON_ERROR));
    }
}
