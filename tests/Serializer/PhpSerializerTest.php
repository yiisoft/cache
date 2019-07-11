<?php
namespace Yiisoft\Cache\Tests\Serializer;

use Yiisoft\Cache\Serializer\PhpSerializer;
use Yiisoft\Cache\Serializer\SerializerInterface;

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
