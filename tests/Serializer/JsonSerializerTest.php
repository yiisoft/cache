<?php
namespace Yiisoft\Cache\Tests\Serializer;

use Yiisoft\Cache\Serializer\JsonSerializer;
use Yiisoft\Cache\Serializer\SerializerInterface;

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
