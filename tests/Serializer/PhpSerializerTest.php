<?php
namespace Yiisoft\Cache\Tests\Serializer;

use Yiisoft\Cache\Serializer\PhpSerializer;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * @group serialize
 */
class PhpSerializerTest extends SerializerTest
{
    /**
     * {@inheritdoc}
     */
    protected function createSerializer(): SerializerInterface
    {
        return new PhpSerializer();
    }
}
