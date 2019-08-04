<?php
namespace Yiisoft\CacheOld\Tests\Serializer;

use Yiisoft\CacheOld\Serializer\JsonSerializer;
use Yiisoft\CacheOld\Serializer\SerializerInterface;

/**
 * @group serialize
 */
class JsonSerializerTest extends SerializerTest
{
    protected function createSerializer(): SerializerInterface
    {
        return new JsonSerializer();
    }
}
