<?php
namespace Yiisoft\Cache\Tests\Serializer;

use Yiisoft\Cache\Serializer\IgbinarySerializer;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * @group serialize
 */
class IgbinarySerializerTest extends SerializerTest
{
    protected function setUp(): void
    {
        if (!function_exists('igbinary_serialize')) {
            $this->markTestSkipped('igbinary extension is required.');
        }

        parent::setUp();
    }

    protected function createSerializer(): SerializerInterface
    {
        return new IgbinarySerializer();
    }
}
