<?php
namespace Yiisoft\CacheOld\Tests\Serializer;

use Yiisoft\CacheOld\Serializer\PhpSerializer;
use Yiisoft\CacheOld\Serializer\SerializerInterface;

/**
 * @group serialize
 */
class PhpSerializerTest extends SerializerTest
{
    protected function createSerializer(): SerializerInterface
    {
        return new PhpSerializer();
    }
}
