<?php
namespace Yiisoft\CacheOld\Tests\Serializer;

use Yiisoft\CacheOld\Serializer\CallbackSerializer;
use Yiisoft\CacheOld\Serializer\SerializerInterface;

/**
 * @group serialize
 */
class CallbackSerializerTest extends SerializerTest
{
    protected function createSerializer(): SerializerInterface
    {
        return new CallbackSerializer('serialize', 'unserialize');
    }
}
